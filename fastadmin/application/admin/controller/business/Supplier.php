<?php

namespace app\admin\controller\business;

use app\admin\library\traits\ErpAuditHelper;
use app\admin\library\traits\ErpCrudHelper;
use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 供应商档案
 *
 * @icon fa fa-truck
 */
class Supplier extends Backend
{
    use ErpAuditHelper;
    use ErpCrudHelper;

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Business\Supplier();
        $this->view->assign('categoryList', $this->model->getCategoryList());
        $this->view->assign('levelList', $this->model->getLevelList());
        $this->view->assign('statusList', $this->model->getStatusList());
        $this->view->assign('settlementCycleList', $this->model->getSettlementCycleList());
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

        $params = $this->prepareSupplierParams($params, true);

        Db::startTrans();
        try {
            $result = $this->model->allowField(true)->save($params);
            if ($result !== false) {
                $this->recordBusinessAudit(
                    'business_supplier',
                    'add',
                    '供应商档案',
                    $params,
                    '新增供应商：' . ($params['supplier_name'] ?: '未命名供应商')
                );
            }
            Db::commit();
        } catch (ValidateException | PDOException | Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        if ($result === false) {
            $this->error(__('No rows were inserted'));
        }

        $this->success('供应商已新增');
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

        $params = $this->prepareSupplierParams($params, false, (string) $row['legacy_id']);

        Db::startTrans();
        try {
            $result = $row->allowField(true)->save($params);
            if ($result !== false) {
                $merged = array_merge($row->toArray(), $params);
                $this->recordBusinessAudit(
                    'business_supplier',
                    'edit',
                    '供应商档案',
                    $merged,
                    '更新供应商：' . (($params['supplier_name'] ?? $row['supplier_name']) ?: '未命名供应商')
                );
            }
            Db::commit();
        } catch (ValidateException | PDOException | Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        if ($result === false) {
            $this->error(__('No rows were updated'));
        }

        $this->success('供应商已更新');
    }

    public function del($ids = null)
    {
        $this->deleteWithAudit($ids, 'business_supplier', '供应商档案', function ($row) {
            return '删除供应商：' . ($row['supplier_name'] ?: '未命名供应商');
        });
    }

    protected function prepareSupplierParams(array $params, bool $isCreate, string $legacyId = ''): array
    {
        $params = $this->preExcludeFields($params);
        if ($isCreate) {
            $this->fillLegacyId($params, 'supplier');
        } else {
            $params['legacy_id'] = $legacyId;
        }

        $this->fillStaffName($params, 'owner_admin_id', 'owner');
        $this->fillAuditFields($params, $isCreate);

        return $params;
    }
}
