<?php

namespace app\admin\controller\app;

use app\admin\library\traits\ErpAuditHelper;
use app\admin\library\traits\ErpCrudHelper;
use app\common\controller\Backend;
use think\Db;

/**
 * APP运营项目
 *
 * @icon fa fa-circle-o
 */
class Project extends Backend
{
    use ErpAuditHelper;
    use ErpCrudHelper;

    /**
     * @var \app\admin\model\App\Project
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\App\Project();
        $this->view->assign('lifecycleStageList', $this->model->getLifecycleStageList());
        $this->view->assign('statusList', $this->model->getStatusList());
        $this->view->assign('priorityList', $this->model->getPriorityList());
        $this->view->assign('managerAdminList', $this->getStaffOptions());
        $this->view->assign('projectList', $this->getProjectOptions());
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

        $params = $this->preExcludeFields($params);
        $this->fillLegacyId($params, 'app_project');
        $this->fillStaffName($params, 'manager_admin_id', 'manager');
        $this->fillRelationLegacy($params, 'project', 'project_id', 'project_legacy_id');

        Db::startTrans();
        try {
            $result = $this->model->allowField(true)->save($params);
            if ($result !== false) {
                $this->recordBusinessAudit('app_project', 'add', 'APP运营项目', $params, '新增APP运营项目：' . ($params['app_name'] ?: '未命名APP') . ' / ' . ($params['name'] ?: '未命名项目'));
            }
            Db::commit();
        } catch (\think\exception\ValidateException | \think\exception\PDOException | \Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        if ($result === false) {
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

        if (!$this->request->isPost()) {
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }

        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $params = $this->preExcludeFields($params);
        $params['legacy_id'] = $row['legacy_id'];
        $this->fillStaffName($params, 'manager_admin_id', 'manager');
        $this->fillRelationLegacy($params, 'project', 'project_id', 'project_legacy_id');

        Db::startTrans();
        try {
            $result = $row->allowField(true)->save($params);
            if ($result !== false) {
                $this->recordBusinessAudit('app_project', 'edit', 'APP运营项目', array_merge($row->toArray(), $params), '更新APP运营项目：' . (($params['app_name'] ?? $row['app_name']) ?: '未命名APP') . ' / ' . (($params['name'] ?? $row['name']) ?: '未命名项目'));
            }
            Db::commit();
        } catch (\think\exception\ValidateException | \think\exception\PDOException | \Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        if ($result === false) {
            $this->error(__('No rows were updated'));
        }
        $this->success();
    }

    public function del($ids = null)
    {
        $this->deleteWithAudit($ids, 'app_project', 'APP运营项目', function ($row) {
            return '删除APP运营项目：' . ($row['app_name'] ?: '未命名APP') . ' / ' . ($row['name'] ?: '未命名项目');
        });
    }
}
