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
 * 研发联动
 *
 * @icon fa fa-circle-o
 */
class TechTicket extends Backend
{
    use ErpAuditHelper;
    use ErpCrudHelper;

    /**
     * @var \app\admin\model\App\TechTicket
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\App\TechTicket();
        $this->view->assign('typeList', $this->model->getTypeList());
        $this->view->assign('statusList', $this->model->getStatusList());
        $this->view->assign('priorityList', $this->model->getPriorityList());
        $this->view->assign('severityList', $this->model->getSeverityList());
        $this->view->assign('sourceList', $this->model->getSourceList());
        $this->view->assign('appProjectList', $this->getAppProjectOptions(false));
        $this->view->assign('projectList', $this->getProjectOptions(false));
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

        $params = $this->prepareTechTicketParams($params, true);

        Db::startTrans();
        try {
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                $this->model->validateFailException()->validate($validate);
            }
            $result = $this->model->allowField(true)->save($params);
            if ($result !== false) {
                $this->recordBusinessAudit('app_tech_ticket', 'add', '研发联动', $params, '新增研发联动：' . ($params['title'] ?: '未命名待办'));
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

        $params = $this->prepareTechTicketParams($params, false);

        Db::startTrans();
        try {
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                $row->validateFailException()->validate($validate);
            }
            $result = $row->allowField(true)->save($params);
            if ($result !== false) {
                $this->recordBusinessAudit('app_tech_ticket', 'edit', '研发联动', array_merge($row->toArray(), $params), '更新研发联动：' . (($params['title'] ?? $row['title']) ?: '未命名待办'));
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

    protected function prepareTechTicketParams(array $params, $isCreate)
    {
        $params = $this->preExcludeFields($params);
        $this->fillLegacyId($params, 'app_tech_ticket');
        $this->fillRelationLegacy($params, 'app_project', 'app_project_id', 'app_project_legacy_id');
        $this->fillRelationLegacy($params, 'project', 'project_id', 'project_legacy_id');
        $this->fillStaffName($params, 'owner_admin_id', 'owner');
        $this->fillStaffName($params, 'reporter_admin_id', 'reporter');

        if (array_key_exists('due_date', $params) && $params['due_date'] === '') {
            $params['due_date'] = null;
        }

        $params['type'] = $params['type'] ?: 'bug';
        $params['status'] = $params['status'] ?: 'pending';
        $params['priority'] = $params['priority'] ?: 'medium';
        $params['severity'] = $params['severity'] ?: 'medium';
        $params['source'] = $params['source'] ?: 'operations';
        $params['estimate_hours'] = isset($params['estimate_hours']) && $params['estimate_hours'] !== '' ? $params['estimate_hours'] : 0;
        $params['actual_hours'] = isset($params['actual_hours']) && $params['actual_hours'] !== '' ? $params['actual_hours'] : 0;

        if ($isCreate && empty($params['legacy_id'])) {
            $params['legacy_id'] = $this->generateLegacyId('app_tech_ticket');
        }

        return $params;
    }

    public function del($ids = null)
    {
        $this->deleteWithAudit($ids, 'app_tech_ticket', '研发联动', function ($row) {
            return '删除研发联动：' . ($row['title'] ?: '未命名待办');
        });
    }
}
