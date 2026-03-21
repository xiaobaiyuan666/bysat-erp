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
 * 审批模板
 *
 * @icon fa fa-sitemap
 */
class ApprovalTemplate extends Backend
{
    use ErpAuditHelper;
    use ErpCrudHelper;

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Business\ApprovalTemplate();
        $this->view->assign('objectTypeList', $this->model->getObjectTypeList());
        $this->view->assign('statusList', $this->model->getStatusList());
        $this->view->assign('isDefaultList', $this->model->getIsDefaultList());
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

        $params = $this->prepareTemplateParams($params, true);

        Db::startTrans();
        try {
            $result = $this->model->allowField(true)->save($params);
            if ($result !== false) {
                $templateId = (int) $this->model->id;
                $this->syncDefaultTemplate((string) $params['object_type'], $templateId, (int) $params['is_default']);
                $this->syncTemplateStepCount($templateId);
                $saved = array_merge($params, ['id' => $templateId]);
                $this->recordBusinessAudit(
                    'business_approval_template',
                    'add',
                    '审批模板',
                    $saved,
                    '新增审批模板：' . ($params['name'] ?: '未命名模板')
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

        $this->success('审批模板已新增');
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

        $params = $this->prepareTemplateParams($params, false, $row->toArray());

        Db::startTrans();
        try {
            $result = $row->allowField(true)->save($params);
            if ($result !== false) {
                $this->syncDefaultTemplate((string) $params['object_type'], (int) $row['id'], (int) $params['is_default']);
                $this->syncTemplateStepCount((int) $row['id']);
                $saved = array_merge($row->toArray(), $params);
                $this->recordBusinessAudit(
                    'business_approval_template',
                    'edit',
                    '审批模板',
                    $saved,
                    '更新审批模板：' . (($params['name'] ?? $row['name']) ?: '未命名模板')
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

        $this->success('审批模板已更新');
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
                $templateId = (int) ($row['id'] ?? 0);
                $pendingCount = Db::name('business_approval')
                    ->where('template_id', $templateId)
                    ->where('status', 'pending')
                    ->count();

                if ($pendingCount > 0) {
                    throw new Exception('该模板仍有待审批记录，无法删除');
                }

                Db::name('business_approval_template_step')->where('template_id', $templateId)->delete();
                $deleted = $item->delete();
                if ($deleted) {
                    $count += $deleted;
                    $this->recordBusinessAudit(
                        'business_approval_template',
                        'delete',
                        '审批模板',
                        $row,
                        '删除审批模板：' . (($row['name'] ?? '') ?: '未命名模板')
                    );
                }
            }
            Db::commit();
        } catch (PDOException | Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        if ($count) {
            $this->success('审批模板已删除');
        }

        $this->error(__('No rows were deleted'));
    }

    protected function buildDefaultRow(): array
    {
        return [
            'name' => trim((string) $this->request->get('name', '')),
            'object_type' => trim((string) $this->request->get('object_type', 'contract')) ?: 'contract',
            'status' => 'active',
            'is_default' => (int) $this->request->get('is_default/d', 0),
            'min_amount' => trim((string) $this->request->get('min_amount', '0')),
            'max_amount' => trim((string) $this->request->get('max_amount', '0')),
            'description' => trim((string) $this->request->get('description', '')),
        ];
    }

    protected function prepareTemplateParams(array $params, bool $isCreate, array $existing = []): array
    {
        $params = $this->preExcludeFields($params);
        $params['name'] = trim((string) ($params['name'] ?? ''));
        $params['object_type'] = trim((string) ($params['object_type'] ?? ($existing['object_type'] ?? '')));
        $params['status'] = trim((string) ($params['status'] ?? ($existing['status'] ?? 'active')));
        $params['is_default'] = (int) ($params['is_default'] ?? ($existing['is_default'] ?? 0));
        $params['min_amount'] = round((float) ($params['min_amount'] ?? ($existing['min_amount'] ?? 0)), 2);
        $params['max_amount'] = round((float) ($params['max_amount'] ?? ($existing['max_amount'] ?? 0)), 2);
        $params['description'] = trim((string) ($params['description'] ?? ''));

        if ($params['name'] === '') {
            throw new Exception('请输入模板名称');
        }
        if (!array_key_exists($params['object_type'], $this->model->getObjectTypeList())) {
            throw new Exception('请选择审批对象类型');
        }
        if (!array_key_exists($params['status'], $this->model->getStatusList())) {
            throw new Exception('请选择模板状态');
        }
        if ($params['min_amount'] < 0 || $params['max_amount'] < 0) {
            throw new Exception('金额范围不能小于 0');
        }
        if ($params['max_amount'] > 0 && $params['max_amount'] < $params['min_amount']) {
            throw new Exception('最高金额不能小于最低金额');
        }
        if ($params['status'] !== 'active') {
            $params['is_default'] = 0;
        }

        if ($isCreate) {
            $this->fillLegacyId($params, 'approval_template');
            $params['step_count'] = 0;
        } else {
            $params['legacy_id'] = $existing['legacy_id'] ?? '';
            $params['step_count'] = (int) ($existing['step_count'] ?? 0);
        }

        $this->fillAuditFields($params, $isCreate);

        return $params;
    }

    protected function syncDefaultTemplate(string $objectType, int $templateId, int $isDefault): void
    {
        if ($objectType === '' || $templateId <= 0) {
            return;
        }

        if ($isDefault === 1) {
            Db::name('business_approval_template')
                ->where('object_type', $objectType)
                ->where('id', '<>', $templateId)
                ->update([
                    'is_default' => 0,
                    'record_updated_at' => date('Y-m-d H:i:s'),
                    'updatetime' => time(),
                ]);
            return;
        }

        Db::name('business_approval_template')
            ->where('id', $templateId)
            ->update([
                'is_default' => 0,
                'record_updated_at' => date('Y-m-d H:i:s'),
                'updatetime' => time(),
            ]);
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
