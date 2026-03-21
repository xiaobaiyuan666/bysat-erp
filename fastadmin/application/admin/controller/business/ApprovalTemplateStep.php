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
 * 审批模板节点
 *
 * @icon fa fa-list-ol
 */
class ApprovalTemplateStep extends Backend
{
    use ErpAuditHelper;
    use ErpCrudHelper;

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Business\ApprovalTemplateStep();
        $this->view->assign('statusList', $this->model->getStatusList());
        $this->view->assign('approverList', $this->getStaffOptions(false));
        $this->view->assign('templateList', $this->getApprovalTemplateOptions('', false));
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

        $params = $this->prepareStepParams($params, true);

        Db::startTrans();
        try {
            $result = $this->model->allowField(true)->save($params);
            if ($result !== false) {
                $stepId = (int) $this->model->id;
                $this->syncTemplateStepCount((int) $params['template_id']);
                $saved = array_merge($params, ['id' => $stepId]);
                $this->recordBusinessAudit(
                    'business_approval_template_step',
                    'add',
                    '审批模板节点',
                    $saved,
                    '新增审批节点：' . ($params['template_name'] ?: '未命名模板') . ' / 第 ' . (int) $params['step_no'] . ' 级'
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

        $this->success('审批节点已新增');
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

        $params = $this->prepareStepParams($params, false, $row->toArray());

        Db::startTrans();
        try {
            $result = $row->allowField(true)->save($params);
            if ($result !== false) {
                $this->syncTemplateStepCount((int) $params['template_id']);
                $saved = array_merge($row->toArray(), $params);
                $this->recordBusinessAudit(
                    'business_approval_template_step',
                    'edit',
                    '审批模板节点',
                    $saved,
                    '更新审批节点：' . (($params['template_name'] ?? $row['template_name']) ?: '未命名模板') . ' / 第 ' . (int) ($params['step_no'] ?? $row['step_no']) . ' 级'
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

        $this->success('审批节点已更新');
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
        $templateIds = [];

        Db::startTrans();
        try {
            foreach ($list as $item) {
                $row = method_exists($item, 'toArray') ? $item->toArray() : (array) $item;
                $templateId = (int) ($row['template_id'] ?? 0);
                $pendingCount = Db::name('business_approval')
                    ->where('template_id', $templateId)
                    ->where('status', 'pending')
                    ->count();

                if ($pendingCount > 0) {
                    throw new Exception('该模板仍有待审批记录，无法删除节点');
                }

                $deleted = $item->delete();
                if ($deleted) {
                    $count += $deleted;
                    $templateIds[] = $templateId;
                    $this->recordBusinessAudit(
                        'business_approval_template_step',
                        'delete',
                        '审批模板节点',
                        $row,
                        '删除审批节点：' . (($row['template_name'] ?? '') ?: '未命名模板') . ' / 第 ' . (int) ($row['step_no'] ?? 0) . ' 级'
                    );
                }
            }

            foreach (array_unique(array_filter($templateIds)) as $templateId) {
                $this->syncTemplateStepCount((int) $templateId);
            }

            Db::commit();
        } catch (PDOException | Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        if ($count) {
            $this->success('审批节点已删除');
        }

        $this->error(__('No rows were deleted'));
    }

    protected function buildDefaultRow(): array
    {
        $templateId = (int) $this->request->get('template_id/d', 0);
        $stepNo = 1;
        if ($templateId > 0) {
            $stepNo = (int) Db::name('business_approval_template_step')
                ->where('template_id', $templateId)
                ->max('step_no') + 1;
        }

        return [
            'template_id' => $templateId,
            'step_no' => max(1, $stepNo),
            'step_name' => '',
            'approver_admin_id' => 0,
            'status' => 'active',
            'notes' => '',
        ];
    }

    protected function prepareStepParams(array $params, bool $isCreate, array $existing = []): array
    {
        $params = $this->preExcludeFields($params);
        $params['template_id'] = (int) ($params['template_id'] ?? ($existing['template_id'] ?? 0));
        $params['step_no'] = (int) ($params['step_no'] ?? ($existing['step_no'] ?? 0));
        $params['step_name'] = trim((string) ($params['step_name'] ?? ''));
        $params['approver_admin_id'] = (int) ($params['approver_admin_id'] ?? ($existing['approver_admin_id'] ?? 0));
        $params['status'] = trim((string) ($params['status'] ?? ($existing['status'] ?? 'active')));
        $params['notes'] = trim((string) ($params['notes'] ?? ''));

        if ($params['template_id'] <= 0) {
            throw new Exception('请选择审批模板');
        }
        if ($params['step_no'] <= 0) {
            throw new Exception('审批层级必须大于 0');
        }
        if ($params['step_name'] === '') {
            throw new Exception('请输入节点名称');
        }
        if ($params['approver_admin_id'] <= 0) {
            throw new Exception('请选择审批人');
        }
        if (!array_key_exists($params['status'], $this->model->getStatusList())) {
            throw new Exception('请选择节点状态');
        }

        $template = Db::name('business_approval_template')
            ->field('id,legacy_id,name,object_type,status')
            ->where('id', $params['template_id'])
            ->find();

        if (!$template) {
            throw new Exception('审批模板不存在');
        }

        $duplicateQuery = Db::name('business_approval_template_step')
            ->where('template_id', $params['template_id'])
            ->where('step_no', $params['step_no']);
        if (!$isCreate) {
            $duplicateQuery->where('id', '<>', (int) ($existing['id'] ?? 0));
        }
        if ((int) $duplicateQuery->count() > 0) {
            throw new Exception('该模板下已存在相同层级，请调整审批层级');
        }

        if ($isCreate) {
            $this->fillLegacyId($params, 'approval_template_step');
        } else {
            $params['legacy_id'] = $existing['legacy_id'] ?? '';
        }

        $params['template_legacy_id'] = (string) ($template['legacy_id'] ?? '');
        $params['template_name'] = (string) ($template['name'] ?? '');
        $params['object_type'] = (string) ($template['object_type'] ?? '');
        $this->fillStaffName($params, 'approver_admin_id', 'approver_name');
        $this->fillStaffLegacy($params, 'approver_admin_id', 'approver_legacy_id');
        $this->fillAuditFields($params, $isCreate);

        return $params;
    }

    protected function syncTemplateStepCount(int $templateId): void
    {
        if ($templateId <= 0) {
            return;
        }

        $stepCount = (int) Db::name('business_approval_template_step')
            ->where('template_id', $templateId)
            ->where('status', 'active')
            ->count();

        Db::name('business_approval_template')
            ->where('id', $templateId)
            ->update([
                'step_count' => $stepCount,
                'record_updated_at' => date('Y-m-d H:i:s'),
                'updatetime' => time(),
            ]);
    }
}
