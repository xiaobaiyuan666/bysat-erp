<?php

namespace app\admin\controller\app;

use app\admin\library\traits\ErpAuditHelper;
use app\admin\library\traits\ErpCrudHelper;
use app\common\controller\Backend;
use Exception;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 版本发布
 *
 * @icon fa fa-circle-o
 */
class Release extends Backend
{
    use ErpAuditHelper;
    use ErpCrudHelper;

    /**
     * @var \app\admin\model\App\Release
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\App\Release();
        $this->view->assign('statusList', $this->model->getStatusList());
        $this->view->assign('customerSyncStatusList', $this->model->getCustomerSyncStatusList());
        $this->view->assign('appProjectList', $this->getAppProjectOptions(false));
        $this->view->assign('ownerAdminList', $this->getStaffOptions(false));
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

        $params = $this->prepareReleaseParams($params, true);

        Db::startTrans();
        try {
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                $this->model->validateFailException()->validate($validate);
            }

            $result = $this->model->allowField(true)->save($params);
            if ($result !== false) {
                $this->recordBusinessAudit('app_release', 'add', '版本发布', $params, '新增版本发布：' . ($params['version'] ?: '未命名版本') . ' / ' . ($params['title'] ?: '未命名发布'));
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
            $row['tech_ticket_ids_value'] = $this->idsJsonToCsv($row['tech_ticket_ids_json']);
            $row['service_ticket_ids_value'] = $this->idsJsonToCsv($row['service_ticket_ids_json']);
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }

        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $params = $this->prepareReleaseParams($params, false);
        $params['legacy_id'] = $row['legacy_id'];

        Db::startTrans();
        try {
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                $row->validateFailException()->validate($validate);
            }

            $result = $row->allowField(true)->save($params);
            if ($result !== false) {
                $this->recordBusinessAudit('app_release', 'edit', '版本发布', array_merge($row->toArray(), $params), '更新版本发布：' . (($params['version'] ?? $row['version']) ?: '未命名版本') . ' / ' . (($params['title'] ?? $row['title']) ?: '未命名发布'));
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

    protected function prepareReleaseParams(array $params, $isCreate)
    {
        $params = $this->preExcludeFields($params);
        $this->fillLegacyId($params, 'app_release');
        $this->fillRelationLegacy($params, 'app_project', 'app_project_id', 'app_project_legacy_id');
        $this->fillStaffName($params, 'owner_admin_id', 'owner');
        $this->fillAuditFields($params, $isCreate);

        foreach (['release_date'] as $field) {
            if (array_key_exists($field, $params) && $params[$field] === '') {
                $params[$field] = null;
            }
        }

        if ($isCreate && empty($params['release_date'])) {
            $params['release_date'] = date('Y-m-d');
        }

        if (empty($params['customer_sync_status'])) {
            $params['customer_sync_status'] = 'pending';
        }

        if (!isset($params['rollback_ready']) || $params['rollback_ready'] === '') {
            $params['rollback_ready'] = 0;
        }

        $params['tech_ticket_ids_json'] = $this->normalizeIdsJson($params['tech_ticket_ids_json'] ?? '[]');
        $params['service_ticket_ids_json'] = $this->normalizeIdsJson($params['service_ticket_ids_json'] ?? '[]');

        return $params;
    }

    protected function normalizeIdsJson($value)
    {
        if (is_array($value)) {
            $ids = $value;
        } else {
            $text = trim((string)$value);
            if ($text === '') {
                return '[]';
            }

            $decoded = json_decode($text, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $ids = $decoded;
            } else {
                $ids = preg_split('/\s*,\s*/', $text, -1, PREG_SPLIT_NO_EMPTY);
            }
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), function ($id) {
            return $id > 0;
        })));

        return json_encode($ids, JSON_UNESCAPED_UNICODE);
    }

    protected function idsJsonToCsv($value)
    {
        $decoded = json_decode((string)$value, true);
        if (!is_array($decoded) || !$decoded) {
            return '';
        }

        return implode(',', array_map('intval', $decoded));
    }

    public function del($ids = null)
    {
        $this->deleteWithAudit($ids, 'app_release', '版本发布', function ($row) {
            return '删除版本发布：' . ($row['version'] ?: '未命名版本') . ' / ' . ($row['title'] ?: '未命名发布');
        });
    }
}
