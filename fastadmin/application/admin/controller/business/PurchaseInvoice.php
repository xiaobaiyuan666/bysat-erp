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
 * 采购发票
 *
 * @icon fa fa-file-text
 */
class PurchaseInvoice extends Backend
{
    use ErpAuditHelper;
    use ErpCrudHelper;

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Business\PurchaseInvoice();
        $this->view->assign('invoiceTypeList', $this->model->getInvoiceTypeList());
        $this->view->assign('statusList', $this->model->getStatusList());
        $this->view->assign('ownerList', $this->getStaffOptions(false));
        $this->view->assign('purchaseOrderList', $this->getPurchaseOrderOptions(false));
        $this->view->assign('purchaseSettlementList', $this->getPurchaseSettlementOptions(false));
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

        $params = $this->prepareInvoiceParams($params, true);

        Db::startTrans();
        try {
            $result = $this->model->allowField(true)->save($params);
            if ($result !== false) {
                $saved = array_merge($params, ['id' => (int) $this->model->id]);
                $this->syncLinkedSettlementInvoiceSummary((int) ($saved['settlement_id'] ?? 0));
                $this->recordBusinessAudit(
                    'business_purchase_invoice',
                    'add',
                    '采购发票',
                    $saved,
                    '新增采购发票：' . (($params['invoice_no'] ?: '未编号发票') . ' / ' . ($params['title'] ?: '未命名发票'))
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

        $this->success('采购发票已新增');
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
        $params = $this->prepareInvoiceParams($params, false, $original, (int) $row['id']);

        Db::startTrans();
        try {
            $result = $row->allowField(true)->save($params);
            if ($result !== false) {
                $saved = array_merge($original, $params, ['id' => (int) $row['id']]);
                if ((int) ($original['settlement_id'] ?? 0) > 0 && (int) ($original['settlement_id'] ?? 0) !== (int) ($saved['settlement_id'] ?? 0)) {
                    $this->syncLinkedSettlementInvoiceSummary((int) $original['settlement_id']);
                }
                $this->syncLinkedSettlementInvoiceSummary((int) ($saved['settlement_id'] ?? 0));
                $this->recordBusinessAudit(
                    'business_purchase_invoice',
                    'edit',
                    '采购发票',
                    $saved,
                    '更新采购发票：' . ((($params['invoice_no'] ?? $original['invoice_no']) ?: '未编号发票') . ' / ' . (($params['title'] ?? $original['title']) ?: '未命名发票'))
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

        $this->success('采购发票已更新');
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
                $settlementId = (int) ($row['settlement_id'] ?? 0);
                $deleted = $item->delete();
                if ($deleted) {
                    $count += $deleted;
                    $this->syncLinkedSettlementInvoiceSummary($settlementId);
                    $this->recordBusinessAudit(
                        'business_purchase_invoice',
                        'delete',
                        '采购发票',
                        $row,
                        '删除采购发票：' . ((($row['invoice_no'] ?? '') ?: '未编号发票') . ' / ' . (($row['title'] ?? '') ?: '未命名发票'))
                    );
                }
            }
            Db::commit();
        } catch (PDOException | Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        if ($count) {
            $this->success('采购发票已删除');
        }

        $this->error(__('No rows were deleted'));
    }

    protected function buildDefaultRow(): array
    {
        $actor = $this->getCurrentActor();
        $purchaseOrderId = (int) $this->request->get('purchase_order_id/d', 0);
        $settlementId = (int) $this->request->get('settlement_id/d', 0);
        $default = [
            'invoice_no' => '',
            'title' => '',
            'purchase_order_id' => $purchaseOrderId,
            'settlement_id' => $settlementId,
            'invoice_type' => 'electronic',
            'invoice_amount' => '0.00',
            'untaxed_amount' => '0.00',
            'tax_amount' => '0.00',
            'invoiced_at' => date('Y-m-d'),
            'received_at' => date('Y-m-d H:i:s'),
            'status' => 'received',
            'owner_admin_id' => (int) ($actor['admin_id'] ?? 0),
            'notes' => '',
        ];

        if ($settlementId > 0) {
            $settlement = $this->getSettlementSnapshot($settlementId);
            if ($settlement) {
                $default['purchase_order_id'] = (int) ($settlement['purchase_order_id'] ?? $purchaseOrderId);
                $default['title'] = '采购发票 / ' . (string) (($settlement['purchase_order_title'] ?? '') ?: ($settlement['title'] ?? '未命名结算单'));
                $remaining = max(0, round((float) ($settlement['settlement_amount'] ?? 0) - (float) ($settlement['invoiced_amount'] ?? 0), 2));
                $default['invoice_amount'] = number_format($remaining, 2, '.', '');
                $default['untaxed_amount'] = $default['invoice_amount'];
                $default['owner_admin_id'] = (int) (($settlement['owner_admin_id'] ?? 0) ?: ($actor['admin_id'] ?? 0));
            }
        } elseif ($purchaseOrderId > 0) {
            $purchaseOrder = $this->getPurchaseOrderSnapshot($purchaseOrderId);
            if ($purchaseOrder) {
                $default['title'] = '采购发票 / ' . (string) $purchaseOrder['title'];
                $default['settlement_id'] = (int) ($purchaseOrder['settlement_id'] ?? 0);
                $default['owner_admin_id'] = (int) (($purchaseOrder['owner_admin_id'] ?? 0) ?: ($actor['admin_id'] ?? 0));
            }
        }

        return $default;
    }

    protected function prepareInvoiceParams(array $params, bool $isCreate, array $existing = [], int $currentId = 0): array
    {
        $params = $this->preExcludeFields($params);

        if ($isCreate) {
            $this->fillLegacyId($params, 'purchase_invoice');
            $params['invoice_no'] = trim((string) ($params['invoice_no'] ?? ''));
            if ($params['invoice_no'] === '') {
                $params['invoice_no'] = 'PI-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 6));
            }
        } else {
            $params['legacy_id'] = $existing['legacy_id'] ?? '';
            $params['invoice_no'] = trim((string) ($params['invoice_no'] ?? ($existing['invoice_no'] ?? '')));
            if ($params['invoice_no'] === '') {
                throw new Exception('请输入发票号码');
            }
        }

        $params['purchase_order_id'] = (int) ($params['purchase_order_id'] ?? ($existing['purchase_order_id'] ?? 0));
        $params['settlement_id'] = (int) ($params['settlement_id'] ?? ($existing['settlement_id'] ?? 0));
        if ($params['purchase_order_id'] <= 0 && $params['settlement_id'] <= 0) {
            throw new Exception('请至少选择采购单或采购结算');
        }

        if ($params['settlement_id'] > 0) {
            $this->syncSettlementRelation($params, $currentId);
        }
        $this->syncPurchaseOrderRelation($params, $currentId);
        $this->fillStaffName($params, 'owner_admin_id', 'owner');
        $this->fillAuditFields($params, $isCreate);

        $params['title'] = trim((string) ($params['title'] ?? ''));
        if ($params['title'] === '') {
            throw new Exception('请输入发票标题');
        }

        $params['invoice_amount'] = round((float) ($params['invoice_amount'] ?? 0), 2);
        $params['tax_amount'] = round((float) ($params['tax_amount'] ?? 0), 2);
        $params['untaxed_amount'] = round((float) ($params['untaxed_amount'] ?? ($params['invoice_amount'] - $params['tax_amount'])), 2);
        if ($params['invoice_amount'] <= 0) {
            throw new Exception('发票金额必须大于 0');
        }
        if ($params['tax_amount'] < 0 || $params['untaxed_amount'] < 0) {
            throw new Exception('税额和不含税金额不能小于 0');
        }
        if ($params['tax_amount'] - $params['invoice_amount'] > 0.00001) {
            throw new Exception('税额不能大于发票金额');
        }

        if (array_key_exists('invoiced_at', $params) && $params['invoiced_at'] === '') {
            $params['invoiced_at'] = null;
        }
        if (array_key_exists('received_at', $params) && $params['received_at'] === '') {
            $params['received_at'] = null;
        }

        if (in_array((string) ($params['status'] ?? ''), ['received', 'verified'], true)) {
            if (empty($params['invoiced_at'])) {
                $params['invoiced_at'] = date('Y-m-d');
            }
            if (empty($params['received_at'])) {
                $params['received_at'] = date('Y-m-d H:i:s');
            }
        }

        if (!array_key_exists('attachment_ids_json', $params) || $params['attachment_ids_json'] === '') {
            $params['attachment_ids_json'] = $existing['attachment_ids_json'] ?? '[]';
            if ($params['attachment_ids_json'] === '') {
                $params['attachment_ids_json'] = '[]';
            }
        }

        return $params;
    }

    protected function syncPurchaseOrderRelation(array &$params, int $currentId): void
    {
        if ((int) ($params['purchase_order_id'] ?? 0) <= 0 && (int) ($params['settlement_id'] ?? 0) > 0) {
            $settlement = $this->getSettlementSnapshot((int) $params['settlement_id']);
            if ($settlement) {
                $params['purchase_order_id'] = (int) ($settlement['purchase_order_id'] ?? 0);
            }
        }

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

        if ((int) ($params['settlement_id'] ?? 0) <= 0 && (int) ($purchaseOrder['settlement_id'] ?? 0) > 0) {
            $params['settlement_id'] = (int) $purchaseOrder['settlement_id'];
            $this->syncSettlementRelation($params, $currentId);
        } elseif (($params['title'] ?? '') === '') {
            $params['title'] = '采购发票 / ' . (string) ($purchaseOrder['title'] ?? '未命名采购单');
        }

        if (empty($params['owner_admin_id']) && !empty($purchaseOrder['owner_admin_id'])) {
            $params['owner_admin_id'] = (int) $purchaseOrder['owner_admin_id'];
        }
    }

    protected function syncSettlementRelation(array &$params, int $currentId): void
    {
        $settlement = $this->getSettlementSnapshot((int) ($params['settlement_id'] ?? 0));
        if (!$settlement) {
            throw new Exception('未找到关联采购结算');
        }

        if ((int) ($params['purchase_order_id'] ?? 0) > 0 && (int) ($settlement['purchase_order_id'] ?? 0) > 0 && (int) $params['purchase_order_id'] !== (int) $settlement['purchase_order_id']) {
            throw new Exception('采购结算与采购单不匹配');
        }

        $params['settlement_id'] = (int) $settlement['id'];
        $params['settlement_legacy_id'] = (string) ($settlement['legacy_id'] ?? '');
        $params['settlement_title'] = (string) ($settlement['title'] ?? '');
        if ((int) ($params['purchase_order_id'] ?? 0) <= 0) {
            $params['purchase_order_id'] = (int) ($settlement['purchase_order_id'] ?? 0);
        }

        if (($params['title'] ?? '') === '') {
            $params['title'] = '采购发票 / ' . (string) (($settlement['purchase_order_title'] ?? '') ?: ($settlement['title'] ?? '未命名结算单'));
        }

        if ((!isset($params['invoice_amount']) || $params['invoice_amount'] === '' || (float) $params['invoice_amount'] <= 0)) {
            $receivedAmount = $this->getSettlementInvoiceSum((int) $settlement['id'], $currentId);
            $remaining = max(0, round((float) ($settlement['settlement_amount'] ?? 0) - $receivedAmount, 2));
            if ($remaining > 0) {
                $params['invoice_amount'] = $remaining;
            }
        }

        if (empty($params['owner_admin_id']) && !empty($settlement['owner_admin_id'])) {
            $params['owner_admin_id'] = (int) $settlement['owner_admin_id'];
        }
    }

    protected function syncLinkedSettlementInvoiceSummary(int $settlementId): void
    {
        if ($settlementId <= 0) {
            return;
        }

        $settlement = $this->getSettlementSnapshot($settlementId);
        if (!$settlement) {
            return;
        }

        $invoiceRows = Db::name('business_purchase_invoice')
            ->field('invoice_no,invoice_amount,invoiced_at,status')
            ->where('settlement_id', $settlementId)
            ->where('status', 'in', ['received', 'verified'])
            ->order('id', 'asc')
            ->select();

        $invoiceTotal = 0.0;
        $invoiceCount = 0;
        $invoiceNo = '';
        $latestInvoiceDate = null;
        foreach ($invoiceRows as $row) {
            $invoiceCount++;
            $invoiceTotal += round((float) ($row['invoice_amount'] ?? 0), 2);
            if ($invoiceCount === 1) {
                $invoiceNo = (string) ($row['invoice_no'] ?? '');
            }
            if (!empty($row['invoiced_at']) && ($latestInvoiceDate === null || $row['invoiced_at'] > $latestInvoiceDate)) {
                $latestInvoiceDate = (string) $row['invoiced_at'];
            }
        }

        $invoiceTotal = round($invoiceTotal, 2);
        $settlementAmount = round((float) ($settlement['settlement_amount'] ?? 0), 2);
        $invoiceStatus = 'none';
        if ($invoiceTotal > 0 && $invoiceTotal + 0.00001 < $settlementAmount) {
            $invoiceStatus = 'partial';
        } elseif ($invoiceTotal > 0 && ($settlementAmount <= 0 || $invoiceTotal + 0.00001 >= $settlementAmount)) {
            $invoiceStatus = 'received';
        }

        if ($invoiceCount > 1) {
            $invoiceNo = '共' . $invoiceCount . '张发票';
        }

        $settlementStatus = (string) ($settlement['status'] ?? 'draft');
        $settledAt = $settlement['settled_at'] ?? null;
        if ($settlementStatus === 'settled' && $invoiceStatus !== 'received') {
            $settlementStatus = 'confirmed';
            $settledAt = null;
        }

        Db::name('business_purchase_settlement')->where('id', $settlementId)->update([
            'invoiced_amount' => $invoiceTotal,
            'invoice_status' => $invoiceStatus,
            'invoice_no' => $invoiceNo,
            'invoiced_at' => $latestInvoiceDate,
            'status' => $settlementStatus,
            'settled_at' => $settledAt,
            'record_updated_at' => date('Y-m-d H:i:s'),
            'updatetime' => time(),
        ]);

        $purchaseOrderId = (int) ($settlement['purchase_order_id'] ?? 0);
        if ($purchaseOrderId <= 0) {
            return;
        }

        $purchaseOrder = Db::name('business_purchase_order')
            ->field('id,payment_plan_id,approval_status')
            ->where('id', $purchaseOrderId)
            ->find();

        if (!$purchaseOrder) {
            return;
        }

        $purchaseStatus = $settlementStatus === 'settled'
            ? 'completed'
            : ((int) ($purchaseOrder['payment_plan_id'] ?? 0) > 0 ? 'processing' : $this->mapApprovalStatusToPurchaseOrderStatus((string) ($purchaseOrder['approval_status'] ?? 'none')));

        Db::name('business_purchase_order')->where('id', $purchaseOrderId)->update([
            'status' => $purchaseStatus,
            'record_updated_at' => date('Y-m-d H:i:s'),
            'updatetime' => time(),
        ]);
    }

    protected function getSettlementInvoiceSum(int $settlementId, int $excludeId = 0): float
    {
        if ($settlementId <= 0) {
            return 0.0;
        }

        $query = Db::name('business_purchase_invoice')
            ->where('settlement_id', $settlementId)
            ->where('status', 'in', ['received', 'verified']);
        if ($excludeId > 0) {
            $query->where('id', '<>', $excludeId);
        }

        return round((float) $query->sum('invoice_amount'), 2);
    }

    protected function getPurchaseOrderSnapshot(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = Db::name('business_purchase_order')
            ->field('id,legacy_id,title,supplier_id,supplier_legacy_id,supplier_name,customer_id,customer_legacy_id,customer_name,contract_id,contract_legacy_id,contract_name,settlement_id,owner_admin_id')
            ->where('id', $id)
            ->find();

        return $row ?: null;
    }

    protected function getSettlementSnapshot(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = Db::name('business_purchase_settlement')
            ->field('id,legacy_id,title,purchase_order_id,purchase_order_title,settlement_amount,invoiced_amount,status,owner_admin_id,settled_at')
            ->where('id', $id)
            ->find();

        return $row ?: null;
    }

    protected function mapApprovalStatusToPurchaseOrderStatus(string $approvalStatus): string
    {
        if ($approvalStatus === 'approved') {
            return 'approved';
        }
        if ($approvalStatus === 'pending') {
            return 'pending_approval';
        }
        if ($approvalStatus === 'rejected') {
            return 'rejected';
        }

        return 'draft';
    }
}
