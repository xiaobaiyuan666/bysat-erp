<?php

namespace app\admin\controller\business;

use app\admin\library\traits\ErpAuditHelper;
use app\admin\library\traits\ErpCrudHelper;
use app\common\controller\Backend;
use think\Db;

/**
 * 合同台账
 *
 * @icon fa fa-file-text-o
 */
class Contract extends Backend
{
    use ErpAuditHelper;
    use ErpCrudHelper;

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Business\Contract();
        $this->view->assign('categoryList', $this->model->getCategoryList());
        $this->view->assign('statusList', $this->model->getStatusList());
        $this->view->assign('ownerList', $this->getStaffOptions(false));
        $this->view->assign('customerList', $this->getCustomerOptions(false));
        $this->view->assign('projectList', $this->getProjectOptions());
        $this->view->assign('appProjectList', $this->getAppProjectOptions());
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

        $params = $this->prepareContractParams($params, true);

        Db::startTrans();
        try {
            $result = $this->model->allowField(true)->save($params);
            if ($result !== false) {
                $this->recordBusinessAudit(
                    'business_contract',
                    'add',
                    '合同台账',
                    $params,
                    '新增合同：' . (($params['contract_no'] ?: '未编号合同') . ' / ' . ($params['name'] ?: '未命名合同'))
                );
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

        $params = $this->prepareContractParams($params, false, $row['legacy_id'], $row['contract_no']);

        Db::startTrans();
        try {
            $result = $row->allowField(true)->save($params);
            if ($result !== false) {
                $merged = array_merge($row->toArray(), $params);
                $this->recordBusinessAudit(
                    'business_contract',
                    'edit',
                    '合同台账',
                    $merged,
                    '更新合同：' . (($params['contract_no'] ?? $row['contract_no']) . ' / ' . (($params['name'] ?? $row['name']) ?: '未命名合同'))
                );
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
        $this->deleteWithAudit($ids, 'business_contract', '合同台账', function ($row) {
            return '删除合同：' . (($row['contract_no'] ?: '未编号合同') . ' / ' . ($row['name'] ?: '未命名合同'));
        });
    }

    protected function prepareContractParams(array $params, bool $isCreate, string $legacyId = '', string $contractNo = ''): array
    {
        $params = $this->preExcludeFields($params);
        if ($isCreate) {
            $this->fillLegacyId($params, 'contract');
        } else {
            $params['legacy_id'] = $legacyId;
        }
        if (empty($params['contract_no'])) {
            $params['contract_no'] = $contractNo ?: ('HT-' . date('Y') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 4)));
        }

        $this->fillRelationLegacy($params, 'business_customer', 'customer_id', 'customer_legacy_id', 'company_name', 'customer_name');
        $this->fillRelationLegacy($params, 'project', 'project_id', 'project_legacy_id');
        $this->fillRelationLegacy($params, 'app_project', 'app_project_id', 'app_project_legacy_id');
        $this->fillStaffName($params, 'owner_admin_id', 'owner');
        $this->fillAuditFields($params, $isCreate);

        foreach (['signed_at', 'start_date', 'end_date'] as $field) {
            if (array_key_exists($field, $params) && $params[$field] === '') {
                $params[$field] = null;
            }
        }

        $params['amount'] = round((float) ($params['amount'] ?? 0), 2);
        $params['invoice_total'] = round((float) ($params['invoice_total'] ?? 0), 2);
        $params['received_total'] = round((float) ($params['received_total'] ?? 0), 2);
        $params['pending_total'] = round(max(0, $params['amount'] - $params['received_total']), 2);
        if (!array_key_exists('attachment_ids_json', $params) || $params['attachment_ids_json'] === '') {
            $params['attachment_ids_json'] = '[]';
        }

        return $params;
    }
}
