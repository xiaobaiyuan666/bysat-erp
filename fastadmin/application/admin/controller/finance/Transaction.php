<?php

namespace app\admin\controller\finance;

use app\admin\library\traits\ErpAuditHelper;
use app\admin\library\traits\ErpCrudHelper;
use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 财务流水
 *
 * @icon fa fa-circle-o
 */
class Transaction extends Backend
{
    use ErpAuditHelper;
    use ErpCrudHelper;

    /**
     * @var \app\admin\model\Finance\Transaction
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Finance\Transaction();
        $this->view->assign('typeList', $this->model->getTypeList());
        $this->view->assign('paymentMethodList', $this->model->getPaymentMethodList());
        $this->view->assign('projectList', $this->getProjectOptions());
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

        $params = $this->preExcludeFields($params);
        $actor = $this->getCurrentActor();

        $this->fillLegacyId($params, 'finance_tx');
        $this->fillRelationLegacy($params, 'project', 'project_id', 'project_legacy_id', 'name', 'project_name');

        $params['created_by_legacy_id'] = $actor['legacy_id'];
        $params['created_by_admin_id'] = $actor['admin_id'];
        $params['created_by_name'] = $actor['name'];
        $params['updated_by_legacy_id'] = $actor['legacy_id'];
        $params['updated_by_admin_id'] = $actor['admin_id'];
        $params['updated_by_name'] = $actor['name'];
        $params['record_created_at'] = (!array_key_exists('record_created_at', $params) || !$params['record_created_at']) ? date('Y-m-d H:i:s') : $params['record_created_at'];
        $params['record_updated_at'] = date('Y-m-d H:i:s');

        Db::startTrans();
        try {
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                $this->model->validateFailException()->validate($validate);
            }
            $result = $this->model->allowField(true)->save($params);
            if ($result !== false) {
                $this->recordBusinessAudit('finance_transaction', 'add', '财务流水', $params, '新增财务流水：' . ($params['counterparty'] ?: '未命名对象') . ' / ' . $params['amount']);
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

        $params = $this->preExcludeFields($params);
        $actor = $this->getCurrentActor();
        $this->fillRelationLegacy($params, 'project', 'project_id', 'project_legacy_id', 'name', 'project_name');
        $params['updated_by_legacy_id'] = $actor['legacy_id'];
        $params['updated_by_admin_id'] = $actor['admin_id'];
        $params['updated_by_name'] = $actor['name'];
        $params['record_updated_at'] = date('Y-m-d H:i:s');

        Db::startTrans();
        try {
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                $row->validateFailException()->validate($validate);
            }
            $result = $row->allowField(true)->save($params);
            if ($result !== false) {
                $this->recordBusinessAudit('finance_transaction', 'edit', '财务流水', array_merge($row->toArray(), $params), '更新财务流水：' . (($params['counterparty'] ?? $row['counterparty']) ?: '未命名对象') . ' / ' . ($params['amount'] ?? $row['amount']));
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

    public function del($ids = null)
    {
        $this->deleteWithAudit($ids, 'finance_transaction', '财务流水', function ($row) {
            return '删除财务流水：' . ($row['counterparty'] ?: '未命名对象') . ' / ' . $row['amount'];
        });
    }
}
