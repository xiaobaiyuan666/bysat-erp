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
 * 审批中心
 *
 * @icon fa fa-check-square-o
 */
class Approval extends Backend
{
    use ErpAuditHelper;
    use ErpCrudHelper;

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Business\Approval();
        $this->view->assign('objectTypeList', $this->model->getObjectTypeList());
        $this->view->assign('statusList', $this->model->getStatusList());
        $this->view->assign('approverList', $this->getStaffOptions(false));
        $this->view->assign('contractList', $this->getContractOptions(false));
        $this->view->assign('paymentPlanList', $this->getPaymentPlanOptions(false));
        $this->view->assign('expenseRequestList', $this->getExpenseRequestOptions(false));
        $this->view->assign('purchaseOrderList', $this->getPurchaseOrderOptions(false));
        $this->view->assign('paymentRequestList', $this->getPaymentRequestOptions(false));
        $this->view->assign('approvalTemplateList', $this->getApprovalTemplateOptions('', false));
        $this->view->assign('approvalTemplateMap', $this->buildApprovalTemplateMap());
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

        $params = $this->prepareApprovalParams($params, true);
        $this->assertNoPendingApproval((string) $params['object_type'], (int) $params['object_id']);

        Db::startTrans();
        try {
            $result = $this->model->allowField(true)->save($params);
            if ($result !== false) {
                $saved = array_merge($params, ['id' => (int) $this->model->id]);
                $this->syncObjectApprovalState((string) $params['object_type'], (int) $params['object_id'], 'pending');
                $this->recordBusinessAudit(
                    'business_approval',
                    'add',
                    '审批中心',
                    $saved,
                    '发起审批：' . (($params['approval_no'] ?: '未编号审批') . ' / ' . ($params['object_title'] ?: '未命名对象'))
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

        $this->success('审批已提交');
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

        if ((string) ($row['status'] ?? '') !== 'pending') {
            $this->error('只有待审批记录可以编辑');
        }

        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $params = $this->prepareApprovalParams($params, false, $row->toArray());

        Db::startTrans();
        try {
            $result = $row->allowField(true)->save($params);
            if ($result !== false) {
                $merged = array_merge($row->toArray(), $params);
                $this->recordBusinessAudit(
                    'business_approval',
                    'edit',
                    '审批中心',
                    $merged,
                    '更新审批：' . (($row['approval_no'] ?: '未编号审批') . ' / ' . ($row['object_title'] ?: '未命名对象'))
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

        $this->success('审批已更新');
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
        $objects = [];

        Db::startTrans();
        try {
            foreach ($list as $item) {
                $row = method_exists($item, 'toArray') ? $item->toArray() : (array) $item;
                $objects[] = ['type' => (string) $row['object_type'], 'id' => (int) $row['object_id']];
                $deleted = $item->delete();
                if ($deleted) {
                    $count += $deleted;
                    $this->recordBusinessAudit(
                        'business_approval',
                        'delete',
                        '审批中心',
                        $row,
                        '删除审批：' . (($row['approval_no'] ?: '未编号审批') . ' / ' . ($row['object_title'] ?: '未命名对象'))
                    );
                }
            }

            foreach ($objects as $object) {
                $this->refreshObjectApprovalState((string) $object['type'], (int) $object['id']);
            }

            Db::commit();
        } catch (PDOException | Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        if ($count) {
            $this->success('审批记录已删除');
        }

        $this->error(__('No rows were deleted'));
    }

    public function approve($ids = null)
    {
        $this->changeStatus($ids, 'approve');
    }

    public function reject($ids = null)
    {
        $this->changeStatus($ids, 'reject');
    }

    public function cancel($ids = null)
    {
        $this->changeStatus($ids, 'cancel');
    }

    protected function changeStatus($ids, string $action): void
    {
        if (!$this->request->isPost()) {
            $this->error(__('Invalid parameters'));
        }

        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if ((string) ($row['status'] ?? '') !== 'pending') {
            $this->error('当前审批状态不可重复处理');
        }

        $actor = $this->getCurrentActor();
        $rowData = $row->toArray();
        $this->assertActorCanHandleApproval($rowData, $actor, $action);

        $decisionNote = trim((string) $this->request->post('note', ''));
        $steps = $this->decodeJsonArray((string) ($row['step_snapshot_json'] ?? '[]'));
        $logs = $this->decodeJsonArray((string) ($row['decision_log_json'] ?? '[]'));
        $currentStep = max(1, (int) ($row['current_step'] ?? 1));
        $totalSteps = max(1, (int) ($row['total_steps'] ?? count($steps) ?: 1));
        $currentStepIndex = max(0, $currentStep - 1);
        $currentStepPayload = $steps[$currentStepIndex] ?? [
            'step_no' => $currentStep,
            'step_name' => (string) ($row['current_step_name'] ?? '人工审批'),
            'approver_admin_id' => (int) ($row['approver_admin_id'] ?? 0),
            'approver_name' => (string) ($row['approver_name'] ?? ''),
        ];

        $logs[] = [
            'step_no' => (int) ($currentStepPayload['step_no'] ?? $currentStep),
            'step_name' => (string) ($currentStepPayload['step_name'] ?? ($row['current_step_name'] ?? '人工审批')),
            'action' => $action,
            'actor_admin_id' => (int) $actor['admin_id'],
            'actor_name' => (string) $actor['name'],
            'note' => $decisionNote,
            'handled_at' => date('Y-m-d H:i:s'),
        ];

        $update = [
            'decision_log_json' => $this->encodeJson($logs),
            'decision_note' => $decisionNote,
            'decided_at' => date('Y-m-d H:i:s'),
        ];
        $this->fillAuditFields($update, false);

        Db::startTrans();
        try {
            if ($action === 'approve' && $currentStep < $totalSteps) {
                $nextStep = $steps[$currentStepIndex + 1] ?? null;
                if (!$nextStep) {
                    throw new Exception('审批模板节点缺失，无法继续流转');
                }

                $update['status'] = 'pending';
                $update['current_step'] = $currentStep + 1;
                $update['current_step_name'] = (string) ($nextStep['step_name'] ?? ('第 ' . ($currentStep + 1) . ' 级审批'));
                $update['approver_admin_id'] = (int) ($nextStep['approver_admin_id'] ?? 0);
                $update['approver_name'] = (string) ($nextStep['approver_name'] ?? '');

                if ((int) $update['approver_admin_id'] <= 0) {
                    throw new Exception('下一审批节点未配置审批人');
                }

                $result = $row->save($update);
                if ($result !== false) {
                    $latest = array_merge($rowData, $update);
                    $this->syncObjectApprovalState((string) $row['object_type'], (int) $row['object_id'], 'pending');
                    $this->recordBusinessAudit(
                        'business_approval',
                        'approve_step',
                        '审批中心',
                        $latest,
                        '审批流转：' . (($row['approval_no'] ?: '未编号审批') . ' / ' . ($row['object_title'] ?: '未命名对象')) . ' / 已进入第 ' . (int) $update['current_step'] . ' 级'
                    );
                }

                Db::commit();
                $this->success('已流转到下一审批节点');
            }

            $statusMap = [
                'approve' => 'approved',
                'reject' => 'rejected',
                'cancel' => 'cancelled',
            ];
            $labelMap = [
                'approve' => '审批通过',
                'reject' => '审批驳回',
                'cancel' => '审批撤回',
            ];

            $status = $statusMap[$action] ?? 'cancelled';
            $update['status'] = $status;
            $update['approver_admin_id'] = (int) $actor['admin_id'];
            $update['approver_name'] = (string) $actor['name'];

            $result = $row->save($update);
            if ($result !== false) {
                $latest = array_merge($rowData, $update);
                $this->syncObjectApprovalState((string) $row['object_type'], (int) $row['object_id'], $status);
                $this->recordBusinessAudit(
                    'business_approval',
                    $status,
                    '审批中心',
                    $latest,
                    ($labelMap[$action] ?? '审批处理') . '：' . (($row['approval_no'] ?: '未编号审批') . ' / ' . ($row['object_title'] ?: '未命名对象'))
                );
            }

            Db::commit();
            $this->success(($labelMap[$action] ?? '审批处理') . '成功');
        } catch (PDOException | Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
    }

    protected function buildDefaultRow(): array
    {
        $objectType = (string) $this->request->get('object_type', '', 'trim');
        $objectId = (int) $this->request->get('object_id/d', 0);
        $templateId = (int) $this->request->get('template_id/d', 0);

        return [
            'object_type' => $objectType,
            'contract_id' => $objectType === 'contract' ? $objectId : 0,
            'payment_plan_id' => $objectType === 'payment_plan' ? $objectId : 0,
            'expense_request_id' => $objectType === 'expense_request' ? $objectId : 0,
            'purchase_order_id' => $objectType === 'purchase_order' ? $objectId : 0,
            'payment_request_id' => $objectType === 'payment_request' ? $objectId : 0,
            'template_id' => $templateId,
            'approver_admin_id' => 0,
            'submit_reason' => '',
        ];
    }

    protected function prepareApprovalParams(array $params, bool $isCreate, array $existing = []): array
    {
        $params = $this->preExcludeFields($params);
        $actor = $this->getCurrentActor();
        $params['submit_reason'] = trim((string) ($params['submit_reason'] ?? ($existing['submit_reason'] ?? '')));
        if ($params['submit_reason'] === '') {
            throw new Exception('请输入发起原因');
        }

        if ($isCreate) {
            $this->fillLegacyId($params, 'approval');
            $params['approval_no'] = 'SP-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 6));
            $params['status'] = 'pending';
            $params['decision_note'] = '';
            $params['applied_at'] = date('Y-m-d H:i:s');
            $params['decided_at'] = null;
            $params['applicant_admin_id'] = (int) $actor['admin_id'];
            $params['applicant_name'] = (string) $actor['name'];

            $this->fillApprovalObjectRelation($params);
            $flow = $this->resolveApprovalFlow((string) $params['object_type'], $params);
            $this->applyApprovalFlow($params, $flow);
        } else {
            $params['legacy_id'] = $existing['legacy_id'] ?? '';
            $params['approval_no'] = $existing['approval_no'] ?? '';
            $params['status'] = $existing['status'] ?? 'pending';
            $params['object_type'] = $existing['object_type'] ?? '';
            $params['object_id'] = $existing['object_id'] ?? 0;
            $params['object_legacy_id'] = $existing['object_legacy_id'] ?? '';
            $params['object_title'] = $existing['object_title'] ?? '';
            $params['customer_id'] = $existing['customer_id'] ?? 0;
            $params['customer_legacy_id'] = $existing['customer_legacy_id'] ?? '';
            $params['customer_name'] = $existing['customer_name'] ?? '';
            $params['contract_id'] = $existing['contract_id'] ?? 0;
            $params['contract_legacy_id'] = $existing['contract_legacy_id'] ?? '';
            $params['contract_name'] = $existing['contract_name'] ?? '';
            $params['payment_plan_id'] = $existing['payment_plan_id'] ?? 0;
            $params['payment_plan_legacy_id'] = $existing['payment_plan_legacy_id'] ?? '';
            $params['payment_plan_title'] = $existing['payment_plan_title'] ?? '';
            $params['expense_request_id'] = $existing['expense_request_id'] ?? 0;
            $params['expense_request_legacy_id'] = $existing['expense_request_legacy_id'] ?? '';
            $params['expense_request_title'] = $existing['expense_request_title'] ?? '';
            $params['purchase_order_id'] = $existing['purchase_order_id'] ?? 0;
            $params['purchase_order_legacy_id'] = $existing['purchase_order_legacy_id'] ?? '';
            $params['purchase_order_title'] = $existing['purchase_order_title'] ?? '';
            $params['payment_request_id'] = $existing['payment_request_id'] ?? 0;
            $params['payment_request_legacy_id'] = $existing['payment_request_legacy_id'] ?? '';
            $params['payment_request_title'] = $existing['payment_request_title'] ?? '';
            $params['template_id'] = (int) ($existing['template_id'] ?? 0);
            $params['template_legacy_id'] = $existing['template_legacy_id'] ?? '';
            $params['template_name'] = $existing['template_name'] ?? '';
            $params['current_step'] = (int) ($existing['current_step'] ?? 1);
            $params['total_steps'] = (int) ($existing['total_steps'] ?? 1);
            $params['current_step_name'] = $existing['current_step_name'] ?? '人工审批';
            $params['step_snapshot_json'] = $existing['step_snapshot_json'] ?? '[]';
            $params['decision_log_json'] = $existing['decision_log_json'] ?? '[]';
            $params['applicant_admin_id'] = $existing['applicant_admin_id'] ?? 0;
            $params['applicant_name'] = $existing['applicant_name'] ?? '';
            $params['applied_at'] = $existing['applied_at'] ?? '';
            $params['decided_at'] = $existing['decided_at'] ?? null;
            $params['decision_note'] = $existing['decision_note'] ?? '';
            $params['approver_admin_id'] = (int) ($params['approver_admin_id'] ?? ($existing['approver_admin_id'] ?? 0));
            $params['approver_name'] = $existing['approver_name'] ?? '';

            if ($params['approver_admin_id'] <= 0) {
                throw new Exception('请选择当前审批人');
            }

            $this->fillStaffName($params, 'approver_admin_id', 'approver_name');
            $this->syncCurrentStepApprover($params);
        }

        $this->fillAuditFields($params, $isCreate);

        return $params;
    }

    protected function resolveApprovalFlow(string $objectType, array $params): array
    {
        $templateId = (int) ($params['template_id'] ?? 0);
        if ($templateId > 0) {
            return $this->loadTemplateFlow($templateId, $objectType);
        }

        $manualApproverId = (int) ($params['approver_admin_id'] ?? 0);
        if ($manualApproverId > 0) {
            $manual = [
                'template_id' => 0,
                'template_legacy_id' => '',
                'template_name' => '手动审批',
                'steps' => [
                    [
                        'step_no' => 1,
                        'step_name' => '人工审批',
                        'approver_admin_id' => $manualApproverId,
                        'approver_name' => '',
                        'approver_legacy_id' => '',
                    ],
                ],
            ];

            $this->fillStaffName($manual['steps'][0], 'approver_admin_id', 'approver_name');
            $this->fillStaffLegacy($manual['steps'][0], 'approver_admin_id', 'approver_legacy_id');

            if (($manual['steps'][0]['approver_name'] ?? '') === '') {
                throw new Exception('手动审批人不存在，请重新选择');
            }

            return $manual;
        }

        $templateId = $this->matchTemplateIdForObject($objectType, $params);
        if ($templateId > 0) {
            return $this->loadTemplateFlow($templateId, $objectType);
        }

        throw new Exception('请先选择审批模板，或至少指定一个审批人');
    }

    protected function applyApprovalFlow(array &$params, array $flow): void
    {
        $steps = $flow['steps'] ?? [];
        $firstStep = $steps[0] ?? null;
        if (!$firstStep) {
            throw new Exception('审批模板未配置有效节点');
        }

        $params['template_id'] = (int) ($flow['template_id'] ?? 0);
        $params['template_legacy_id'] = (string) ($flow['template_legacy_id'] ?? '');
        $params['template_name'] = (string) ($flow['template_name'] ?? '');
        $params['current_step'] = 1;
        $params['total_steps'] = count($steps);
        $params['current_step_name'] = (string) ($firstStep['step_name'] ?? '人工审批');
        $params['approver_admin_id'] = (int) ($firstStep['approver_admin_id'] ?? 0);
        $params['approver_name'] = (string) ($firstStep['approver_name'] ?? '');
        $params['step_snapshot_json'] = $this->encodeJson($steps);
        $params['decision_log_json'] = '[]';

        if ($params['approver_admin_id'] <= 0 || $params['approver_name'] === '') {
            throw new Exception('首个审批节点未配置有效审批人');
        }
    }

    protected function syncCurrentStepApprover(array &$params): void
    {
        $steps = $this->decodeJsonArray((string) ($params['step_snapshot_json'] ?? '[]'));
        $currentIndex = max(0, (int) ($params['current_step'] ?? 1) - 1);
        if (!isset($steps[$currentIndex])) {
            return;
        }

        $step = $steps[$currentIndex];
        $step['approver_admin_id'] = (int) ($params['approver_admin_id'] ?? 0);
        $step['approver_name'] = (string) ($params['approver_name'] ?? '');
        $steps[$currentIndex] = $step;
        $params['step_snapshot_json'] = $this->encodeJson($steps);
    }

    protected function fillApprovalObjectRelation(array &$params): void
    {
        $objectType = (string) ($params['object_type'] ?? '');

        if ($objectType === 'contract') {
            $contractId = (int) ($params['contract_id'] ?? 0);
            $contract = Db::name('business_contract')
                ->field('id,legacy_id,contract_no,name,status,customer_id,customer_legacy_id,customer_name')
                ->where('id', $contractId)
                ->find();

            if (!$contract) {
                throw new Exception('请选择有效的合同');
            }
            if (!in_array((string) ($contract['status'] ?? ''), ['draft', 'review'], true)) {
                throw new Exception('只有草稿或审批中的合同可以发起审批');
            }

            $params['object_id'] = (int) $contract['id'];
            $params['object_legacy_id'] = (string) ($contract['legacy_id'] ?? '');
            $params['object_title'] = trim((string) (($contract['contract_no'] ?: '未编号合同') . ' / ' . ($contract['name'] ?: '未命名合同')), ' /');
            $params['customer_id'] = (int) ($contract['customer_id'] ?? 0);
            $params['customer_legacy_id'] = (string) ($contract['customer_legacy_id'] ?? '');
            $params['customer_name'] = (string) ($contract['customer_name'] ?? '');
            $params['contract_id'] = (int) $contract['id'];
            $params['contract_legacy_id'] = (string) ($contract['legacy_id'] ?? '');
            $params['contract_name'] = (string) ($contract['name'] ?? '');
            $params['payment_plan_id'] = 0;
            $params['payment_plan_legacy_id'] = '';
            $params['payment_plan_title'] = '';
            $params['expense_request_id'] = 0;
            $params['expense_request_legacy_id'] = '';
            $params['expense_request_title'] = '';
            $params['purchase_order_id'] = 0;
            $params['purchase_order_legacy_id'] = '';
            $params['purchase_order_title'] = '';
            return;
        }

        if ($objectType === 'payment_plan') {
            $paymentPlanId = (int) ($params['payment_plan_id'] ?? 0);
            $plan = Db::name('business_payment_plan')
                ->field('id,legacy_id,title,status,customer_id,customer_legacy_id,customer_name,contract_id,contract_legacy_id,contract_name')
                ->where('id', $paymentPlanId)
                ->find();

            if (!$plan) {
                throw new Exception('请选择有效的付款计划');
            }
            if (!in_array((string) ($plan['status'] ?? ''), ['pending', 'overdue'], true)) {
                throw new Exception('只有待付款或已逾期的付款计划可以发起审批');
            }

            $params['object_id'] = (int) $plan['id'];
            $params['object_legacy_id'] = (string) ($plan['legacy_id'] ?? '');
            $params['object_title'] = (string) ($plan['title'] ?? '');
            $params['customer_id'] = (int) ($plan['customer_id'] ?? 0);
            $params['customer_legacy_id'] = (string) ($plan['customer_legacy_id'] ?? '');
            $params['customer_name'] = (string) ($plan['customer_name'] ?? '');
            $params['contract_id'] = (int) ($plan['contract_id'] ?? 0);
            $params['contract_legacy_id'] = (string) ($plan['contract_legacy_id'] ?? '');
            $params['contract_name'] = (string) ($plan['contract_name'] ?? '');
            $params['payment_plan_id'] = (int) $plan['id'];
            $params['payment_plan_legacy_id'] = (string) ($plan['legacy_id'] ?? '');
            $params['payment_plan_title'] = (string) ($plan['title'] ?? '');
            $params['expense_request_id'] = 0;
            $params['expense_request_legacy_id'] = '';
            $params['expense_request_title'] = '';
            $params['purchase_order_id'] = 0;
            $params['purchase_order_legacy_id'] = '';
            $params['purchase_order_title'] = '';
            return;
        }

        if ($objectType === 'expense_request') {
            $expenseRequestId = (int) ($params['expense_request_id'] ?? 0);
            $expense = Db::name('business_expense_request')
                ->field('id,legacy_id,request_no,title,status,customer_id,customer_legacy_id,customer_name,contract_id,contract_legacy_id,contract_name')
                ->where('id', $expenseRequestId)
                ->find();

            if (!$expense) {
                throw new Exception('请选择有效的费用申请');
            }
            if (!in_array((string) ($expense['status'] ?? ''), ['draft', 'rejected'], true)) {
                throw new Exception('只有草稿或已驳回的费用申请可以发起审批');
            }

            $params['object_id'] = (int) $expense['id'];
            $params['object_legacy_id'] = (string) ($expense['legacy_id'] ?? '');
            $params['object_title'] = trim((string) (($expense['request_no'] ?: '未编号申请') . ' / ' . ($expense['title'] ?: '未命名申请')), ' /');
            $params['customer_id'] = (int) ($expense['customer_id'] ?? 0);
            $params['customer_legacy_id'] = (string) ($expense['customer_legacy_id'] ?? '');
            $params['customer_name'] = (string) ($expense['customer_name'] ?? '');
            $params['contract_id'] = (int) ($expense['contract_id'] ?? 0);
            $params['contract_legacy_id'] = (string) ($expense['contract_legacy_id'] ?? '');
            $params['contract_name'] = (string) ($expense['contract_name'] ?? '');
            $params['payment_plan_id'] = 0;
            $params['payment_plan_legacy_id'] = '';
            $params['payment_plan_title'] = '';
            $params['expense_request_id'] = (int) $expense['id'];
            $params['expense_request_legacy_id'] = (string) ($expense['legacy_id'] ?? '');
            $params['expense_request_title'] = (string) ($expense['title'] ?? '');
            $params['purchase_order_id'] = 0;
            $params['purchase_order_legacy_id'] = '';
            $params['purchase_order_title'] = '';
            return;
        }

        if ($objectType === 'purchase_order') {
            $purchaseOrderId = (int) ($params['purchase_order_id'] ?? 0);
            $purchaseOrder = Db::name('business_purchase_order')
                ->field('id,legacy_id,order_no,title,status,customer_id,customer_legacy_id,customer_name,contract_id,contract_legacy_id,contract_name')
                ->where('id', $purchaseOrderId)
                ->find();

            if (!$purchaseOrder) {
                throw new Exception('请选择有效的采购单');
            }
            if (!in_array((string) ($purchaseOrder['status'] ?? ''), ['draft', 'rejected'], true)) {
                throw new Exception('只有草稿或已驳回的采购单可以发起审批');
            }

            $params['object_id'] = (int) $purchaseOrder['id'];
            $params['object_legacy_id'] = (string) ($purchaseOrder['legacy_id'] ?? '');
            $params['object_title'] = trim((string) (($purchaseOrder['order_no'] ?: '未编号采购单') . ' / ' . ($purchaseOrder['title'] ?: '未命名采购单')), ' /');
            $params['customer_id'] = (int) ($purchaseOrder['customer_id'] ?? 0);
            $params['customer_legacy_id'] = (string) ($purchaseOrder['customer_legacy_id'] ?? '');
            $params['customer_name'] = (string) ($purchaseOrder['customer_name'] ?? '');
            $params['contract_id'] = (int) ($purchaseOrder['contract_id'] ?? 0);
            $params['contract_legacy_id'] = (string) ($purchaseOrder['contract_legacy_id'] ?? '');
            $params['contract_name'] = (string) ($purchaseOrder['contract_name'] ?? '');
            $params['payment_plan_id'] = 0;
            $params['payment_plan_legacy_id'] = '';
            $params['payment_plan_title'] = '';
            $params['expense_request_id'] = 0;
            $params['expense_request_legacy_id'] = '';
            $params['expense_request_title'] = '';
            $params['purchase_order_id'] = (int) $purchaseOrder['id'];
            $params['purchase_order_legacy_id'] = (string) ($purchaseOrder['legacy_id'] ?? '');
            $params['purchase_order_title'] = (string) ($purchaseOrder['title'] ?? '');
            $params['payment_request_id'] = 0;
            $params['payment_request_legacy_id'] = '';
            $params['payment_request_title'] = '';
            return;
        }

        if ($objectType === 'payment_request') {
            $paymentRequestId = (int) ($params['payment_request_id'] ?? 0);
            $paymentRequest = Db::name('business_payment_request')
                ->field('id,legacy_id,request_no,title,status,customer_id,customer_legacy_id,customer_name,contract_id,contract_legacy_id,contract_name,payment_plan_id,payment_plan_legacy_id,payment_plan_title,purchase_order_id,purchase_order_legacy_id,purchase_order_title')
                ->where('id', $paymentRequestId)
                ->find();

            if (!$paymentRequest) {
                throw new Exception('请选择有效的付款申请');
            }
            if (!in_array((string) ($paymentRequest['status'] ?? ''), ['draft', 'rejected'], true)) {
                throw new Exception('只有草稿或已驳回的付款申请可以发起审批');
            }

            $params['object_id'] = (int) $paymentRequest['id'];
            $params['object_legacy_id'] = (string) ($paymentRequest['legacy_id'] ?? '');
            $params['object_title'] = trim((string) (($paymentRequest['request_no'] ?: '未编号付款申请') . ' / ' . ($paymentRequest['title'] ?: '未命名付款申请')), ' /');
            $params['customer_id'] = (int) ($paymentRequest['customer_id'] ?? 0);
            $params['customer_legacy_id'] = (string) ($paymentRequest['customer_legacy_id'] ?? '');
            $params['customer_name'] = (string) ($paymentRequest['customer_name'] ?? '');
            $params['contract_id'] = (int) ($paymentRequest['contract_id'] ?? 0);
            $params['contract_legacy_id'] = (string) ($paymentRequest['contract_legacy_id'] ?? '');
            $params['contract_name'] = (string) ($paymentRequest['contract_name'] ?? '');
            $params['payment_plan_id'] = (int) ($paymentRequest['payment_plan_id'] ?? 0);
            $params['payment_plan_legacy_id'] = (string) ($paymentRequest['payment_plan_legacy_id'] ?? '');
            $params['payment_plan_title'] = (string) ($paymentRequest['payment_plan_title'] ?? '');
            $params['expense_request_id'] = 0;
            $params['expense_request_legacy_id'] = '';
            $params['expense_request_title'] = '';
            $params['purchase_order_id'] = (int) ($paymentRequest['purchase_order_id'] ?? 0);
            $params['purchase_order_legacy_id'] = (string) ($paymentRequest['purchase_order_legacy_id'] ?? '');
            $params['purchase_order_title'] = (string) ($paymentRequest['purchase_order_title'] ?? '');
            $params['payment_request_id'] = (int) $paymentRequest['id'];
            $params['payment_request_legacy_id'] = (string) ($paymentRequest['legacy_id'] ?? '');
            $params['payment_request_title'] = (string) ($paymentRequest['title'] ?? '');
            return;
        }

        throw new Exception('请选择审批对象类型');
    }

    protected function assertNoPendingApproval(string $objectType, int $objectId): void
    {
        if ($objectType === '' || $objectId <= 0) {
            return;
        }

        $exists = Db::name('business_approval')
            ->where('object_type', $objectType)
            ->where('object_id', $objectId)
            ->where('status', 'pending')
            ->count();

        if ($exists) {
            throw new Exception('当前对象已有待审批记录，请先处理现有审批');
        }
    }

    protected function assertActorCanHandleApproval(array $row, array $actor, string $action): void
    {
        $actorId = (int) ($actor['admin_id'] ?? 0);
        if ($actorId === 1) {
            return;
        }

        $currentApproverId = (int) ($row['approver_admin_id'] ?? 0);
        if ($action === 'cancel') {
            $applicantId = (int) ($row['applicant_admin_id'] ?? 0);
            if ($actorId !== $applicantId && ($currentApproverId > 0 && $actorId !== $currentApproverId)) {
                throw new Exception('只有发起人、当前审批人或超级管理员可以撤回审批');
            }
            return;
        }

        if ($currentApproverId > 0 && $actorId !== $currentApproverId) {
            throw new Exception('当前审批节点已指派给 ' . (($row['approver_name'] ?? '') ?: '指定审批人') . '，请由对应审批人处理');
        }
    }

    protected function refreshObjectApprovalState(string $objectType, int $objectId): void
    {
        if ($objectType === '' || $objectId <= 0) {
            return;
        }

        $latest = Db::name('business_approval')
            ->where('object_type', $objectType)
            ->where('object_id', $objectId)
            ->order('id', 'desc')
            ->find();

        if (!$latest) {
            $this->syncObjectApprovalState($objectType, $objectId, 'none');
            return;
        }

        $this->syncObjectApprovalState($objectType, $objectId, (string) ($latest['status'] ?? 'none'));
    }

    protected function syncObjectApprovalState(string $objectType, int $objectId, string $approvalStatus): void
    {
        $approvalAt = in_array($approvalStatus, ['pending', 'approved', 'rejected', 'cancelled'], true)
            ? date('Y-m-d H:i:s')
            : null;

        if ($objectType === 'contract') {
            $row = Db::name('business_contract')->field('status')->where('id', $objectId)->find();
            if (!$row) {
                return;
            }

            $payload = [
                'approval_status' => $approvalStatus,
                'approval_updated_at' => $approvalAt,
                'record_updated_at' => date('Y-m-d H:i:s'),
                'updatetime' => time(),
            ];

            if ($approvalStatus === 'pending') {
                $payload['status'] = 'review';
            } elseif ($approvalStatus === 'approved' && in_array((string) $row['status'], ['draft', 'review'], true)) {
                $payload['status'] = 'active';
            } elseif (in_array($approvalStatus, ['rejected', 'cancelled', 'none'], true) && (string) $row['status'] === 'review') {
                $payload['status'] = 'draft';
            }

            Db::name('business_contract')->where('id', $objectId)->update($payload);
            return;
        }

        if ($objectType === 'payment_plan') {
            $row = Db::name('business_payment_plan')->field('status')->where('id', $objectId)->find();
            if (!$row) {
                return;
            }

            $payload = [
                'approval_status' => $approvalStatus,
                'approval_updated_at' => $approvalAt,
                'record_updated_at' => date('Y-m-d H:i:s'),
                'updatetime' => time(),
            ];

            if ($approvalStatus === 'approved' && in_array((string) $row['status'], ['pending', 'overdue'], true)) {
                $payload['status'] = 'processing';
            } elseif (in_array($approvalStatus, ['rejected', 'cancelled', 'none'], true) && (string) $row['status'] === 'processing') {
                $payload['status'] = 'pending';
            }

            Db::name('business_payment_plan')->where('id', $objectId)->update($payload);
            return;
        }

        if ($objectType === 'expense_request') {
            $row = Db::name('business_expense_request')->field('status,payment_plan_id')->where('id', $objectId)->find();
            if (!$row) {
                return;
            }

            $payload = [
                'approval_status' => $approvalStatus,
                'approval_updated_at' => $approvalAt,
                'record_updated_at' => date('Y-m-d H:i:s'),
                'updatetime' => time(),
            ];

            if ($approvalStatus === 'pending') {
                $payload['status'] = 'pending_approval';
            } elseif ($approvalStatus === 'approved' && in_array((string) $row['status'], ['draft', 'rejected', 'pending_approval'], true)) {
                $payload['status'] = 'approved';
            } elseif ($approvalStatus === 'rejected') {
                $payload['status'] = 'rejected';
            } elseif (in_array($approvalStatus, ['cancelled', 'none'], true) && (string) $row['status'] === 'pending_approval' && (int) $row['payment_plan_id'] <= 0) {
                $payload['status'] = 'draft';
            }

            Db::name('business_expense_request')->where('id', $objectId)->update($payload);
            return;
        }

        if ($objectType === 'purchase_order') {
            $row = Db::name('business_purchase_order')->field('status,payment_plan_id')->where('id', $objectId)->find();
            if (!$row) {
                return;
            }

            $payload = [
                'approval_status' => $approvalStatus,
                'approval_updated_at' => $approvalAt,
                'record_updated_at' => date('Y-m-d H:i:s'),
                'updatetime' => time(),
            ];

            if ($approvalStatus === 'pending') {
                $payload['status'] = 'pending_approval';
            } elseif ($approvalStatus === 'approved' && in_array((string) $row['status'], ['draft', 'rejected', 'pending_approval'], true)) {
                $payload['status'] = 'approved';
            } elseif ($approvalStatus === 'rejected') {
                $payload['status'] = 'rejected';
            } elseif (in_array($approvalStatus, ['cancelled', 'none'], true) && (string) $row['status'] === 'pending_approval' && (int) $row['payment_plan_id'] <= 0) {
                $payload['status'] = 'draft';
            }

            Db::name('business_purchase_order')->where('id', $objectId)->update($payload);
            return;
        }

        if ($objectType === 'payment_request') {
            $row = Db::name('business_payment_request')->field('status')->where('id', $objectId)->find();
            if (!$row) {
                return;
            }

            $payload = [
                'approval_status' => $approvalStatus,
                'approval_updated_at' => $approvalAt,
                'record_updated_at' => date('Y-m-d H:i:s'),
                'updatetime' => time(),
            ];

            if ($approvalStatus === 'pending') {
                $payload['status'] = 'pending_approval';
            } elseif ($approvalStatus === 'approved' && in_array((string) $row['status'], ['draft', 'rejected', 'pending_approval'], true)) {
                $payload['status'] = 'approved';
                $payload['approved_at'] = date('Y-m-d H:i:s');
            } elseif ($approvalStatus === 'rejected') {
                $payload['status'] = 'rejected';
            } elseif (in_array($approvalStatus, ['cancelled', 'none'], true) && (string) $row['status'] === 'pending_approval') {
                $payload['status'] = 'draft';
            }

            Db::name('business_payment_request')->where('id', $objectId)->update($payload);
            return;
        }

    }

    protected function buildApprovalTemplateMap(): array
    {
        if (!$this->tableExistsByName('business_approval_template')) {
            return [];
        }

        $templates = Db::name('business_approval_template')
            ->field('id,name,object_type,is_default,status,step_count,min_amount,max_amount')
            ->order('object_type', 'asc')
            ->order('is_default', 'desc')
            ->order('id', 'asc')
            ->select();

        $stepMap = [];
        if ($this->tableExistsByName('business_approval_template_step')) {
            $steps = Db::name('business_approval_template_step')
                ->field('template_id,step_no,step_name,approver_name,status')
                ->where('status', 'active')
                ->order('template_id', 'asc')
                ->order('step_no', 'asc')
                ->select();

            foreach ($steps as $step) {
                $templateId = (int) ($step['template_id'] ?? 0);
                if ($templateId <= 0) {
                    continue;
                }
                $stepMap[$templateId][] = [
                    'step_no' => (int) ($step['step_no'] ?? 0),
                    'step_name' => (string) ($step['step_name'] ?? ''),
                    'approver_name' => (string) ($step['approver_name'] ?? ''),
                ];
            }
        }

        $result = [];
        foreach ($templates as $template) {
            $templateId = (int) ($template['id'] ?? 0);
            $result[$templateId] = [
                'id' => $templateId,
                'name' => (string) ($template['name'] ?? ''),
                'object_type' => (string) ($template['object_type'] ?? ''),
                'is_default' => (int) ($template['is_default'] ?? 0),
                'status' => (string) ($template['status'] ?? 'inactive'),
                'step_count' => (int) ($template['step_count'] ?? 0),
                'min_amount' => round((float) ($template['min_amount'] ?? 0), 2),
                'max_amount' => round((float) ($template['max_amount'] ?? 0), 2),
                'steps' => $stepMap[$templateId] ?? [],
            ];
        }

        return $result;
    }

    protected function findDefaultTemplateId(string $objectType): int
    {
        if ($objectType === '' || !$this->tableExistsByName('business_approval_template')) {
            return 0;
        }

        return (int) Db::name('business_approval_template')
            ->where('object_type', $objectType)
            ->where('status', 'active')
            ->where('is_default', 1)
            ->value('id');
    }

    protected function matchTemplateIdForObject(string $objectType, array $params): int
    {
        if ($objectType === '' || !$this->tableExistsByName('business_approval_template')) {
            return 0;
        }

        $amount = $this->resolveApprovalObjectAmount($objectType, $params);
        $templates = Db::name('business_approval_template')
            ->field('id,is_default,min_amount,max_amount')
            ->where('object_type', $objectType)
            ->where('status', 'active')
            ->order('is_default', 'asc')
            ->order('min_amount', 'desc')
            ->order('id', 'asc')
            ->select();

        $defaultId = 0;
        foreach ($templates as $template) {
            $templateId = (int) ($template['id'] ?? 0);
            if ((int) ($template['is_default'] ?? 0) === 1) {
                $defaultId = $templateId;
                continue;
            }

            if ($this->matchTemplateAmountRange(
                $amount,
                (float) ($template['min_amount'] ?? 0),
                (float) ($template['max_amount'] ?? 0)
            )) {
                return $templateId;
            }
        }

        return $defaultId;
    }

    protected function resolveApprovalObjectAmount(string $objectType, array $params): float
    {
        if ($objectType === 'contract') {
            return round((float) Db::name('business_contract')->where('id', (int) ($params['contract_id'] ?? 0))->value('amount'), 2);
        }
        if ($objectType === 'payment_plan') {
            return round((float) Db::name('business_payment_plan')->where('id', (int) ($params['payment_plan_id'] ?? 0))->value('amount'), 2);
        }
        if ($objectType === 'expense_request') {
            return round((float) Db::name('business_expense_request')->where('id', (int) ($params['expense_request_id'] ?? 0))->value('request_amount'), 2);
        }
        if ($objectType === 'purchase_order') {
            return round((float) Db::name('business_purchase_order')->where('id', (int) ($params['purchase_order_id'] ?? 0))->value('order_amount'), 2);
        }
        if ($objectType === 'payment_request') {
            return round((float) Db::name('business_payment_request')->where('id', (int) ($params['payment_request_id'] ?? 0))->value('request_amount'), 2);
        }
        return 0.0;
    }

    protected function matchTemplateAmountRange(float $amount, float $minAmount, float $maxAmount): bool
    {
        if ($amount < $minAmount) {
            return false;
        }

        if ($maxAmount > 0 && $amount > $maxAmount) {
            return false;
        }

        return true;
    }

    protected function loadTemplateFlow(int $templateId, string $objectType): array
    {
        if (!$this->tableExistsByName('business_approval_template')) {
            throw new Exception('审批模板表不存在，请先执行安装脚本');
        }

        $template = Db::name('business_approval_template')
            ->field('id,legacy_id,name,object_type,status')
            ->where('id', $templateId)
            ->find();

        if (!$template) {
            throw new Exception('审批模板不存在，请重新选择');
        }
        if ((string) ($template['status'] ?? '') !== 'active') {
            throw new Exception('审批模板已停用，请重新选择');
        }
        if ($objectType !== '' && (string) ($template['object_type'] ?? '') !== $objectType) {
            throw new Exception('审批模板与当前审批对象类型不匹配');
        }

        $steps = Db::name('business_approval_template_step')
            ->field('step_no,step_name,approver_admin_id,approver_name,approver_legacy_id')
            ->where('template_id', (int) $template['id'])
            ->where('status', 'active')
            ->order('step_no', 'asc')
            ->select();

        if (!$steps) {
            throw new Exception('审批模板尚未配置有效节点');
        }

        $normalized = [];
        foreach ($steps as $step) {
            if ((int) ($step['approver_admin_id'] ?? 0) <= 0) {
                throw new Exception('审批模板存在未配置审批人的节点，请先完善模板');
            }
            $normalized[] = [
                'step_no' => (int) ($step['step_no'] ?? 0),
                'step_name' => (string) ($step['step_name'] ?? ('第 ' . (int) ($step['step_no'] ?? 0) . ' 级审批')),
                'approver_admin_id' => (int) ($step['approver_admin_id'] ?? 0),
                'approver_name' => (string) ($step['approver_name'] ?? ''),
                'approver_legacy_id' => (string) ($step['approver_legacy_id'] ?? ''),
            ];
        }

        return [
            'template_id' => (int) $template['id'],
            'template_legacy_id' => (string) ($template['legacy_id'] ?? ''),
            'template_name' => (string) ($template['name'] ?? ''),
            'steps' => $normalized,
        ];
    }

    protected function encodeJson(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function decodeJsonArray(string $value): array
    {
        if ($value === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }
}
