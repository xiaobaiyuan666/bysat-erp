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
 * 采购结算
 *
 * @icon fa fa-balance-scale
 */
class PurchaseSettlement extends Backend
{
    use ErpAuditHelper;
    use ErpCrudHelper;

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Business\PurchaseSettlement();
        $this->view->assign('statusList', $this->model->getStatusList());
        $this->view->assign('invoiceStatusList', $this->model->getInvoiceStatusList());
        $this->view->assign('ownerList', $this->getStaffOptions(false));
        $this->view->assign('purchaseOrderList', $this->getPurchaseOrderOptions(false));
        $this->view->assign('paymentPlanList', $this->getPaymentPlanOptions(false));
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

        $params = $this->prepareSettlementParams($params, true);

        Db::startTrans();
        try {
            $result = $this->model->allowField(true)->save($params);
            if ($result !== false) {
                $saved = array_merge($params, ['id' => (int) $this->model->id]);
                $this->syncLinkedPurchaseOrderAfterSettlementSave($saved);
                $this->recordBusinessAudit(
                    'business_purchase_settlement',
                    'add',
                    '采购结算',
                    $saved,
                    '新增采购结算：' . (($params['settlement_no'] ?: '未编号结算单') . ' / ' . ($params['title'] ?: '未命名结算单'))
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

        $this->success('采购结算已新增');
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

        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $original = $row->toArray();
        $params = $this->prepareSettlementParams($params, false, $original, (int) $row['id']);

        Db::startTrans();
        try {
            $result = $row->allowField(true)->save($params);
            if ($result !== false) {
                if ((int) ($original['purchase_order_id'] ?? 0) > 0 && (int) ($original['purchase_order_id'] ?? 0) !== (int) ($params['purchase_order_id'] ?? 0)) {
                    $this->syncLinkedPurchaseOrderAfterSettlementDelete($original);
                }

                $saved = array_merge($original, $params, ['id' => (int) $row['id']]);
                $this->syncLinkedPurchaseOrderAfterSettlementSave($saved);
                $this->recordBusinessAudit(
                    'business_purchase_settlement',
                    'edit',
                    '采购结算',
                    $saved,
                    '更新采购结算：' . ((($params['settlement_no'] ?? $original['settlement_no']) ?: '未编号结算单') . ' / ' . (($params['title'] ?? $original['title']) ?: '未命名结算单'))
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

        $this->success('采购结算已更新');
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
                if ($this->tableExistsByName('business_purchase_invoice')) {
                    $invoiceCount = (int) Db::name('business_purchase_invoice')
                        ->where('settlement_id', (int) ($row['id'] ?? 0))
                        ->count();
                    if ($invoiceCount > 0) {
                        throw new Exception('该采购结算已关联采购发票，请先处理采购发票');
                    }
                }
                if ($this->tableExistsByName('business_payment_request')) {
                    $paymentRequestCount = (int) Db::name('business_payment_request')
                        ->where('settlement_id', (int) ($row['id'] ?? 0))
                        ->count();
                    if ($paymentRequestCount > 0) {
                        throw new Exception('该采购结算已关联付款申请，请先处理付款申请');
                    }
                }

                $deleted = $item->delete();
                if ($deleted) {
                    $count += $deleted;
                    $this->syncLinkedPurchaseOrderAfterSettlementDelete($row);
                    $this->recordBusinessAudit(
                        'business_purchase_settlement',
                        'delete',
                        '采购结算',
                        $row,
                        '删除采购结算：' . ((($row['settlement_no'] ?? '') ?: '未编号结算单') . ' / ' . (($row['title'] ?? '') ?: '未命名结算单'))
                    );
                }
            }
            Db::commit();
        } catch (PDOException | Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        if ($count) {
            $this->success('采购结算已删除');
        }

        $this->error(__('No rows were deleted'));
    }

    protected function buildDefaultRow(): array
    {
        $actor = $this->getCurrentActor();
        $purchaseOrderId = (int) $this->request->get('purchase_order_id/d', 0);
        $paymentPlanId = (int) $this->request->get('payment_plan_id/d', 0);
        $default = [
            'title' => '',
            'purchase_order_id' => $purchaseOrderId,
            'payment_plan_id' => $paymentPlanId,
            'settlement_amount' => '0.00',
            'paid_amount' => '0.00',
            'invoiced_amount' => '0.00',
            'balance_amount' => '0.00',
            'invoice_status' => 'none',
            'invoice_no' => '',
            'invoiced_at' => '',
            'status' => 'reconciling',
            'owner_admin_id' => (int) ($actor['admin_id'] ?? 0),
            'settled_at' => '',
            'notes' => '',
        ];

        if ($purchaseOrderId > 0) {
            $purchaseOrder = $this->getPurchaseOrderSnapshot($purchaseOrderId);
            if ($purchaseOrder) {
                $default['title'] = '采购结算 / ' . (string) $purchaseOrder['title'];
                $default['payment_plan_id'] = (int) ($purchaseOrder['payment_plan_id'] ?? $paymentPlanId);
                $default['settlement_amount'] = number_format((float) ($purchaseOrder['order_amount'] ?? 0), 2, '.', '');
                $default['owner_admin_id'] = (int) (($purchaseOrder['owner_admin_id'] ?? 0) ?: ($actor['admin_id'] ?? 0));
                $default['status'] = (int) ($purchaseOrder['payment_plan_id'] ?? 0) > 0 ? 'reconciling' : 'draft';
            }
        }

        if ((int) $default['payment_plan_id'] > 0) {
            $paymentPlan = $this->getPaymentPlanSnapshot((int) $default['payment_plan_id']);
            if ($paymentPlan) {
                if (empty($default['title']) && !empty($paymentPlan['purchase_order_title'])) {
                    $default['title'] = '采购结算 / ' . (string) $paymentPlan['purchase_order_title'];
                }
                if ((float) $default['settlement_amount'] <= 0) {
                    $default['settlement_amount'] = number_format((float) ($paymentPlan['amount'] ?? 0), 2, '.', '');
                }
                if ((string) ($paymentPlan['status'] ?? '') === 'paid') {
                    $default['paid_amount'] = number_format((float) ($paymentPlan['amount'] ?? 0), 2, '.', '');
                }
            }
        }

        $default['balance_amount'] = number_format(max(0, (float) $default['settlement_amount'] - (float) $default['paid_amount']), 2, '.', '');

        return $default;
    }

    protected function prepareSettlementParams(array $params, bool $isCreate, array $existing = [], int $currentId = 0): array
    {
        $params = $this->preExcludeFields($params);

        if ($isCreate) {
            $this->fillLegacyId($params, 'purchase_settlement');
            $params['settlement_no'] = 'PS-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 6));
        } else {
            $params['legacy_id'] = $existing['legacy_id'] ?? '';
            $params['settlement_no'] = $existing['settlement_no'] ?? '';
        }

        $params['purchase_order_id'] = (int) ($params['purchase_order_id'] ?? ($existing['purchase_order_id'] ?? 0));
        $params['payment_plan_id'] = (int) ($params['payment_plan_id'] ?? ($existing['payment_plan_id'] ?? 0));

        if ($params['purchase_order_id'] <= 0) {
            throw new Exception('请选择采购单');
        }

        $this->syncPurchaseOrderRelation($params, $isCreate);
        $this->syncPaymentPlanRelation($params);
        $this->fillStaffName($params, 'owner_admin_id', 'owner');
        $this->fillAuditFields($params, $isCreate);

        foreach (['invoiced_at', 'settled_at'] as $field) {
            if (array_key_exists($field, $params) && $params[$field] === '') {
                $params[$field] = null;
            }
        }

        $params['settlement_amount'] = round((float) ($params['settlement_amount'] ?? 0), 2);
        $params['paid_amount'] = round((float) ($params['paid_amount'] ?? 0), 2);
        $params['invoiced_amount'] = round((float) ($params['invoiced_amount'] ?? 0), 2);
        $params['balance_amount'] = round(max(0, $params['settlement_amount'] - $params['paid_amount']), 2);

        if (($params['invoice_status'] ?? '') === 'received' && empty($params['invoiced_at'])) {
            $params['invoiced_at'] = date('Y-m-d');
        }

        if (($params['status'] ?? '') === 'settled') {
            if ($params['settlement_amount'] > 0 && $params['paid_amount'] + 0.00001 < $params['settlement_amount']) {
                throw new Exception('只有金额结清后才能标记为已结算');
            }
            if (($params['invoice_status'] ?? 'none') !== 'received') {
                throw new Exception('只有发票已到齐后才能标记为已结算');
            }
            if (empty($params['settled_at'])) {
                $params['settled_at'] = date('Y-m-d H:i:s');
            }
        } elseif (($params['status'] ?? '') === 'cancelled') {
            $params['settled_at'] = null;
        }

        if (!array_key_exists('attachment_ids_json', $params) || $params['attachment_ids_json'] === '') {
            $params['attachment_ids_json'] = $existing['attachment_ids_json'] ?? '[]';
            if ($params['attachment_ids_json'] === '') {
                $params['attachment_ids_json'] = '[]';
            }
        }

        $this->ensureSingleSettlementPerPurchaseOrder((int) $params['purchase_order_id'], $currentId);

        return $params;
    }

    protected function syncPurchaseOrderRelation(array &$params, bool $isCreate): void
    {
        $purchaseOrder = $this->getPurchaseOrderSnapshot((int) ($params['purchase_order_id'] ?? 0));
        if (!$purchaseOrder) {
            throw new Exception('未找到关联采购单');
        }

        $params['purchase_order_id'] = (int) $purchaseOrder['id'];
        $params['purchase_order_legacy_id'] = (string) ($purchaseOrder['legacy_id'] ?? '');
        $params['purchase_order_title'] = (string) ($purchaseOrder['title'] ?? '');
        $params['supplier_id'] = (int) ($purchaseOrder['supplier_id'] ?? 0);
        $params['supplier_legacy_id'] = (string) ($purchaseOrder['supplier_legacy_id'] ?? '');
        $params['supplier_name'] = (string) ($purchaseOrder['supplier_name'] ?? '');
        $params['customer_id'] = (int) ($purchaseOrder['customer_id'] ?? 0);
        $params['customer_legacy_id'] = (string) ($purchaseOrder['customer_legacy_id'] ?? '');
        $params['customer_name'] = (string) ($purchaseOrder['customer_name'] ?? '');
        $params['contract_id'] = (int) ($purchaseOrder['contract_id'] ?? 0);
        $params['contract_legacy_id'] = (string) ($purchaseOrder['contract_legacy_id'] ?? '');
        $params['contract_name'] = (string) ($purchaseOrder['contract_name'] ?? '');

        if (empty($params['payment_plan_id']) && (int) ($purchaseOrder['payment_plan_id'] ?? 0) > 0) {
            $params['payment_plan_id'] = (int) $purchaseOrder['payment_plan_id'];
            $params['payment_plan_legacy_id'] = (string) ($purchaseOrder['payment_plan_legacy_id'] ?? '');
            $params['payment_plan_title'] = (string) ($purchaseOrder['payment_plan_title'] ?? '');
        }

        if (empty($params['title'])) {
            $params['title'] = '采购结算 / ' . (string) ($purchaseOrder['title'] ?? '未命名采购单');
        }
        if ($isCreate && (!isset($params['settlement_amount']) || $params['settlement_amount'] === '' || (float) $params['settlement_amount'] <= 0)) {
            $params['settlement_amount'] = round((float) ($purchaseOrder['order_amount'] ?? 0), 2);
        }
        if (empty($params['owner_admin_id']) && !empty($purchaseOrder['owner_admin_id'])) {
            $params['owner_admin_id'] = (int) $purchaseOrder['owner_admin_id'];
        }
    }

    protected function syncPaymentPlanRelation(array &$params): void
    {
        $paymentPlanId = (int) ($params['payment_plan_id'] ?? 0);
        if ($paymentPlanId <= 0) {
            $params['payment_plan_legacy_id'] = '';
            $params['payment_plan_title'] = '';
            return;
        }

        $paymentPlan = $this->getPaymentPlanSnapshot($paymentPlanId);
        if (!$paymentPlan) {
            throw new Exception('未找到关联付款计划');
        }

        if ((int) ($paymentPlan['purchase_order_id'] ?? 0) > 0 && (int) ($params['purchase_order_id'] ?? 0) > 0 && (int) $paymentPlan['purchase_order_id'] !== (int) $params['purchase_order_id']) {
            throw new Exception('付款计划与采购单不匹配');
        }

        $params['payment_plan_id'] = (int) $paymentPlan['id'];
        $params['payment_plan_legacy_id'] = (string) ($paymentPlan['legacy_id'] ?? '');
        $params['payment_plan_title'] = (string) ($paymentPlan['title'] ?? '');

        if ((int) ($paymentPlan['purchase_order_id'] ?? 0) > 0 && (int) ($params['purchase_order_id'] ?? 0) <= 0) {
            $params['purchase_order_id'] = (int) $paymentPlan['purchase_order_id'];
            $this->syncPurchaseOrderRelation($params, false);
        }

        if ((string) ($paymentPlan['status'] ?? '') === 'paid' && (float) ($params['paid_amount'] ?? 0) <= 0) {
            $params['paid_amount'] = round((float) ($paymentPlan['amount'] ?? 0), 2);
        }
    }

    protected function syncLinkedPurchaseOrderAfterSettlementSave(array $settlement): void
    {
        $purchaseOrderId = (int) ($settlement['purchase_order_id'] ?? 0);
        if ($purchaseOrderId <= 0) {
            return;
        }

        $purchaseOrder = Db::name('business_purchase_order')
            ->field('id,payment_plan_id,approval_status')
            ->where('id', $purchaseOrderId)
            ->find();

        if (!$purchaseOrder) {
            return;
        }

        $payload = [
            'settlement_id' => (int) ($settlement['id'] ?? 0),
            'settlement_legacy_id' => (string) ($settlement['legacy_id'] ?? ''),
            'settlement_title' => (string) ($settlement['title'] ?? ''),
            'record_updated_at' => date('Y-m-d H:i:s'),
            'updatetime' => time(),
        ];

        if ((string) ($settlement['status'] ?? '') === 'settled') {
            $payload['status'] = 'completed';
        } elseif ((string) ($settlement['status'] ?? '') === 'cancelled') {
            $payload['status'] = (int) ($purchaseOrder['payment_plan_id'] ?? 0) > 0 ? 'processing' : $this->mapApprovalStatusToPurchaseOrderStatus((string) ($purchaseOrder['approval_status'] ?? 'none'));
        } else {
            $payload['status'] = (int) ($purchaseOrder['payment_plan_id'] ?? 0) > 0 ? 'processing' : 'approved';
        }

        Db::name('business_purchase_order')->where('id', $purchaseOrderId)->update($payload);
    }

    protected function syncLinkedPurchaseOrderAfterSettlementDelete(array $settlement): void
    {
        $purchaseOrderId = (int) ($settlement['purchase_order_id'] ?? 0);
        if ($purchaseOrderId <= 0) {
            return;
        }

        $purchaseOrder = Db::name('business_purchase_order')
            ->field('id,payment_plan_id,approval_status')
            ->where('id', $purchaseOrderId)
            ->find();

        if (!$purchaseOrder) {
            return;
        }

        Db::name('business_purchase_order')
            ->where('id', $purchaseOrderId)
            ->update([
                'settlement_id' => 0,
                'settlement_legacy_id' => '',
                'settlement_title' => '',
                'status' => (int) ($purchaseOrder['payment_plan_id'] ?? 0) > 0 ? 'processing' : $this->mapApprovalStatusToPurchaseOrderStatus((string) ($purchaseOrder['approval_status'] ?? 'none')),
                'record_updated_at' => date('Y-m-d H:i:s'),
                'updatetime' => time(),
            ]);
    }

    protected function ensureSingleSettlementPerPurchaseOrder(int $purchaseOrderId, int $currentId = 0): void
    {
        $query = Db::name('business_purchase_settlement')->where('purchase_order_id', $purchaseOrderId);
        if ($currentId > 0) {
            $query->where('id', '<>', $currentId);
        }

        $existing = $query->field('id,title')->find();
        if ($existing) {
            throw new Exception('该采购单已经存在采购结算：' . ($existing['title'] ?: ('#' . $existing['id'])));
        }
    }

    protected function getPurchaseOrderSnapshot(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = Db::name('business_purchase_order')
            ->field('id,legacy_id,title,order_amount,supplier_id,supplier_legacy_id,supplier_name,customer_id,customer_legacy_id,customer_name,contract_id,contract_legacy_id,contract_name,payment_plan_id,payment_plan_legacy_id,payment_plan_title,owner,owner_admin_id,approval_status')
            ->where('id', $id)
            ->find();

        return $row ?: null;
    }

    protected function getPaymentPlanSnapshot(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = Db::name('business_payment_plan')
            ->field('id,legacy_id,title,amount,status,purchase_order_id,purchase_order_legacy_id,purchase_order_title,contract_id,contract_legacy_id,contract_name,customer_id,customer_legacy_id,customer_name')
            ->where('id', $id)
            ->find();

        return $row ?: null;
    }

    protected function mapApprovalStatusToPurchaseOrderStatus(string $approvalStatus): string
    {
        if ($approvalStatus === 'approved') {
            return 'approved';
        }
        if ($approvalStatus === 'pending') {
            return 'pending_approval';
        }
        if ($approvalStatus === 'rejected') {
            return 'rejected';
        }

        return 'draft';
    }
}
