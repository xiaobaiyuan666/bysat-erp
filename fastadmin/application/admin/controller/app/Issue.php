<?php

namespace app\admin\controller\app;

use app\admin\library\traits\ErpAuditHelper;
use app\admin\library\traits\ErpCrudHelper;
use app\common\controller\Backend;
use think\Db;

/**
 * 问题记录
 *
 * @icon fa fa-circle-o
 */
class Issue extends Backend
{
    use ErpAuditHelper;
    use ErpCrudHelper;

    /**
     * @var \app\admin\model\App\Issue
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\App\Issue();
        $this->view->assign('sourceList', $this->model->getSourceList());
        $this->view->assign('channelList', $this->model->getChannelList());
        $this->view->assign('categoryList', $this->model->getCategoryList());
        $this->view->assign('statusList', $this->model->getStatusList());
        $this->view->assign('priorityList', $this->model->getPriorityList());
        $this->view->assign('assigneeAdminList', $this->getStaffOptions());
        $this->view->assign('appProjectList', $this->getAppProjectOptions());
        $this->view->assign('projectList', $this->getProjectOptions());
        $this->view->assign('techTicketList', $this->getTechTicketOptions());
        $this->view->assign('notifyList', ['0' => '否', '1' => '是']);
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

        $params = $this->prepareIssueParams($params, true);

        Db::startTrans();
        try {
            $result = $this->model->allowField(true)->save($params);
            if ($result !== false) {
                $this->recordBusinessAudit('app_issue', 'add', '问题记录', $params, '新增问题记录：' . ($params['title'] ?: '未命名问题'));
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

        $params = $this->prepareIssueParams($params, false);

        Db::startTrans();
        try {
            $result = $row->allowField(true)->save($params);
            if ($result !== false) {
                $this->recordBusinessAudit('app_issue', 'edit', '问题记录', array_merge($row->toArray(), $params), '更新问题记录：' . (($params['title'] ?? $row['title']) ?: '未命名问题'));
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

    protected function prepareIssueParams(array $params, $isCreate)
    {
        $params = $this->preExcludeFields($params);
        $this->fillLegacyId($params, 'app_issue');
        $this->fillStaffName($params, 'assignee_admin_id', 'assignee');
        $this->fillRelationLegacy($params, 'app_project', 'app_project_id', 'app_project_legacy_id');
        $this->fillRelationLegacy($params, 'project', 'project_id', 'project_legacy_id');
        $this->fillRelationLegacy($params, 'app_tech_ticket', 'tech_ticket_id', 'tech_ticket_legacy_id');
        $this->fillAuditFields($params, $isCreate);

        foreach (['opened_at', 'last_follow_up_at', 'resolve_due_at', 'customer_notified_at', 'customer_confirmed_at'] as $field) {
            if (array_key_exists($field, $params) && $params[$field] === '') {
                $params[$field] = null;
            }
        }

        $params['customer_notified'] = !empty($params['customer_notified']) ? 1 : 0;
        $params['customer_confirmed'] = !empty($params['customer_confirmed']) ? 1 : 0;

        if ($isCreate && empty($params['ticket_no'])) {
            $params['ticket_no'] = 'ISS-' . date('ymdHis') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 4));
        }
        if ($isCreate && empty($params['opened_at'])) {
            $params['opened_at'] = date('Y-m-d H:i:s');
        }
        if (empty($params['last_follow_up_at']) && !empty($params['opened_at'])) {
            $params['last_follow_up_at'] = $params['opened_at'];
        }
        if (empty($params['customer_notified'])) {
            $params['customer_notified_to'] = '';
            $params['customer_notified_channel'] = '';
            $params['customer_notified_at'] = null;
            $params['customer_feedback_result'] = '';
        }
        if (empty($params['customer_confirmed'])) {
            $params['customer_confirmed_at'] = null;
            $params['customer_confirmation_note'] = '';
        }

        return $params;
    }

    public function del($ids = null)
    {
        $this->deleteWithAudit($ids, 'app_issue', '问题记录', function ($row) {
            return '删除问题记录：' . ($row['title'] ?: '未命名问题');
        });
    }
}
