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
 * 费用申请
 *
 * @icon fa fa-money
 */
class ExpenseRequest extends Backend
{
    use ErpAuditHelper;
    use ErpCrudHelper;

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Business\ExpenseRequest();
        $this->view->assign('expenseTypeList', $this->model->getExpenseTypeList());
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

        $params = $this->prepareExpenseParams($params, true);

        Db::startTrans();
        try {
            $result = $this->model->allowField(true)->save($params);
            if ($result !== false) {
                $this->recordBusinessAudit(
                    'business_expense_request',
                    'add',
                    '费用申请',
                    $params,
                    '新增费用申请：' . (($params['request_no'] ?: '未编号申请') . ' / ' . ($params['title'] ?: '未命名申请'))
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

        $this->success('费用申请已新增');
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
            $this->error('审批中的费用申请不可编辑');
        }

        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $params = $this->prepareExpenseParams($params, false, $row->toArray());

        Db::startTrans();
        try {
            $result = $row->allowField(true)->save($params);
            if ($result !== false) {
                $merged = array_merge($row->toArray(), $params);
                $this->recordBusinessAudit(
                    'business_expense_request',
                    'edit',
                    '费用申请',
                    $merged,
                    '更新费用申请：' . ((($params['request_no'] ?? $row['request_no']) ?: '未编号申请') . ' / ' . (($params['title'] ?? $row['title']) ?: '未命名申请'))
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

        $this->success('费用申请已更新');
    }

    public function del($ids = null)
    {
        $this->deleteWithAudit($ids, 'business_expense_request', '费用申请', function ($row) {
            return '删除费用申请：' . ((($row['request_no'] ?? '') ?: '未编号申请') . ' / ' . ($row['title'] ?: '未命名申请'));
        });
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
            $this->error('只有已批准且尚未生成付款计划的费用申请才可以继续生成付款计划');
        }
        if ((int) $row['payment_plan_id'] > 0) {
            $this->error('该费用申请已经生成付款计划');
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
            'expense_request_id' => (int) $row['id'],
            'expense_request_legacy_id' => (string) ($row['legacy_id'] ?? ''),
            'expense_request_title' => (string) ($row['title'] ?? ''),
            'title' => '费用付款 / ' . (string) $row['title'],
            'payee_name' => (string) ($row['supplier_name'] ?: '待确认收款方'),
            'plan_type' => $this->mapExpenseTypeToPaymentPlanType((string) $row['expense_type']),
            'due_date' => (string) ($row['expected_pay_date'] ?: $row['requested_at']),
            'amount' => round((float) ($row['request_amount'] ?? 0), 2),
            'status' => 'pending',
            'approval_status' => 'none',
            'owner' => (string) ($row['owner'] ?? $actor['name']),
            'owner_admin_id' => (int) ($row['owner_admin_id'] ?? $actor['admin_id']),
            'actual_paid_at' => null,
            'notes' => '由费用申请自动生成：' . (($row['request_no'] ?: '未编号申请') . ' / ' . ($row['title'] ?: '未命名申请')),
        ];
        $this->fillAuditFields($paymentPlan, true);

        Db::startTrans();
        try {
            Db::name('business_payment_plan')->insert($paymentPlan);
            $paymentPlanId = (int) Db::name('business_payment_plan')->getLastInsID();

            $expensePayload = [
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
            $row->save($expensePayload);

            $this->recordBusinessAudit('business_payment_plan', 'add', '付款计划', $paymentPlan, '由费用申请生成付款计划：' . $paymentPlan['title']);
            $this->recordBusinessAudit(
                'business_expense_request',
                'create_payment_plan',
                '费用申请',
                array_merge($row->toArray(), $expensePayload),
                '为费用申请生成付款计划：' . (($row['request_no'] ?: '未编号申请') . ' / ' . ($row['title'] ?: '未命名申请'))
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
            'expense_type' => 'procurement',
            'request_amount' => '0.00',
            'requested_at' => date('Y-m-d H:i:s'),
            'expected_pay_date' => date('Y-m-d'),
            'reason' => '',
            'notes' => '',
        ];
    }

    protected function prepareExpenseParams(array $params, bool $isCreate, array $existing = []): array
    {
        $params = $this->preExcludeFields($params);
        if ($isCreate) {
            $this->fillLegacyId($params, 'expense_request');
            $params['request_no'] = 'FY-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 6));
            $params['status'] = 'draft';
            $params['approval_status'] = 'none';
            $params['approval_updated_at'] = null;
            $params['payment_plan_id'] = 0;
            $params['payment_plan_legacy_id'] = '';
            $params['payment_plan_title'] = '';
        } else {
            $params['legacy_id'] = $existing['legacy_id'] ?? '';
            $params['request_no'] = $existing['request_no'] ?? '';
            $params['status'] = $existing['status'] ?? 'draft';
            $params['approval_status'] = $existing['approval_status'] ?? 'none';
            $params['approval_updated_at'] = $existing['approval_updated_at'] ?? null;
            $params['payment_plan_id'] = $existing['payment_plan_id'] ?? 0;
            $params['payment_plan_legacy_id'] = $existing['payment_plan_legacy_id'] ?? '';
            $params['payment_plan_title'] = $existing['payment_plan_title'] ?? '';
        }

        $this->syncContractCustomerRelation($params);
        $this->syncSupplierRelation($params);
        $this->fillRelationLegacy($params, 'business_supplier', 'supplier_id', 'supplier_legacy_id', 'supplier_name', 'supplier_name');
        $this->fillRelationLegacy($params, 'business_contract', 'contract_id', 'contract_legacy_id', 'name', 'contract_name');
        $this->fillRelationLegacy($params, 'business_customer', 'customer_id', 'customer_legacy_id', 'company_name', 'customer_name');
        $this->fillStaffName($params, 'owner_admin_id', 'owner');
        $this->fillAuditFields($params, $isCreate);

        foreach (['requested_at', 'expected_pay_date'] as $field) {
            if (array_key_exists($field, $params) && $params[$field] === '') {
                $params[$field] = null;
            }
        }

        $params['request_amount'] = round((float) ($params['request_amount'] ?? 0), 2);
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

    protected function mapExpenseTypeToPaymentPlanType(string $type): string
    {
        $map = [
            'procurement' => 'supplier',
            'software' => 'service',
            'service' => 'service',
            'outsourcing' => 'service',
            'refund' => 'refund',
        ];

        return $map[$type] ?? 'other';
    }
}
