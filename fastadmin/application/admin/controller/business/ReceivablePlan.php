<?php

namespace app\admin\controller\business;

use app\admin\library\traits\ErpAuditHelper;
use app\admin\library\traits\ErpCrudHelper;
use app\common\controller\Backend;
use think\Db;

/**
 * 回款计划
 *
 * @icon fa fa-calendar-check-o
 */
class ReceivablePlan extends Backend
{
    use ErpAuditHelper;
    use ErpCrudHelper;

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Business\ReceivablePlan();
        $this->view->assign('statusList', $this->model->getStatusList());
        $this->view->assign('ownerList', $this->getStaffOptions(false));
        $this->view->assign('customerList', $this->getCustomerOptions(false));
        $this->view->assign('contractList', $this->getContractOptions(false));
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

        $params = $this->preparePlanParams($params, true);

        Db::startTrans();
        try {
            $result = $this->model->allowField(true)->save($params);
            if ($result !== false) {
                $this->recordBusinessAudit('business_receivable_plan', 'add', '回款计划', $params, '新增回款计划：' . ($params['title'] ?: '未命名计划'));
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

        $params = $this->preparePlanParams($params, false, $row['legacy_id']);

        Db::startTrans();
        try {
            $result = $row->allowField(true)->save($params);
            if ($result !== false) {
                $merged = array_merge($row->toArray(), $params);
                $this->recordBusinessAudit('business_receivable_plan', 'edit', '回款计划', $merged, '更新回款计划：' . (($params['title'] ?? $row['title']) ?: '未命名计划'));
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
        $this->deleteWithAudit($ids, 'business_receivable_plan', '回款计划', function ($row) {
            return '删除回款计划：' . ($row['title'] ?: '未命名计划');
        });
    }

    protected function preparePlanParams(array $params, bool $isCreate, string $legacyId = ''): array
    {
        $params = $this->preExcludeFields($params);
        if ($isCreate) {
            $this->fillLegacyId($params, 'plan');
        } else {
            $params['legacy_id'] = $legacyId;
        }
        $this->fillRelationLegacy($params, 'business_contract', 'contract_id', 'contract_legacy_id', 'name', 'contract_name');
        $this->fillRelationLegacy($params, 'business_customer', 'customer_id', 'customer_legacy_id', 'company_name', 'customer_name');
        $this->fillStaffName($params, 'owner_admin_id', 'owner');
        $this->fillAuditFields($params, $isCreate);

        if (array_key_exists('due_date', $params) && $params['due_date'] === '') {
            $params['due_date'] = null;
        }
        if (array_key_exists('actual_received_at', $params) && $params['actual_received_at'] === '') {
            $params['actual_received_at'] = null;
        }

        $params['amount'] = round((float) ($params['amount'] ?? 0), 2);

        return $params;
    }
}
