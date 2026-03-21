<?php

namespace app\admin\controller\business;

use app\admin\library\traits\ErpAuditHelper;
use app\admin\library\traits\ErpCrudHelper;
use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;

/**
 * 客户跟进记录
 *
 * @icon fa fa-commenting-o
 */
class CustomerFollowup extends Backend
{
    use ErpAuditHelper;
    use ErpCrudHelper;

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Business\CustomerFollowup();
        $this->view->assign('followupTypeList', $this->model->getFollowupTypeList());
        $this->view->assign('statusList', $this->model->getStatusList());
        $this->view->assign('ownerList', $this->getStaffOptions(false));
        $this->view->assign('customerList', $this->getCustomerOptions(false));
        $this->view->assign('contractList', $this->getContractOptions(false));
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
            if ($result !== false) {
                $this->syncCustomerLastFollowUpAt((int) ($params['customer_id'] ?? 0));
                $this->recordBusinessAudit(
                    'business_customer_followup',
                    'add',
                    '客户跟进记录',
                    $params,
                    '新增客户跟进：' . (($params['customer_name'] ?: '未关联合同客户') . ' / ' . ($params['title'] ?: '未命名跟进'))
                );
            }
            Db::commit();
        } catch (\think\exception\ValidateException | \Exception | \think\exception\PDOException $e) {
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
        $actor = $this->getCurrentActor();
        $default = [
            'title' => trim((string) $this->request->get('title', '')),
            'customer_id' => (int) $this->request->get('customer_id/d', 0),
            'contract_id' => (int) $this->request->get('contract_id/d', 0),
            'followup_type' => trim((string) $this->request->get('followup_type', 'meeting')) ?: 'meeting',
            'follow_up_at' => trim((string) $this->request->get('follow_up_at', '')) ?: date('Y-m-d H:i:s'),
            'next_follow_up_at' => trim((string) $this->request->get('next_follow_up_at', '')),
            'status' => trim((string) $this->request->get('status', 'done')) ?: 'done',
            'owner_admin_id' => (int) ($this->request->get('owner_admin_id/d', 0) ?: ($actor['admin_id'] ?? 0)),
            'contact_name' => trim((string) $this->request->get('contact_name', '')),
            'result_summary' => trim((string) $this->request->get('result_summary', '')),
            'notes' => trim((string) $this->request->get('notes', '')),
        ];

        if ($default['contract_id'] > 0 && $default['customer_id'] <= 0) {
            $default['customer_id'] = (int) Db::name('business_contract')
                ->where('id', $default['contract_id'])
                ->value('customer_id');
        }

        return $default;
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

        $oldCustomerId = (int) $row['customer_id'];
        $params = $this->prepareFollowupParams($params, false, $row['legacy_id']);

        Db::startTrans();
        try {
            $result = $row->allowField(true)->save($params);
            if ($result !== false) {
                $merged = array_merge($row->toArray(), $params);
                $this->syncCustomerLastFollowUpAt($oldCustomerId);
                $this->syncCustomerLastFollowUpAt((int) ($params['customer_id'] ?? 0));
                $this->recordBusinessAudit(
                    'business_customer_followup',
                    'edit',
                    '客户跟进记录',
                    $merged,
                    '更新客户跟进：' . ((($params['customer_name'] ?? $row['customer_name']) ?: '未关联合同客户') . ' / ' . (($params['title'] ?? $row['title']) ?: '未命名跟进'))
                );
            }
            Db::commit();
        } catch (\think\exception\ValidateException | \Exception | \think\exception\PDOException $e) {
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
        if (false === $this->request->isPost()) {
            $this->error(__('Invalid parameters'));
        }

        $ids = $ids ?: $this->request->post('ids');
        if (empty($ids)) {
            $this->error(__('Parameter %s can not be empty', 'ids'));
        }

        $pk = $this->model->getPk();
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            $this->model->where($this->dataLimitField, 'in', $adminIds);
        }

        $list = $this->model->where($pk, 'in', $ids)->select();
        $count = 0;
        $customerIds = [];

        Db::startTrans();
        try {
            foreach ($list as $item) {
                $row = method_exists($item, 'toArray') ? $item->toArray() : (array) $item;
                $customerIds[] = (int) ($row['customer_id'] ?? 0);
                $deleted = $item->delete();
                if ($deleted) {
                    $count += $deleted;
                    $this->recordBusinessAudit(
                        'business_customer_followup',
                        'delete',
                        '客户跟进记录',
                        $row,
                        '删除客户跟进：' . (($row['customer_name'] ?: '未关联合同客户') . ' / ' . ($row['title'] ?: '未命名跟进'))
                    );
                }
            }

            foreach (array_unique(array_filter($customerIds)) as $customerId) {
                $this->syncCustomerLastFollowUpAt((int) $customerId);
            }
            Db::commit();
        } catch (PDOException | Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        if ($count) {
            $this->success();
        }

        $this->error(__('No rows were deleted'));
    }

    protected function prepareFollowupParams(array $params, bool $isCreate, string $legacyId = ''): array
    {
        $params = $this->preExcludeFields($params);
        if ($isCreate) {
            $this->fillLegacyId($params, 'customer_followup');
        } else {
            $params['legacy_id'] = $legacyId;
        }

        $this->syncContractCustomerRelation($params);
        $this->fillRelationLegacy($params, 'business_customer', 'customer_id', 'customer_legacy_id', 'company_name', 'customer_name');
        $this->fillRelationLegacy($params, 'business_contract', 'contract_id', 'contract_legacy_id', 'name', 'contract_name');
        $this->fillStaffName($params, 'owner_admin_id', 'owner');
        $this->fillAuditFields($params, $isCreate);

        foreach (['follow_up_at', 'next_follow_up_at'] as $field) {
            if (array_key_exists($field, $params) && $params[$field] === '') {
                $params[$field] = null;
            }
        }

        return $params;
    }

    protected function syncContractCustomerRelation(array &$params): void
    {
        $contractId = (int) ($params['contract_id'] ?? 0);
        if ($contractId <= 0) {
            return;
        }

        $contract = Db::name('business_contract')
            ->field('id,legacy_id,name,customer_id,customer_legacy_id,customer_name')
            ->where('id', $contractId)
            ->find();

        if (!$contract) {
            return;
        }

        $params['contract_id'] = (int) $contract['id'];
        $params['contract_legacy_id'] = (string) ($contract['legacy_id'] ?? '');
        $params['contract_name'] = (string) ($contract['name'] ?? '');
        $params['customer_id'] = (int) ($contract['customer_id'] ?? 0);
        $params['customer_legacy_id'] = (string) ($contract['customer_legacy_id'] ?? '');
        $params['customer_name'] = (string) ($contract['customer_name'] ?? '');
    }

    protected function syncCustomerLastFollowUpAt(int $customerId): void
    {
        if ($customerId <= 0 || !$this->tableExistsByName('business_customer')) {
            return;
        }

        $latest = null;
        if ($this->tableExistsByName('business_customer_followup')) {
            $latest = Db::name('business_customer_followup')
                ->where('customer_id', $customerId)
                ->order('follow_up_at', 'desc')
                ->value('follow_up_at');
        }

        Db::name('business_customer')
            ->where('id', $customerId)
            ->update([
                'last_follow_up_at' => $latest ?: null,
                'record_updated_at' => date('Y-m-d H:i:s'),
                'updatetime' => time(),
            ]);
    }
}
