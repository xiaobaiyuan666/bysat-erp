<?php

namespace app\admin\controller\finance;

use app\admin\library\FinanceSmartBookkeepingService;
use app\admin\library\traits\ErpAuditHelper;
use app\admin\library\traits\ErpCrudHelper;
use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\Config;
use think\Response;
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

    protected $noNeedRight = ['smartbookbootstrap', 'smartbook', 'smartbooksave', 'reportprint', 'reportexport'];

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
            'reportSnapshots' => $this->buildReportSnapshots(),
            'reportSections' => $this->buildReportSections(),
            'trendSeries' => $this->buildTrendSeries(),
            'agingSections' => $this->buildAgingSections(),
            'financeAlerts' => $this->buildFinanceAlerts(),
            'reportActions' => $this->buildReportActions(),
            'quickActions' => $this->buildQuickActions(),
            'quickPanels' => $this->buildQuickPanels($profile),
            'usageGuide' => $this->buildUsageGuide(),
            'billPreview' => $this->buildBillPreview(),
            'approvalTodos' => $this->buildApprovalTodos($profile),
            'invoiceTodos' => $this->buildInvoiceTodos(),
            'attachmentTodos' => $this->buildAttachmentTodos(),
            'recentTransactions' => $this->buildRecentTransactions(),
        ]);

        return $this->view->fetch();
    }

    public function reportprint()
    {
        $this->guardWorkbenchPermission();
        $profile = $this->getCurrentUserProfile();
        $this->view->engine->layout(false);
        $this->view->assign([
            'currentUser' => $this->buildCurrentUser($profile),
            'summaryCards' => $this->buildSummaryCards($profile),
            'reportSnapshots' => $this->buildReportSnapshots(),
            'reportSections' => $this->buildReportSections(),
            'trendSeries' => $this->buildTrendSeries(),
            'agingSections' => $this->buildAgingSections(),
            'financeAlerts' => $this->buildFinanceAlerts(),
            'billPreview' => $this->buildBillPreview(),
            'brandInfo' => $this->buildBrandInfo(),
            'printedAt' => date('Y-m-d H:i:s'),
        ]);

        return $this->view->fetch('finance/workbench/reportprint');
    }

    public function reportexport()
    {
        $this->guardWorkbenchPermission();
        $section = (string)$this->request->get('section', 'day', 'trim');

        if ($section === 'aging') {
            $filename = 'finance-aging-' . date('Ymd-His') . '.csv';
            $headers = ['类型', '区间', '单据数', '金额', '说明'];
            $rows = [];
            foreach ($this->buildAgingSections() as $group) {
                foreach ($group['rows'] as $row) {
                    $rows[] = [
                        $group['title'],
                        $row['label'],
                        (string)$row['count'],
                        (string)$row['amount_text'],
                        (string)($row['hint'] ?? ''),
                    ];
                }
            }

            return $this->createCsvResponse($filename, $headers, $rows);
        }

        $selected = null;
        foreach ($this->buildReportSections() as $item) {
            if ((string)($item['key'] ?? '') === $section) {
                $selected = $item;
                break;
            }
        }

        if (!$selected) {
            $this->error('导出类型不存在');
        }

        $filename = 'finance-' . $section . '-' . date('Ymd-His') . '.csv';
        $headers = ['周期', '收入', '支出', '净流入', '应收', '应付', '流水笔数', '账单笔数', '趋势'];
        $rows = [];
        foreach ($selected['rows'] as $row) {
            $rows[] = [
                (string)$row['label'],
                (string)$row['income_text'],
                (string)$row['expense_text'],
                (string)$row['net_text'],
                (string)$row['receivable_text'],
                (string)$row['payable_text'],
                (string)$row['transaction_count'],
                (string)$row['invoice_count'],
                (string)($row['trend_text'] ?? ''),
            ];
        }

        return $this->createCsvResponse($filename, $headers, $rows);
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

    protected function buildBillPreview(): array
    {
        $items = [];

        if ($this->tableExists('finance_transaction')) {
            $rows = Db::name('finance_transaction')
                ->field('id,transaction_date,type,category,counterparty,amount,payment_method,attachment_ids_json')
                ->order('transaction_date', 'desc')
                ->order('id', 'desc')
                ->limit(4)
                ->select();

            foreach ($this->formatTransactionRows($rows, true) as $row) {
                $items[] = [
                    'kind' => 'transaction',
                    'date' => (string)($row['transaction_date'] ?? ''),
                    'badge' => '流水',
                    'title' => (string)($row['counterparty'] ?: '财务流水'),
                    'type_text' => (string)($row['type_text'] ?? ''),
                    'status_text' => empty($row['attachment_ids_json']) ? '待补附件' : '附件已齐',
                    'meta' => trim((string)($row['category'] ?? '') . ' / ' . (string)($row['payment_method_text'] ?? '-'), ' /'),
                    'amount_text' => (string)$row['amount_text'],
                    'url' => (string)url('finance/transaction/edit', ['ids' => $row['id']]),
                    'print_url' => (string)url('finance/transaction/printview', ['ids' => $row['id']]),
                ];
            }
        }

        if ($this->tableExists('finance_invoice')) {
            $rows = Db::name('finance_invoice')
                ->field('id,kind,title,counterparty,amount,due_date,status')
                ->order('due_date', 'desc')
                ->order('id', 'desc')
                ->limit(4)
                ->select();

            foreach ($this->formatInvoiceRows($rows) as $row) {
                $items[] = [
                    'kind' => 'invoice',
                    'date' => (string)($row['due_date'] ?? ''),
                    'badge' => '账单',
                    'title' => (string)($row['title'] ?: '往来单据'),
                    'type_text' => (string)($row['kind_text'] ?? ''),
                    'status_text' => (string)($row['status_text'] ?? ''),
                    'meta' => (string)($row['counterparty'] ?? '-'),
                    'amount_text' => (string)$row['amount_text'],
                    'url' => (string)url('finance/invoice/edit', ['ids' => $row['id']]),
                    'print_url' => (string)url('finance/invoice/printview', ['ids' => $row['id']]),
                ];
            }
        }

        usort($items, function ($left, $right) {
            return strcmp((string)($right['date'] ?? ''), (string)($left['date'] ?? ''));
        });

        return array_slice($items, 0, 5);
    }

    protected function buildReportSnapshots(): array
    {
        $snapshots = [];
        foreach ($this->buildReportSections() as $section) {
            $row = $section['rows'][0] ?? [];
            if (!$row) {
                continue;
            }

            $snapshots[] = [
                'title' => (string)$section['snapshot_title'],
                'label' => (string)($row['label'] ?? '-'),
                'income_text' => (string)($row['income_text'] ?? $this->formatMoney(0)),
                'expense_text' => (string)($row['expense_text'] ?? $this->formatMoney(0)),
                'net_text' => (string)($row['net_text'] ?? $this->formatMoney(0)),
                'receivable_text' => (string)($row['receivable_text'] ?? $this->formatMoney(0)),
                'payable_text' => (string)($row['payable_text'] ?? $this->formatMoney(0)),
                'transactions' => (int)($row['transaction_count'] ?? 0),
                'invoices' => (int)($row['invoice_count'] ?? 0),
                'trend_text' => (string)($row['trend_text'] ?? '较上一周期持平'),
                'trend_class' => (string)($row['trend_class'] ?? 'is-flat'),
            ];
        }

        return $snapshots;
    }

    protected function buildReportSections(): array
    {
        static $sections = null;
        if ($sections !== null) {
            return $sections;
        }

        $sections = [
            [
                'key' => 'day',
                'title' => '每日报表',
                'snapshot_title' => '今日财务',
                'rows' => $this->buildPeriodStats('day', 7),
            ],
            [
                'key' => 'week',
                'title' => '每周报表',
                'snapshot_title' => '本周财务',
                'rows' => $this->buildPeriodStats('week', 8),
            ],
            [
                'key' => 'month',
                'title' => '每月报表',
                'snapshot_title' => '本月财务',
                'rows' => $this->buildPeriodStats('month', 6),
            ],
        ];

        return $sections;
    }

    protected function buildPeriodStats(string $mode, int $periods): array
    {
        $ranges = $this->buildDateRanges($mode, $periods);
        if (!$ranges) {
            return [];
        }

        $oldestStart = $ranges[count($ranges) - 1]['start'];
        $latestEnd = $ranges[0]['end'];
        $transactions = $this->loadTransactionsForStats($oldestStart, $latestEnd);
        $invoices = $this->loadInvoicesForStats($oldestStart, $latestEnd);
        $maxFlow = 0.0;
        $rows = [];

        foreach ($ranges as $range) {
            $income = 0.0;
            $expense = 0.0;
            $receivable = 0.0;
            $payable = 0.0;
            $transactionCount = 0;
            $invoiceCount = 0;

            foreach ($transactions as $transaction) {
                $date = (string)($transaction['transaction_date'] ?? '');
                if ($date < $range['start'] || $date > $range['end_date']) {
                    continue;
                }

                $transactionCount++;
                if ((string)$transaction['type'] === 'income') {
                    $income += (float)$transaction['amount'];
                } elseif ((string)$transaction['type'] === 'expense') {
                    $expense += (float)$transaction['amount'];
                }
            }

            foreach ($invoices as $invoice) {
                $date = (string)($invoice['report_date'] ?? '');
                if ($date < $range['start'] || $date > $range['end_date']) {
                    continue;
                }

                $invoiceCount++;
                if ((string)$invoice['kind'] === 'receivable') {
                    $receivable += (float)$invoice['amount'];
                } elseif ((string)$invoice['kind'] === 'payable') {
                    $payable += (float)$invoice['amount'];
                }
            }

            $net = $income - $expense;
            $maxFlow = max($maxFlow, $income, $expense, abs($net), $receivable, $payable);
            $rows[] = [
                'label' => $range['label'],
                'income' => $income,
                'expense' => $expense,
                'net' => $net,
                'receivable' => $receivable,
                'payable' => $payable,
                'income_text' => $this->formatMoney($income),
                'expense_text' => $this->formatMoney($expense),
                'net_text' => $this->formatMoney($net),
                'receivable_text' => $this->formatMoney($receivable),
                'payable_text' => $this->formatMoney($payable),
                'transaction_count' => $transactionCount,
                'invoice_count' => $invoiceCount,
                'bar_value' => max($income, $expense, abs($net), $receivable, $payable),
            ];
        }

        $maxFlow = max($maxFlow, 1);
        foreach ($rows as &$row) {
            $row['bar_width'] = (int)max(10, round(($row['bar_value'] / $maxFlow) * 100));
        }
        unset($row);

        $rows = $this->decorateTrendRows($rows);

        return $rows;
    }

    protected function decorateTrendRows(array $rows): array
    {
        $count = count($rows);
        for ($index = 0; $index < $count; $index++) {
            $current = $rows[$index];
            $next = $rows[$index + 1] ?? null;
            $currentNet = $this->parseMoney($current['net_text'] ?? '0');
            $previousNet = $next ? $this->parseMoney($next['net_text'] ?? '0') : $currentNet;
            $delta = $currentNet - $previousNet;

            if (abs($delta) < 0.005) {
                $rows[$index]['trend_text'] = '较上一周期持平';
                $rows[$index]['trend_class'] = 'is-flat';
            } elseif ($delta > 0) {
                $rows[$index]['trend_text'] = '较上一周期 +' . $this->formatMoney($delta);
                $rows[$index]['trend_class'] = 'is-up';
            } else {
                $rows[$index]['trend_text'] = '较上一周期 ' . $this->formatMoney($delta);
                $rows[$index]['trend_class'] = 'is-down';
            }
        }

        return $rows;
    }

    protected function buildTrendSeries(): array
    {
        $groups = [];
        foreach ($this->buildReportSections() as $section) {
            $rows = $section['rows'];
            if (!$rows) {
                continue;
            }

            $values = [];
            foreach ($rows as $row) {
                $values[] = (float)$row['income'];
                $values[] = (float)$row['expense'];
                $values[] = (float)$row['net'];
            }

            $minValue = min($values);
            $maxValue = max($values);
            if (abs($maxValue - $minValue) < 0.0001) {
                $maxValue += 1;
                $minValue -= 1;
            }

            $width = 420;
            $height = 152;
            $paddingX = 24;
            $paddingY = 18;
            $chartHeight = $height - ($paddingY * 2);
            $stepX = count($rows) > 1 ? ($width - ($paddingX * 2)) / (count($rows) - 1) : 0;

            $groups[] = [
                'key' => (string)$section['key'],
                'title' => (string)$section['title'],
                'view_box' => '0 0 ' . $width . ' ' . $height,
                'labels' => array_map(function ($row, $index) use ($paddingX, $stepX, $height) {
                    return [
                        'x' => round($paddingX + ($stepX * $index), 1),
                        'y' => $height - 6,
                        'label' => (string)$row['label'],
                    ];
                }, $rows, array_keys($rows)),
                'series' => [
                    $this->buildTrendLine('收入', 'income', '#1d9d57', $rows, $minValue, $maxValue, $width, $chartHeight, $paddingX, $paddingY),
                    $this->buildTrendLine('支出', 'expense', '#d93025', $rows, $minValue, $maxValue, $width, $chartHeight, $paddingX, $paddingY),
                    $this->buildTrendLine('净流入', 'net', '#3c8dbc', $rows, $minValue, $maxValue, $width, $chartHeight, $paddingX, $paddingY),
                ],
            ];
        }

        return $groups;
    }

    protected function buildTrendLine(string $name, string $field, string $color, array $rows, float $minValue, float $maxValue, int $width, int $chartHeight, int $paddingX, int $paddingY): array
    {
        $stepX = count($rows) > 1 ? ($width - ($paddingX * 2)) / (count($rows) - 1) : 0;
        $points = [];

        foreach ($rows as $index => $row) {
            $value = (float)$row[$field];
            $x = $paddingX + ($stepX * $index);
            $y = $paddingY + (($maxValue - $value) / ($maxValue - $minValue)) * $chartHeight;
            $points[] = [
                'x' => round($x, 1),
                'y' => round($y, 1),
                'label' => (string)$row['label'],
                'value_text' => (string)$row[$field . '_text'],
            ];
        }

        return [
            'name' => $name,
            'color' => $color,
            'polyline' => implode(' ', array_map(function ($point) {
                return $point['x'] . ',' . $point['y'];
            }, $points)),
            'points' => $points,
        ];
    }

    protected function buildAgingSections(): array
    {
        static $sections = null;
        if ($sections !== null) {
            return $sections;
        }

        $sections = [
            $this->buildAgingSection('receivable', '应收账龄'),
            $this->buildAgingSection('payable', '应付账龄'),
        ];

        return $sections;
    }

    protected function buildAgingSection(string $kind, string $title): array
    {
        if (!$this->tableExists('finance_invoice')) {
            return [
                'key' => $kind,
                'title' => $title,
                'due_soon_count' => 0,
                'due_soon_text' => $this->formatMoney(0),
                'rows' => [],
            ];
        }

        $bucketMeta = [
            'not_due' => ['label' => '未逾期', 'hint' => '账期正常'],
            'overdue_0_7' => ['label' => '逾期 0-7 天', 'hint' => '建议立即跟进'],
            'overdue_8_15' => ['label' => '逾期 8-15 天', 'hint' => '需要明确处理节点'],
            'overdue_16_30' => ['label' => '逾期 16-30 天', 'hint' => '建议升级跟进'],
            'overdue_31_plus' => ['label' => '逾期 30+ 天', 'hint' => '高风险账龄'],
        ];
        $buckets = [];
        foreach ($bucketMeta as $key => $meta) {
            $buckets[$key] = [
                'key' => $key,
                'label' => $meta['label'],
                'hint' => $meta['hint'],
                'count' => 0,
                'amount' => 0.0,
            ];
        }

        $dueSoonCount = 0;
        $dueSoonAmount = 0.0;
        $today = strtotime(date('Y-m-d'));
        $rows = Db::name('finance_invoice')
            ->field('amount,due_date,status')
            ->where('kind', $kind)
            ->where('status', 'in', ['pending', 'partial', 'overdue'])
            ->select();

        foreach ($rows as $row) {
            $dueDate = trim((string)($row['due_date'] ?? ''));
            if ($dueDate === '') {
                continue;
            }

            $dueTimestamp = strtotime($dueDate);
            $days = (int)floor(($today - $dueTimestamp) / 86400);
            $amount = (float)$row['amount'];

            if ($days < 0) {
                $bucketKey = 'not_due';
                if ($days >= -7) {
                    $dueSoonCount++;
                    $dueSoonAmount += $amount;
                }
            } elseif ($days <= 7) {
                $bucketKey = 'overdue_0_7';
            } elseif ($days <= 15) {
                $bucketKey = 'overdue_8_15';
            } elseif ($days <= 30) {
                $bucketKey = 'overdue_16_30';
            } else {
                $bucketKey = 'overdue_31_plus';
            }

            $buckets[$bucketKey]['count']++;
            $buckets[$bucketKey]['amount'] += $amount;
        }

        $maxAmount = 1.0;
        foreach ($buckets as $bucket) {
            $maxAmount = max($maxAmount, (float)$bucket['amount']);
        }

        $resultRows = [];
        foreach ($buckets as $bucket) {
            $bucket['amount_text'] = $this->formatMoney((float)$bucket['amount']);
            $bucket['bar_width'] = (int)max(10, round(((float)$bucket['amount'] / $maxAmount) * 100));
            $resultRows[] = $bucket;
        }

        return [
            'key' => $kind,
            'title' => $title,
            'due_soon_count' => $dueSoonCount,
            'due_soon_text' => $this->formatMoney($dueSoonAmount),
            'rows' => $resultRows,
        ];
    }

    protected function buildFinanceAlerts(): array
    {
        $alerts = [];
        $today = date('Y-m-d');
        $soonDate = date('Y-m-d', strtotime('+7 day'));

        if ($this->tableExists('finance_invoice')) {
            $overdueReceivable = Db::name('finance_invoice')
                ->where('kind', 'receivable')
                ->where('status', 'in', ['pending', 'partial', 'overdue'])
                ->where('due_date', '<', $today)
                ->sum('amount');
            if ((float)$overdueReceivable > 0) {
                $alerts[] = [
                    'level' => 'danger',
                    'title' => '逾期应收待跟进',
                    'summary' => $this->formatMoney((float)$overdueReceivable),
                    'detail' => '已有到期未回款单据，建议优先催收并核对回款计划。',
                    'url' => (string)url('finance/invoice/index', ['kind' => 'receivable', 'status' => 'overdue']),
                    'action' => '查看应收',
                ];
            }

            $overduePayable = Db::name('finance_invoice')
                ->where('kind', 'payable')
                ->where('status', 'in', ['pending', 'partial', 'overdue'])
                ->where('due_date', '<', $today)
                ->sum('amount');
            if ((float)$overduePayable > 0) {
                $alerts[] = [
                    'level' => 'warning',
                    'title' => '逾期应付待处理',
                    'summary' => $this->formatMoney((float)$overduePayable),
                    'detail' => '已有到期未支付单据，建议检查付款排期和审批状态。',
                    'url' => (string)url('finance/invoice/index', ['kind' => 'payable', 'status' => 'overdue']),
                    'action' => '查看应付',
                ];
            }

            $dueSoonReceivable = Db::name('finance_invoice')
                ->where('kind', 'receivable')
                ->where('status', 'in', ['pending', 'partial'])
                ->where('due_date', '>=', $today)
                ->where('due_date', '<=', $soonDate)
                ->sum('amount');
            if ((float)$dueSoonReceivable > 0) {
                $alerts[] = [
                    'level' => 'info',
                    'title' => '7 天内即将到期应收',
                    'summary' => $this->formatMoney((float)$dueSoonReceivable),
                    'detail' => '未来 7 天内有应收即将到期，建议提前安排催收节奏。',
                    'url' => (string)url('finance/invoice/index', ['kind' => 'receivable']),
                    'action' => '查看回款',
                ];
            }

            $dueSoonPayable = Db::name('finance_invoice')
                ->where('kind', 'payable')
                ->where('status', 'in', ['pending', 'partial'])
                ->where('due_date', '>=', $today)
                ->where('due_date', '<=', $soonDate)
                ->sum('amount');
            if ((float)$dueSoonPayable > 0) {
                $alerts[] = [
                    'level' => 'info',
                    'title' => '7 天内即将到期应付',
                    'summary' => $this->formatMoney((float)$dueSoonPayable),
                    'detail' => '未来 7 天内有应付到期，建议提前确认审批与资金安排。',
                    'url' => (string)url('finance/invoice/index', ['kind' => 'payable']),
                    'action' => '查看付款',
                ];
            }
        }

        if ($this->tableExists('business_payment_request')) {
            $pendingPayments = (float)Db::name('business_payment_request')
                ->where('status', 'pending_approval')
                ->sum('request_amount');
            if ($pendingPayments > 0) {
                $alerts[] = [
                    'level' => 'warning',
                    'title' => '付款申请待审批',
                    'summary' => $this->formatMoney($pendingPayments),
                    'detail' => '已有待审批付款申请，建议同步检查现金流与付款节点。',
                    'url' => (string)url('business/payment_request/index'),
                    'action' => '查看付款申请',
                ];
            }
        }

        if ($this->tableExists('finance_transaction')) {
            $missingAttachments = Db::name('finance_transaction')
                ->where(function ($query) {
                    $query->whereNull('attachment_ids_json')->whereOr('attachment_ids_json', '');
                })
                ->count();
            if ((int)$missingAttachments > 0) {
                $alerts[] = [
                    'level' => 'info',
                    'title' => '票据附件仍未补齐',
                    'summary' => (int)$missingAttachments . ' 笔',
                    'detail' => '建议尽快补传付款凭证、发票或截图，避免台账与票据脱节。',
                    'url' => (string)url('finance/transaction/index'),
                    'action' => '打开流水',
                ];
            }
        }

        if (!$alerts) {
            $alerts[] = [
                'level' => 'ok',
                'title' => '当前无明显财务异常',
                'summary' => '状态正常',
                'detail' => '逾期单据、附件缺失和审批风险当前都处于可控范围。',
                'url' => (string)url('finance/workbench/index'),
                'action' => '刷新工作台',
            ];
        }

        return array_slice($alerts, 0, 5);
    }

    protected function buildReportActions(): array
    {
        return [
            [
                'title' => '打印汇总报表',
                'url' => (string)url('finance/workbench/reportprint'),
                'class' => 'btn btn-primary btn-dialog',
                'target' => 'dialog',
                'dialog' => true,
            ],
            [
                'title' => '导出日报 CSV',
                'url' => (string)url('finance/workbench/reportexport', ['section' => 'day']),
                'class' => 'btn btn-default',
                'target' => '_blank',
                'dialog' => false,
            ],
            [
                'title' => '导出周报 CSV',
                'url' => (string)url('finance/workbench/reportexport', ['section' => 'week']),
                'class' => 'btn btn-default',
                'target' => '_blank',
                'dialog' => false,
            ],
            [
                'title' => '导出月报 CSV',
                'url' => (string)url('finance/workbench/reportexport', ['section' => 'month']),
                'class' => 'btn btn-default',
                'target' => '_blank',
                'dialog' => false,
            ],
            [
                'title' => '导出账龄 CSV',
                'url' => (string)url('finance/workbench/reportexport', ['section' => 'aging']),
                'class' => 'btn btn-default',
                'target' => '_blank',
                'dialog' => false,
            ],
        ];
    }

    protected function buildDateRanges(string $mode, int $periods): array
    {
        $ranges = [];
        $today = strtotime(date('Y-m-d'));

        for ($index = 0; $index < $periods; $index++) {
            if ($mode === 'day') {
                $startTs = strtotime("-{$index} day", $today);
                $endTs = strtotime('+1 day', $startTs);
                $label = date('m-d', $startTs);
            } elseif ($mode === 'week') {
                $weekStart = strtotime('-' . (date('N', $today) - 1) . ' day', $today);
                $startTs = strtotime('-' . ($index * 7) . ' day', $weekStart);
                $endTs = strtotime('+7 day', $startTs);
                $label = date('m.d', $startTs) . ' - ' . date('m.d', strtotime('-1 day', $endTs));
            } else {
                $monthStart = strtotime(date('Y-m-01', $today));
                $startTs = strtotime("-{$index} month", $monthStart);
                $startTs = strtotime(date('Y-m-01', $startTs));
                $endTs = strtotime('+1 month', $startTs);
                $label = date('Y-m', $startTs);
            }

            $ranges[] = [
                'start' => date('Y-m-d', $startTs),
                'end' => date('Y-m-d', $endTs),
                'end_date' => date('Y-m-d', strtotime('-1 day', $endTs)),
                'label' => $label,
            ];
        }

        return $ranges;
    }

    protected function loadTransactionsForStats(string $startDate, string $endDateExclusive): array
    {
        if (!$this->tableExists('finance_transaction')) {
            return [];
        }

        return Db::name('finance_transaction')
            ->field('transaction_date,type,amount')
            ->where('transaction_date', '>=', $startDate)
            ->where('transaction_date', '<', $endDateExclusive)
            ->select();
    }

    protected function loadInvoicesForStats(string $startDate, string $endDateExclusive): array
    {
        if (!$this->tableExists('finance_invoice')) {
            return [];
        }

        $startTimestamp = strtotime($startDate . ' 00:00:00');
        $endTimestamp = strtotime($endDateExclusive . ' 00:00:00');
        $rows = Db::name('finance_invoice')
            ->field('kind,amount,due_date,createtime')
            ->where(function ($query) use ($startDate, $endDateExclusive, $startTimestamp, $endTimestamp) {
                $query->where(function ($timeQuery) use ($startTimestamp, $endTimestamp) {
                    $timeQuery->where('createtime', '>=', $startTimestamp)->where('createtime', '<', $endTimestamp);
                })
                    ->whereOr(function ($orQuery) use ($startDate, $endDateExclusive) {
                        $orQuery->where('due_date', '>=', $startDate)->where('due_date', '<', $endDateExclusive);
                    });
            })
            ->select();

        foreach ($rows as &$row) {
            $dueDate = trim((string)($row['due_date'] ?? ''));
            $row['report_date'] = $dueDate !== ''
                ? $dueDate
                : date('Y-m-d', (int)($row['createtime'] ?? time()));
        }
        unset($row);

        return $rows;
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
        $prefix = $value < 0 ? '-￥' : '￥';
        return $prefix . number_format(abs($value), 2);
    }

    protected function parseMoney(string $value): float
    {
        $normalized = str_replace(['￥', ',', ' '], '', $value);
        return (float)$normalized;
    }

    protected function createCsvResponse(string $filename, array $headers, array $rows): Response
    {
        $content = chr(239) . chr(187) . chr(191);
        $content .= $this->buildCsvLine($headers);
        foreach ($rows as $row) {
            $content .= $this->buildCsvLine($row);
        }

        return Response::create($content, 'html', 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    protected function buildCsvLine(array $columns): string
    {
        $escaped = array_map(function ($value) {
            $string = (string)$value;
            $string = str_replace('"', '""', $string);
            return '"' . $string . '"';
        }, $columns);

        return implode(',', $escaped) . "\r\n";
    }

    protected function buildBrandInfo(): array
    {
        $siteName = (string)(Config::get('site.name') ?: 'ERP AI 管理系统');

        return [
            'company_name' => $siteName,
            'system_name' => (string)(Config::get('site.login_subtitle') ?: '企业 ERP AI 智能管理系统'),
            'website' => (string)(Config::get('site.site_home_label') ?: Config::get('site.site_home_url') ?: ''),
            'copyright' => (string)(Config::get('site.copyright') ?: Config::get('site.beian') ?: ''),
        ];
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
