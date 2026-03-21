<?php

namespace app\admin\controller\business;

use app\admin\library\traits\ErpAuditHelper;
use app\admin\library\traits\ErpCrudHelper;
use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 采购单
 *
 * @icon fa fa-shopping-cart
 */
class PurchaseOrder extends Backend
{
    use ErpAuditHelper;
    use ErpCrudHelper;

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Business\PurchaseOrder();
        $this->view->assign('purchaseTypeList', $this->model->getPurchaseTypeList());
        $this->view->assign('statusList', $this->model->getStatusList());
        $this->view->assign('ownerList', $this->getStaffOptions(false));
        $this->view->assign('supplierList', $this->getSupplierOptions(false));
        $this->view->assign('customerList', $this->getCustomerOptions(false));
        $this->view->assign('contractList', $this->getContractOptions(false));
    }

    public function add()
    {
        if (!$this->request->isPost()) {
            $this->view->assign('defaultRow', $this->buildDefaultRow());
            return $this->view->fetch();
        }

        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $params = $this->preparePurchaseOrderParams($params, true);

        Db::startTrans();
        try {
            $result = $this->model->allowField(true)->save($params);
            if ($result !== false) {
                $saved = array_merge($params, ['id' => (int) $this->model->id]);
                $this->recordBusinessAudit(
                    'business_purchase_order',
                    'add',
                    '采购单',
                    $saved,
                    '新增采购单：' . (($params['order_no'] ?: '未编号采购单') . ' / ' . ($params['title'] ?: '未命名采购单'))
                );
            }
            Db::commit();
        } catch (ValidateException | PDOException | Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        if ($result === false) {
            $this->error(__('No rows were inserted'));
        }

        $this->success('采购单已新增');
    }

    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }

        if (!$this->request->isPost()) {
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }

        if ((string) $row['approval_status'] === 'pending') {
            $this->error('审批中的采购单不允许编辑');
        }

        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $params = $this->preparePurchaseOrderParams($params, false, $row->toArray());

        Db::startTrans();
        try {
            $result = $row->allowField(true)->save($params);
            if ($result !== false) {
                $merged = array_merge($row->toArray(), $params);
                $this->recordBusinessAudit(
                    'business_purchase_order',
                    'edit',
                    '采购单',
                    $merged,
                    '更新采购单：' . ((($params['order_no'] ?? $row['order_no']) ?: '未编号采购单') . ' / ' . (($params['title'] ?? $row['title']) ?: '未命名采购单'))
                );
            }
            Db::commit();
        } catch (ValidateException | PDOException | Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        if ($result === false) {
            $this->error(__('No rows were updated'));
        }

        $this->success('采购单已更新');
    }

    public function del($ids = null)
    {
        if (!$this->request->isPost()) {
            $this->error(__('Invalid parameters'));
        }

        $ids = $ids ?: $this->request->post('ids');
        if (empty($ids)) {
            $this->error(__('Parameter %s can not be empty', 'ids'));
        }

        $pk = $this->model->getPk();
        $list = $this->model->where($pk, 'in', $ids)->select();
        if (!$list || count($list) === 0) {
            $this->error(__('No Results were found'));
        }

        $count = 0;
        Db::startTrans();
        try {
            foreach ($list as $item) {
                $row = method_exists($item, 'toArray') ? $item->toArray() : (array) $item;

                if ((string) ($row['approval_status'] ?? '') === 'pending') {
                    throw new Exception('审批中的采购单不允许删除');
                }
                if ((int) ($row['payment_plan_id'] ?? 0) > 0) {
                    throw new Exception('该采购单已生成付款计划，请先处理付款计划');
                }
                if ((int) ($row['reconciliation_id'] ?? 0) > 0) {
                    throw new Exception('该采购单已存在采购对账，请先处理采购对账');
                }
                if ((int) ($row['settlement_id'] ?? 0) > 0) {
                    throw new Exception('该采购单已生成采购结算，请先处理采购结算');
                }
                if ($this->tableExistsByName('business_purchase_invoice')) {
                    $invoiceCount = (int) Db::name('business_purchase_invoice')
                        ->where('purchase_order_id', (int) ($row['id'] ?? 0))
                        ->count();
                    if ($invoiceCount > 0) {
                        throw new Exception('该采购单已存在采购发票，请先处理采购发票');
                    }
                }

                $deleted = $item->delete();
                if ($deleted) {
                    $count += $deleted;
                    $this->recordBusinessAudit(
                        'business_purchase_order',
                        'delete',
                        '采购单',
                        $row,
                        '删除采购单：' . ((($row['order_no'] ?? '') ?: '未编号采购单') . ' / ' . (($row['title'] ?? '') ?: '未命名采购单'))
                    );
                }
            }

            Db::commit();
        } catch (PDOException | Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        if ($count) {
            $this->success('采购单已删除');
        }

        $this->error(__('No rows were deleted'));
    }

    public function createpaymentplan($ids = null)
    {
        if (!$this->request->isPost()) {
            $this->error(__('Invalid parameters'));
        }

        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }

        if ((string) $row['approval_status'] !== 'approved' || !in_array((string) $row['status'], ['approved'], true)) {
            $this->error('只有已批准且尚未生成付款计划的采购单才能继续生成付款计划');
        }
        if ((int) $row['payment_plan_id'] > 0) {
            $this->error('该采购单已经生成付款计划');
        }

        $actor = $this->getCurrentActor();
        $paymentPlan = [
            'legacy_id' => $this->generateLegacyId('payment_plan'),
            'contract_legacy_id' => (string) ($row['contract_legacy_id'] ?? ''),
            'contract_id' => (int) ($row['contract_id'] ?? 0),
            'contract_name' => (string) ($row['contract_name'] ?? ''),
            'customer_legacy_id' => (string) ($row['customer_legacy_id'] ?? ''),
            'customer_id' => (int) ($row['customer_id'] ?? 0),
            'customer_name' => (string) ($row['customer_name'] ?? ''),
            'purchase_order_id' => (int) $row['id'],
            'purchase_order_legacy_id' => (string) ($row['legacy_id'] ?? ''),
            'purchase_order_title' => (string) ($row['title'] ?? ''),
            'title' => '采购付款 / ' . (string) $row['title'],
            'payee_name' => (string) ($row['supplier_name'] ?: '待确认收款方'),
            'plan_type' => $this->mapPurchaseTypeToPaymentPlanType((string) $row['purchase_type']),
            'due_date' => (string) ($row['expected_delivery_date'] ?: substr((string) $row['ordered_at'], 0, 10)),
            'amount' => round((float) ($row['order_amount'] ?? 0), 2),
            'status' => 'pending',
            'approval_status' => 'none',
            'owner' => (string) ($row['owner'] ?? $actor['name']),
            'owner_admin_id' => (int) ($row['owner_admin_id'] ?? $actor['admin_id']),
            'actual_paid_at' => null,
            'notes' => '由采购单自动生成：' . (($row['order_no'] ?: '未编号采购单') . ' / ' . ($row['title'] ?: '未命名采购单')),
        ];
        $this->fillAuditFields($paymentPlan, true);

        Db::startTrans();
        try {
            Db::name('business_payment_plan')->insert($paymentPlan);
            $paymentPlanId = (int) Db::name('business_payment_plan')->getLastInsID();

            $purchasePayload = [
                'payment_plan_id' => $paymentPlanId,
                'payment_plan_legacy_id' => $paymentPlan['legacy_id'],
                'payment_plan_title' => $paymentPlan['title'],
                'status' => 'processing',
                'updated_by_admin_id' => (int) $actor['admin_id'],
                'updated_by_name' => (string) $actor['name'],
                'updated_by_legacy_id' => (string) $actor['legacy_id'],
                'record_updated_at' => date('Y-m-d H:i:s'),
                'updatetime' => time(),
            ];
            $row->save($purchasePayload);

            $this->recordBusinessAudit('business_payment_plan', 'add', '付款计划', $paymentPlan, '由采购单生成付款计划：' . $paymentPlan['title']);
            $this->recordBusinessAudit(
                'business_purchase_order',
                'create_payment_plan',
                '采购单',
                array_merge($row->toArray(), $purchasePayload),
                '为采购单生成付款计划：' . (($row['order_no'] ?: '未编号采购单') . ' / ' . ($row['title'] ?: '未命名采购单'))
            );

            Db::commit();
        } catch (PDOException | Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        $this->success('付款计划已生成');
    }

    protected function buildDefaultRow(): array
    {
        $actor = $this->getCurrentActor();

        return [
            'supplier_id' => (int) $this->request->get('supplier_id/d', 0),
            'customer_id' => (int) $this->request->get('customer_id/d', 0),
            'contract_id' => (int) $this->request->get('contract_id/d', 0),
            'owner_admin_id' => (int) $actor['admin_id'],
            'purchase_type' => 'service',
            'order_amount' => '0.00',
            'ordered_at' => date('Y-m-d H:i:s'),
            'expected_delivery_date' => date('Y-m-d', strtotime('+7 day')),
            'purchase_content' => '',
            'notes' => '',
        ];
    }

    protected function preparePurchaseOrderParams(array $params, bool $isCreate, array $existing = []): array
    {
        $params = $this->preExcludeFields($params);
        if ($isCreate) {
            $this->fillLegacyId($params, 'purchase_order');
            $params['order_no'] = 'PO-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 6));
            $params['status'] = 'draft';
            $params['approval_status'] = 'none';
            $params['approval_updated_at'] = null;
            $params['payment_plan_id'] = 0;
            $params['payment_plan_legacy_id'] = '';
            $params['payment_plan_title'] = '';
            $params['settlement_id'] = 0;
            $params['settlement_legacy_id'] = '';
            $params['settlement_title'] = '';
        } else {
            $params['legacy_id'] = $existing['legacy_id'] ?? '';
            $params['order_no'] = $existing['order_no'] ?? '';
            $params['status'] = $existing['status'] ?? 'draft';
            $params['approval_status'] = $existing['approval_status'] ?? 'none';
            $params['approval_updated_at'] = $existing['approval_updated_at'] ?? null;
            $params['payment_plan_id'] = $existing['payment_plan_id'] ?? 0;
            $params['payment_plan_legacy_id'] = $existing['payment_plan_legacy_id'] ?? '';
            $params['payment_plan_title'] = $existing['payment_plan_title'] ?? '';
            $params['settlement_id'] = $existing['settlement_id'] ?? 0;
            $params['settlement_legacy_id'] = $existing['settlement_legacy_id'] ?? '';
            $params['settlement_title'] = $existing['settlement_title'] ?? '';
        }

        $this->syncContractCustomerRelation($params);
        $this->syncSupplierRelation($params);
        $this->fillRelationLegacy($params, 'business_supplier', 'supplier_id', 'supplier_legacy_id', 'supplier_name', 'supplier_name');
        $this->fillRelationLegacy($params, 'business_contract', 'contract_id', 'contract_legacy_id', 'name', 'contract_name');
        $this->fillRelationLegacy($params, 'business_customer', 'customer_id', 'customer_legacy_id', 'company_name', 'customer_name');
        $this->fillStaffName($params, 'owner_admin_id', 'owner');
        $this->fillAuditFields($params, $isCreate);

        foreach (['ordered_at', 'expected_delivery_date', 'actual_delivery_at'] as $field) {
            if (array_key_exists($field, $params) && $params[$field] === '') {
                $params[$field] = null;
            }
        }

        $params['order_amount'] = round((float) ($params['order_amount'] ?? 0), 2);
        if (!array_key_exists('attachment_ids_json', $params) || $params['attachment_ids_json'] === '') {
            $params['attachment_ids_json'] = '[]';
        }

        return $params;
    }

    protected function syncContractCustomerRelation(array &$params): void
    {
        $contractId = (int) ($params['contract_id'] ?? 0);
        if ($contractId <= 0) {
            return;
        }

        $contract = Db::name('business_contract')
            ->field('id,legacy_id,name,customer_id,customer_legacy_id,customer_name')
            ->where('id', $contractId)
            ->find();

        if (!$contract) {
            return;
        }

        $params['contract_id'] = (int) $contract['id'];
        $params['contract_legacy_id'] = (string) ($contract['legacy_id'] ?? '');
        $params['contract_name'] = (string) ($contract['name'] ?? '');
        $params['customer_id'] = (int) ($contract['customer_id'] ?? 0);
        $params['customer_legacy_id'] = (string) ($contract['customer_legacy_id'] ?? '');
        $params['customer_name'] = (string) ($contract['customer_name'] ?? '');
    }

    protected function syncSupplierRelation(array &$params): void
    {
        $supplierId = (int) ($params['supplier_id'] ?? 0);
        if ($supplierId <= 0) {
            $params['supplier_legacy_id'] = '';
            $params['supplier_name'] = '';
            return;
        }

        $supplier = Db::name('business_supplier')
            ->field('id,legacy_id,supplier_name')
            ->where('id', $supplierId)
            ->find();

        if (!$supplier) {
            return;
        }

        $params['supplier_id'] = (int) $supplier['id'];
        $params['supplier_legacy_id'] = (string) ($supplier['legacy_id'] ?? '');
        $params['supplier_name'] = (string) ($supplier['supplier_name'] ?? '');
    }

    protected function mapPurchaseTypeToPaymentPlanType(string $type): string
    {
        $map = [
            'software' => 'supplier',
            'cloud' => 'supplier',
            'service' => 'service',
            'outsourcing' => 'service',
            'marketing' => 'commission',
            'hardware' => 'supplier',
            'office' => 'other',
        ];

        return $map[$type] ?? 'other';
    }
}
