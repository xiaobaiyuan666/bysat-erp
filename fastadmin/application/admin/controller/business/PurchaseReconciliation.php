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
 * 采购对账
 *
 * @icon fa fa-random
 */
class PurchaseReconciliation extends Backend
{
    use ErpAuditHelper;
    use ErpCrudHelper;

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Business\PurchaseReconciliation();
        $this->view->assign('statusList', $this->model->getStatusList());
        $this->view->assign('ownerList', $this->getStaffOptions(false));
        $this->view->assign('purchaseOrderList', $this->getPurchaseOrderOptions(false));
        $this->view->assign('paymentPlanList', $this->getPaymentPlanOptions(false));
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

        $params = $this->prepareReconciliationParams($params, true);

        Db::startTrans();
        try {
            $result = $this->model->allowField(true)->save($params);
            if ($result !== false) {
                $saved = array_merge($params, ['id' => (int) $this->model->id]);
                $this->syncLinkedPurchaseOrderAfterReconciliationSave($saved);
                $this->recordBusinessAudit(
                    'business_purchase_reconciliation',
                    'add',
                    '采购对账',
                    $saved,
                    '新增采购对账：' . (($params['reconcile_no'] ?: '未编号对账单') . ' / ' . ($params['title'] ?: '未命名对账单'))
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

        $this->success('采购对账已新增');
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

        $original = $row->toArray();
        $params = $this->prepareReconciliationParams($params, false, $original, (int) $row['id']);

        Db::startTrans();
        try {
            $result = $row->allowField(true)->save($params);
            if ($result !== false) {
                if ((int) ($original['purchase_order_id'] ?? 0) > 0 && (int) ($original['purchase_order_id'] ?? 0) !== (int) ($params['purchase_order_id'] ?? 0)) {
                    $this->syncLinkedPurchaseOrderAfterReconciliationDelete($original);
                }

                $saved = array_merge($original, $params, ['id' => (int) $row['id']]);
                $this->syncLinkedPurchaseOrderAfterReconciliationSave($saved);
                $this->recordBusinessAudit(
                    'business_purchase_reconciliation',
                    'edit',
                    '采购对账',
                    $saved,
                    '更新采购对账：' . ((($params['reconcile_no'] ?? $original['reconcile_no']) ?: '未编号对账单') . ' / ' . (($params['title'] ?? $original['title']) ?: '未命名对账单'))
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

        $this->success('采购对账已更新');
    }

    public function del($ids = null)
    {
        if (!$this->request->isPost()) {
            $this->error(__('Invalid parameters'));
        }

        $ids = $ids ?: $this->request->post('ids');
        if (empty($ids)) {
            $this->error(__('Parameter %s can not be empty', 'ids'));
        }

        $pk = $this->model->getPk();
        $list = $this->model->where($pk, 'in', $ids)->select();
        if (!$list || count($list) === 0) {
            $this->error(__('No Results were found'));
        }

        $count = 0;
        Db::startTrans();
        try {
            foreach ($list as $item) {
                $row = method_exists($item, 'toArray') ? $item->toArray() : (array) $item;
                $purchaseOrder = $this->getPurchaseOrderSnapshot((int) ($row['purchase_order_id'] ?? 0));
                if ($purchaseOrder && (int) ($purchaseOrder['settlement_id'] ?? 0) > 0) {
                    throw new Exception('该采购单已进入采购结算，不能删除对账单');
                }

                $deleted = $item->delete();
                if ($deleted) {
                    $count += $deleted;
                    $this->syncLinkedPurchaseOrderAfterReconciliationDelete($row);
                    $this->recordBusinessAudit(
                        'business_purchase_reconciliation',
                        'delete',
                        '采购对账',
                        $row,
                        '删除采购对账：' . ((($row['reconcile_no'] ?? '') ?: '未编号对账单') . ' / ' . (($row['title'] ?? '') ?: '未命名对账单'))
                    );
                }
            }
            Db::commit();
        } catch (PDOException | Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        if ($count) {
            $this->success('采购对账已删除');
        }

        $this->error(__('No rows were deleted'));
    }

    protected function buildDefaultRow(): array
    {
        $actor = $this->getCurrentActor();
        $purchaseOrderId = (int) $this->request->get('purchase_order_id/d', 0);
        $paymentPlanId = (int) $this->request->get('payment_plan_id/d', 0);
        $default = [
            'title' => '',
            'purchase_order_id' => $purchaseOrderId,
            'payment_plan_id' => $paymentPlanId,
            'order_amount' => '0.00',
            'confirmed_amount' => '0.00',
            'variance_amount' => '0.00',
            'reconciled_at' => date('Y-m-d H:i:s'),
            'status' => 'reconciling',
            'owner_admin_id' => (int) ($actor['admin_id'] ?? 0),
            'notes' => '',
        ];

        if ($purchaseOrderId > 0) {
            $purchaseOrder = $this->getPurchaseOrderSnapshot($purchaseOrderId);
            if ($purchaseOrder) {
                $default['title'] = '采购对账 / ' . (string) $purchaseOrder['title'];
                $default['payment_plan_id'] = (int) (($purchaseOrder['payment_plan_id'] ?? 0) ?: $paymentPlanId);
                $default['order_amount'] = number_format((float) ($purchaseOrder['order_amount'] ?? 0), 2, '.', '');
                $default['confirmed_amount'] = $default['order_amount'];
                $default['owner_admin_id'] = (int) (($purchaseOrder['owner_admin_id'] ?? 0) ?: ($actor['admin_id'] ?? 0));
            }
        }

        return $default;
    }

    protected function prepareReconciliationParams(array $params, bool $isCreate, array $existing = [], int $currentId = 0): array
    {
        $params = $this->preExcludeFields($params);

        if ($isCreate) {
            $this->fillLegacyId($params, 'purchase_reconciliation');
            $params['reconcile_no'] = 'PR-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 6));
        } else {
            $params['legacy_id'] = $existing['legacy_id'] ?? '';
            $params['reconcile_no'] = $existing['reconcile_no'] ?? '';
        }

        $params['purchase_order_id'] = (int) ($params['purchase_order_id'] ?? ($existing['purchase_order_id'] ?? 0));
        $params['payment_plan_id'] = (int) ($params['payment_plan_id'] ?? ($existing['payment_plan_id'] ?? 0));
        if ($params['purchase_order_id'] <= 0) {
            throw new Exception('请选择采购单');
        }

        $this->syncPurchaseOrderRelation($params, $isCreate);
        $this->syncPaymentPlanRelation($params);
        $this->fillStaffName($params, 'owner_admin_id', 'owner');
        $this->fillAuditFields($params, $isCreate);

        $params['title'] = trim((string) ($params['title'] ?? ''));
        if ($params['title'] === '') {
            throw new Exception('请输入对账标题');
        }

        $params['order_amount'] = round((float) ($params['order_amount'] ?? 0), 2);
        $params['confirmed_amount'] = round((float) ($params['confirmed_amount'] ?? ($params['order_amount'] ?? 0)), 2);
        $params['variance_amount'] = round($params['confirmed_amount'] - $params['order_amount'], 2);

        if (array_key_exists('reconciled_at', $params) && $params['reconciled_at'] === '') {
            $params['reconciled_at'] = null;
        }

        if (in_array((string) ($params['status'] ?? ''), ['confirmed', 'closed'], true) && empty($params['reconciled_at'])) {
            $params['reconciled_at'] = date('Y-m-d H:i:s');
        }

        if (!array_key_exists('attachment_ids_json', $params) || $params['attachment_ids_json'] === '') {
            $params['attachment_ids_json'] = $existing['attachment_ids_json'] ?? '[]';
            if ($params['attachment_ids_json'] === '') {
                $params['attachment_ids_json'] = '[]';
            }
        }

        $this->ensureSingleReconciliationPerPurchaseOrder((int) $params['purchase_order_id'], $currentId);

        return $params;
    }

    protected function syncPurchaseOrderRelation(array &$params, bool $isCreate): void
    {
        $purchaseOrder = $this->getPurchaseOrderSnapshot((int) ($params['purchase_order_id'] ?? 0));
        if (!$purchaseOrder) {
            throw new Exception('未找到关联采购单');
        }

        $params['purchase_order_id'] = (int) $purchaseOrder['id'];
        $params['purchase_order_legacy_id'] = (string) ($purchaseOrder['legacy_id'] ?? '');
        $params['purchase_order_title'] = (string) ($purchaseOrder['title'] ?? '');
        $params['supplier_id'] = (int) ($purchaseOrder['supplier_id'] ?? 0);
        $params['supplier_legacy_id'] = (string) ($purchaseOrder['supplier_legacy_id'] ?? '');
        $params['supplier_name'] = (string) ($purchaseOrder['supplier_name'] ?? '');
        $params['customer_id'] = (int) ($purchaseOrder['customer_id'] ?? 0);
        $params['customer_legacy_id'] = (string) ($purchaseOrder['customer_legacy_id'] ?? '');
        $params['customer_name'] = (string) ($purchaseOrder['customer_name'] ?? '');
        $params['contract_id'] = (int) ($purchaseOrder['contract_id'] ?? 0);
        $params['contract_legacy_id'] = (string) ($purchaseOrder['contract_legacy_id'] ?? '');
        $params['contract_name'] = (string) ($purchaseOrder['contract_name'] ?? '');

        if (empty($params['payment_plan_id']) && (int) ($purchaseOrder['payment_plan_id'] ?? 0) > 0) {
            $params['payment_plan_id'] = (int) $purchaseOrder['payment_plan_id'];
            $params['payment_plan_legacy_id'] = (string) ($purchaseOrder['payment_plan_legacy_id'] ?? '');
            $params['payment_plan_title'] = (string) ($purchaseOrder['payment_plan_title'] ?? '');
        }

        if (($params['title'] ?? '') === '') {
            $params['title'] = '采购对账 / ' . (string) ($purchaseOrder['title'] ?? '未命名采购单');
        }
        if ($isCreate && (!isset($params['order_amount']) || $params['order_amount'] === '' || (float) $params['order_amount'] <= 0)) {
            $params['order_amount'] = round((float) ($purchaseOrder['order_amount'] ?? 0), 2);
        }
        if ($isCreate && (!isset($params['confirmed_amount']) || $params['confirmed_amount'] === '')) {
            $params['confirmed_amount'] = round((float) ($purchaseOrder['order_amount'] ?? 0), 2);
        }
        if (empty($params['owner_admin_id']) && !empty($purchaseOrder['owner_admin_id'])) {
            $params['owner_admin_id'] = (int) $purchaseOrder['owner_admin_id'];
        }
    }

    protected function syncPaymentPlanRelation(array &$params): void
    {
        $paymentPlanId = (int) ($params['payment_plan_id'] ?? 0);
        if ($paymentPlanId <= 0) {
            $params['payment_plan_legacy_id'] = '';
            $params['payment_plan_title'] = '';
            return;
        }

        $paymentPlan = $this->getPaymentPlanSnapshot($paymentPlanId);
        if (!$paymentPlan) {
            throw new Exception('未找到关联付款计划');
        }

        if ((int) ($paymentPlan['purchase_order_id'] ?? 0) > 0 && (int) $paymentPlan['purchase_order_id'] !== (int) ($params['purchase_order_id'] ?? 0)) {
            throw new Exception('付款计划与采购单不匹配');
        }

        $params['payment_plan_id'] = (int) $paymentPlan['id'];
        $params['payment_plan_legacy_id'] = (string) ($paymentPlan['legacy_id'] ?? '');
        $params['payment_plan_title'] = (string) ($paymentPlan['title'] ?? '');
    }

    protected function syncLinkedPurchaseOrderAfterReconciliationSave(array $reconciliation): void
    {
        $purchaseOrderId = (int) ($reconciliation['purchase_order_id'] ?? 0);
        if ($purchaseOrderId <= 0) {
            return;
        }

        Db::name('business_purchase_order')->where('id', $purchaseOrderId)->update([
            'reconciliation_id' => (int) ($reconciliation['id'] ?? 0),
            'reconciliation_legacy_id' => (string) ($reconciliation['legacy_id'] ?? ''),
            'reconciliation_title' => (string) ($reconciliation['title'] ?? ''),
            'record_updated_at' => date('Y-m-d H:i:s'),
            'updatetime' => time(),
        ]);
    }

    protected function syncLinkedPurchaseOrderAfterReconciliationDelete(array $reconciliation): void
    {
        $purchaseOrderId = (int) ($reconciliation['purchase_order_id'] ?? 0);
        if ($purchaseOrderId <= 0) {
            return;
        }

        Db::name('business_purchase_order')->where('id', $purchaseOrderId)->update([
            'reconciliation_id' => 0,
            'reconciliation_legacy_id' => '',
            'reconciliation_title' => '',
            'record_updated_at' => date('Y-m-d H:i:s'),
            'updatetime' => time(),
        ]);
    }

    protected function ensureSingleReconciliationPerPurchaseOrder(int $purchaseOrderId, int $currentId = 0): void
    {
        $query = Db::name('business_purchase_reconciliation')->where('purchase_order_id', $purchaseOrderId);
        if ($currentId > 0) {
            $query->where('id', '<>', $currentId);
        }

        $existing = $query->field('id,title')->find();
        if ($existing) {
            throw new Exception('该采购单已经存在采购对账：' . (($existing['title'] ?? '') ?: ('#' . $existing['id'])));
        }
    }

    protected function getPurchaseOrderSnapshot(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = Db::name('business_purchase_order')
            ->field('id,legacy_id,title,order_amount,supplier_id,supplier_legacy_id,supplier_name,customer_id,customer_legacy_id,customer_name,contract_id,contract_legacy_id,contract_name,payment_plan_id,payment_plan_legacy_id,payment_plan_title,settlement_id,owner_admin_id')
            ->where('id', $id)
            ->find();

        return $row ?: null;
    }

    protected function getPaymentPlanSnapshot(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = Db::name('business_payment_plan')
            ->field('id,legacy_id,title,purchase_order_id,purchase_order_title')
            ->where('id', $id)
            ->find();

        return $row ?: null;
    }
}
