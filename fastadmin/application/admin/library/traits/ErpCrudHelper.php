<?php

namespace app\admin\library\traits;

use think\Db;

trait ErpCrudHelper
{
    protected function getStaffOptions($includeEmpty = true)
    {
        $options = $includeEmpty ? [0 => '未选择'] : [];
        $rows = Db::name('staff_profile')
            ->field('admin_id,name,department,title,status')
            ->where('admin_id', '>', 0)
            ->order('status', 'desc')
            ->order('department', 'asc')
            ->order('name', 'asc')
            ->select();

        foreach ($rows as $row) {
            $label = $row['name'];
            if (!empty($row['department'])) {
                $label .= ' / ' . $row['department'];
            }
            if (!empty($row['title'])) {
                $label .= ' / ' . $row['title'];
            }
            if (($row['status'] ?? '') !== 'active') {
                $label .= '（停用）';
            }
            $options[(int) $row['admin_id']] = $label;
        }

        return $options;
    }

    protected function getProjectOptions($includeEmpty = true)
    {
        $options = $includeEmpty ? [0 => '未关联'] : [];
        $rows = Db::name('project')
            ->field('id,name,client,status')
            ->order('status', 'asc')
            ->order('name', 'asc')
            ->select();

        foreach ($rows as $row) {
            $label = $row['name'];
            if (!empty($row['client'])) {
                $label .= ' / ' . $row['client'];
            }
            $options[(int) $row['id']] = $label;
        }

        return $options;
    }

    protected function getAppProjectOptions($includeEmpty = true)
    {
        $options = $includeEmpty ? [0 => '未关联'] : [];
        $rows = Db::name('app_project')
            ->field('id,app_name,name,app_version,status')
            ->order('status', 'asc')
            ->order('app_name', 'asc')
            ->select();

        foreach ($rows as $row) {
            $label = $row['app_name'];
            if (!empty($row['app_version'])) {
                $label .= ' / ' . $row['app_version'];
            }
            if (!empty($row['name'])) {
                $label .= ' / ' . $row['name'];
            }
            $options[(int) $row['id']] = $label;
        }

        return $options;
    }

    protected function getCustomerOptions($includeEmpty = true)
    {
        $options = $includeEmpty ? [0 => '未关联客户'] : [];

        if (!$this->tableExistsByName('business_customer')) {
            return $options;
        }

        $rows = Db::name('business_customer')
            ->field('id,company_name,short_name,owner,status')
            ->order('status', 'asc')
            ->order('company_name', 'asc')
            ->select();

        foreach ($rows as $row) {
            $label = $row['company_name'];
            if (!empty($row['short_name'])) {
                $label .= ' / ' . $row['short_name'];
            }
            if (!empty($row['owner'])) {
                $label .= ' / ' . $row['owner'];
            }
            $options[(int) $row['id']] = $label;
        }

        return $options;
    }

    protected function getSupplierOptions($includeEmpty = true)
    {
        $options = $includeEmpty ? [0 => '未关联供应商'] : [];

        if (!$this->tableExistsByName('business_supplier')) {
            return $options;
        }

        $rows = Db::name('business_supplier')
            ->field('id,supplier_name,short_name,contact_name,status')
            ->order('status', 'asc')
            ->order('supplier_name', 'asc')
            ->select();

        foreach ($rows as $row) {
            $label = $row['supplier_name'];
            if (!empty($row['short_name'])) {
                $label .= ' / ' . $row['short_name'];
            }
            if (!empty($row['contact_name'])) {
                $label .= ' / ' . $row['contact_name'];
            }
            $options[(int) $row['id']] = $label;
        }

        return $options;
    }

    protected function getContractOptions($includeEmpty = true)
    {
        $options = $includeEmpty ? [0 => '未关联合同'] : [];

        if (!$this->tableExistsByName('business_contract')) {
            return $options;
        }

        $rows = Db::name('business_contract')
            ->field('id,contract_no,name,customer_name,status')
            ->order('status', 'asc')
            ->order('id', 'desc')
            ->select();

        foreach ($rows as $row) {
            $label = ($row['contract_no'] ?: '未编号合同') . ' / ' . $row['name'];
            if (!empty($row['customer_name'])) {
                $label .= ' / ' . $row['customer_name'];
            }
            $options[(int) $row['id']] = $label;
        }

        return $options;
    }

    protected function getPaymentPlanOptions($includeEmpty = true)
    {
        $options = $includeEmpty ? [0 => '未关联付款计划'] : [];

        if (!$this->tableExistsByName('business_payment_plan')) {
            return $options;
        }

        $rows = Db::name('business_payment_plan')
            ->field('id,title,payee_name,contract_name,status')
            ->order('status', 'asc')
            ->order('id', 'desc')
            ->select();

        foreach ($rows as $row) {
            $label = $row['title'];
            if (!empty($row['payee_name'])) {
                $label .= ' / ' . $row['payee_name'];
            }
            if (!empty($row['contract_name'])) {
                $label .= ' / ' . $row['contract_name'];
            }
            $options[(int) $row['id']] = $label;
        }

        return $options;
    }

    protected function getExpenseRequestOptions($includeEmpty = true)
    {
        $options = $includeEmpty ? [0 => '未关联费用申请'] : [];

        if (!$this->tableExistsByName('business_expense_request')) {
            return $options;
        }

        $rows = Db::name('business_expense_request')
            ->field('id,request_no,title,supplier_name,status')
            ->order('status', 'asc')
            ->order('id', 'desc')
            ->select();

        foreach ($rows as $row) {
            $label = ($row['request_no'] ?: '未编号申请') . ' / ' . $row['title'];
            if (!empty($row['supplier_name'])) {
                $label .= ' / ' . $row['supplier_name'];
            }
            $options[(int) $row['id']] = $label;
        }

        return $options;
    }

    protected function getPurchaseOrderOptions($includeEmpty = true)
    {
        $options = $includeEmpty ? [0 => '未关联采购单'] : [];

        if (!$this->tableExistsByName('business_purchase_order')) {
            return $options;
        }

        $rows = Db::name('business_purchase_order')
            ->field('id,order_no,title,supplier_name,status')
            ->order('status', 'asc')
            ->order('id', 'desc')
            ->select();

        foreach ($rows as $row) {
            $label = ($row['order_no'] ?: '未编号采购单') . ' / ' . $row['title'];
            if (!empty($row['supplier_name'])) {
                $label .= ' / ' . $row['supplier_name'];
            }
            $options[(int) $row['id']] = $label;
        }

        return $options;
    }

    protected function getPurchaseSettlementOptions($includeEmpty = true)
    {
        $options = $includeEmpty ? [0 => '未关联采购结算'] : [];

        if (!$this->tableExistsByName('business_purchase_settlement')) {
            return $options;
        }

        $rows = Db::name('business_purchase_settlement')
            ->field('id,settlement_no,title,purchase_order_title,status')
            ->order('status', 'asc')
            ->order('id', 'desc')
            ->select();

        foreach ($rows as $row) {
            $label = ($row['settlement_no'] ?: '未编号结算单') . ' / ' . $row['title'];
            if (!empty($row['purchase_order_title'])) {
                $label .= ' / ' . $row['purchase_order_title'];
            }
            $options[(int) $row['id']] = $label;
        }

        return $options;
    }

    protected function getPaymentRequestOptions($includeEmpty = true)
    {
        $options = $includeEmpty ? [0 => '未关联付款申请'] : [];

        if (!$this->tableExistsByName('business_payment_request')) {
            return $options;
        }

        $rows = Db::name('business_payment_request')
            ->field('id,request_no,title,settlement_title,status')
            ->order('status', 'asc')
            ->order('id', 'desc')
            ->select();

        foreach ($rows as $row) {
            $label = ($row['request_no'] ?: '未编号付款申请') . ' / ' . $row['title'];
            if (!empty($row['settlement_title'])) {
                $label .= ' / ' . $row['settlement_title'];
            }
            $options[(int) $row['id']] = $label;
        }

        return $options;
    }

    protected function getApprovalTemplateOptions($objectType = '', $includeEmpty = true)
    {
        $options = $includeEmpty ? [0 => '未选择审批模板'] : [];

        if (!$this->tableExistsByName('business_approval_template')) {
            return $options;
        }

        $query = Db::name('business_approval_template')
            ->field('id,name,object_type,step_count,is_default,status')
            ->order('is_default', 'desc')
            ->order('object_type', 'asc')
            ->order('id', 'asc');

        if ($objectType !== '') {
            $query->where('object_type', $objectType);
        }

        $rows = $query->select();
        foreach ($rows as $row) {
            $label = $row['name'];
            if (!empty($row['step_count'])) {
                $label .= ' / ' . (int) $row['step_count'] . ' 级';
            }
            if (!empty($row['is_default'])) {
                $label .= ' / 默认';
            }
            if (($row['status'] ?? '') !== 'active') {
                $label .= ' / 已停用';
            }
            $options[(int) $row['id']] = $label;
        }

        return $options;
    }

    protected function getTechTicketOptions($includeEmpty = true)
    {
        $options = $includeEmpty ? [0 => '未关联'] : [];
        $rows = Db::name('app_tech_ticket')
            ->field('id,title,type,status')
            ->order('status', 'asc')
            ->order('id', 'desc')
            ->select();

        foreach ($rows as $row) {
            $label = '#' . $row['id'] . ' / ' . $row['title'];
            if (!empty($row['type'])) {
                $label .= ' / ' . strtoupper($row['type']);
            }
            $options[(int) $row['id']] = $label;
        }

        return $options;
    }

    protected function getCurrentActor()
    {
        $adminId = (int) $this->auth->id;
        $info = $this->auth->getUserInfo($adminId);
        $profile = Db::name('staff_profile')
            ->field('legacy_id,name')
            ->where('admin_id', $adminId)
            ->find();

        return [
            'admin_id' => $adminId,
            'legacy_id' => $profile['legacy_id'] ?? '',
            'name' => $profile['name'] ?? ($info['nickname'] ?? ($info['username'] ?? '系统用户')),
        ];
    }

    protected function generateLegacyId($prefix)
    {
        return strtolower($prefix) . '_' . date('YmdHis') . '_' . substr(md5(uniqid('', true)), 0, 8);
    }

    protected function fillLegacyId(array &$params, $prefix)
    {
        if (!array_key_exists('legacy_id', $params) || !$params['legacy_id']) {
            $params['legacy_id'] = $this->generateLegacyId($prefix);
        }
    }

    protected function fillStaffName(array &$params, $idField, $nameField)
    {
        $adminId = isset($params[$idField]) ? (int) $params[$idField] : 0;
        if ($adminId <= 0) {
            $params[$nameField] = '';
            return;
        }

        $staff = Db::name('staff_profile')
            ->field('legacy_id,name')
            ->where('admin_id', $adminId)
            ->find();

        if (!$staff) {
            return;
        }

        $params[$nameField] = $staff['name'];
    }

    protected function fillStaffLegacy(array &$params, $idField, $legacyField)
    {
        $adminId = isset($params[$idField]) ? (int) $params[$idField] : 0;
        if ($adminId <= 0) {
            $params[$legacyField] = '';
            return;
        }

        $staff = Db::name('staff_profile')
            ->field('legacy_id')
            ->where('admin_id', $adminId)
            ->find();

        if (!$staff) {
            return;
        }

        $params[$legacyField] = $staff['legacy_id'] ?? '';
    }

    protected function fillRelationLegacy(array &$params, $table, $idField, $legacyField, $nameField = null, $targetField = null)
    {
        $relatedId = isset($params[$idField]) ? (int) $params[$idField] : 0;
        if ($relatedId <= 0) {
            $params[$legacyField] = '';
            if ($targetField) {
                $params[$targetField] = '';
            }
            return;
        }

        $fields = ['legacy_id'];
        if ($nameField) {
            $fields[] = $nameField;
        }
        $row = Db::name($table)
            ->field(implode(',', $fields))
            ->where('id', $relatedId)
            ->find();

        if (!$row) {
            return;
        }

        $params[$legacyField] = $row['legacy_id'] ?? '';
        if ($nameField && $targetField) {
            $params[$targetField] = $row[$nameField] ?? '';
        }
    }

    protected function fillAuditFields(array &$params, $isCreate = false)
    {
        $actor = $this->getCurrentActor();

        if ($isCreate) {
            $params['created_by_admin_id'] = $actor['admin_id'];
            $params['created_by_name'] = $actor['name'];
            $params['created_by_legacy_id'] = $actor['legacy_id'];
            if (!array_key_exists('record_created_at', $params) || !$params['record_created_at']) {
                $params['record_created_at'] = date('Y-m-d H:i:s');
            }
        }

        $params['updated_by_admin_id'] = $actor['admin_id'];
        $params['updated_by_name'] = $actor['name'];
        $params['updated_by_legacy_id'] = $actor['legacy_id'];
        $params['record_updated_at'] = date('Y-m-d H:i:s');
    }

    protected function tableExistsByName(string $table): bool
    {
        static $cache = [];

        if (array_key_exists($table, $cache)) {
            return $cache[$table];
        }

        $fullTable = config('database.prefix') . $table;
        $cache[$table] = !empty(Db::query("SHOW TABLES LIKE '{$fullTable}'"));

        return $cache[$table];
    }
}
