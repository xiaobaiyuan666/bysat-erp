<?php

$financeTab = input_string($_GET, 'tab', 'transactions');
if (!in_array($financeTab, ['transactions', 'invoices'], true)) {
    $financeTab = 'transactions';
}

$keyword = input_string($_GET, 'keyword');
$typeFilter = input_string($_GET, 'type');
$kindFilter = input_string($_GET, 'kind');
$statusFilter = input_string($_GET, 'status');
$projectFilter = input_string($_GET, 'project_id');
$overdueOnly = input_bool($_GET, 'overdue');
$editTransactionId = input_string($_GET, 'edit_transaction_id');
$editInvoiceId = input_string($_GET, 'edit_invoice_id');
$attachTransactionId = input_string($_GET, 'attach_transaction_id');
$attachInvoiceId = input_string($_GET, 'attach_invoice_id');

$projectNameLookup = project_lookup($data['projects']);
$editingTransaction = $editTransactionId === '' ? null : find_record_by_id($data['transactions'], $editTransactionId);
$editingInvoice = $editInvoiceId === '' ? null : find_record_by_id($data['invoices'], $editInvoiceId);
$attachingTransaction = $attachTransactionId === '' ? null : find_record_by_id($data['transactions'], $attachTransactionId);
$attachingInvoice = $attachInvoiceId === '' ? null : find_record_by_id($data['invoices'], $attachInvoiceId);
$smartExamples = [
    '今天给晨光办公付款100元，微信支付，买办公用品',
    '昨天收到星环科技回款50000元，银行转账，官网项目尾款',
    '今天给阿里云付款6800元，银行转账，云服务器续费',
];

$filteredTransactions = [];
$filteredInvoices = [];
$financeStats = [];
$expenseRankItems = [];
$financeDonutSegments = [];

$renderAttachments = static function (array $attachments): void {
    $attachments = array_slice(record_attachments(['attachments' => $attachments]), 0, 3);

    if ($attachments === []) {
        echo '<span class="attachment-empty">-</span>';
        return;
    }

    echo '<div class="attachment-strip">';
    foreach ($attachments as $attachment) {
        $path = h(attachment_public_path($attachment));
        $name = h((string) ($attachment['name'] ?? '附件'));
        echo '<a class="attachment-thumb" href="' . $path . '" target="_blank" rel="noopener noreferrer"><img src="' . $path . '" alt="' . $name . '"></a>';
    }
    echo '</div>';
};

$renderAttachmentEditor = static function (array $record): void {
    $attachments = record_attachments($record);

    if ($attachments === []) {
        echo '<div class="empty-state">当前没有附件。</div>';
        return;
    }

    echo '<div class="attachment-manage">';
    foreach ($attachments as $attachment) {
        $path = h(attachment_public_path($attachment));
        $name = h((string) ($attachment['name'] ?? '附件'));
        $id = h((string) ($attachment['id'] ?? ''));
        echo '<label class="attachment-manage__item">';
        echo '<span class="attachment-manage__preview"><img src="' . $path . '" alt="' . $name . '"></span>';
        echo '<span class="attachment-manage__meta"><strong>' . $name . '</strong><a href="' . $path . '" target="_blank" rel="noopener noreferrer">查看原图</a></span>';
        echo '<span class="attachment-manage__remove"><input type="checkbox" name="remove_attachment_ids[]" value="' . $id . '"> 删除</span>';
        echo '</label>';
    }
    echo '</div>';
};

$filteredTransactions = array_values(array_filter(
    $transactionRows,
    static function (array $row) use ($keyword, $typeFilter, $projectFilter, $projectNameLookup): bool {
        if ($typeFilter !== '' && (string) $row['type'] !== $typeFilter) {
            return false;
        }

        if ($projectFilter !== '' && (string) $row['project_id'] !== $projectFilter) {
            return false;
        }

        if ($keyword === '') {
            return true;
        }

        $haystack = implode(' ', [
            (string) $row['category'],
            (string) $row['counterparty'],
            (string) $row['notes'],
            project_name($projectNameLookup, (string) $row['project_id']),
        ]);

        return text_contains_ci($haystack, $keyword);
    }
));

$filteredInvoices = array_values(array_filter(
    $invoiceRows,
    static function (array $row) use ($keyword, $kindFilter, $statusFilter, $projectFilter, $overdueOnly): bool {
        if ($kindFilter !== '' && (string) $row['kind'] !== $kindFilter) {
            return false;
        }

        if ($statusFilter !== '' && (string) $row['status'] !== $statusFilter) {
            return false;
        }

        if ($projectFilter !== '' && (string) $row['project_id'] !== $projectFilter) {
            return false;
        }

        if ($overdueOnly && !(bool) $row['overdue']) {
            return false;
        }

        if ($keyword === '') {
            return true;
        }

        $haystack = implode(' ', [
            (string) $row['title'],
            (string) $row['counterparty'],
            (string) $row['notes'],
            (string) $row['project_name'],
        ]);

        return text_contains_ci($haystack, $keyword);
    }
));

$financeStats = [
    ['label' => '净现金流', 'value' => money((float) $dashboard['net_cashflow']), 'hint' => '累计收入减累计支出', 'tone' => (float) $dashboard['net_cashflow'] >= 0 ? 'success' : 'danger'],
    ['label' => '待回款', 'value' => money((float) $dashboard['open_receivables']), 'hint' => '应收待结金额', 'tone' => 'info'],
    ['label' => '待付款', 'value' => money((float) $dashboard['open_payables']), 'hint' => '应付待结金额', 'tone' => 'warning'],
    ['label' => '逾期单据', 'value' => (string) ($invoiceSummary['receivable']['overdue_count'] + $invoiceSummary['payable']['overdue_count']) . ' 笔', 'hint' => money((float) $dashboard['overdue_receivables'] + (float) $dashboard['overdue_payables']), 'tone' => 'danger'],
];

$expenseMax = max(array_map(static fn(array $row): float => (float) $row['amount'], $expenseRows) ?: [1.0]);
$expenseRankItems = array_map(static function (array $row) use ($expenseMax): array {
    return [
        'label' => (string) $row['category'],
        'value' => money((float) $row['amount']),
        'hint' => '费用分布',
        'percent' => percent((float) $row['amount'], $expenseMax, 100.0),
        'tone' => 'warning',
    ];
}, $expenseRows);

$financeDonutSegments = [
    ['label' => '应收未结', 'value' => (float) $dashboard['open_receivables'], 'display' => money((float) $dashboard['open_receivables']), 'tone' => 'info'],
    ['label' => '应付未结', 'value' => (float) $dashboard['open_payables'], 'display' => money((float) $dashboard['open_payables']), 'tone' => 'warning'],
    ['label' => '逾期应收', 'value' => (float) $dashboard['overdue_receivables'], 'display' => money((float) $dashboard['overdue_receivables']), 'tone' => 'danger'],
];
$pendingFinanceTodos = array_slice(array_values(array_filter(
    $invoiceRows,
    static fn(array $row): bool => (string) $row['status'] !== 'paid'
)), 0, 5);

$attachmentBacklogRows = [];
foreach ($transactionRows as $row) {
    if ((string) ($row['type'] ?? '') !== 'expense') {
        continue;
    }

    if (record_attachments($row) !== []) {
        continue;
    }

    $attachmentBacklogRows[] = [
        'kind' => '流水',
        'label' => (string) $row['counterparty'] . ' ' . money((float) $row['amount']),
        'hint' => display_date((string) $row['date']) . ' · ' . (string) $row['category'],
        'link' => 'index.php?page=finance&tab=transactions&attach_transaction_id=' . urlencode((string) $row['id']),
    ];

    if (count($attachmentBacklogRows) >= 3) {
        break;
    }
}

foreach ($invoiceRows as $row) {
    if ((string) ($row['status'] ?? '') === 'paid') {
        continue;
    }

    if (record_attachments($row) !== []) {
        continue;
    }

    $attachmentBacklogRows[] = [
        'kind' => (string) $row['kind'] === 'receivable' ? '应收' : '应付',
        'label' => (string) $row['title'],
        'hint' => display_date((string) $row['due_date']) . ' · ' . money((float) $row['amount']),
        'link' => 'index.php?page=finance&tab=invoices&attach_invoice_id=' . urlencode((string) $row['id']),
    ];

    if (count($attachmentBacklogRows) >= 6) {
        break;
    }
}

$financeActionCards = [
    [
        'title' => '待回款跟进',
        'value' => money((float) $dashboard['open_receivables']),
        'hint' => '优先催收未结应收',
        'link' => 'index.php?page=finance&tab=invoices&kind=receivable',
    ],
    [
        'title' => '待付款安排',
        'value' => money((float) $dashboard['open_payables']),
        'hint' => '合理安排现金支出节奏',
        'link' => 'index.php?page=finance&tab=invoices&kind=payable',
    ],
    [
        'title' => '凭证待补',
        'value' => (string) count($attachmentBacklogRows) . ' 条',
        'hint' => '补齐截图、发票、回单',
        'link' => 'index.php?page=finance&tab=transactions',
    ],
];
?>
<section class="panel smart-entry">
    <div class="table-panel__header">
        <div class="table-panel__main">
            <div class="tabbar">
                <span class="tabbar__item is-active">一句话智能记账</span>
            </div>
            <p class="table-panel__desc">可以直接输入“今天给某某付款 100 元”“收到某客户回款 5000 元”这种中文句子，系统会自动生成一笔流水。没配模型也能先用规则解析。</p>
        </div>
    </div>

    <form class="smart-form" method="post" action="index.php?page=finance" enctype="multipart/form-data">
        <input type="hidden" name="action" value="smart_bookkeeping">
        <textarea name="smart_text" rows="4" placeholder="例如：今天给晨光办公付款 100 元，微信支付，买办公用品。&#10;例如：昨天收到星环科技回款 50000 元，银行转账，官网项目尾款。"></textarea>
        <div class="smart-form__side">
            <div class="smart-mode-card">
                <strong><?= h($aiConfigured ? '当前会先调用大模型，再按规则校验落账' : '当前未配置模型，先按规则解析入账'); ?></strong>
                <p><?= h($aiConfigured ? '模型会按固定字段输出 JSON，再写入系统；解析不完整时才回退规则。' : '建议先到智能工作台配置兼容 OpenAI 协议模型，记账效果会明显更准。'); ?></p>
            </div>
            <label>
                <span>关联项目</span>
                <select name="project_id">
                    <option value="">自动识别 / 通用事项</option>
                    <?php foreach ($data['projects'] as $project): ?>
                        <option value="<?= h((string) $project['id']); ?>"><?= h((string) $project['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>付款附件</span>
                <input type="file" name="attachments[]" accept="image/*" multiple>
            </label>
            <p class="upload-note">支持上传付款截图、发票照片、小票图片。单张不超过 8MB。</p>
            <button class="button button--primary" type="submit">智能记一笔</button>
        </div>
    </form>
    <div class="smart-bottom-bar">
        <div class="smart-entry__meta">
            <span class="toolbar-tag toolbar-tag--primary"><?= h($aiConfigured ? '大模型优先入账' : '规则兜底入账'); ?></span>
            <span class="toolbar-tag"><?= h($aiConfigured ? ((string) $aiSettings['model']) : '未配置模型'); ?></span>
            <?php if (!$aiConfigured): ?>
                <a class="toolbar-tag toolbar-tag--link" href="index.php?page=dashboard">去配置模型</a>
            <?php endif; ?>
        </div>
        <div class="smart-example-list">
            <?php foreach ($smartExamples as $example): ?>
                <button class="smart-example" type="button" data-fill-target="smart_text" data-fill-value="<?= h($example); ?>"><?= h($example); ?></button>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<div class="modal" id="transaction-modal" data-modal>
    <div class="modal__dialog">
        <div class="modal__header">
            <div>
                <h3>新增流水</h3>
                <p>录完直接回到表格，不跳到新页面。</p>
            </div>
            <button class="modal__close" type="button" data-modal-close>&times;</button>
        </div>

        <form class="form-grid" method="post" action="index.php?page=finance" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_transaction">
            <input type="hidden" name="return_tab" value="transactions">

            <label>
                <span>日期</span>
                <input type="date" name="date" value="<?= h(date('Y-m-d')); ?>" required>
            </label>

            <label>
                <span>类型</span>
                <select name="type" required>
                    <?php foreach (transaction_type_options() as $value => $label): ?>
                        <option value="<?= h($value); ?>"><?= h($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                <span>分类</span>
                <input type="text" name="category" list="transaction-categories" placeholder="如：项目回款、工资发放" required>
            </label>

            <label>
                <span>往来方</span>
                <input type="text" name="counterparty" placeholder="客户、供应商或内部团队" required>
            </label>

            <label>
                <span>金额</span>
                <input type="number" name="amount" min="0" step="0.01" placeholder="0.00" required>
            </label>

            <label>
                <span>支付方式</span>
                <select name="payment_method" required>
                    <?php foreach (payment_method_options() as $value => $label): ?>
                        <option value="<?= h($value); ?>"><?= h($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="form-grid__wide">
                <span>关联项目</span>
                <select name="project_id">
                    <option value="">通用事项</option>
                    <?php foreach ($data['projects'] as $project): ?>
                        <option value="<?= h((string) $project['id']); ?>"><?= h((string) $project['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="form-grid__wide">
                <span>附件图片</span>
                <input type="file" name="attachments[]" accept="image/*" multiple>
            </label>

            <label class="form-grid__wide">
                <span>备注</span>
                <textarea name="notes" rows="3" placeholder="补充业务背景、付款说明或节点信息"></textarea>
            </label>

            <div class="modal__footer">
                <button class="button button--default" type="button" data-modal-close>取消</button>
                <button class="button button--primary" type="submit">保存流水</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="invoice-modal" data-modal>
    <div class="modal__dialog">
        <div class="modal__header">
            <div>
                <h3>新增应收 / 应付</h3>
                <p>录入完成后继续在表格里更新状态、附件和备注。</p>
            </div>
            <button class="modal__close" type="button" data-modal-close>&times;</button>
        </div>

        <form class="form-grid" method="post" action="index.php?page=finance" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_invoice">
            <input type="hidden" name="return_tab" value="invoices">

            <label>
                <span>类型</span>
                <select name="kind" required>
                    <?php foreach (invoice_kind_options() as $value => $label): ?>
                        <option value="<?= h($value); ?>"><?= h($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                <span>状态</span>
                <select name="status" required>
                    <option value="pending">待处理</option>
                    <option value="partial">部分结清</option>
                    <option value="paid">已完成</option>
                </select>
            </label>

            <label class="form-grid__wide">
                <span>标题</span>
                <input type="text" name="title" placeholder="如：官网项目二期尾款" required>
            </label>

            <label>
                <span>往来方</span>
                <input type="text" name="counterparty" placeholder="客户或供应商名称" required>
            </label>

            <label>
                <span>金额</span>
                <input type="number" name="amount" min="0" step="0.01" placeholder="0.00" required>
            </label>

            <label>
                <span>到期日</span>
                <input type="date" name="due_date" required>
            </label>

            <label>
                <span>关联项目</span>
                <select name="project_id">
                    <option value="">通用事项</option>
                    <?php foreach ($data['projects'] as $project): ?>
                        <option value="<?= h((string) $project['id']); ?>"><?= h((string) $project['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="form-grid__wide">
                <span>附件图片</span>
                <input type="file" name="attachments[]" accept="image/*" multiple>
            </label>

            <label class="form-grid__wide">
                <span>备注</span>
                <textarea name="notes" rows="3" placeholder="补充合同节点、开票信息或付款说明"></textarea>
            </label>

            <div class="modal__footer">
                <button class="button button--default" type="button" data-modal-close>取消</button>
                <button class="button button--primary" type="submit">保存记录</button>
            </div>
        </form>
    </div>
</div>

<?php if ($attachingTransaction !== null): ?>
    <div class="modal is-open">
        <div class="modal__dialog modal__dialog--compact">
            <div class="modal__header">
                <div>
                    <h3>补传流水附件</h3>
                    <p>不改账单主体，只处理图片附件。</p>
                </div>
                <a class="modal__close" href="index.php?page=finance&tab=transactions">&times;</a>
            </div>

            <form class="form-grid" method="post" action="index.php?page=finance" enctype="multipart/form-data">
                <input type="hidden" name="action" value="append_transaction_attachments">
                <input type="hidden" name="return_tab" value="transactions">
                <input type="hidden" name="transaction_id" value="<?= h((string) $attachingTransaction['id']); ?>">

                <div class="form-grid__wide">
                    <span class="form-label">当前附件</span>
                    <?php $renderAttachmentEditor($attachingTransaction); ?>
                </div>
                <label class="form-grid__wide">
                    <span>新增附件图片</span>
                    <input type="file" name="attachments[]" accept="image/*" multiple>
                </label>

                <div class="modal__footer">
                    <a class="button button--default" href="index.php?page=finance&tab=transactions">取消</a>
                    <button class="button button--primary" type="submit">保存附件</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($editingTransaction !== null): ?>
    <div class="modal is-open">
        <div class="modal__dialog">
            <div class="modal__header">
                <div>
                    <h3>编辑流水</h3>
                    <p>可以修改金额、备注、项目和附件，删除附件会同步清理文件。</p>
                </div>
                <a class="modal__close" href="index.php?page=finance&tab=transactions">&times;</a>
            </div>

            <form class="form-grid" method="post" action="index.php?page=finance" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_transaction">
                <input type="hidden" name="return_tab" value="transactions">
                <input type="hidden" name="transaction_id" value="<?= h((string) $editingTransaction['id']); ?>">

                <label>
                    <span>日期</span>
                    <input type="date" name="date" value="<?= h((string) $editingTransaction['date']); ?>" required>
                </label>
                <label>
                    <span>类型</span>
                    <select name="type" required>
                        <?php foreach (transaction_type_options() as $value => $label): ?>
                            <option value="<?= h($value); ?>" <?= selected_if((string) $editingTransaction['type'], $value); ?>><?= h($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>分类</span>
                    <input type="text" name="category" list="transaction-categories" value="<?= h((string) $editingTransaction['category']); ?>" required>
                </label>
                <label>
                    <span>往来方</span>
                    <input type="text" name="counterparty" value="<?= h((string) $editingTransaction['counterparty']); ?>" required>
                </label>
                <label>
                    <span>金额</span>
                    <input type="number" name="amount" min="0" step="0.01" value="<?= h((string) $editingTransaction['amount']); ?>" required>
                </label>
                <label>
                    <span>支付方式</span>
                    <select name="payment_method" required>
                        <?php foreach (payment_method_options() as $value => $label): ?>
                            <option value="<?= h($value); ?>" <?= selected_if((string) $editingTransaction['payment_method'], $value); ?>><?= h($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="form-grid__wide">
                    <span>关联项目</span>
                    <select name="project_id">
                        <option value="">通用事项</option>
                        <?php foreach ($data['projects'] as $project): ?>
                            <option value="<?= h((string) $project['id']); ?>" <?= selected_if((string) $editingTransaction['project_id'], (string) $project['id']); ?>><?= h((string) $project['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="form-grid__wide">
                    <span class="form-label">现有附件</span>
                    <?php $renderAttachmentEditor($editingTransaction); ?>
                </div>
                <label class="form-grid__wide">
                    <span>新增附件图片</span>
                    <input type="file" name="attachments[]" accept="image/*" multiple>
                </label>
                <label class="form-grid__wide">
                    <span>备注</span>
                    <textarea name="notes" rows="3"><?= h((string) $editingTransaction['notes']); ?></textarea>
                </label>

                <div class="modal__footer">
                    <a class="button button--default" href="index.php?page=finance&tab=transactions">取消</a>
                    <button class="button button--primary" type="submit">保存修改</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($attachingInvoice !== null): ?>
    <div class="modal is-open">
        <div class="modal__dialog modal__dialog--compact">
            <div class="modal__header">
                <div>
                    <h3>补传应收 / 应付附件</h3>
                    <p>适合后续补发票、付款截图或合同照片。</p>
                </div>
                <a class="modal__close" href="index.php?page=finance&tab=invoices">&times;</a>
            </div>

            <form class="form-grid" method="post" action="index.php?page=finance" enctype="multipart/form-data">
                <input type="hidden" name="action" value="append_invoice_attachments">
                <input type="hidden" name="return_tab" value="invoices">
                <input type="hidden" name="invoice_id" value="<?= h((string) $attachingInvoice['id']); ?>">

                <div class="form-grid__wide">
                    <span class="form-label">当前附件</span>
                    <?php $renderAttachmentEditor($attachingInvoice); ?>
                </div>
                <label class="form-grid__wide">
                    <span>新增附件图片</span>
                    <input type="file" name="attachments[]" accept="image/*" multiple>
                </label>

                <div class="modal__footer">
                    <a class="button button--default" href="index.php?page=finance&tab=invoices">取消</a>
                    <button class="button button--primary" type="submit">保存附件</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($editingInvoice !== null): ?>
    <div class="modal is-open">
        <div class="modal__dialog">
            <div class="modal__header">
                <div>
                    <h3>编辑应收 / 应付</h3>
                    <p>可以改金额、状态、到期日、备注和附件。</p>
                </div>
                <a class="modal__close" href="index.php?page=finance&tab=invoices">&times;</a>
            </div>

            <form class="form-grid" method="post" action="index.php?page=finance" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_invoice">
                <input type="hidden" name="return_tab" value="invoices">
                <input type="hidden" name="invoice_id" value="<?= h((string) $editingInvoice['id']); ?>">

                <label>
                    <span>类型</span>
                    <select name="kind" required>
                        <?php foreach (invoice_kind_options() as $value => $label): ?>
                            <option value="<?= h($value); ?>" <?= selected_if((string) $editingInvoice['kind'], $value); ?>><?= h($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>状态</span>
                    <select name="status" required>
                        <?php foreach (invoice_status_options((string) $editingInvoice['kind']) as $value => $label): ?>
                            <option value="<?= h($value); ?>" <?= selected_if((string) $editingInvoice['status'], $value); ?>><?= h($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="form-grid__wide">
                    <span>标题</span>
                    <input type="text" name="title" value="<?= h((string) $editingInvoice['title']); ?>" required>
                </label>
                <label>
                    <span>往来方</span>
                    <input type="text" name="counterparty" value="<?= h((string) $editingInvoice['counterparty']); ?>" required>
                </label>
                <label>
                    <span>金额</span>
                    <input type="number" name="amount" min="0" step="0.01" value="<?= h((string) $editingInvoice['amount']); ?>" required>
                </label>
                <label>
                    <span>到期日</span>
                    <input type="date" name="due_date" value="<?= h((string) $editingInvoice['due_date']); ?>" required>
                </label>
                <label>
                    <span>关联项目</span>
                    <select name="project_id">
                        <option value="">通用事项</option>
                        <?php foreach ($data['projects'] as $project): ?>
                            <option value="<?= h((string) $project['id']); ?>" <?= selected_if((string) $editingInvoice['project_id'], (string) $project['id']); ?>><?= h((string) $project['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="form-grid__wide">
                    <span class="form-label">现有附件</span>
                    <?php $renderAttachmentEditor($editingInvoice); ?>
                </div>
                <label class="form-grid__wide">
                    <span>新增附件图片</span>
                    <input type="file" name="attachments[]" accept="image/*" multiple>
                </label>
                <label class="form-grid__wide">
                    <span>备注</span>
                    <textarea name="notes" rows="3"><?= h((string) $editingInvoice['notes']); ?></textarea>
                </label>

                <div class="modal__footer">
                    <a class="button button--default" href="index.php?page=finance&tab=invoices">取消</a>
                    <button class="button button--primary" type="submit">保存修改</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<section class="todo-board">
    <section class="mini-panel">
        <div class="mini-panel__header">
            <h4>财务待办</h4>
            <p>先做回款、付款和到期跟进。</p>
        </div>
        <div class="todo-list">
            <?php foreach ($pendingFinanceTodos as $row): ?>
                <a class="todo-item" href="index.php?page=finance&tab=invoices&edit_invoice_id=<?= h((string) $row['id']); ?>">
                    <span class="todo-item__tag"><?= h((string) $row['kind'] === 'receivable' ? '应收' : '应付'); ?></span>
                    <div class="todo-item__body">
                        <strong><?= h((string) $row['title']); ?></strong>
                        <p><?= h((string) $row['counterparty']); ?> · <?= h(display_date((string) $row['due_date'])); ?> · <?= h(money((float) $row['amount'])); ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
            <?php if ($pendingFinanceTodos === []): ?>
                <div class="empty-state">当前没有待处理财务单据。</div>
            <?php endif; ?>
        </div>
    </section>

    <section class="mini-panel">
        <div class="mini-panel__header">
            <h4>凭证待补</h4>
            <p>付款截图、发票、回单尽量跟单走。</p>
        </div>
        <div class="todo-list">
            <?php foreach ($attachmentBacklogRows as $row): ?>
                <a class="todo-item" href="<?= h((string) $row['link']); ?>">
                    <span class="todo-item__tag todo-item__tag--plain"><?= h((string) $row['kind']); ?></span>
                    <div class="todo-item__body">
                        <strong><?= h((string) $row['label']); ?></strong>
                        <p><?= h((string) $row['hint']); ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
            <?php if ($attachmentBacklogRows === []): ?>
                <div class="empty-state">当前没有待补附件的记录。</div>
            <?php endif; ?>
        </div>
    </section>

    <section class="mini-panel">
        <div class="mini-panel__header">
            <h4>财务动作</h4>
            <p>把常做动作做成经营动作卡。</p>
        </div>
        <div class="action-card-list">
            <?php foreach ($financeActionCards as $card): ?>
                <a class="action-card" href="<?= h((string) $card['link']); ?>">
                    <span><?= h((string) $card['title']); ?></span>
                    <strong><?= h((string) $card['value']); ?></strong>
                    <p><?= h((string) $card['hint']); ?></p>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
</section>

<datalist id="transaction-categories">
    <?php foreach (transaction_category_suggestions() as $category): ?>
        <option value="<?= h($category); ?>"></option>
    <?php endforeach; ?>
</datalist>

<?php render_stats_grid($financeStats, 'stats-grid--four'); ?>

<section class="panel">
    <div class="table-panel__header">
        <div class="table-panel__main">
            <div class="tabbar">
                <a class="tabbar__item <?= $financeTab === 'transactions' ? 'is-active' : ''; ?>" href="index.php?page=finance&tab=transactions">流水台账</a>
                <a class="tabbar__item <?= $financeTab === 'invoices' ? 'is-active' : ''; ?>" href="index.php?page=finance&tab=invoices">应收应付</a>
            </div>
            <p class="table-panel__desc"><?= $financeTab === 'transactions' ? '按流水维度看收支记录，支持附件、编辑和删除。' : '按应收应付维度跟进回款、付款、逾期、附件和节点。'; ?></p>
        </div>

        <div class="toolbar__group toolbar__group--wrap">
            <a class="button button--primary" href="#" data-modal-open="transaction-modal">新增流水</a>
            <a class="button button--default" href="#" data-modal-open="invoice-modal">新增应收应付</a>
        </div>
    </div>

    <div class="quick-links quick-links--dense">
        <a class="quick-link" href="index.php?page=finance&tab=transactions&type=income">
            <strong>只看收入</strong>
            <span>聚焦回款和收入流水</span>
        </a>
        <a class="quick-link" href="index.php?page=finance&tab=transactions&type=expense">
            <strong>只看支出</strong>
            <span>聚焦成本和付款流水</span>
        </a>
        <a class="quick-link" href="index.php?page=finance&tab=invoices&kind=receivable">
            <strong>应收视角</strong>
            <span>先看客户回款</span>
        </a>
        <a class="quick-link" href="index.php?page=finance&tab=invoices&kind=payable">
            <strong>应付视角</strong>
            <span>安排付款节奏</span>
        </a>
        <a class="quick-link" href="index.php?page=finance&tab=invoices&overdue=1">
            <strong>逾期单据</strong>
            <span>优先处理异常账款</span>
        </a>
    </div>

    <div class="report-grid">
        <section class="mini-panel">
            <div class="mini-panel__header">
                <h4>现金流趋势</h4>
                <p>收入和支出趋势图，方便看经营节奏。</p>
            </div>
            <?php render_trend_chart($cashflowRows); ?>
        </section>

        <section class="mini-panel">
            <div class="mini-panel__header">
                <h4>费用分类排行</h4>
                <p>先抓大头成本，再谈优化。</p>
            </div>
            <?php render_rank_list($expenseRankItems); ?>
        </section>

        <section class="mini-panel">
            <div class="mini-panel__header">
                <h4>应收应付结构</h4>
                <p>未结金额和逾期压力一起看。</p>
            </div>
            <?php render_donut_chart($financeDonutSegments, money((float) $dashboard['open_receivables'] + (float) $dashboard['open_payables']), '未结金额'); ?>
        </section>
    </div>
</section>

<section class="panel">
    <form class="filter-bar" method="get" action="index.php">
        <input type="hidden" name="page" value="finance">
        <input type="hidden" name="tab" value="<?= h($financeTab); ?>">

        <label class="filter-field filter-field--keyword">
            <span>关键词</span>
            <input type="text" name="keyword" value="<?= h($keyword); ?>" placeholder="<?= h($financeTab === 'transactions' ? '分类、往来方、备注、项目' : '标题、往来方、备注、项目'); ?>">
        </label>

        <?php if ($financeTab === 'transactions'): ?>
            <label class="filter-field">
                <span>类型</span>
                <select name="type">
                    <option value="">全部</option>
                    <?php foreach (transaction_type_options() as $value => $label): ?>
                        <option value="<?= h($value); ?>" <?= selected_if($typeFilter, $value); ?>><?= h($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php else: ?>
            <label class="filter-field">
                <span>类型</span>
                <select name="kind">
                    <option value="">全部</option>
                    <?php foreach (invoice_kind_options() as $value => $label): ?>
                        <option value="<?= h($value); ?>" <?= selected_if($kindFilter, $value); ?>><?= h($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="filter-field">
                <span>状态</span>
                <select name="status">
                    <option value="">全部</option>
                    <option value="pending" <?= selected_if($statusFilter, 'pending'); ?>>待处理</option>
                    <option value="partial" <?= selected_if($statusFilter, 'partial'); ?>>部分结清</option>
                    <option value="paid" <?= selected_if($statusFilter, 'paid'); ?>>已完成</option>
                </select>
            </label>
        <?php endif; ?>

        <label class="filter-field">
            <span>项目</span>
            <select name="project_id">
                <option value="">全部项目</option>
                <?php foreach ($data['projects'] as $project): ?>
                    <option value="<?= h((string) $project['id']); ?>" <?= selected_if($projectFilter, (string) $project['id']); ?>><?= h((string) $project['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <?php if ($financeTab === 'invoices'): ?>
            <label class="filter-field filter-field--checkbox">
                <span>视角</span>
                <label class="checkline">
                    <input type="checkbox" name="overdue" value="1" <?= checked_if($overdueOnly); ?>>
                    <span>只看逾期</span>
                </label>
            </label>
        <?php endif; ?>

        <div class="filter-actions">
            <button class="button button--primary" type="submit">筛选</button>
            <a class="button button--default" href="index.php?page=finance&tab=<?= h($financeTab); ?>">重置</a>
        </div>
    </form>

    <div class="table-toolbar">
        <div class="table-toolbar__title">
            当前共 <?= h((string) ($financeTab === 'transactions' ? count($filteredTransactions) : count($filteredInvoices))); ?> 条记录
            <?php if ($financeTab === 'invoices' && $overdueOnly): ?>
                <span class="toolbar-tag">逾期视角</span>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($financeTab === 'transactions'): ?>
        <div class="table-shell">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>日期</th>
                        <th>类型</th>
                        <th>分类</th>
                        <th>往来方</th>
                        <th>项目</th>
                        <th>支付方式</th>
                        <th>金额</th>
                        <th>附件</th>
                        <th>备注</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filteredTransactions as $transaction): ?>
                        <?php $attachments = record_attachments($transaction); ?>
                        <tr>
                            <td data-label="日期"><?= h(display_date((string) $transaction['date'])); ?></td>
                            <td data-label="类型">
                                <span class="badge badge--<?= h((string) ($transaction['type'] === 'income' ? 'success' : 'warning')); ?>">
                                    <?= h((string) ($transaction['type'] === 'income' ? '收入' : '支出')); ?>
                                </span>
                            </td>
                            <td data-label="分类"><?= h((string) $transaction['category']); ?></td>
                            <td data-label="往来方"><?= h((string) $transaction['counterparty']); ?></td>
                            <td data-label="项目"><?= h(project_name($projectNameLookup, (string) $transaction['project_id'])); ?></td>
                            <td data-label="支付方式"><?= h(payment_method_options()[(string) $transaction['payment_method']] ?? '其他'); ?></td>
                            <td data-label="金额" class="<?= $transaction['type'] === 'income' ? 'text-success' : 'text-warning'; ?>">
                                <?= $transaction['type'] === 'income' ? '+' : '-'; ?><?= money((float) $transaction['amount']); ?>
                            </td>
                            <td data-label="附件"><?php $renderAttachments($attachments); ?></td>
                            <td data-label="备注"><?= h((string) ($transaction['notes'] === '' ? '-' : trim_text((string) $transaction['notes'], 48))); ?></td>
                            <td data-label="操作">
                                <div class="record-actions">
                                    <a class="button button--default button--small" href="index.php?page=finance&tab=transactions&edit_transaction_id=<?= h((string) $transaction['id']); ?>">编辑</a>
                                    <a class="button button--ghost button--small" href="index.php?page=finance&tab=transactions&attach_transaction_id=<?= h((string) $transaction['id']); ?>"><?= $attachments === [] ? '补传附件' : '附件管理'; ?></a>
                                    <form method="post" action="index.php?page=finance" onsubmit="return confirm('确认删除这笔流水吗？');">
                                        <input type="hidden" name="action" value="delete_transaction">
                                        <input type="hidden" name="return_tab" value="transactions">
                                        <input type="hidden" name="transaction_id" value="<?= h((string) $transaction['id']); ?>">
                                        <button class="button button--danger button--small" type="submit">删除</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (count($filteredTransactions) === 0): ?>
                        <tr>
                            <td colspan="10">
                                <div class="empty-state">没有符合条件的流水记录。</div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="table-shell">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>类型</th>
                        <th>标题</th>
                        <th>往来方</th>
                        <th>项目</th>
                        <th>到期日</th>
                        <th>金额</th>
                        <th>附件</th>
                        <th>状态</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filteredInvoices as $invoice): ?>
                        <?php $attachments = record_attachments($invoice); ?>
                        <tr>
                            <td data-label="类型"><?= h(invoice_kind_label((string) $invoice['kind'])); ?></td>
                            <td data-label="标题">
                                <strong><?= h((string) $invoice['title']); ?></strong>
                                <div class="table-subtext"><?= h((string) ($invoice['notes'] === '' ? '-' : trim_text((string) $invoice['notes'], 48))); ?></div>
                            </td>
                            <td data-label="往来方"><?= h((string) $invoice['counterparty']); ?></td>
                            <td data-label="项目"><?= h((string) $invoice['project_name']); ?></td>
                            <td data-label="到期日"><?= h(display_date((string) $invoice['due_date'])); ?></td>
                            <td data-label="金额"><?= money((float) $invoice['amount']); ?></td>
                            <td data-label="附件"><?php $renderAttachments($attachments); ?></td>
                            <td data-label="状态">
                                <span class="badge badge--<?= h(invoice_status_tone((string) $invoice['status'], (bool) $invoice['overdue'])); ?>">
                                    <?= h((bool) $invoice['overdue'] ? '已逾期' : invoice_status_label((string) $invoice['kind'], (string) $invoice['status'])); ?>
                                </span>
                            </td>
                            <td data-label="操作">
                                <div class="record-actions record-actions--stack">
                                    <form class="inline-form" method="post" action="index.php?page=finance">
                                        <input type="hidden" name="action" value="update_invoice_status">
                                        <input type="hidden" name="return_tab" value="invoices">
                                        <input type="hidden" name="invoice_id" value="<?= h((string) $invoice['id']); ?>">
                                        <input type="hidden" name="kind" value="<?= h((string) $invoice['kind']); ?>">
                                        <select name="status">
                                            <?php foreach (invoice_status_options((string) $invoice['kind']) as $value => $label): ?>
                                                <option value="<?= h($value); ?>" <?= selected_if((string) $invoice['status'], $value); ?>><?= h($label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="button button--default button--small" type="submit">更新状态</button>
                                    </form>
                                    <div class="record-actions">
                                        <a class="button button--default button--small" href="index.php?page=finance&tab=invoices&edit_invoice_id=<?= h((string) $invoice['id']); ?>">编辑</a>
                                        <a class="button button--ghost button--small" href="index.php?page=finance&tab=invoices&attach_invoice_id=<?= h((string) $invoice['id']); ?>"><?= $attachments === [] ? '补传附件' : '附件管理'; ?></a>
                                        <form method="post" action="index.php?page=finance" onsubmit="return confirm('确认删除这条应收/应付记录吗？');">
                                            <input type="hidden" name="action" value="delete_invoice">
                                            <input type="hidden" name="return_tab" value="invoices">
                                            <input type="hidden" name="invoice_id" value="<?= h((string) $invoice['id']); ?>">
                                            <button class="button button--danger button--small" type="submit">删除</button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (count($filteredInvoices) === 0): ?>
                        <tr>
                            <td colspan="9">
                                <div class="empty-state">没有符合条件的应收应付记录。</div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
