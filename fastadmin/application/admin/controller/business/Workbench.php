<?php

namespace app\admin\controller\business;

use app\common\controller\Backend;
use think\Db;

/**
 * 客户与采购工作台
 *
 * @icon fa fa-dashboard
 */
class Workbench extends Backend
{
    public function index()
    {
        $profile = $this->getCurrentUserProfile();

        $this->view->assign([
            'currentUser' => $this->buildCurrentUser($profile),
            'summaryCards' => $this->buildSummaryCards($profile),
            'quickActions' => $this->buildQuickActions(),
            'myCustomers' => $this->buildMyCustomers($profile),
            'myPurchaseOrders' => $this->buildMyPurchaseOrders($profile),
            'myApprovals' => $this->buildMyApprovals($profile),
            'myPaymentRequests' => $this->buildMyPaymentRequests($profile),
        ]);

        return $this->view->fetch();
    }

    protected function getCurrentUserProfile(): array
    {
        $adminId = (int)($this->auth->id ?? 0);
        $info = $this->auth->getUserInfo($adminId);
        $profile = Db::name('staff_profile')
            ->field('admin_id,account,name,title,department,role_key')
            ->where('admin_id', $adminId)
            ->find();

        return [
            'admin_id' => $adminId,
            'name' => $profile['name'] ?? ($info['nickname'] ?? ($info['username'] ?? '系统用户')),
            'title' => $profile['title'] ?? '运营岗位',
            'department' => $profile['department'] ?? '运营部',
            'role_key' => $profile['role_key'] ?? 'operations',
        ];
    }

    protected function buildCurrentUser(array $profile): array
    {
        return [
            'name' => $profile['name'],
            'title' => $profile['title'],
            'department' => $profile['department'],
            'guide' => $this->buildRoleGuide((string)$profile['role_key']),
        ];
    }

    protected function buildRoleGuide(string $roleKey): string
    {
        $map = [
            'operations' => '先跟进我的客户，再推进采购和付款申请。',
            'finance' => '先处理采购审批和付款申请，再看客户合同台账。',
            'admin' => '先看待审批和采购链，再处理客户跟进。',
            'viewer' => '先看我的客户和采购单，再进入完整台账。',
        ];

        return $map[$roleKey] ?? '先处理我的客户和采购，再进入台账。';
    }

    protected function buildSummaryCards(array $profile): array
    {
        $adminId = (int)($profile['admin_id'] ?? 0);
        $roleKey = (string)($profile['role_key'] ?? 'operations');

        return [
            [
                'title' => '我的客户',
                'value' => $this->safeCount('business_customer', function ($query) use ($adminId, $roleKey) {
                    $query->where('status', 'active');
                    if (!in_array($roleKey, ['admin', 'finance'], true)) {
                        $query->where('owner_admin_id', $adminId);
                    }
                }),
                'hint' => '今天先看自己负责的客户和最近跟进节奏。',
                'url' => (string)url('business/customer/index'),
            ],
            [
                'title' => '我的采购单',
                'value' => $this->safeCount('business_purchase_order', function ($query) use ($adminId, $roleKey) {
                    $query->where('status', 'in', ['draft', 'pending_approval', 'approved', 'processing']);
                    if (!in_array($roleKey, ['admin', 'finance'], true)) {
                        $query->where('owner_admin_id', $adminId);
                    }
                }),
                'hint' => '先处理还在流程中的采购单，不要让采购链断开。',
                'url' => (string)url('business/purchase_order/index'),
            ],
            [
                'title' => '待我审批',
                'value' => $this->safeCount('business_approval', function ($query) use ($adminId) {
                    $query->where('status', 'pending')->where('approver_admin_id', $adminId);
                }),
                'hint' => '先把分配给我的采购或付款审批清掉。',
                'url' => (string)url('business/approval/index'),
            ],
            [
                'title' => '我的付款申请',
                'value' => $this->safeCount('business_payment_request', function ($query) use ($adminId, $roleKey) {
                    $query->where('status', 'in', ['draft', 'pending_approval', 'approved']);
                    if (!in_array($roleKey, ['admin', 'finance'], true)) {
                        $query->where('owner_admin_id', $adminId);
                    }
                }),
                'hint' => '付款申请、审批和付款执行统一从这里推进。',
                'url' => (string)url('business/payment_request/index'),
            ],
        ];
    }

    protected function buildQuickActions(): array
    {
        return [
            ['title' => '新增客户', 'icon' => 'fa fa-address-card-o', 'url' => (string)url('business/customer/add')],
            ['title' => '新增合同', 'icon' => 'fa fa-file-text-o', 'url' => (string)url('business/contract/add')],
            ['title' => '新增采购单', 'icon' => 'fa fa-plus-circle', 'url' => (string)url('business/purchase_order/add')],
            ['title' => '付款申请', 'icon' => 'fa fa-credit-card-alt', 'url' => (string)url('business/payment_request/index')],
            ['title' => '审批中心', 'icon' => 'fa fa-check-square-o', 'url' => (string)url('business/approval/index')],
            ['title' => 'AI 客户分析', 'icon' => 'fa fa-comments-o', 'url' => (string)url('ai/conversation/index', ['focus' => 'business'])],
        ];
    }

    protected function buildMyCustomers(array $profile): array
    {
        if (!$this->tableExists('business_customer')) {
            return [];
        }

        $adminId = (int)($profile['admin_id'] ?? 0);
        $roleKey = (string)($profile['role_key'] ?? 'operations');
        $query = Db::name('business_customer')
            ->field('id,company_name,industry,owner,last_follow_up_at,stage,status')
            ->where('status', 'active');

        if (!in_array($roleKey, ['admin', 'finance'], true)) {
            $query->where('owner_admin_id', $adminId);
        }

        $rows = $query
            ->orderRaw('ISNULL(last_follow_up_at) DESC, last_follow_up_at ASC')
            ->order('id', 'desc')
            ->limit(8)
            ->select();

        return $this->formatCustomerRows($rows);
    }

    protected function buildMyPurchaseOrders(array $profile): array
    {
        if (!$this->tableExists('business_purchase_order')) {
            return [];
        }

        $adminId = (int)($profile['admin_id'] ?? 0);
        $roleKey = (string)($profile['role_key'] ?? 'operations');
        $query = Db::name('business_purchase_order')
            ->field('id,title,supplier_name,order_amount,status,approval_status,owner,ordered_at')
            ->where('status', 'in', ['draft', 'pending_approval', 'approved', 'processing']);

        if (!in_array($roleKey, ['admin', 'finance'], true)) {
            $query->where('owner_admin_id', $adminId);
        }

        $rows = $query->order('ordered_at', 'desc')->limit(8)->select();

        return $this->formatPurchaseRows($rows);
    }

    protected function buildMyApprovals(array $profile): array
    {
        if (!$this->tableExists('business_approval')) {
            return [];
        }

        $adminId = (int)($profile['admin_id'] ?? 0);
        $roleKey = (string)($profile['role_key'] ?? 'operations');
        $query = Db::name('business_approval')
            ->field('id,approval_no,object_type,object_title,current_step_name,applicant_name,applied_at')
            ->where('status', 'pending');

        if ($adminId > 0) {
            $query->where('approver_admin_id', $adminId);
        }

        $rows = $query->order('applied_at', 'asc')->limit(8)->select();

        if (!$rows && in_array($roleKey, ['admin', 'viewer'], true)) {
            $rows = Db::name('business_approval')
                ->field('id,approval_no,object_type,object_title,current_step_name,applicant_name,applied_at')
                ->where('status', 'pending')
                ->order('applied_at', 'asc')
                ->limit(8)
                ->select();
        }

        return $this->formatApprovalRows($rows);
    }

    protected function buildMyPaymentRequests(array $profile): array
    {
        if (!$this->tableExists('business_payment_request')) {
            return [];
        }

        $adminId = (int)($profile['admin_id'] ?? 0);
        $roleKey = (string)($profile['role_key'] ?? 'operations');
        $query = Db::name('business_payment_request')
            ->field('id,request_no,title,settlement_title,request_amount,owner,status,approval_status,requested_at')
            ->where('status', 'in', ['draft', 'pending_approval', 'approved']);

        if (!in_array($roleKey, ['admin', 'finance'], true)) {
            $query->where('owner_admin_id', $adminId);
        }

        $rows = $query->order('requested_at', 'desc')->limit(8)->select();

        return $this->formatPaymentRequestRows($rows);
    }

    protected function formatCustomerRows($rows): array
    {
        $stageMap = [
            'lead' => '线索期',
            'proposal' => '方案期',
            'contracted' => '已签约',
            'delivery' => '交付中',
            'repeat' => '复购中',
            'lost' => '已流失',
        ];

        $items = [];
        foreach ($rows as $row) {
            $row['stage_text'] = $stageMap[$row['stage']] ?? $row['stage'];
            $items[] = $row;
        }

        return $items;
    }

    protected function formatPurchaseRows($rows): array
    {
        $statusMap = [
            'draft' => '草稿',
            'pending_approval' => '待审批',
            'approved' => '已批准',
            'processing' => '处理中',
            'completed' => '已完成',
            'rejected' => '已驳回',
            'cancelled' => '已取消',
        ];

        $items = [];
        foreach ($rows as $row) {
            $row['status_text'] = $statusMap[$row['status']] ?? $row['status'];
            $row['amount_text'] = '￥' . number_format((float)$row['order_amount'], 2);
            $items[] = $row;
        }

        return $items;
    }

    protected function formatApprovalRows($rows): array
    {
        $typeMap = [
            'contract' => '合同审批',
            'payment_plan' => '付款审批',
            'expense_request' => '费用审批',
            'purchase_order' => '采购审批',
            'payment_request' => '付款申请审批',
        ];

        $items = [];
        foreach ($rows as $row) {
            $row['type_text'] = $typeMap[$row['object_type']] ?? $row['object_type'];
            $items[] = $row;
        }

        return $items;
    }

    protected function formatPaymentRequestRows($rows): array
    {
        $statusMap = [
            'draft' => '草稿',
            'pending_approval' => '待审批',
            'approved' => '已批准',
            'paid' => '已付款',
            'rejected' => '已驳回',
            'cancelled' => '已取消',
        ];

        $items = [];
        foreach ($rows as $row) {
            $row['status_text'] = $statusMap[$row['status']] ?? $row['status'];
            $row['amount_text'] = '￥' . number_format((float)$row['request_amount'], 2);
            $items[] = $row;
        }

        return $items;
    }

    protected function safeCount(string $table, ?callable $callback = null): int
    {
        if (!$this->tableExists($table)) {
            return 0;
        }

        $query = Db::name($table);
        if ($callback) {
            $callback($query);
        }

        return (int)$query->count();
    }

    protected function tableExists(string $table): bool
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
