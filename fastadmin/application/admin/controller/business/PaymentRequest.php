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
 * 付款申请
 *
 * @icon fa fa-credit-card-alt
 */
class PaymentRequest extends Backend
{
    use ErpAuditHelper;
    use ErpCrudHelper;

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Business\PaymentRequest();
        $this->view->assign('statusList', $this->model->getStatusList());
        $this->view->assign('ownerList', $this->getStaffOptions(false));
        $this->view->assign('purchaseOrderList', $this->getPurchaseOrderOptions(false));
        $this->view->assign('purchaseSettlementList', $this->getPurchaseSettlementOptions(false));
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

        $params = $this->prepareRequestParams($params, true);

        Db::startTrans();
        try {
            $result = $this->model->allowField(true)->save($params);
            if ($result !== false) {
                $saved = array_merge($params, ['id' => (int) $this->model->id]);
                $this->syncLinkedRecords((int) ($saved['payment_plan_id'] ?? 0), (int) ($saved['settlement_id'] ?? 0));
                $this->recordBusinessAudit(
                    'business_payment_request',
                    'add',
                    '付款申请',
                    $saved,
                    '新增付款申请：' . (($saved['request_no'] ?: '未编号付款申请') . ' / ' . ($saved['title'] ?: '未命名付款申请'))
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

        $this->success('付款申请已新增');
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

        if ((string) ($row['approval_status'] ?? '') === 'pending') {
            $this->error('审批中的付款申请不允许直接编辑');
        }

        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $original = $row->toArray();
        $params = $this->prepareRequestParams($params, false, $original, (int) $row['id']);

        Db::startTrans();
        try {
            $result = $row->allowField(true)->save($params);
            if ($result !== false) {
                $saved = array_merge($original, $params, ['id' => (int) $row['id']]);
                $this->syncLinkedRecords((int) ($original['payment_plan_id'] ?? 0), (int) ($original['settlement_id'] ?? 0));
                if ((int) ($saved['payment_plan_id'] ?? 0) !== (int) ($original['payment_plan_id'] ?? 0)
                    || (int) ($saved['settlement_id'] ?? 0) !== (int) ($original['settlement_id'] ?? 0)) {
                    $this->syncLinkedRecords((int) ($saved['payment_plan_id'] ?? 0), (int) ($saved['settlement_id'] ?? 0));
                }
                $this->recordBusinessAudit(
                    'business_payment_request',
                    'edit',
                    '付款申请',
                    $saved,
                    '更新付款申请：' . ((($saved['request_no'] ?? '') ?: '未编号付款申请') . ' / ' . (($saved['title'] ?? '') ?: '未命名付款申请'))
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

        $this->success('付款申请已更新');
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
                    throw new Exception('审批中的付款申请不允许删除');
                }

                $deleted = $item->delete();
                if ($deleted) {
                    $count += $deleted;
                    $this->syncLinkedRecords((int) ($row['payment_plan_id'] ?? 0), (int) ($row['settlement_id'] ?? 0));
                    $this->recordBusinessAudit(
                        'business_payment_request',
                        'delete',
                        '付款申请',
                        $row,
                        '删除付款申请：' . ((($row['request_no'] ?? '') ?: '未编号付款申请') . ' / ' . (($row['title'] ?? '') ?: '未命名付款申请'))
                    );
                }
            }
            Db::commit();
        } catch (PDOException | Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        if ($count) {
            $this->success('付款申请已删除');
        }

        $this->error(__('No rows were deleted'));
    }

    public function markpaid($ids = null)
    {
        if (!$this->request->isPost()) {
            $this->error(__('Invalid parameters'));
        }

        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }

        $rowData = $row->toArray();
        if ((string) ($rowData['approval_status'] ?? '') !== 'approved' || (string) ($rowData['status'] ?? '') !== 'approved') {
            $this->error('只有审批通过的付款申请才能标记为已付款');
        }

        $payload = [
            'status' => 'paid',
            'paid_amount' => round((float) ($rowData['request_amount'] ?? 0), 2),
            'paid_at' => date('Y-m-d H:i:s'),
        ];
        $this->fillAuditFields($payload, false);

        Db::startTrans();
        try {
            $result = $row->save($payload);
            if ($result !== false) {
                $saved = array_merge($rowData, $payload, ['id' => (int) $row['id']]);
                $this->syncLinkedRecords((int) ($saved['payment_plan_id'] ?? 0), (int) ($saved['settlement_id'] ?? 0));
                $this->recordBusinessAudit(
                    'business_payment_request',
                    'paid',
                    '付款申请',
                    $saved,
                    '标记付款完成：' . ((($saved['request_no'] ?? '') ?: '未编号付款申请') . ' / ' . (($saved['title'] ?? '') ?: '未命名付款申请'))
                );
            }
            Db::commit();
        } catch (PDOException | Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        if ($result === false) {
            $this->error(__('No rows were updated'));
        }

        $this->success('付款申请已标记为已付款');
    }

    protected function buildDefaultRow(): array
    {
        $actor = $this->getCurrentActor();
        $purchaseOrderId = (int) $this->request->get('purchase_order_id/d', 0);
        $settlementId = (int) $this->request->get('settlement_id/d', 0);
        $paymentPlanId = (int) $this->request->get('payment_plan_id/d', 0);

        $default = [
            'title' => '',
            'purchase_order_id' => $purchaseOrderId,
            'settlement_id' => $settlementId,
            'payment_plan_id' => $paymentPlanId,
            'request_amount' => '0.00',
            'requested_at' => date('Y-m-d H:i:s'),
            'status' => 'draft',
            'owner_admin_id' => (int) ($actor['admin_id'] ?? 0),
            'notes' => '',
        ];

        if ($settlementId > 0) {
            $settlement = $this->getSettlementSnapshot($settlementId);
            if ($settlement) {
                $default['purchase_order_id'] = (int) ($settlement['purchase_order_id'] ?? 0);
                $default['payment_plan_id'] = (int) ($settlement['payment_plan_id'] ?? 0);
                $default['title'] = '采购付款申请 / ' . (($settlement['purchase_order_title'] ?? '') ?: ($settlement['title'] ?? '未命名结算单'));
                $remaining = max(0, round((float) ($settlement['balance_amount'] ?? 0), 2));
                $default['request_amount'] = number_format($remaining > 0 ? $remaining : round((float) ($settlement['settlement_amount'] ?? 0), 2), 2, '.', '');
                $default['owner_admin_id'] = (int) (($settlement['owner_admin_id'] ?? 0) ?: ($actor['admin_id'] ?? 0));
            }
        } elseif ($paymentPlanId > 0) {
            $paymentPlan = $this->getPaymentPlanSnapshot($paymentPlanId);
            if ($paymentPlan) {
                $default['purchase_order_id'] = (int) ($paymentPlan['purchase_order_id'] ?? 0);
                $default['title'] = '付款申请 / ' . (($paymentPlan['title'] ?? '') ?: '未命名付款计划');
                $default['request_amount'] = number_format((float) ($paymentPlan['amount'] ?? 0), 2, '.', '');
            }
        } elseif ($purchaseOrderId > 0) {
            $purchaseOrder = $this->getPurchaseOrderSnapshot($purchaseOrderId);
            if ($purchaseOrder) {
                $default['payment_plan_id'] = (int) ($purchaseOrder['payment_plan_id'] ?? 0);
                $default['title'] = '采购付款申请 / ' . (($purchaseOrder['title'] ?? '') ?: '未命名采购单');
                $default['request_amount'] = number_format((float) ($purchaseOrder['order_amount'] ?? 0), 2, '.', '');
                $default['owner_admin_id'] = (int) (($purchaseOrder['owner_admin_id'] ?? 0) ?: ($actor['admin_id'] ?? 0));
            }
        }

        return $default;
    }

    protected function prepareRequestParams(array $params, bool $isCreate, array $existing = [], int $currentId = 0): array
    {
        $params = $this->preExcludeFields($params);

        if ($isCreate) {
            $this->fillLegacyId($params, 'payment_request');
            $params['request_no'] = trim((string) ($params['request_no'] ?? ''));
            if ($params['request_no'] === '') {
                $params['request_no'] = 'PR-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 6));
            }
            $params['approval_status'] = 'none';
            $params['approval_updated_at'] = null;
            $params['paid_amount'] = 0;
            $params['approved_at'] = null;
            $params['paid_at'] = null;
        } else {
            $params['legacy_id'] = $existing['legacy_id'] ?? '';
            $params['request_no'] = trim((string) ($params['request_no'] ?? ($existing['request_no'] ?? '')));
            if ($params['request_no'] === '') {
                $params['request_no'] = $existing['request_no'] ?? '';
            }
            $params['approval_status'] = (string) ($existing['approval_status'] ?? 'none');
            $params['approval_updated_at'] = $existing['approval_updated_at'] ?? null;
            $params['paid_amount'] = round((float) ($existing['paid_amount'] ?? 0), 2);
            $params['approved_at'] = $existing['approved_at'] ?? null;
            $params['paid_at'] = $existing['paid_at'] ?? null;
        }

        $params['purchase_order_id'] = (int) ($params['purchase_order_id'] ?? ($existing['purchase_order_id'] ?? 0));
        $params['settlement_id'] = (int) ($params['settlement_id'] ?? ($existing['settlement_id'] ?? 0));
        $params['payment_plan_id'] = (int) ($params['payment_plan_id'] ?? ($existing['payment_plan_id'] ?? 0));

        if ($params['purchase_order_id'] <= 0 && $params['settlement_id'] <= 0 && $params['payment_plan_id'] <= 0) {
            throw new Exception('请至少选择采购单、采购结算或付款计划');
        }

        if ($params['settlement_id'] > 0) {
            $this->syncSettlementRelation($params);
        }
        if ($params['payment_plan_id'] > 0) {
            $this->syncPaymentPlanRelation($params);
        }
        if ($params['purchase_order_id'] > 0) {
            $this->syncPurchaseOrderRelation($params);
        }

        $params['title'] = trim((string) ($params['title'] ?? ''));
        if ($params['title'] === '') {
            $params['title'] = $params['settlement_title'] ?: ($params['payment_plan_title'] ?: ($params['purchase_order_title'] ?: '未命名付款申请'));
        }

        $this->fillStaffName($params, 'owner_admin_id', 'owner');
        $this->fillAuditFields($params, $isCreate);

        foreach (['requested_at', 'approved_at', 'paid_at'] as $field) {
            if (array_key_exists($field, $params) && $params[$field] === '') {
                $params[$field] = null;
            }
        }

        $params['request_amount'] = round((float) ($params['request_amount'] ?? 0), 2);
        if ($params['request_amount'] <= 0) {
            throw new Exception('付款申请金额必须大于 0');
        }

        $params['status'] = (string) ($params['status'] ?? ($existing['status'] ?? 'draft'));
        if (!array_key_exists('attachment_ids_json', $params) || $params['attachment_ids_json'] === '') {
            $params['attachment_ids_json'] = $existing['attachment_ids_json'] ?? '[]';
            if ($params['attachment_ids_json'] === '') {
                $params['attachment_ids_json'] = '[]';
            }
        }

        $this->normalizeApprovalState($params, $existing);
        $this->ensureSingleActiveRequest($params, $currentId);

        return $params;
    }

    protected function normalizeApprovalState(array &$params, array $existing = []): void
    {
        $originalApprovalStatus = (string) ($existing['approval_status'] ?? ($params['approval_status'] ?? 'none'));
        $status = (string) ($params['status'] ?? 'draft');
        $approvalStatus = $originalApprovalStatus;

        if ($status === 'pending_approval') {
            $approvalStatus = 'pending';
        } elseif ($status === 'approved') {
            $approvalStatus = 'approved';
            if (empty($params['approved_at'])) {
                $params['approved_at'] = date('Y-m-d H:i:s');
            }
        } elseif ($status === 'paid') {
            $approvalStatus = 'approved';
            $params['paid_amount'] = round((float) ($params['request_amount'] ?? 0), 2);
            if (empty($params['approved_at'])) {
                $params['approved_at'] = date('Y-m-d H:i:s');
            }
            if (empty($params['paid_at'])) {
                $params['paid_at'] = date('Y-m-d H:i:s');
            }
        } elseif ($status === 'rejected') {
            $approvalStatus = 'rejected';
            $params['paid_amount'] = 0;
            $params['paid_at'] = null;
        } elseif ($status === 'cancelled') {
            $approvalStatus = 'cancelled';
            $params['paid_amount'] = 0;
            $params['paid_at'] = null;
        } elseif ($status === 'draft') {
            $approvalStatus = 'none';
            $params['paid_amount'] = 0;
            $params['paid_at'] = null;
            if (($params['approved_at'] ?? null) === '') {
                $params['approved_at'] = null;
            }
        }

        $params['approval_status'] = $approvalStatus;
        if ($approvalStatus === 'none') {
            $params['approval_updated_at'] = null;
        } elseif ($approvalStatus !== $originalApprovalStatus || empty($params['approval_updated_at'])) {
            $params['approval_updated_at'] = date('Y-m-d H:i:s');
        }
    }

    protected function ensureSingleActiveRequest(array $params, int $currentId = 0): void
    {
        $query = Db::name('business_payment_request')
            ->where('status', 'not in', ['cancelled', 'rejected']);

        if ($currentId > 0) {
            $query->where('id', '<>', $currentId);
        }

        if ((int) ($params['settlement_id'] ?? 0) > 0) {
            $query->where('settlement_id', (int) $params['settlement_id']);
        } elseif ((int) ($params['payment_plan_id'] ?? 0) > 0) {
            $query->where('payment_plan_id', (int) $params['payment_plan_id']);
        } else {
            $query->where('purchase_order_id', (int) ($params['purchase_order_id'] ?? 0));
        }

        $existing = $query->field('id,request_no,title')->find();
        if ($existing) {
            throw new Exception('当前对象已存在有效付款申请：' . ((($existing['request_no'] ?? '') ?: '未编号付款申请') . ' / ' . (($existing['title'] ?? '') ?: ('#' . $existing['id']))));
        }
    }

    protected function syncSettlementRelation(array &$params): void
    {
        $settlement = $this->getSettlementSnapshot((int) ($params['settlement_id'] ?? 0));
        if (!$settlement) {
            throw new Exception('未找到关联采购结算');
        }
        if ((string) ($settlement['status'] ?? '') === 'cancelled') {
            throw new Exception('已取消的采购结算不能发起付款申请');
        }

        $params['settlement_id'] = (int) $settlement['id'];
        $params['settlement_legacy_id'] = (string) ($settlement['legacy_id'] ?? '');
        $params['settlement_title'] = (string) ($settlement['title'] ?? '');
        $params['purchase_order_id'] = (int) ($settlement['purchase_order_id'] ?? 0);
        $params['purchase_order_legacy_id'] = (string) ($settlement['purchase_order_legacy_id'] ?? '');
        $params['purchase_order_title'] = (string) ($settlement['purchase_order_title'] ?? '');
        $params['payment_plan_id'] = (int) ($settlement['payment_plan_id'] ?? 0);
        $params['payment_plan_legacy_id'] = (string) ($settlement['payment_plan_legacy_id'] ?? '');
        $params['payment_plan_title'] = (string) ($settlement['payment_plan_title'] ?? '');
        $params['supplier_id'] = (int) ($settlement['supplier_id'] ?? 0);
        $params['supplier_legacy_id'] = (string) ($settlement['supplier_legacy_id'] ?? '');
        $params['supplier_name'] = (string) ($settlement['supplier_name'] ?? '');
        $params['customer_id'] = (int) ($settlement['customer_id'] ?? 0);
        $params['customer_legacy_id'] = (string) ($settlement['customer_legacy_id'] ?? '');
        $params['customer_name'] = (string) ($settlement['customer_name'] ?? '');
        $params['contract_id'] = (int) ($settlement['contract_id'] ?? 0);
        $params['contract_legacy_id'] = (string) ($settlement['contract_legacy_id'] ?? '');
        $params['contract_name'] = (string) ($settlement['contract_name'] ?? '');

        if (empty($params['title'])) {
            $params['title'] = '采购付款申请 / ' . (($settlement['purchase_order_title'] ?? '') ?: ($settlement['title'] ?? '未命名结算单'));
        }
        if (empty($params['request_amount']) || (float) $params['request_amount'] <= 0) {
            $remaining = max(0, round((float) ($settlement['balance_amount'] ?? 0), 2));
            $params['request_amount'] = $remaining > 0 ? $remaining : round((float) ($settlement['settlement_amount'] ?? 0), 2);
        }
        if (empty($params['owner_admin_id']) && !empty($settlement['owner_admin_id'])) {
            $params['owner_admin_id'] = (int) $settlement['owner_admin_id'];
        }
    }

    protected function syncPaymentPlanRelation(array &$params): void
    {
        $paymentPlan = $this->getPaymentPlanSnapshot((int) ($params['payment_plan_id'] ?? 0));
        if (!$paymentPlan) {
            throw new Exception('未找到关联付款计划');
        }

        if ((int) ($params['purchase_order_id'] ?? 0) > 0
            && (int) ($paymentPlan['purchase_order_id'] ?? 0) > 0
            && (int) $params['purchase_order_id'] !== (int) $paymentPlan['purchase_order_id']) {
            throw new Exception('付款计划与采购单不匹配');
        }

        $params['payment_plan_id'] = (int) $paymentPlan['id'];
        $params['payment_plan_legacy_id'] = (string) ($paymentPlan['legacy_id'] ?? '');
        $params['payment_plan_title'] = (string) ($paymentPlan['title'] ?? '');
        $params['customer_id'] = (int) ($paymentPlan['customer_id'] ?? ($params['customer_id'] ?? 0));
        $params['customer_legacy_id'] = (string) ($paymentPlan['customer_legacy_id'] ?? ($params['customer_legacy_id'] ?? ''));
        $params['customer_name'] = (string) ($paymentPlan['customer_name'] ?? ($params['customer_name'] ?? ''));
        $params['contract_id'] = (int) ($paymentPlan['contract_id'] ?? ($params['contract_id'] ?? 0));
        $params['contract_legacy_id'] = (string) ($paymentPlan['contract_legacy_id'] ?? ($params['contract_legacy_id'] ?? ''));
        $params['contract_name'] = (string) ($paymentPlan['contract_name'] ?? ($params['contract_name'] ?? ''));

        if ((int) ($paymentPlan['purchase_order_id'] ?? 0) > 0 && (int) ($params['purchase_order_id'] ?? 0) <= 0) {
            $params['purchase_order_id'] = (int) $paymentPlan['purchase_order_id'];
        }

        if (empty($params['title'])) {
            $params['title'] = '付款申请 / ' . (($paymentPlan['title'] ?? '') ?: '未命名付款计划');
        }
        if (empty($params['request_amount']) || (float) $params['request_amount'] <= 0) {
            $params['request_amount'] = round((float) ($paymentPlan['amount'] ?? 0), 2);
        }
    }

    protected function syncPurchaseOrderRelation(array &$params): void
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
        $params['customer_id'] = (int) ($purchaseOrder['customer_id'] ?? ($params['customer_id'] ?? 0));
        $params['customer_legacy_id'] = (string) ($purchaseOrder['customer_legacy_id'] ?? ($params['customer_legacy_id'] ?? ''));
        $params['customer_name'] = (string) ($purchaseOrder['customer_name'] ?? ($params['customer_name'] ?? ''));
        $params['contract_id'] = (int) ($purchaseOrder['contract_id'] ?? ($params['contract_id'] ?? 0));
        $params['contract_legacy_id'] = (string) ($purchaseOrder['contract_legacy_id'] ?? ($params['contract_legacy_id'] ?? ''));
        $params['contract_name'] = (string) ($purchaseOrder['contract_name'] ?? ($params['contract_name'] ?? ''));

        if ((int) ($purchaseOrder['payment_plan_id'] ?? 0) > 0 && (int) ($params['payment_plan_id'] ?? 0) <= 0) {
            $params['payment_plan_id'] = (int) $purchaseOrder['payment_plan_id'];
            $params['payment_plan_legacy_id'] = (string) ($purchaseOrder['payment_plan_legacy_id'] ?? '');
            $params['payment_plan_title'] = (string) ($purchaseOrder['payment_plan_title'] ?? '');
        }

        if (empty($params['title'])) {
            $params['title'] = '采购付款申请 / ' . (($purchaseOrder['title'] ?? '') ?: '未命名采购单');
        }
        if (empty($params['request_amount']) || (float) $params['request_amount'] <= 0) {
            $params['request_amount'] = round((float) ($purchaseOrder['order_amount'] ?? 0), 2);
        }
        if (empty($params['owner_admin_id']) && !empty($purchaseOrder['owner_admin_id'])) {
            $params['owner_admin_id'] = (int) $purchaseOrder['owner_admin_id'];
        }
    }

    protected function syncLinkedRecords(int $paymentPlanId, int $settlementId): void
    {
        if ($paymentPlanId > 0) {
            $this->syncLinkedPaymentPlanState($paymentPlanId);
        }
        if ($settlementId > 0) {
            $this->syncLinkedSettlementState($settlementId);
        }
    }

    protected function syncLinkedPaymentPlanState(int $paymentPlanId): void
    {
        if ($paymentPlanId <= 0) {
            return;
        }

        $plan = Db::name('business_payment_plan')
            ->field('id,amount,status,due_date')
            ->where('id', $paymentPlanId)
            ->find();
        if (!$plan) {
            return;
        }

        $requests = Db::name('business_payment_request')
            ->field('status,request_amount,paid_amount,paid_at')
            ->where('payment_plan_id', $paymentPlanId)
            ->select();

        $paidAmount = 0.0;
        $hasProgress = false;
        $hasAny = false;
        $latestPaidAt = null;
        foreach ($requests as $request) {
            $hasAny = true;
            $status = (string) ($request['status'] ?? '');
            if ($status === 'paid') {
                $paidAmount += round((float) (($request['paid_amount'] ?? 0) ?: ($request['request_amount'] ?? 0)), 2);
                $latestPaidAt = (string) ($request['paid_at'] ?? $latestPaidAt);
            } elseif (in_array($status, ['pending_approval', 'approved'], true)) {
                $hasProgress = true;
            }
        }

        $amount = round((float) ($plan['amount'] ?? 0), 2);
        $payload = [
            'record_updated_at' => date('Y-m-d H:i:s'),
            'updatetime' => time(),
        ];

        if ($amount > 0 && $paidAmount + 0.00001 >= $amount) {
            $payload['status'] = 'paid';
            $payload['actual_paid_at'] = $latestPaidAt ?: date('Y-m-d H:i:s');
        } elseif ($hasProgress) {
            $payload['status'] = 'processing';
            $payload['actual_paid_at'] = null;
        } elseif ((string) ($plan['status'] ?? '') !== 'cancelled') {
            $payload['status'] = $this->resolvePendingPaymentPlanStatus((string) ($plan['due_date'] ?? ''));
            $payload['actual_paid_at'] = null;
        } elseif (!$hasAny) {
            $payload['status'] = 'cancelled';
        }

        Db::name('business_payment_plan')->where('id', $paymentPlanId)->update($payload);
    }

    protected function syncLinkedSettlementState(int $settlementId): void
    {
        if ($settlementId <= 0) {
            return;
        }

        $settlement = Db::name('business_purchase_settlement')
            ->field('id,settlement_amount,invoice_status,status,purchase_order_id,payment_plan_id')
            ->where('id', $settlementId)
            ->find();
        if (!$settlement) {
            return;
        }

        $requests = Db::name('business_payment_request')
            ->field('status,request_amount,paid_amount,paid_at')
            ->where('settlement_id', $settlementId)
            ->select();

        $paidAmount = 0.0;
        $hasProgress = false;
        $latestPaidAt = null;
        foreach ($requests as $request) {
            $status = (string) ($request['status'] ?? '');
            if ($status === 'paid') {
                $paidAmount += round((float) (($request['paid_amount'] ?? 0) ?: ($request['request_amount'] ?? 0)), 2);
                $latestPaidAt = (string) ($request['paid_at'] ?? $latestPaidAt);
            } elseif (in_array($status, ['pending_approval', 'approved'], true)) {
                $hasProgress = true;
            }
        }

        $settlementAmount = round((float) ($settlement['settlement_amount'] ?? 0), 2);
        $payload = [
            'paid_amount' => round(min($paidAmount, $settlementAmount > 0 ? $settlementAmount : $paidAmount), 2),
            'balance_amount' => round(max(0, $settlementAmount - $paidAmount), 2),
            'record_updated_at' => date('Y-m-d H:i:s'),
            'updatetime' => time(),
        ];

        if ($settlementAmount > 0 && $paidAmount + 0.00001 >= $settlementAmount && (string) ($settlement['invoice_status'] ?? 'none') === 'received') {
            $payload['status'] = 'settled';
            $payload['settled_at'] = $latestPaidAt ?: date('Y-m-d H:i:s');
        } elseif ($hasProgress || $paidAmount > 0) {
            $payload['status'] = 'confirmed';
            $payload['settled_at'] = null;
        } elseif ((string) ($settlement['status'] ?? '') !== 'cancelled') {
            $payload['status'] = (string) ($settlement['invoice_status'] ?? 'none') === 'received' ? 'confirmed' : 'reconciling';
            $payload['settled_at'] = null;
        }

        Db::name('business_purchase_settlement')->where('id', $settlementId)->update($payload);
        $this->syncLinkedPurchaseOrderStateBySettlement($settlementId);
    }

    protected function syncLinkedPurchaseOrderStateBySettlement(int $settlementId): void
    {
        $settlement = Db::name('business_purchase_settlement')
            ->field('id,purchase_order_id,payment_plan_id,status')
            ->where('id', $settlementId)
            ->find();
        if (!$settlement || (int) ($settlement['purchase_order_id'] ?? 0) <= 0) {
            return;
        }

        $purchaseOrder = Db::name('business_purchase_order')
            ->field('id,approval_status,payment_plan_id')
            ->where('id', (int) $settlement['purchase_order_id'])
            ->find();
        if (!$purchaseOrder) {
            return;
        }

        $status = (string) ($settlement['status'] ?? 'draft');
        $payload = [
            'record_updated_at' => date('Y-m-d H:i:s'),
            'updatetime' => time(),
        ];

        if ($status === 'settled') {
            $payload['status'] = 'completed';
        } elseif ($status === 'cancelled') {
            $payload['status'] = (int) ($purchaseOrder['payment_plan_id'] ?? 0) > 0
                ? 'processing'
                : $this->mapApprovalStatusToPurchaseOrderStatus((string) ($purchaseOrder['approval_status'] ?? 'none'));
        } else {
            $payload['status'] = (int) ($purchaseOrder['payment_plan_id'] ?? 0) > 0 ? 'processing' : 'approved';
        }

        Db::name('business_purchase_order')->where('id', (int) $settlement['purchase_order_id'])->update($payload);
    }

    protected function resolvePendingPaymentPlanStatus(string $dueDate): string
    {
        if ($dueDate !== '' && $dueDate < date('Y-m-d')) {
            return 'overdue';
        }

        return 'pending';
    }

    protected function getPurchaseOrderSnapshot(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = Db::name('business_purchase_order')
            ->field('id,legacy_id,title,order_amount,supplier_id,supplier_legacy_id,supplier_name,customer_id,customer_legacy_id,customer_name,contract_id,contract_legacy_id,contract_name,payment_plan_id,payment_plan_legacy_id,payment_plan_title,owner_admin_id')
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
            ->field('id,legacy_id,title,amount,status,purchase_order_id,purchase_order_title,contract_id,contract_legacy_id,contract_name,customer_id,customer_legacy_id,customer_name')
            ->where('id', $id)
            ->find();

        return $row ?: null;
    }

    protected function getSettlementSnapshot(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = Db::name('business_purchase_settlement')
            ->field('id,legacy_id,title,purchase_order_id,purchase_order_legacy_id,purchase_order_title,payment_plan_id,payment_plan_legacy_id,payment_plan_title,supplier_id,supplier_legacy_id,supplier_name,customer_id,customer_legacy_id,customer_name,contract_id,contract_legacy_id,contract_name,settlement_amount,balance_amount,status,invoice_status,owner_admin_id')
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
