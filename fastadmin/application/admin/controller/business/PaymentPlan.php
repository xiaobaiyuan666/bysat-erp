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
 * 付款计划
 *
 * @icon fa fa-credit-card
 */
class PaymentPlan extends Backend
{
    use ErpAuditHelper;
    use ErpCrudHelper;

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Business\PaymentPlan();
        $this->view->assign('planTypeList', $this->model->getPlanTypeList());
        $this->view->assign('statusList', $this->model->getStatusList());
        $this->view->assign('ownerList', $this->getStaffOptions(false));
        $this->view->assign('customerList', $this->getCustomerOptions(false));
        $this->view->assign('contractList', $this->getContractOptions(false));
    }

    public function add()
    {
        if (!$this->request->isPost()) {
            return $this->view->fetch();
        }

        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $params = $this->preparePlanParams($params, true);

        Db::startTrans();
        try {
            $result = $this->model->allowField(true)->save($params);
            if ($result !== false) {
                $saved = array_merge($params, ['id' => (int) $this->model->id]);
                $this->recordBusinessAudit('business_payment_plan', 'add', '付款计划', $saved, '新增付款计划：' . ($params['title'] ?: '未命名计划'));
                $this->syncLinkedExpenseRequestAfterPlanSave($saved);
                $this->syncLinkedPurchaseOrderAfterPlanSave($saved);
            }
            Db::commit();
        } catch (ValidateException | Exception | PDOException $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        if ($result === false) {
            $this->error(__('No rows were inserted'));
        }

        $this->success('付款计划已新增');
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

        $params = $this->preparePlanParams($params, false, $row->toArray());

        Db::startTrans();
        try {
            $result = $row->allowField(true)->save($params);
            if ($result !== false) {
                $merged = array_merge($row->toArray(), $params);
                $saved = array_merge($merged, ['id' => (int) $row['id']]);
                $this->recordBusinessAudit('business_payment_plan', 'edit', '付款计划', $saved, '更新付款计划：' . (($params['title'] ?? $row['title']) ?: '未命名计划'));
                $this->syncLinkedExpenseRequestAfterPlanSave($saved);
                $this->syncLinkedPurchaseOrderAfterPlanSave($saved);
            }
            Db::commit();
        } catch (ValidateException | Exception | PDOException $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        if ($result === false) {
            $this->error(__('No rows were updated'));
        }

        $this->success('付款计划已更新');
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
        $count = 0;

        Db::startTrans();
        try {
            foreach ($list as $item) {
                $row = method_exists($item, 'toArray') ? $item->toArray() : (array) $item;
                $settlementExists = $this->tableExistsByName('business_purchase_settlement')
                    ? (int) Db::name('business_purchase_settlement')->where('payment_plan_id', (int) $row['id'])->count()
                    : 0;
                $paymentRequestExists = $this->tableExistsByName('business_payment_request')
                    ? (int) Db::name('business_payment_request')->where('payment_plan_id', (int) $row['id'])->count()
                    : 0;

                if ($settlementExists > 0) {
                    throw new Exception('该付款计划已关联采购结算，请先处理采购结算');
                }

                if ($paymentRequestExists > 0) {
                    throw new Exception('该付款计划已关联付款申请，请先处理付款申请');
                }

                $deleted = $item->delete();
                if ($deleted) {
                    $count += $deleted;
                    $this->recordBusinessAudit('business_payment_plan', 'delete', '付款计划', $row, '删除付款计划：' . ($row['title'] ?: '未命名计划'));
                    $this->syncLinkedExpenseRequestAfterPlanDelete($row);
                    $this->syncLinkedPurchaseOrderAfterPlanDelete($row);
                }
            }

            Db::commit();
        } catch (PDOException | Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        if ($count) {
            $this->success('付款计划已删除');
        }

        $this->error(__('No rows were deleted'));
    }

    protected function preparePlanParams(array $params, bool $isCreate, array $existing = []): array
    {
        $params = $this->preExcludeFields($params);
        if ($isCreate) {
            $this->fillLegacyId($params, 'payment_plan');
            $params['expense_request_id'] = 0;
            $params['expense_request_legacy_id'] = '';
            $params['expense_request_title'] = '';
            $params['purchase_order_id'] = 0;
            $params['purchase_order_legacy_id'] = '';
            $params['purchase_order_title'] = '';
        } else {
            $params['legacy_id'] = $existing['legacy_id'] ?? '';
            $params['expense_request_id'] = $existing['expense_request_id'] ?? 0;
            $params['expense_request_legacy_id'] = $existing['expense_request_legacy_id'] ?? '';
            $params['expense_request_title'] = $existing['expense_request_title'] ?? '';
            $params['purchase_order_id'] = $existing['purchase_order_id'] ?? 0;
            $params['purchase_order_legacy_id'] = $existing['purchase_order_legacy_id'] ?? '';
            $params['purchase_order_title'] = $existing['purchase_order_title'] ?? '';
            $params['approval_status'] = $existing['approval_status'] ?? ($params['approval_status'] ?? 'none');
            $params['approval_updated_at'] = $existing['approval_updated_at'] ?? ($params['approval_updated_at'] ?? null);
        }

        $this->syncContractCustomerRelation($params);
        $this->fillRelationLegacy($params, 'business_contract', 'contract_id', 'contract_legacy_id', 'name', 'contract_name');
        $this->fillRelationLegacy($params, 'business_customer', 'customer_id', 'customer_legacy_id', 'company_name', 'customer_name');
        $this->fillStaffName($params, 'owner_admin_id', 'owner');
        $this->fillAuditFields($params, $isCreate);

        foreach (['due_date', 'actual_paid_at'] as $field) {
            if (array_key_exists($field, $params) && $params[$field] === '') {
                $params[$field] = null;
            }
        }

        $params['amount'] = round((float) ($params['amount'] ?? 0), 2);
        if (($params['status'] ?? '') === 'paid' && empty($params['actual_paid_at'])) {
            $params['actual_paid_at'] = date('Y-m-d H:i:s');
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
        if (!empty($contract['customer_id'])) {
            $params['customer_id'] = (int) $contract['customer_id'];
            $params['customer_legacy_id'] = (string) ($contract['customer_legacy_id'] ?? '');
            $params['customer_name'] = (string) ($contract['customer_name'] ?? '');
        }
    }

    protected function syncLinkedExpenseRequestAfterPlanSave(array $plan): void
    {
        $expenseRequestId = (int) ($plan['expense_request_id'] ?? 0);
        if ($expenseRequestId <= 0) {
            return;
        }

        $status = (string) ($plan['status'] ?? 'pending');
        $payload = [
            'payment_plan_id' => (int) ($plan['id'] ?? 0),
            'payment_plan_legacy_id' => (string) ($plan['legacy_id'] ?? ''),
            'payment_plan_title' => (string) ($plan['title'] ?? ''),
            'record_updated_at' => date('Y-m-d H:i:s'),
            'updatetime' => time(),
        ];

        if ($status === 'paid') {
            $payload['status'] = 'paid';
        } elseif ($status === 'cancelled') {
            $payload['status'] = 'approved';
            $payload['payment_plan_id'] = 0;
            $payload['payment_plan_legacy_id'] = '';
            $payload['payment_plan_title'] = '';
        } else {
            $payload['status'] = 'processing';
        }

        Db::name('business_expense_request')->where('id', $expenseRequestId)->update($payload);
    }

    protected function syncLinkedPurchaseOrderAfterPlanSave(array $plan): void
    {
        $purchaseOrderId = (int) ($plan['purchase_order_id'] ?? 0);
        if ($purchaseOrderId <= 0) {
            return;
        }

        $purchaseOrder = Db::name('business_purchase_order')
            ->field('id,approval_status,settlement_id')
            ->where('id', $purchaseOrderId)
            ->find();

        if (!$purchaseOrder) {
            return;
        }

        $settlement = $this->getLinkedSettlementSnapshot((int) ($purchaseOrder['settlement_id'] ?? 0));
        $status = (string) ($plan['status'] ?? 'pending');
        $payload = [
            'payment_plan_id' => (int) ($plan['id'] ?? 0),
            'payment_plan_legacy_id' => (string) ($plan['legacy_id'] ?? ''),
            'payment_plan_title' => (string) ($plan['title'] ?? ''),
            'record_updated_at' => date('Y-m-d H:i:s'),
            'updatetime' => time(),
        ];

        if ($settlement && ($settlement['status'] ?? '') === 'settled') {
            $payload['status'] = 'completed';
        } elseif ($status === 'cancelled' && empty($purchaseOrder['settlement_id'])) {
            $payload['status'] = 'approved';
            $payload['payment_plan_id'] = 0;
            $payload['payment_plan_legacy_id'] = '';
            $payload['payment_plan_title'] = '';
        } else {
            $payload['status'] = 'processing';
        }

        Db::name('business_purchase_order')->where('id', $purchaseOrderId)->update($payload);
    }

    protected function syncLinkedExpenseRequestAfterPlanDelete(array $plan): void
    {
        $expenseRequestId = (int) ($plan['expense_request_id'] ?? 0);
        if ($expenseRequestId <= 0) {
            return;
        }

        $expense = Db::name('business_expense_request')
            ->field('approval_status,status')
            ->where('id', $expenseRequestId)
            ->find();

        if (!$expense) {
            return;
        }

        $status = 'draft';
        if (($expense['approval_status'] ?? '') === 'approved') {
            $status = 'approved';
        } elseif (($expense['approval_status'] ?? '') === 'pending') {
            $status = 'pending_approval';
        } elseif (($expense['approval_status'] ?? '') === 'rejected') {
            $status = 'rejected';
        }

        Db::name('business_expense_request')
            ->where('id', $expenseRequestId)
            ->update([
                'status' => $status,
                'payment_plan_id' => 0,
                'payment_plan_legacy_id' => '',
                'payment_plan_title' => '',
                'record_updated_at' => date('Y-m-d H:i:s'),
                'updatetime' => time(),
            ]);
    }

    protected function syncLinkedPurchaseOrderAfterPlanDelete(array $plan): void
    {
        $purchaseOrderId = (int) ($plan['purchase_order_id'] ?? 0);
        if ($purchaseOrderId <= 0) {
            return;
        }

        $purchaseOrder = Db::name('business_purchase_order')
            ->field('approval_status,status')
            ->where('id', $purchaseOrderId)
            ->find();

        if (!$purchaseOrder) {
            return;
        }

        $status = 'draft';
        if (($purchaseOrder['approval_status'] ?? '') === 'approved') {
            $status = 'approved';
        } elseif (($purchaseOrder['approval_status'] ?? '') === 'pending') {
            $status = 'pending_approval';
        } elseif (($purchaseOrder['approval_status'] ?? '') === 'rejected') {
            $status = 'rejected';
        }

        Db::name('business_purchase_order')
            ->where('id', $purchaseOrderId)
            ->update([
                'status' => $status,
                'payment_plan_id' => 0,
                'payment_plan_legacy_id' => '',
                'payment_plan_title' => '',
                'record_updated_at' => date('Y-m-d H:i:s'),
                'updatetime' => time(),
            ]);
    }

    protected function getLinkedSettlementSnapshot(int $settlementId): ?array
    {
        if ($settlementId <= 0 || !$this->tableExistsByName('business_purchase_settlement')) {
            return null;
        }

        $settlement = Db::name('business_purchase_settlement')
            ->field('id,status')
            ->where('id', $settlementId)
            ->find();

        return $settlement ?: null;
    }
}
