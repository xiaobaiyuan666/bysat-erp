<?php

namespace app\admin\controller\app;

use app\admin\library\traits\ErpAuditHelper;
use app\admin\library\traits\ErpCrudHelper;
use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 运营里程碑
 *
 * @icon fa fa-circle-o
 */
class Milestone extends Backend
{
    use ErpAuditHelper;
    use ErpCrudHelper;

    /**
     * @var \app\admin\model\App\Milestone
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\App\Milestone();
        $this->view->assign('statusList', $this->model->getStatusList());
        $this->view->assign('appProjectList', $this->getAppProjectOptions(false));
        $this->view->assign('staffList', $this->getStaffOptions(false));
    }

    public function add()
    {
        if (false === $this->request->isPost()) {
            return $this->view->fetch();
        }

        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $params = $this->prepareMilestoneParams($params, true);

        Db::startTrans();
        try {
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                $this->model->validateFailException()->validate($validate);
            }
            $result = $this->model->allowField(true)->save($params);
            if ($result !== false) {
                $this->recordBusinessAudit('app_milestone', 'add', '运营里程碑', $params, '新增运营里程碑：' . ($params['title'] ?: '未命名里程碑'));
            }
            Db::commit();
        } catch (ValidateException | PDOException | Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        if (false === $result) {
            $this->error(__('No rows were inserted'));
        }

        $this->success();
    }

    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }

        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds) && !in_array($row[$this->dataLimitField], $adminIds)) {
            $this->error(__('You have no permission'));
        }

        if (false === $this->request->isPost()) {
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }

        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $params = $this->prepareMilestoneParams($params, false);

        Db::startTrans();
        try {
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                $row->validateFailException()->validate($validate);
            }
            $result = $row->allowField(true)->save($params);
            if ($result !== false) {
                $this->recordBusinessAudit('app_milestone', 'edit', '运营里程碑', array_merge($row->toArray(), $params), '更新运营里程碑：' . (($params['title'] ?? $row['title']) ?: '未命名里程碑'));
            }
            Db::commit();
        } catch (ValidateException | PDOException | Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        if (false === $result) {
            $this->error(__('No rows were updated'));
        }

        $this->success();
    }

    protected function prepareMilestoneParams(array $params, $isCreate)
    {
        $params = $this->preExcludeFields($params);
        $this->fillLegacyId($params, 'app_milestone');
        $this->fillRelationLegacy($params, 'app_project', 'app_project_id', 'app_project_legacy_id');
        $this->fillStaffName($params, 'owner_admin_id', 'owner');

        if (array_key_exists('due_date', $params) && $params['due_date'] === '') {
            $params['due_date'] = null;
        }

        $params['status'] = $params['status'] ?: 'pending';
        $params['progress'] = isset($params['progress']) && $params['progress'] !== '' ? (int)$params['progress'] : 0;

        if ($isCreate && empty($params['legacy_id'])) {
            $params['legacy_id'] = $this->generateLegacyId('app_milestone');
        }

        return $params;
    }

    public function del($ids = null)
    {
        $this->deleteWithAudit($ids, 'app_milestone', '运营里程碑', function ($row) {
            return '删除运营里程碑：' . ($row['title'] ?: '未命名里程碑');
        });
    }
}
