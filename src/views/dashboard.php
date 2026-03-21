<?php

$openInvoices = array_slice(array_values(array_filter(
    $invoiceRows,
    static fn(array $row): bool => (string) $row['status'] !== 'paid'
)), 0, 6);

$urgentTasks = array_slice(array_values(array_filter(
    $taskRows,
    static fn(array $row): bool => (bool) $row['overdue'] || (string) $row['status'] !== 'done'
)), 0, 6);

$overviewStats = [
    ['label' => '累计收入', 'value' => money((float) $dashboard['total_income']), 'hint' => '系统已登记收入总额', 'tone' => 'success'],
    ['label' => '累计支出', 'value' => money((float) $dashboard['total_expense']), 'hint' => '系统已登记支出总额', 'tone' => 'warning'],
    ['label' => '净现金流', 'value' => money((float) $dashboard['net_cashflow']), 'hint' => '收入减去支出后的结果', 'tone' => (float) $dashboard['net_cashflow'] >= 0 ? 'info' : 'danger'],
    ['label' => '待回款', 'value' => money((float) $dashboard['open_receivables']), 'hint' => '未完成应收合计', 'tone' => 'info'],
    ['label' => '进行中项目', 'value' => (string) $dashboard['active_projects'] . ' 个', 'hint' => '规划中、进行中和交付中的项目', 'tone' => 'neutral'],
    ['label' => '逾期任务', 'value' => (string) $dashboard['overdue_tasks'] . ' 项', 'hint' => '需要优先处理的执行风险', 'tone' => (int) $dashboard['overdue_tasks'] > 0 ? 'danger' : 'success'],
];

$expenseMax = max(array_map(static fn(array $row): float => (float) $row['amount'], $expenseRows) ?: [1.0]);
$expenseRankItems = array_map(static function (array $row) use ($expenseMax): array {
    return [
        'label' => (string) $row['category'],
        'value' => money((float) $row['amount']),
        'hint' => '支出分类占比',
        'percent' => percent((float) $row['amount'], $expenseMax, 100.0),
        'tone' => 'warning',
    ];
}, $expenseRows);

$projectRiskMax = max(array_map(static fn(array $row): float => max((float) $row['budget_usage'], (float) $row['progress']), $projectHealthRows) ?: [1.0]);
$projectRiskItems = array_map(static function (array $project) use ($projectRiskMax): array {
    $riskTone = (int) $project['risk_score'] >= 4 ? 'danger' : ((int) $project['risk_score'] >= 2 ? 'warning' : 'success');
    $hint = project_status_label((string) $project['status']) . ' · 进度 ' . $project['progress'] . '%';
    if ((bool) $project['due_soon']) {
        $hint .= ' · 即将到期';
    }
    if ((int) $project['task_overdue'] > 0) {
        $hint .= ' · 逾期任务 ' . $project['task_overdue'] . ' 项';
    }

    return [
        'label' => (string) $project['name'],
        'value' => number_format((float) $project['budget_usage'], 1) . '%',
        'hint' => $hint,
        'percent' => percent(max((float) $project['budget_usage'], (float) $project['progress']), $projectRiskMax, 100.0),
        'tone' => $riskTone,
    ];
}, $projectHealthRows);

$assigneeMax = max(array_map(static fn(array $row): float => (float) $row['open_tasks'], $assigneeLoadRows) ?: [1.0]);
$assigneeItems = array_map(static function (array $row) use ($assigneeMax): array {
    $hint = '预估 ' . number_format((float) $row['estimate_hours'], 1) . 'h';
    if ((int) $row['overdue_tasks'] > 0) {
        $hint .= ' · 逾期 ' . $row['overdue_tasks'] . ' 项';
    }

    return [
        'label' => (string) $row['assignee'],
        'value' => (string) $row['open_tasks'] . ' 项',
        'hint' => $hint,
        'percent' => percent((float) $row['open_tasks'], $assigneeMax, 100.0),
        'tone' => (int) $row['overdue_tasks'] > 0 ? 'danger' : 'info',
    ];
}, $assigneeLoadRows);

$collectionSegments = [
    [
        'label' => '待回款',
        'value' => (float) $dashboard['open_receivables'],
        'display' => money((float) $dashboard['open_receivables']),
        'tone' => 'info',
    ],
    [
        'label' => '待付款',
        'value' => (float) $dashboard['open_payables'],
        'display' => money((float) $dashboard['open_payables']),
        'tone' => 'warning',
    ],
    [
        'label' => '逾期应收',
        'value' => (float) $dashboard['overdue_receivables'],
        'display' => money((float) $dashboard['overdue_receivables']),
        'tone' => 'danger',
    ],
];

$taskSegments = array_map(static function (array $row): array {
    return [
        'label' => (string) $row['label'],
        'value' => (float) $row['count'],
        'display' => (string) $row['count'] . ' 项',
        'tone' => (string) $row['tone'],
    ];
}, $taskSummary);
?>

<section class="workspace-toolbar">
    <div class="toolbar__group toolbar__group--wrap">
        <button class="button button--default" type="button" data-window-toggle="window-overview">经营总览</button>
        <button class="button button--default" type="button" data-window-toggle="window-reports">统计报表</button>
        <button class="button button--default" type="button" data-window-toggle="window-finance">财务窗口</button>
        <button class="button button--default" type="button" data-window-toggle="window-projects">项目窗口</button>
        <button class="button button--default" type="button" data-window-toggle="window-tasks">任务窗口</button>
        <button class="button button--default" type="button" data-window-toggle="window-ai">AI 助手</button>
    </div>
    <div class="toolbar__group toolbar__group--wrap">
        <a class="button button--primary" href="#" data-modal-open="ai-settings-modal">模型设置</a>
        <form method="post" action="index.php?page=dashboard">
            <input type="hidden" name="action" value="clear_ai_conversation">
            <button class="button button--default" type="submit">清空对话</button>
        </form>
    </div>
</section>

<section class="dashboard-layout">
    <div class="dashboard-main">
        <article class="workspace-window" id="window-overview" data-window>
            <?php
            render_window_header(
                'Overview',
                '经营驾驶舱',
                '从经营、回款、交付和执行四个角度看当前业务状态。',
                '<div class="toolbar__group"><a class="button button--default" href="index.php?page=finance&tab=invoices">查看回款</a><a class="button button--default" href="index.php?page=projects&tab=tasks">查看任务</a></div>'
            );
            ?>
            <div class="workspace-window__body">
                <?php render_stats_grid($overviewStats); ?>
                <div class="detail-grid">
                    <section class="mini-panel">
                        <div class="mini-panel__header">
                            <h4>经营提醒</h4>
                            <p>更符合老板和财务负责人日常查看习惯的重点提示。</p>
                        </div>
                        <?php render_alert_list($businessAlerts); ?>
                    </section>
                    <section class="mini-panel">
                        <div class="mini-panel__header">
                            <h4>快捷入口</h4>
                            <p>常用动作直接跳转，不用反复找模块。</p>
                        </div>
                        <div class="quick-links">
                            <a class="quick-link" href="index.php?page=finance&tab=transactions&type=income">
                                <strong>收入流水</strong>
                                <span>只看收入记录</span>
                            </a>
                            <a class="quick-link" href="index.php?page=finance&tab=invoices&kind=receivable">
                                <strong>待回款</strong>
                                <span>进入应收视图</span>
                            </a>
                            <a class="quick-link" href="index.php?page=projects&tab=projects&status=active">
                                <strong>进行中项目</strong>
                                <span>聚焦进行中的项目</span>
                            </a>
                            <a class="quick-link" href="index.php?page=projects&tab=tasks&overdue=1">
                                <strong>逾期任务</strong>
                                <span>优先清理风险任务</span>
                            </a>
                        </div>
                    </section>
                </div>
            </div>
        </article>

        <article class="workspace-window" id="window-reports" data-window>
            <?php render_window_header('Reports', '统计报表', '补足趋势图、结构图和风险排名，避免只剩表格。'); ?>
            <div class="workspace-window__body">
                <div class="report-grid">
                    <section class="mini-panel">
                        <div class="mini-panel__header">
                            <h4>近 6 个月现金流趋势</h4>
                            <p>收入与支出对比，净额直接写在柱图下方。</p>
                        </div>
                        <?php render_trend_chart($cashflowRows); ?>
                    </section>

                    <section class="mini-panel">
                        <div class="mini-panel__header">
                            <h4>费用分类排行</h4>
                            <p>先盯大头支出，再做成本优化。</p>
                        </div>
                        <?php render_rank_list($expenseRankItems); ?>
                    </section>

                    <section class="mini-panel">
                        <div class="mini-panel__header">
                            <h4>应收应付结构</h4>
                            <p>在途款项和逾期压力一眼看清。</p>
                        </div>
                        <?php render_donut_chart($collectionSegments, money((float) $dashboard['open_receivables'] + (float) $dashboard['open_payables']), '在途款项'); ?>
                    </section>

                    <section class="mini-panel">
                        <div class="mini-panel__header">
                            <h4>任务状态分布</h4>
                            <p>执行层面是否顺畅，先看状态再看负责人。</p>
                        </div>
                        <?php render_donut_chart($taskSegments, (string) count($taskRows) . ' 项', '任务总数'); ?>
                    </section>
                </div>
            </div>
        </article>

        <article class="workspace-window" id="window-finance" data-window>
            <?php
            render_window_header(
                'Finance',
                '财务窗口',
                '待回款、近期付款和最近流水放在一起，不用来回切页。',
                '<a class="button button--default" href="index.php?page=finance&tab=transactions">进入财务台账</a>'
            );
            ?>
            <div class="workspace-window__body">
                <div class="quick-links quick-links--dense">
                    <a class="quick-link" href="index.php?page=finance&tab=invoices&kind=receivable&status=pending">
                        <strong>应收待跟进</strong>
                        <span><?= h((string) ($invoiceSummary['receivable']['pending_count'] + $invoiceSummary['receivable']['partial_count'])); ?> 笔未结</span>
                    </a>
                    <a class="quick-link" href="index.php?page=finance&tab=invoices&overdue=1">
                        <strong>逾期单据</strong>
                        <span><?= money((float) $dashboard['overdue_receivables'] + (float) $dashboard['overdue_payables']); ?></span>
                    </a>
                    <a class="quick-link" href="index.php?page=finance&tab=transactions&type=expense">
                        <strong>支出流水</strong>
                        <span>查看最近成本支出</span>
                    </a>
                </div>
                <div class="workspace-split">
                    <section class="mini-panel">
                        <div class="mini-panel__header">
                            <h4>未来 15 天到期单据</h4>
                            <p>优先看逾期和即将到期的回款付款节点。</p>
                        </div>
                        <div class="stack-list">
                            <?php foreach (array_slice($dueInvoiceRows, 0, 6) as $invoice): ?>
                                <div class="stack-item">
                                    <div>
                                        <strong><?= h((string) $invoice['title']); ?></strong>
                                        <p><?= h(invoice_kind_label((string) $invoice['kind'])); ?> · <?= h((string) $invoice['counterparty']); ?></p>
                                    </div>
                                    <div class="stack-item__meta">
                                        <?php render_chip((bool) $invoice['overdue'] ? '已逾期' : display_date((string) $invoice['due_date']), (bool) $invoice['overdue'] ? 'danger' : 'warning'); ?>
                                        <strong><?= money((float) $invoice['amount']); ?></strong>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php if ($dueInvoiceRows === []): ?>
                                <div class="empty-state">近 15 天没有待处理单据。</div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <section class="mini-panel">
                        <div class="mini-panel__header">
                            <h4>最近流水</h4>
                            <p>适合快速看收支节奏和异常波动。</p>
                        </div>
                        <div class="table-shell">
                            <table class="data-table data-table--compact">
                                <thead>
                                    <tr>
                                        <th>日期</th>
                                        <th>分类</th>
                                        <th>往来方</th>
                                        <th>金额</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentTransactions as $transaction): ?>
                                        <tr>
                                            <td data-label="日期"><?= h(display_date((string) $transaction['date'])); ?></td>
                                            <td data-label="分类"><?= h((string) $transaction['category']); ?></td>
                                            <td data-label="往来方"><?= h((string) $transaction['counterparty']); ?></td>
                                            <td data-label="金额" class="<?= $transaction['type'] === 'income' ? 'text-success' : 'text-warning'; ?>">
                                                <?= $transaction['type'] === 'income' ? '+' : '-'; ?><?= money((float) $transaction['amount']); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>
        </article>

        <article class="workspace-window" id="window-projects" data-window>
            <?php
            render_window_header(
                'Projects',
                '项目窗口',
                '先看高风险项目，再看负责人和预算消耗。',
                '<a class="button button--default" href="index.php?page=projects&tab=projects">进入项目台账</a>'
            );
            ?>
            <div class="workspace-window__body">
                <div class="workspace-split">
                    <section class="mini-panel">
                        <div class="mini-panel__header">
                            <h4>项目风险排行</h4>
                            <p>预算、进度、到期和逾期任务综合排序。</p>
                        </div>
                        <?php render_rank_list($projectRiskItems); ?>
                    </section>

                    <section class="mini-panel">
                        <div class="mini-panel__header">
                            <h4>项目概览</h4>
                            <p>从项目视角看预算与回款。</p>
                        </div>
                        <div class="table-shell">
                            <table class="data-table data-table--compact">
                                <thead>
                                    <tr>
                                        <th>项目</th>
                                        <th>状态</th>
                                        <th>预算</th>
                                        <th>进度</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($projectRows, 0, 6) as $project): ?>
                                        <tr>
                                            <td data-label="项目">
                                                <strong><?= h((string) $project['name']); ?></strong>
                                                <div class="table-subtext"><?= h((string) $project['owner']); ?></div>
                                            </td>
                                            <td data-label="状态"><?php render_chip(project_status_label((string) $project['status']), project_status_tone((string) $project['status'])); ?></td>
                                            <td data-label="预算"><?= money((float) $project['budget']); ?></td>
                                            <td data-label="进度">
                                                <div class="progress-cell">
                                                    <?php render_progress_bar((float) $project['progress'], 'info'); ?>
                                                    <span><?= h((string) $project['progress']); ?>%</span>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>
        </article>

        <article class="workspace-window" id="window-tasks" data-window>
            <?php
            render_window_header(
                'Tasks',
                '任务窗口',
                '把负责人负荷和紧急任务拆开看，更适合国内团队协作节奏。',
                '<a class="button button--default" href="index.php?page=projects&tab=tasks">进入任务台账</a>'
            );
            ?>
            <div class="workspace-window__body">
                <div class="workspace-split">
                    <section class="mini-panel">
                        <div class="mini-panel__header">
                            <h4>负责人负荷</h4>
                            <p>看谁任务堆积、谁还在消化逾期。</p>
                        </div>
                        <?php render_rank_list($assigneeItems); ?>
                    </section>

                    <section class="mini-panel">
                        <div class="mini-panel__header">
                            <h4>紧急任务清单</h4>
                            <p>按逾期优先，再看未完成任务。</p>
                        </div>
                        <div class="stack-list">
                            <?php foreach ($urgentTasks as $task): ?>
                                <div class="stack-item">
                                    <div>
                                        <strong><?= h((string) $task['title']); ?></strong>
                                        <p><?= h((string) $task['project_name']); ?> · <?= h((string) $task['assignee']); ?></p>
                                    </div>
                                    <div class="stack-item__meta">
                                        <?php render_chip((bool) $task['overdue'] ? '已逾期' : task_status_label((string) $task['status']), (bool) $task['overdue'] ? 'danger' : task_status_tone((string) $task['status'])); ?>
                                        <strong><?= h(display_date((string) $task['due_date'])); ?></strong>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php if ($urgentTasks === []): ?>
                                <div class="empty-state">当前没有待处理任务。</div>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
            </div>
        </article>
    </div>

    <aside class="dashboard-side">
        <article class="workspace-window workspace-window--sticky" id="window-ai" data-window>
            <?php
            render_window_header(
                'AI Assistant',
                '经营分析助手',
                '默认带上经营指标、应收应付、项目预算、任务负荷和最近流水，让回答更细。',
                '<span class="toolbar-tag">' . h($aiConfigured ? '已连接模型' : '待配置模型') . '</span>'
            );
            ?>
            <div class="workspace-window__body">
                <div class="assistant-status">
                    <div class="assistant-status__main">
                        <strong><?= h($aiConfigured ? ((string) $aiSettings['provider_name']) : '尚未配置模型'); ?></strong>
                        <p><?= h($aiConfigured ? ai_endpoint((string) $aiSettings['base_url']) : '支持 OpenAI 兼容协议，可接 OpenAI、DeepSeek、硅基流动、Ollama 等兼容接口。'); ?></p>
                    </div>
                    <a class="text-link" href="#" data-modal-open="ai-settings-modal">修改设置</a>
                </div>

                <div class="assistant-context">
                    <span>默认喂给模型：</span>
                    <?php render_chip('经营指标', 'info'); ?>
                    <?php render_chip('应收应付', 'warning'); ?>
                    <?php render_chip('项目预算', 'neutral'); ?>
                    <?php render_chip('任务负荷', 'danger'); ?>
                    <?php render_chip('最近流水', 'success'); ?>
                </div>

                <div class="preset-grid">
                    <?php foreach ($aiPresets as $preset): ?>
                        <form method="post" action="index.php?page=dashboard" class="preset-card">
                            <input type="hidden" name="action" value="ask_ai">
                            <input type="hidden" name="question" value="<?= h((string) $preset['prompt']); ?>">
                            <button class="preset-card__button" type="submit">
                                <strong><?= h((string) $preset['label']); ?></strong>
                                <span><?= h((string) $preset['description']); ?></span>
                            </button>
                        </form>
                    <?php endforeach; ?>
                </div>

                <div class="assistant-thread">
                    <?php foreach (ai_conversation($data) as $message): ?>
                        <div class="assistant-message assistant-message--<?= h((string) $message['role']); ?>">
                            <div class="assistant-message__meta">
                                <strong><?= h((string) ($message['role'] === 'user' ? '你' : 'AI')); ?></strong>
                                <span><?= h((string) ($message['created_at'] ?? '')); ?></span>
                            </div>
                            <div class="assistant-message__content"><?= nl2br(h((string) $message['content'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <form class="assistant-form" method="post" action="index.php?page=dashboard">
                    <input type="hidden" name="action" value="ask_ai">
                    <textarea name="question" rows="5" placeholder="例如：请结合当前应收、近 30 天流水和项目预算，分析未来一个月现金流风险，并按今天 / 本周 / 本月给我动作建议。"></textarea>
                    <div class="assistant-form__actions">
                        <button class="button button--primary" type="submit">发送给 AI</button>
                        <span class="assistant-form__hint">适合问回款风险、项目毛利、预算超支、任务优先级和经营日报。</span>
                    </div>
                </form>
            </div>
        </article>
    </aside>
</section>

<div class="modal" id="ai-settings-modal" data-modal>
    <div class="modal__dialog">
        <div class="modal__header">
            <div>
                <h3>模型设置</h3>
                <p>这里接的是 OpenAI 兼容协议。一般填 Base URL、API Key、模型名就能接其他兼容模型。</p>
            </div>
            <button class="modal__close" type="button" data-modal-close>&times;</button>
        </div>

        <form class="form-grid" method="post" action="index.php?page=dashboard">
            <input type="hidden" name="action" value="save_ai_settings">

            <label>
                <span>显示名称</span>
                <input type="text" name="provider_name" value="<?= h((string) $aiSettings['provider_name']); ?>" placeholder="如：OpenAI / DeepSeek / Ollama">
            </label>

            <label>
                <span>模型名称</span>
                <input type="text" name="model" value="<?= h((string) $aiSettings['model']); ?>" placeholder="如：gpt-4.1-mini / deepseek-chat">
            </label>

            <label class="form-grid__wide">
                <span>Base URL</span>
                <input type="text" name="base_url" value="<?= h((string) $aiSettings['base_url']); ?>" placeholder="如：https://api.openai.com/v1 或完整 /chat/completions 地址">
            </label>

            <label class="form-grid__wide">
                <span>API Key</span>
                <input type="password" name="api_key" value="<?= h((string) $aiSettings['api_key']); ?>" placeholder="本地无鉴权服务可留空">
            </label>

            <label>
                <span>Temperature</span>
                <input type="number" name="temperature" min="0" max="2" step="0.1" value="<?= h((string) $aiSettings['temperature']); ?>">
            </label>

            <label class="form-grid__wide">
                <span>系统提示词</span>
                <textarea name="system_prompt" rows="8" placeholder="可补充你自己的输出要求"><?= h((string) $aiSettings['system_prompt']); ?></textarea>
            </label>

            <div class="modal__footer">
                <button class="button button--default" type="button" data-modal-close>取消</button>
                <button class="button button--primary" type="submit">保存设置</button>
            </div>
        </form>
    </div>
</div>
