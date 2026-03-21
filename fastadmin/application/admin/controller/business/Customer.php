<?php

namespace app\admin\controller\business;

use app\admin\library\traits\ErpAuditHelper;
use app\admin\library\traits\ErpCrudHelper;
use app\common\controller\Backend;
use think\Db;

/**
 * 客户档案
 *
 * @icon fa fa-address-book-o
 */
class Customer extends Backend
{
    use ErpAuditHelper;
    use ErpCrudHelper;

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Business\Customer();
        $this->view->assign('customerLevelList', $this->model->getCustomerLevelList());
        $this->view->assign('sourceList', $this->model->getSourceList());
        $this->view->assign('stageList', $this->model->getStageList());
        $this->view->assign('statusList', $this->model->getStatusList());
        $this->view->assign('ownerList', $this->getStaffOptions(false));
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

        $params = $this->prepareCustomerParams($params, true);

        Db::startTrans();
        try {
            $result = $this->model->allowField(true)->save($params);
            if ($result !== false) {
                $this->recordBusinessAudit('business_customer', 'add', '客户档案', $params, '新增客户档案：' . ($params['company_name'] ?: '未命名客户'));
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

        $params = $this->prepareCustomerParams($params, false, $row['legacy_id']);

        Db::startTrans();
        try {
            $result = $row->allowField(true)->save($params);
            if ($result !== false) {
                $merged = array_merge($row->toArray(), $params);
                $this->recordBusinessAudit('business_customer', 'edit', '客户档案', $merged, '更新客户档案：' . (($params['company_name'] ?? $row['company_name']) ?: '未命名客户'));
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
        $this->deleteWithAudit($ids, 'business_customer', '客户档案', function ($row) {
            return '删除客户档案：' . ($row['company_name'] ?: '未命名客户');
        });
    }

    protected function prepareCustomerParams(array $params, bool $isCreate, string $legacyId = ''): array
    {
        $params = $this->preExcludeFields($params);
        if ($isCreate) {
            $this->fillLegacyId($params, 'customer');
        } else {
            $params['legacy_id'] = $legacyId;
        }
        $this->fillStaffName($params, 'owner_admin_id', 'owner');
        $this->fillAuditFields($params, $isCreate);
        if (array_key_exists('last_follow_up_at', $params) && $params['last_follow_up_at'] === '') {
            $params['last_follow_up_at'] = null;
        }

        return $params;
    }
}
