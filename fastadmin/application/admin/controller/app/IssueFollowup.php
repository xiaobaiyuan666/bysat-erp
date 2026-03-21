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
 * 问题跟进
 *
 * @icon fa fa-circle-o
 */
class IssueFollowup extends Backend
{
    use ErpAuditHelper;
    use ErpCrudHelper;

    /**
     * @var \app\admin\model\App\IssueFollowup
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\App\IssueFollowup();
        $this->view->assign('typeList', $this->model->getTypeList());
        $this->view->assign('visibilityList', $this->model->getVisibilityList());
        $this->view->assign('statusList', $this->model->getStatusList());
        $this->view->assign('issueList', $this->getIssueOptions());
        $this->view->assign('currentIssueId', (int)$this->request->get('issue_id/d', 0));
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

        $params = $this->prepareFollowupParams($params, true);

        Db::startTrans();
        try {
            $result = $this->model->allowField(true)->save($params);
            $this->syncIssueSnapshot($params);
            if ($result !== false) {
                $this->recordBusinessAudit('app_issue_followup', 'add', '问题跟进', $params, '新增问题跟进：' . $this->summarizeContent($params['content']));
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
        $issueId = (int) $this->request->get('issue_id/d', 0);
        $status = trim((string) $this->request->get('status', ''));
        if ($issueId > 0 && $status === '') {
            $status = (string) Db::name('app_issue')->where('id', $issueId)->value('status');
        }

        return [
            'issue_id' => $issueId,
            'type' => trim((string) $this->request->get('type', 'follow_up')) ?: 'follow_up',
            'visibility' => trim((string) $this->request->get('visibility', 'internal')) ?: 'internal',
            'content' => trim((string) $this->request->get('content', '')),
            'status' => $status ?: 'processing',
            'next_action' => trim((string) $this->request->get('next_action', '')),
        ];
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

        $params = $this->prepareFollowupParams($params, false);

        Db::startTrans();
        try {
            $result = $row->allowField(true)->save($params);
            $this->syncIssueSnapshot(array_merge($row->toArray(), $params));
            if ($result !== false) {
                $this->recordBusinessAudit('app_issue_followup', 'edit', '问题跟进', array_merge($row->toArray(), $params), '更新问题跟进：' . $this->summarizeContent($params['content'] ?? $row['content']));
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

    protected function prepareFollowupParams(array $params, $isCreate)
    {
        $params = $this->preExcludeFields($params);
        $params['content'] = trim((string)($params['content'] ?? ''));
        $params['next_action'] = trim((string)($params['next_action'] ?? ''));
        $params['issue_id'] = (int)($params['issue_id'] ?? 0);

        if ($params['issue_id'] <= 0) {
            throw new Exception('请选择所属问题');
        }
        if ($params['content'] === '') {
            throw new Exception('请填写跟进内容');
        }

        $this->fillLegacyId($params, 'app_issue_follow');
        $this->fillRelationLegacy($params, 'app_issue', 'issue_id', 'issue_legacy_id');
        $this->fillAuditFields($params, $isCreate);

        if (empty($params['status'])) {
            $params['status'] = Db::name('app_issue')->where('id', $params['issue_id'])->value('status') ?: 'processing';
        }

        return $params;
    }

    protected function syncIssueSnapshot(array $params)
    {
        $issueId = (int)($params['issue_id'] ?? 0);
        if ($issueId <= 0) {
            return;
        }

        $updatedAt = $params['record_updated_at'] ?? date('Y-m-d H:i:s');
        $update = [
            'last_follow_up_at' => $updatedAt,
            'updatetime' => time(),
        ];

        if (!empty($params['status'])) {
            $update['status'] = $params['status'];
        }
        if (($params['visibility'] ?? '') === 'customer') {
            $update['customer_notified'] = 1;
            $update['customer_notified_at'] = $updatedAt;
        }

        Db::name('app_issue')->where('id', $issueId)->update($update);
    }

    protected function getIssueOptions($includeEmpty = true)
    {
        $options = $includeEmpty ? [0 => '请选择问题'] : [];
        $rows = Db::name('app_issue')
            ->field('id,ticket_no,title,customer,status')
            ->order('id', 'desc')
            ->select();

        $statusList = (new \app\admin\model\App\Issue())->getStatusList();
        foreach ($rows as $row) {
            $label = ($row['ticket_no'] ?: ('#' . $row['id'])) . ' / ' . $row['title'];
            if ($row['customer']) {
                $label .= ' / ' . $row['customer'];
            }
            if (!empty($statusList[$row['status']])) {
                $label .= ' / ' . $statusList[$row['status']];
            }
            $options[(int)$row['id']] = $label;
        }

        return $options;
    }

    protected function summarizeContent($content)
    {
        $content = trim((string)$content);
        if (function_exists('mb_substr')) {
            return mb_substr($content, 0, 60);
        }
        return substr($content, 0, 60);
    }

    public function del($ids = null)
    {
        $this->deleteWithAudit($ids, 'app_issue_followup', '问题跟进', function ($row) {
            return '删除问题跟进：' . $this->summarizeContent($row['content'] ?? '');
        });
    }
}
