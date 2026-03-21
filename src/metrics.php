<?php

declare(strict_types=1);

function dashboard_metrics(array $data): array
{
    $income = 0.0;
    $expense = 0.0;

    foreach ($data['transactions'] as $transaction) {
        $amount = (float) $transaction['amount'];

        if ((string) $transaction['type'] === 'income') {
            $income += $amount;
        } else {
            $expense += $amount;
        }
    }

    $openReceivables = 0.0;
    $openPayables = 0.0;
    $overdueReceivables = 0.0;
    $overduePayables = 0.0;

    foreach ($data['invoices'] as $invoice) {
        if ((string) $invoice['status'] === 'paid') {
            continue;
        }

        $amount = (float) $invoice['amount'];
        $isOverdue = is_overdue((string) $invoice['due_date'], (string) $invoice['status'], ['paid']);

        if ((string) $invoice['kind'] === 'receivable') {
            $openReceivables += $amount;
            if ($isOverdue) {
                $overdueReceivables += $amount;
            }
        } else {
            $openPayables += $amount;
            if ($isOverdue) {
                $overduePayables += $amount;
            }
        }
    }

    $activeProjects = 0;
    foreach ($data['projects'] as $project) {
        if (in_array((string) $project['status'], ['planning', 'active', 'delivery'], true)) {
            $activeProjects++;
        }
    }

    $overdueTasks = 0;
    $completedTasks = 0;
    foreach ($data['tasks'] as $task) {
        if ((string) $task['status'] === 'done') {
            $completedTasks++;
        }

        if (is_overdue((string) $task['due_date'], (string) $task['status'], ['done'])) {
            $overdueTasks++;
        }
    }

    return [
        'total_income' => $income,
        'total_expense' => $expense,
        'net_cashflow' => $income - $expense,
        'open_receivables' => $openReceivables,
        'open_payables' => $openPayables,
        'overdue_receivables' => $overdueReceivables,
        'overdue_payables' => $overduePayables,
        'active_projects' => $activeProjects,
        'overdue_tasks' => $overdueTasks,
        'completed_tasks' => $completedTasks,
        'task_total' => count($data['tasks']),
    ];
}

function recent_transactions(array $transactions, int $limit = 12): array
{
    usort($transactions, static function (array $left, array $right): int {
        return strcmp((string) $right['date'], (string) $left['date']);
    });

    return array_slice($transactions, 0, $limit);
}

function monthly_cashflow(array $transactions, int $limit = 6): array
{
    $buckets = [];

    foreach ($transactions as $transaction) {
        $month = substr((string) $transaction['date'], 0, 7);

        if ($month === '') {
            continue;
        }

        if (!isset($buckets[$month])) {
            $buckets[$month] = [
                'month' => $month,
                'income' => 0.0,
                'expense' => 0.0,
                'net' => 0.0,
            ];
        }

        $amount = (float) $transaction['amount'];

        if ((string) $transaction['type'] === 'income') {
            $buckets[$month]['income'] += $amount;
        } else {
            $buckets[$month]['expense'] += $amount;
        }

        $buckets[$month]['net'] = $buckets[$month]['income'] - $buckets[$month]['expense'];
    }

    ksort($buckets);

    return array_slice(array_values($buckets), -$limit);
}

function category_breakdown(array $transactions, string $type = 'expense', int $limit = 6): array
{
    $buckets = [];

    foreach ($transactions as $transaction) {
        if ((string) $transaction['type'] !== $type) {
            continue;
        }

        $category = (string) $transaction['category'];

        if (!isset($buckets[$category])) {
            $buckets[$category] = 0.0;
        }

        $buckets[$category] += (float) $transaction['amount'];
    }

    arsort($buckets);

    $rows = [];
    foreach (array_slice($buckets, 0, $limit, true) as $category => $amount) {
        $rows[] = [
            'category' => $category,
            'amount' => $amount,
        ];
    }

    return $rows;
}

function invoice_rows(array $data): array
{
    $lookup = project_lookup($data['projects']);
    $rows = [];

    foreach ($data['invoices'] as $invoice) {
        $invoice['project_name'] = project_name($lookup, (string) ($invoice['project_id'] ?? ''));
        $invoice['overdue'] = is_overdue((string) $invoice['due_date'], (string) $invoice['status'], ['paid']);
        $rows[] = $invoice;
    }

    usort($rows, static function (array $left, array $right): int {
        if ((bool) $left['overdue'] !== (bool) $right['overdue']) {
            return $left['overdue'] ? -1 : 1;
        }

        return strcmp((string) $left['due_date'], (string) $right['due_date']);
    });

    return $rows;
}

function project_summaries(array $data): array
{
    $spentByProject = [];
    $incomeByProject = [];

    foreach ($data['transactions'] as $transaction) {
        $projectId = (string) ($transaction['project_id'] ?? '');

        if ($projectId === '') {
            continue;
        }

        if ((string) $transaction['type'] === 'expense') {
            $spentByProject[$projectId] = ($spentByProject[$projectId] ?? 0.0) + (float) $transaction['amount'];
        }

        if ((string) $transaction['type'] === 'income') {
            $incomeByProject[$projectId] = ($incomeByProject[$projectId] ?? 0.0) + (float) $transaction['amount'];
        }
    }

    $taskStats = [];
    foreach ($data['tasks'] as $task) {
        $projectId = (string) $task['project_id'];

        if (!isset($taskStats[$projectId])) {
            $taskStats[$projectId] = [
                'total' => 0,
                'done' => 0,
                'open' => 0,
                'overdue' => 0,
            ];
        }

        $taskStats[$projectId]['total']++;

        if ((string) $task['status'] === 'done') {
            $taskStats[$projectId]['done']++;
        } else {
            $taskStats[$projectId]['open']++;
        }

        if (is_overdue((string) $task['due_date'], (string) $task['status'], ['done'])) {
            $taskStats[$projectId]['overdue']++;
        }
    }

    $rows = [];
    foreach ($data['projects'] as $project) {
        $projectId = (string) $project['id'];
        $budget = (float) $project['budget'];
        $spent = (float) ($spentByProject[$projectId] ?? 0.0);
        $income = (float) ($incomeByProject[$projectId] ?? 0.0);
        $totalTasks = (int) ($taskStats[$projectId]['total'] ?? 0);
        $doneTasks = (int) ($taskStats[$projectId]['done'] ?? 0);
        $openTasks = (int) ($taskStats[$projectId]['open'] ?? 0);
        $overdueTasks = (int) ($taskStats[$projectId]['overdue'] ?? 0);

        $project['spent'] = $spent;
        $project['income'] = $income;
        $project['margin'] = $income - $spent;
        $project['budget_usage'] = percent($spent, $budget, 100.0);
        $project['progress'] = $totalTasks > 0 ? round(($doneTasks / $totalTasks) * 100) : 0;
        $project['task_total'] = $totalTasks;
        $project['task_done'] = $doneTasks;
        $project['task_open'] = $openTasks;
        $project['task_overdue'] = $overdueTasks;

        $rows[] = $project;
    }

    usort($rows, static function (array $left, array $right): int {
        return strcmp((string) $left['due_date'], (string) $right['due_date']);
    });

    return $rows;
}

function task_rows(array $data): array
{
    $lookup = project_lookup($data['projects']);
    $rows = [];

    foreach ($data['tasks'] as $task) {
        $task['project_name'] = project_name($lookup, (string) $task['project_id']);
        $task['overdue'] = is_overdue((string) $task['due_date'], (string) $task['status'], ['done']);
        $rows[] = $task;
    }

    usort($rows, static function (array $left, array $right): int {
        if ((bool) $left['overdue'] !== (bool) $right['overdue']) {
            return $left['overdue'] ? -1 : 1;
        }

        return strcmp((string) $left['due_date'], (string) $right['due_date']);
    });

    return $rows;
}

function invoice_status_summary(array $invoiceRows): array
{
    $summary = [
        'receivable' => [
            'label' => '应收',
            'total_count' => 0,
            'total_amount' => 0.0,
            'pending_count' => 0,
            'pending_amount' => 0.0,
            'partial_count' => 0,
            'partial_amount' => 0.0,
            'paid_count' => 0,
            'paid_amount' => 0.0,
            'overdue_count' => 0,
            'overdue_amount' => 0.0,
        ],
        'payable' => [
            'label' => '应付',
            'total_count' => 0,
            'total_amount' => 0.0,
            'pending_count' => 0,
            'pending_amount' => 0.0,
            'partial_count' => 0,
            'partial_amount' => 0.0,
            'paid_count' => 0,
            'paid_amount' => 0.0,
            'overdue_count' => 0,
            'overdue_amount' => 0.0,
        ],
    ];

    foreach ($invoiceRows as $row) {
        $kind = (string) $row['kind'];
        $status = (string) $row['status'];
        $amount = (float) $row['amount'];

        if (!isset($summary[$kind])) {
            continue;
        }

        $summary[$kind]['total_count']++;
        $summary[$kind]['total_amount'] += $amount;

        if (isset($summary[$kind][$status . '_count'])) {
            $summary[$kind][$status . '_count']++;
            $summary[$kind][$status . '_amount'] += $amount;
        }

        if ((bool) ($row['overdue'] ?? false)) {
            $summary[$kind]['overdue_count']++;
            $summary[$kind]['overdue_amount'] += $amount;
        }
    }

    return $summary;
}

function task_status_summary(array $taskRows): array
{
    $mapping = [
        'todo' => ['label' => '待开始', 'tone' => 'neutral'],
        'doing' => ['label' => '进行中', 'tone' => 'info'],
        'review' => ['label' => '待验收', 'tone' => 'warning'],
        'done' => ['label' => '已完成', 'tone' => 'success'],
        'overdue' => ['label' => '已逾期', 'tone' => 'danger'],
    ];

    $counts = [
        'todo' => 0,
        'doing' => 0,
        'review' => 0,
        'done' => 0,
        'overdue' => 0,
    ];

    foreach ($taskRows as $task) {
        $status = (string) $task['status'];

        if (isset($counts[$status])) {
            $counts[$status]++;
        }

        if ((bool) ($task['overdue'] ?? false)) {
            $counts['overdue']++;
        }
    }

    $total = max(count($taskRows), 1);
    $rows = [];

    foreach ($mapping as $key => $config) {
        $rows[] = [
            'key' => $key,
            'label' => $config['label'],
            'count' => $counts[$key],
            'percent' => percent((float) $counts[$key], (float) $total, 100.0),
            'tone' => $config['tone'],
        ];
    }

    return $rows;
}

function project_health_rows(array $projectRows, int $limit = 6): array
{
    $today = date('Y-m-d');
    $tenDaysLater = date('Y-m-d', strtotime('+10 days'));
    $rows = [];

    foreach ($projectRows as $project) {
        $riskScore = 0;
        $dueSoon = false;
        $dueDate = (string) ($project['due_date'] ?? '');
        $status = (string) ($project['status'] ?? '');

        if ($dueDate !== '' && $dueDate >= $today && $dueDate <= $tenDaysLater && $status !== 'done') {
            $dueSoon = true;
            $riskScore += 2;
        }

        if ((float) ($project['budget_usage'] ?? 0.0) >= 90.0) {
            $riskScore += 3;
        } elseif ((float) ($project['budget_usage'] ?? 0.0) >= 70.0) {
            $riskScore += 2;
        }

        if ((int) ($project['task_overdue'] ?? 0) > 0) {
            $riskScore += 2;
        }

        if ($status === 'paused') {
            $riskScore += 3;
        }

        if ($status === 'delivery' && (int) ($project['progress'] ?? 0) < 85) {
            $riskScore += 1;
        }

        $project['risk_score'] = $riskScore;
        $project['due_soon'] = $dueSoon;
        $rows[] = $project;
    }

    usort($rows, static function (array $left, array $right): int {
        if ((int) $left['risk_score'] !== (int) $right['risk_score']) {
            return (int) $right['risk_score'] <=> (int) $left['risk_score'];
        }

        return strcmp((string) $left['due_date'], (string) $right['due_date']);
    });

    return array_slice($rows, 0, $limit);
}

function assignee_load_rows(array $taskRows, int $limit = 6): array
{
    $buckets = [];

    foreach ($taskRows as $task) {
        $assignee = (string) ($task['assignee'] ?? '未指派');

        if (!isset($buckets[$assignee])) {
            $buckets[$assignee] = [
                'assignee' => $assignee,
                'open_tasks' => 0,
                'overdue_tasks' => 0,
                'estimate_hours' => 0.0,
                'actual_hours' => 0.0,
            ];
        }

        if ((string) $task['status'] !== 'done') {
            $buckets[$assignee]['open_tasks']++;
        }

        if ((bool) ($task['overdue'] ?? false)) {
            $buckets[$assignee]['overdue_tasks']++;
        }

        $buckets[$assignee]['estimate_hours'] += (float) ($task['estimate_hours'] ?? 0.0);
        $buckets[$assignee]['actual_hours'] += (float) ($task['actual_hours'] ?? 0.0);
    }

    usort($buckets, static function (array $left, array $right): int {
        if ((int) $left['open_tasks'] !== (int) $right['open_tasks']) {
            return (int) $right['open_tasks'] <=> (int) $left['open_tasks'];
        }

        return (int) $right['overdue_tasks'] <=> (int) $left['overdue_tasks'];
    });

    return array_slice(array_values($buckets), 0, $limit);
}

function due_invoice_rows(array $invoiceRows, int $days = 15, string $kind = ''): array
{
    $today = date('Y-m-d');
    $deadline = date('Y-m-d', strtotime('+' . $days . ' days'));

    $rows = array_values(array_filter($invoiceRows, static function (array $row) use ($today, $deadline, $kind): bool {
        if ((string) $row['status'] === 'paid') {
            return false;
        }

        if ($kind !== '' && (string) $row['kind'] !== $kind) {
            return false;
        }

        $dueDate = (string) $row['due_date'];

        if ($dueDate === '') {
            return false;
        }

        return $dueDate <= $deadline || (bool) ($row['overdue'] ?? false) || $dueDate <= $today;
    }));

    usort($rows, static function (array $left, array $right): int {
        if ((bool) $left['overdue'] !== (bool) $right['overdue']) {
            return $left['overdue'] ? -1 : 1;
        }

        return strcmp((string) $left['due_date'], (string) $right['due_date']);
    });

    return $rows;
}

function business_alerts(array $data): array
{
    $dashboard = dashboard_metrics($data);
    $invoiceRows = invoice_rows($data);
    $projectRows = project_summaries($data);
    $alerts = [];

    if ((float) $dashboard['overdue_receivables'] > 0) {
        $alerts[] = '存在逾期应收 ' . money((float) $dashboard['overdue_receivables']) . '，优先推进回款。';
    }

    if ((float) $dashboard['open_payables'] > 0) {
        $alerts[] = '待付款合计 ' . money((float) $dashboard['open_payables']) . '，建议按到期日排付款计划。';
    }

    if ((int) $dashboard['overdue_tasks'] > 0) {
        $alerts[] = '当前有 ' . $dashboard['overdue_tasks'] . ' 项逾期任务，项目交付需要重点关注。';
    }

    $riskProjects = project_health_rows($projectRows, 2);
    if (count($riskProjects) > 0 && (int) $riskProjects[0]['risk_score'] >= 3) {
        $alerts[] = '项目 "' . (string) $riskProjects[0]['name'] . '" 风险较高，预算使用率 ' .
            number_format((float) $riskProjects[0]['budget_usage'], 1) . '%。';
    }

    $dueReceivables = due_invoice_rows($invoiceRows, 10, 'receivable');
    if (count($dueReceivables) > 0) {
        $alerts[] = '未来 10 天内有 ' . count($dueReceivables) . ' 笔应收节点，请提前安排催收。';
    }

    if ($alerts === []) {
        $alerts[] = '当前经营面相对平稳，可继续关注现金流、回款节奏和高优先级任务。';
    }

    return array_slice($alerts, 0, 5);
}
