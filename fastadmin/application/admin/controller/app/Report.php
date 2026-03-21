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
 * 运营周报
 *
 * @icon fa fa-circle-o
 */
class Report extends Backend
{
    use ErpAuditHelper;
    use ErpCrudHelper;

    /**
     * @var \app\admin\model\App\Report
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\App\Report();
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

        $params = $this->prepareReportParams($params, true);

        Db::startTrans();
        try {
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                $this->model->validateFailException()->validate($validate);
            }
            $result = $this->model->allowField(true)->save($params);
            if ($result !== false) {
                $this->recordBusinessAudit('app_report', 'add', '运营周报', $params, '新增运营周报：' . ($params['title'] ?: '未命名周报'));
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

        $params = $this->prepareReportParams($params, false);

        Db::startTrans();
        try {
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                $row->validateFailException()->validate($validate);
            }
            $result = $row->allowField(true)->save($params);
            if ($result !== false) {
                $this->recordBusinessAudit('app_report', 'edit', '运营周报', array_merge($row->toArray(), $params), '更新运营周报：' . (($params['title'] ?? $row['title']) ?: '未命名周报'));
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

    protected function prepareReportParams(array $params, $isCreate)
    {
        $params = $this->preExcludeFields($params);
        $this->fillLegacyId($params, 'app_report');
        $this->fillRelationLegacy($params, 'app_project', 'app_project_id', 'app_project_legacy_id');
        $this->fillStaffName($params, 'owner_admin_id', 'owner');
        $this->fillAuditFields($params, $isCreate);

        if (array_key_exists('report_date', $params) && $params['report_date'] === '') {
            $params['report_date'] = null;
        }
        if (array_key_exists('record_created_at', $params) && $params['record_created_at'] === '') {
            $params['record_created_at'] = null;
        }
        if (array_key_exists('record_updated_at', $params) && $params['record_updated_at'] === '') {
            $params['record_updated_at'] = null;
        }

        if ($isCreate && empty($params['legacy_id'])) {
            $params['legacy_id'] = $this->generateLegacyId('app_report');
        }

        return $params;
    }

    public function del($ids = null)
    {
        $this->deleteWithAudit($ids, 'app_report', '运营周报', function ($row) {
            return '删除运营周报：' . ($row['title'] ?: '未命名周报');
        });
    }
}
