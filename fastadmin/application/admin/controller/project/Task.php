<?php

namespace app\admin\controller\project;

use app\admin\library\traits\ErpAuditHelper;
use app\admin\library\traits\ErpCrudHelper;
use app\common\controller\Backend;
use Exception;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 项目任务
 *
 * @icon fa fa-circle-o
 */
class Task extends Backend
{
    use ErpAuditHelper;
    use ErpCrudHelper;

    /**
     * @var \app\admin\model\Project\Task
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Project\Task();
        $this->view->assign('statusList', $this->model->getStatusList());
        $this->view->assign('priorityList', $this->model->getPriorityList());
        $this->view->assign('projectList', $this->getProjectOptions(false));
        $this->view->assign('assigneeList', $this->getStaffOptions(false));
    }

    public function add()
    {
        if (false === $this->request->isPost()) {
            $this->view->assign('defaultRow', $this->buildDefaultRow());
            return $this->view->fetch();
        }

        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $params['legacy_id'] = '';
        $params['project_legacy_id'] = '';
        $params['assignee'] = '';
        $this->fillLegacyId($params, 'task');
        $this->fillRelationLegacy($params, 'project', 'project_id', 'project_legacy_id', 'name');
        $this->fillStaffName($params, 'assignee_admin_id', 'assignee');

        $result = false;
        Db::startTrans();
        try {
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                $this->model->validateFailException()->validate($validate);
            }
            $result = $this->model->allowField(true)->save($params);
            if ($result !== false) {
                $this->recordBusinessAudit('project_task', 'add', '项目任务', $params, '新增项目任务：' . ($params['title'] ?: '未命名任务'));
            }
            Db::commit();
        } catch (ValidateException | PDOException | Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        if ($result === false) {
            $this->error(__('No rows were inserted'));
        }

        $this->success();
    }

    protected function buildDefaultRow(): array
    {
        $actor = $this->getCurrentActor();

        return [
            'project_id' => (int) $this->request->get('project_id/d', 0),
            'title' => trim((string) $this->request->get('title', '')),
            'assignee_admin_id' => (int) ($this->request->get('assignee_admin_id/d', 0) ?: ($actor['admin_id'] ?? 0)),
            'status' => trim((string) $this->request->get('status', 'todo')) ?: 'todo',
            'priority' => trim((string) $this->request->get('priority', 'medium')) ?: 'medium',
            'due_date' => trim((string) $this->request->get('due_date', '')) ?: date('Y-m-d'),
            'estimate_hours' => trim((string) $this->request->get('estimate_hours', '0.00')) ?: '0.00',
            'actual_hours' => trim((string) $this->request->get('actual_hours', '0.00')) ?: '0.00',
            'notes' => trim((string) $this->request->get('notes', '')),
        ];
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

        $params['legacy_id'] = $row['legacy_id'];
        $params['project_legacy_id'] = '';
        $params['assignee'] = '';
        $this->fillRelationLegacy($params, 'project', 'project_id', 'project_legacy_id', 'name');
        $this->fillStaffName($params, 'assignee_admin_id', 'assignee');

        $result = false;
        Db::startTrans();
        try {
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                $row->validateFailException()->validate($validate);
            }
            $result = $row->allowField(true)->save($params);
            if ($result !== false) {
                $this->recordBusinessAudit('project_task', 'edit', '项目任务', array_merge($row->toArray(), $params), '更新项目任务：' . (($params['title'] ?? $row['title']) ?: '未命名任务'));
            }
            Db::commit();
        } catch (ValidateException | PDOException | Exception $e) {
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
        $this->deleteWithAudit($ids, 'project_task', '项目任务', function ($row) {
            return '删除项目任务：' . ($row['title'] ?: '未命名任务');
        });
    }
}
