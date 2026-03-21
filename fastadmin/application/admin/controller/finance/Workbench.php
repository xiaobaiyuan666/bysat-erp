<?php

namespace app\admin\controller\finance;

use app\admin\library\FinanceSmartBookkeepingService;
use app\admin\library\traits\ErpAuditHelper;
use app\admin\library\traits\ErpCrudHelper;
use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;

/**
 * 财务工作台
 *
 * @icon fa fa-rmb
 */
class Workbench extends Backend
{
    use ErpAuditHelper;
    use ErpCrudHelper;

    protected $noNeedRight = ['smartbookbootstrap', 'smartbook', 'smartbooksave'];

    /**
     * @var FinanceSmartBookkeepingService
     */
    protected $smartBookkeepingService = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->smartBookkeepingService = new FinanceSmartBookkeepingService();
    }

    public function index()
    {
        $this->assignconfig('smartBookkeepingBootstrapUrl', url('finance/workbench/smartbookbootstrap'));
        $this->assignconfig('smartBookkeepingParseUrl', url('finance/workbench/smartbook'));
        $this->assignconfig('smartBookkeepingSaveUrl', url('finance/workbench/smartbooksave'));

        $profile = $this->getCurrentUserProfile();

        $this->view->assign([
            'currentUser' => $this->buildCurrentUser($profile),
            'summaryCards' => $this->buildSummaryCards($profile),
            'quickActions' => $this->buildQuickActions(),
            'quickPanels' => $this->buildQuickPanels($profile),
            'usageGuide' => $this->buildUsageGuide(),
            'approvalTodos' => $this->buildApprovalTodos($profile),
            'invoiceTodos' => $this->buildInvoiceTodos(),
            'attachmentTodos' => $this->buildAttachmentTodos(),
            'recentTransactions' => $this->buildRecentTransactions(),
        ]);

        return $this->view->fetch();
    }

    public function smartbookbootstrap()
    {
        $this->guardWorkbenchPermission();
        $this->success('获取成功', null, $this->smartBookkeepingService->getBootstrapData());
    }

    public function smartbook()
    {
        $this->guardWorkbenchPermission();

        if (!$this->request->isPost()) {
            $this->error('请求方式错误');
        }

        $text = (string)$this->request->post('text', '', 'trim');
        $projectId = (int)$this->request->post('project_id/d', 0);
        $result = $this->smartBookkeepingService->parse($text, $projectId);

        if (!$result['ok']) {
            $this->error($result['error'] ?? '智能记账解析失败', null, $result);
        }

        $this->success('已生成记账草稿', null, $result);
    }

    public function smartbooksave()
    {
        $this->guardWorkbenchPermission();

        if (!$this->request->isPost()) {
            $this->error('请求方式错误');
        }

        $payloadJson = (string)$this->request->post('payload_json', '', 'trim');
        $payload = json_decode($payloadJson, true);
        if (!is_array($payload)) {
            $this->error('记账草稿格式不正确，请重新解析后再保存。');
        }

        $normalized = $this->smartBookkeepingService->normalizeDraftPayload($payload);
        if (!$normalized['ok']) {
            $this->error($normalized['error'] ?? '记账草稿校验失败');
        }

        Db::startTrans();
        try {
            $saved = $normalized['record_type'] === 'invoice'
                ? $this->saveInvoiceDraft($normalized)
                : $this->saveTransactionDraft($normalized);
            Db::commit();
        } catch (PDOException | Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
            return;
        }

        $this->success('已写入系统', null, $saved);
    }

    protected function saveTransactionDraft(array $payload): array
    {
        $draft = $payload['draft'];
        $params = [
            'transaction_date' => $draft['transaction_date'],
            'type' => $draft['type'],
            'category' => $draft['category'],
            'counterparty' => $draft['counterparty'],
            'amount' => $draft['amount'],
            'payment_method' => $draft['payment_method'],
            'project_id' => (int)$draft['project_id'],
            'notes' => $this->buildSmartNote($draft['notes'], $payload),
            'attachment_ids_json' => '',
        ];

        $this->fillLegacyId($params, 'finance_tx');
        $this->fillRelationLegacy($params, 'project', 'project_id', 'project_legacy_id', 'name', 'project_name');
        $this->fillAuditFields($params, true);

        $model = new \app\admin\model\Finance\Transaction();
        $result = $model->allowField(true)->save($params);
        if ($result === false) {
            throw new Exception('财务流水写入失败');
        }

        $params['id'] = (int)$model->id;
        $this->recordBusinessAudit(
            'finance_transaction',
            'add',
            '财务流水',
            $params,
            '智能记账新增流水：' . $draft['counterparty'] . ' / ' . number_format((float)$draft['amount'], 2)
        );

        return [
            'record_type' => 'transaction',
            'id' => (int)$model->id,
            'summary' => $payload['summary'],
            'source_label' => $payload['source_label'],
            'needs_review' => $payload['needs_review'],
            'edit_url' => (string)url('finance/transaction/edit', ['ids' => $model->id]),
            'index_url' => (string)url('finance/transaction/index'),
        ];
    }

    protected function saveInvoiceDraft(array $payload): array
    {
        $draft = $payload['draft'];
        $params = [
            'kind' => $draft['kind'],
            'title' => $draft['title'],
            'counterparty' => $draft['counterparty'],
            'amount' => $draft['amount'],
            'due_date' => $draft['due_date'],
            'status' => $draft['status'],
            'project_id' => (int)$draft['project_id'],
            'notes' => $this->buildSmartNote($draft['notes'], $payload),
            'attachment_ids_json' => '',
        ];

        $this->fillLegacyId($params, 'finance_inv');
        $this->fillRelationLegacy($params, 'project', 'project_id', 'project_legacy_id', 'name', 'project_name');
        $this->fillAuditFields($params, true);

        $model = new \app\admin\model\Finance\Invoice();
        $result = $model->allowField(true)->save($params);
        if ($result === false) {
            throw new Exception('应收应付写入失败');
        }

        $params['id'] = (int)$model->id;
        $this->recordBusinessAudit(
            'finance_invoice',
            'add',
            '应收应付',
            $params,
            '智能记账新增单据：' . $draft['title'] . ' / ' . number_format((float)$draft['amount'], 2)
        );

        return [
            'record_type' => 'invoice',
            'id' => (int)$model->id,
            'summary' => $payload['summary'],
            'source_label' => $payload['source_label'],
            'needs_review' => $payload['needs_review'],
            'edit_url' => (string)url('finance/invoice/edit', ['ids' => $model->id]),
            'index_url' => (string)url('finance/invoice/index'),
        ];
    }

    protected function buildSmartNote(string $notes, array $payload): string
    {
        $tags = ['智能记账', $payload['source_label'] ?: '规则兜底'];
        if (!empty($payload['needs_review'])) {
            $tags[] = '建议复核';
        }

        $prefix = '【' . implode(' / ', array_filter($tags)) . '】';
        $notes = trim($notes);

        return $notes === '' ? $prefix : $prefix . "\n" . $notes;
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
            'title' => $profile['title'] ?? '财务岗位',
            'department' => $profile['department'] ?? '财务部',
            'role_key' => $profile['role_key'] ?? 'finance',
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
            'finance' => '先处理待我审批、回款和付款单据，再补附件和记账。',
            'admin' => '先看审批和资金风险，再进入财务台账处理细项。',
            'viewer' => '先看今天的资金待办，再进入流水和单据台账。',
        ];

        return $map[$roleKey] ?? '先看资金待办，再进入财务台账处理。';
    }

    protected function buildSummaryCards(array $profile): array
    {
        $adminId = (int)($profile['admin_id'] ?? 0);

        return [
            [
                'title' => '待我审批',
                'value' => $this->safeCount('business_approval', function ($query) use ($adminId) {
                    $query->where('status', 'pending')->where('approver_admin_id', $adminId);
                }),
                'hint' => '先清掉已经分配给我的审批事项。',
                'url' => (string)url('business/approval/index', ['approver_admin_id' => $adminId, 'status' => 'pending']),
            ],
            [
                'title' => '待回款金额',
                'value' => $this->formatMoney($this->sumAmount('finance_invoice', 'amount', function ($query) {
                    $query->where('kind', 'receivable')->where('status', 'in', ['pending', 'partial', 'overdue']);
                })),
                'hint' => '今天优先看应收、逾期和临近到期单据。',
                'url' => (string)url('finance/invoice/index', ['kind' => 'receivable']),
            ],
            [
                'title' => '待付款金额',
                'value' => $this->formatMoney(
                    $this->sumAmount('finance_invoice', 'amount', function ($query) {
                        $query->where('kind', 'payable')->where('status', 'in', ['pending', 'partial', 'overdue']);
                    }) + $this->sumAmount('business_payment_request', 'request_amount', function ($query) {
                        $query->where('status', 'in', ['pending_approval', 'approved']);
                    })
                ),
                'hint' => '付款单据和付款申请统一从这里看。',
                'url' => (string)url('business/payment_request/index'),
            ],
            [
                'title' => '待补附件',
                'value' => $this->safeCount('finance_transaction', function ($query) {
                    $query->where(function ($subQuery) {
                        $subQuery->whereNull('attachment_ids_json')->whereOr('attachment_ids_json', '');
                    });
                }),
                'hint' => '先入账，后补传票据图片，避免台账断链。',
                'url' => (string)url('finance/transaction/index'),
            ],
        ];
    }

    protected function buildQuickPanels(array $profile): array
    {
        $adminId = (int)($profile['admin_id'] ?? 0);

        return [
            [
                'title' => '今天先做三件事',
                'hint' => '按顺序处理关键待办，先把最重要的几件事做完。',
                'items' => [
                    ['title' => '先处理我的审批', 'url' => (string)url('business/approval/index', ['approver_admin_id' => $adminId, 'status' => 'pending'])],
                    ['title' => '先补交易附件', 'url' => (string)url('finance/transaction/index')],
                    ['title' => '先写一笔新流水', 'url' => (string)url('finance/transaction/add')],
                ],
            ],
            [
                'title' => '常用入口',
                'hint' => '高频动作统一放这里，减少到处点菜单。',
                'items' => [
                    ['title' => '新增应收', 'url' => (string)url('finance/invoice/add', ['kind' => 'receivable'])],
                    ['title' => '新增应付', 'url' => (string)url('finance/invoice/add', ['kind' => 'payable'])],
                    ['title' => '打开财务台账', 'url' => (string)url('finance/transaction/index')],
                ],
            ],
        ];
    }

    protected function buildUsageGuide(): array
    {
        return [
            '开门顺序',
            '先看【待我审批】，处理完后直接从这里进入单据/流水。',
            '再看【待补附件】，优先补完最靠前的待办流水。',
            '最后点开【智能记账】快速落地新记录。',
        ];
    }

    protected function buildQuickActions(): array
    {
        return [
            ['title' => '新增收入', 'icon' => 'fa fa-plus-circle', 'url' => (string)url('finance/transaction/add', ['type' => 'income'])],
            ['title' => '新增支出', 'icon' => 'fa fa-minus-circle', 'url' => (string)url('finance/transaction/add', ['type' => 'expense'])],
            ['title' => '新增应收', 'icon' => 'fa fa-file-text-o', 'url' => (string)url('finance/invoice/add', ['kind' => 'receivable'])],
            ['title' => '新增应付', 'icon' => 'fa fa-credit-card', 'url' => (string)url('finance/invoice/add', ['kind' => 'payable'])],
            ['title' => 'AI 财务分析', 'icon' => 'fa fa-comments-o', 'url' => (string)url('ai/conversation/index', ['focus' => 'finance'])],
        ];
    }

    protected function buildApprovalTodos(array $profile): array
    {
        if (!$this->tableExists('business_approval')) {
            return [];
        }

        $adminId = (int)($profile['admin_id'] ?? 0);
        $roleKey = (string)($profile['role_key'] ?? 'finance');
        $query = Db::name('business_approval')
            ->field('id,approval_no,object_type,object_title,current_step_name,applicant_name,approver_name,applied_at')
            ->where('status', 'pending');

        if ($adminId > 0) {
            $query->where('approver_admin_id', $adminId);
        }

        $rows = $query->order('applied_at', 'asc')->limit(8)->select();

        if (!$rows && in_array($roleKey, ['admin', 'viewer'], true)) {
            $rows = Db::name('business_approval')
                ->field('id,approval_no,object_type,object_title,current_step_name,applicant_name,approver_name,applied_at')
                ->where('status', 'pending')
                ->order('applied_at', 'asc')
                ->limit(8)
                ->select();
        }

        return $this->formatApprovalRows($rows);
    }

    protected function buildInvoiceTodos(): array
    {
        if (!$this->tableExists('finance_invoice')) {
            return [];
        }

        $rows = Db::name('finance_invoice')
            ->field('id,kind,title,counterparty,amount,due_date,status')
            ->where('status', 'in', ['pending', 'partial', 'overdue'])
            ->orderRaw("FIELD(status, 'overdue', 'partial', 'pending')")
            ->order('due_date', 'asc')
            ->limit(8)
            ->select();

        return $this->formatInvoiceRows($rows);
    }

    protected function buildAttachmentTodos(): array
    {
        if (!$this->tableExists('finance_transaction')) {
            return [];
        }

        $rows = Db::name('finance_transaction')
            ->field('id,transaction_date,type,category,counterparty,amount,attachment_ids_json')
            ->where(function ($query) {
                $query->whereNull('attachment_ids_json')->whereOr('attachment_ids_json', '');
            })
            ->order('transaction_date', 'desc')
            ->order('id', 'desc')
            ->limit(8)
            ->select();

        return $this->formatTransactionRows($rows, false);
    }

    protected function buildRecentTransactions(): array
    {
        if (!$this->tableExists('finance_transaction')) {
            return [];
        }

        $rows = Db::name('finance_transaction')
            ->field('id,transaction_date,type,category,counterparty,amount,payment_method')
            ->order('transaction_date', 'desc')
            ->order('id', 'desc')
            ->limit(8)
            ->select();

        return $this->formatTransactionRows($rows, true);
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

    protected function formatInvoiceRows($rows): array
    {
        $statusMap = [
            'pending' => '待处理',
            'partial' => '部分完成',
            'paid' => '已完成',
            'overdue' => '已逾期',
            'cancelled' => '已作废',
        ];
        $kindMap = [
            'receivable' => '应收',
            'payable' => '应付',
        ];

        $items = [];
        foreach ($rows as $row) {
            $row['status_text'] = $statusMap[$row['status']] ?? $row['status'];
            $row['kind_text'] = $kindMap[$row['kind']] ?? $row['kind'];
            $row['amount_text'] = $this->formatMoney((float)$row['amount']);
            $items[] = $row;
        }

        return $items;
    }

    protected function formatTransactionRows($rows, bool $includePaymentMethod): array
    {
        $typeMap = [
            'income' => '收入',
            'expense' => '支出',
        ];
        $paymentMethodMap = [
            'bank' => '银行转账',
            'wechat' => '微信',
            'alipay' => '支付宝',
            'cash' => '现金',
            'other' => '其他',
        ];

        $items = [];
        foreach ($rows as $row) {
            $row['type_text'] = $typeMap[$row['type']] ?? $row['type'];
            $row['amount_text'] = $this->formatMoney((float)$row['amount']);
            if ($includePaymentMethod) {
                $row['payment_method_text'] = $paymentMethodMap[$row['payment_method']] ?? ($row['payment_method'] ?: '-');
            }
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

    protected function sumAmount(string $table, string $field = 'amount', ?callable $callback = null): float
    {
        if (!$this->tableExists($table)) {
            return 0.0;
        }

        $query = Db::name($table);
        if ($callback) {
            $callback($query);
        }

        return (float)$query->sum($field);
    }

    protected function formatMoney(float $value): string
    {
        return '￥' . number_format($value, 2);
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

    protected function guardWorkbenchPermission(): void
    {
        if (!$this->auth->check('finance/workbench/index')) {
            $this->error('你没有权限访问财务工作台');
        }
    }
}
