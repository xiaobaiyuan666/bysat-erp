<?php

declare(strict_types=1);

function tech_ticket_type_options(): array
{
    return [
        'bug' => 'Bug 修复',
        'feature' => '功能升级',
        'improvement' => '体验优化',
    ];
}

function tech_ticket_status_options(): array
{
    return [
        'pending' => '待评估',
        'queued' => '待开发',
        'doing' => '开发中',
        'testing' => '测试中',
        'released' => '已发布',
        'closed' => '已关闭',
    ];
}

function tech_ticket_severity_options(): array
{
    return [
        'blocker' => '阻塞',
        'high' => '高',
        'medium' => '中',
        'low' => '低',
    ];
}

function tech_ticket_source_options(): array
{
    return [
        'operations' => '运营提出',
        'customer' => '客户反馈',
        'product' => '产品规划',
        'internal' => '内部优化',
    ];
}

function tech_ticket_type_label(string $type): string
{
    return (string) (tech_ticket_type_options()[$type] ?? '未知类型');
}

function tech_ticket_status_label(string $status): string
{
    return (string) (tech_ticket_status_options()[$status] ?? '未知状态');
}

function tech_ticket_status_tone(string $status): string
{
    return [
        'pending' => 'warning',
        'queued' => 'info',
        'doing' => 'primary',
        'testing' => 'warning',
        'released' => 'success',
        'closed' => 'neutral',
    ][$status] ?? 'neutral';
}

function tech_ticket_severity_label(string $severity): string
{
    return (string) (tech_ticket_severity_options()[$severity] ?? '未知等级');
}

function tech_ticket_severity_tone(string $severity): string
{
    return [
        'blocker' => 'danger',
        'high' => 'warning',
        'medium' => 'info',
        'low' => 'success',
    ][$severity] ?? 'neutral';
}

function tech_ticket_source_label(string $source): string
{
    return (string) (tech_ticket_source_options()[$source] ?? '未知来源');
}

function tech_ticket_payload_from_request(array $source, array $current = []): array
{
    return [
        'id' => (string) ($current['id'] ?? next_id('tech')),
        'ops_project_id' => input_string($source, 'ops_project_id', (string) ($current['ops_project_id'] ?? '')),
        'project_id' => input_string($source, 'project_id', (string) ($current['project_id'] ?? '')),
        'title' => input_string($source, 'title', (string) ($current['title'] ?? '')),
        'type' => input_string($source, 'type', (string) ($current['type'] ?? 'bug')),
        'status' => input_string($source, 'status', (string) ($current['status'] ?? 'pending')),
        'priority' => input_string($source, 'priority', (string) ($current['priority'] ?? 'medium')),
        'severity' => input_string($source, 'severity', (string) ($current['severity'] ?? 'medium')),
        'source' => input_string($source, 'source', (string) ($current['source'] ?? 'operations')),
        'app_module' => input_string($source, 'app_module', (string) ($current['app_module'] ?? '')),
        'app_version' => input_string($source, 'app_version', (string) ($current['app_version'] ?? '')),
        'owner' => input_string($source, 'owner', (string) ($current['owner'] ?? '')),
        'reporter' => input_string($source, 'reporter', (string) ($current['reporter'] ?? '')),
        'due_date' => input_string($source, 'due_date', (string) ($current['due_date'] ?? '')),
        'impact' => input_string($source, 'impact', (string) ($current['impact'] ?? '')),
        'solution_plan' => input_string($source, 'solution_plan', (string) ($current['solution_plan'] ?? '')),
        'estimate_hours' => input_float($source, 'estimate_hours', (float) ($current['estimate_hours'] ?? 0)),
        'actual_hours' => input_float($source, 'actual_hours', (float) ($current['actual_hours'] ?? 0)),
        'notes' => input_string($source, 'notes', (string) ($current['notes'] ?? '')),
    ];
}

function tech_release_priority(array $release): int
{
    return match ((string) ($release['release_status'] ?? '')) {
        'rolled_back' => 6,
        'ready' => 5,
        'testing' => 4,
        'planned' => 3,
        'released' => 2,
        default => 1,
    };
}

function tech_ticket_release_lookup(array $releases): array
{
    $lookup = [];

    foreach ($releases as $release) {
        $releaseId = (string) ($release['id'] ?? '');

        if ($releaseId === '') {
            continue;
        }

        $status = (string) ($release['status'] ?? 'planned');
        $candidate = [
            'release_id' => $releaseId,
            'release_version' => (string) ($release['version'] ?? ''),
            'release_title' => (string) ($release['title'] ?? ''),
            'release_status' => $status,
            'release_status_label' => function_exists('ops_release_status_label') ? ops_release_status_label($status) : $status,
            'release_status_tone' => function_exists('ops_release_status_tone') ? ops_release_status_tone($status) : 'neutral',
            'release_date' => (string) ($release['release_date'] ?? ''),
            'release_channel' => (string) ($release['channel'] ?? ''),
            'release_notes' => (string) ($release['release_notes'] ?? ''),
            'rollback_ready' => filter_var($release['rollback_ready'] ?? false, FILTER_VALIDATE_BOOL),
            'release_waiting' => in_array($status, ['planned', 'testing', 'ready'], true),
            'release_attention' => $status === 'rolled_back',
            'release_priority' => 0,
        ];
        $candidate['release_priority'] = tech_release_priority($candidate);

        foreach (ops_string_array($release['tech_ticket_ids'] ?? []) as $ticketId) {
            if ($ticketId === '') {
                continue;
            }

            $current = $lookup[$ticketId] ?? null;
            $replace = $current === null;

            if (is_array($current)) {
                $currentPriority = (int) ($current['release_priority'] ?? 0);
                $candidatePriority = (int) ($candidate['release_priority'] ?? 0);

                if ($candidatePriority > $currentPriority) {
                    $replace = true;
                } elseif ($candidatePriority === $currentPriority) {
                    $replace = (string) ($candidate['release_date'] ?? '') >= (string) ($current['release_date'] ?? '');
                }
            }

            if ($replace) {
                $lookup[$ticketId] = $candidate;
            }
        }
    }

    return $lookup;
}

function tech_ticket_rows(array $data): array
{
    $opsLookup = ops_project_lookup($data['ops_projects'] ?? []);
    $deliveryLookup = project_lookup($data['projects'] ?? []);
    $userLookup = user_lookup($data['users'] ?? []);
    $releaseLookup = tech_ticket_release_lookup($data['ops_releases'] ?? []);
    $rows = [];

    foreach ($data['tech_tickets'] ?? [] as $ticket) {
        $ticketId = (string) ($ticket['id'] ?? '');
        $opsProjectId = (string) ($ticket['ops_project_id'] ?? '');
        $projectId = (string) ($ticket['project_id'] ?? '');
        $opsProject = $opsLookup[$opsProjectId] ?? [];
        $release = $releaseLookup[$ticketId] ?? [];
        $type = (string) ($ticket['type'] ?? 'bug');
        $status = (string) ($ticket['status'] ?? 'pending');
        $severity = (string) ($ticket['severity'] ?? 'medium');
        $priority = (string) ($ticket['priority'] ?? 'medium');
        $releaseVersion = (string) ($release['release_version'] ?? '');
        $releaseTitle = (string) ($release['release_title'] ?? '');
        $releaseDisplay = trim($releaseVersion . ($releaseTitle !== '' ? ' / ' . $releaseTitle : ''), ' /');

        $row = [
            'id' => $ticketId,
            'ops_project_id' => $opsProjectId,
            'ops_project_name' => (string) ($opsProject['name'] ?? '未关联 APP 项目'),
            'project_id' => $projectId,
            'project_name' => project_name($deliveryLookup, $projectId),
            'app_name' => (string) ($opsProject['app_name'] ?? ''),
            'app_version' => (string) ($ticket['app_version'] ?? ($opsProject['app_version'] ?? '')),
            'lifecycle_stage' => (string) ($opsProject['lifecycle_stage'] ?? ''),
            'lifecycle_stage_label' => (string) ($opsProject['lifecycle_stage'] ?? '') !== '' ? ops_lifecycle_stage_label((string) $opsProject['lifecycle_stage']) : '',
            'title' => (string) ($ticket['title'] ?? ''),
            'type' => $type,
            'type_label' => tech_ticket_type_label($type),
            'status' => $status,
            'status_label' => tech_ticket_status_label($status),
            'status_tone' => tech_ticket_status_tone($status),
            'priority' => $priority,
            'priority_label' => priority_label($priority),
            'priority_tone' => priority_tone($priority),
            'severity' => $severity,
            'severity_label' => tech_ticket_severity_label($severity),
            'severity_tone' => tech_ticket_severity_tone($severity),
            'source' => (string) ($ticket['source'] ?? 'operations'),
            'source_label' => tech_ticket_source_label((string) ($ticket['source'] ?? 'operations')),
            'app_module' => (string) ($ticket['app_module'] ?? ''),
            'owner' => (string) ($ticket['owner'] ?? ''),
            'reporter' => (string) ($ticket['reporter'] ?? ''),
            'due_date' => (string) ($ticket['due_date'] ?? ''),
            'impact' => (string) ($ticket['impact'] ?? ''),
            'solution_plan' => (string) ($ticket['solution_plan'] ?? ''),
            'estimate_hours' => (float) ($ticket['estimate_hours'] ?? 0),
            'actual_hours' => (float) ($ticket['actual_hours'] ?? 0),
            'release_id' => (string) ($release['release_id'] ?? ''),
            'release_version' => $releaseVersion,
            'release_title' => $releaseTitle,
            'release_display' => $releaseDisplay,
            'release_status' => (string) ($release['release_status'] ?? ''),
            'release_status_label' => (string) ($release['release_status_label'] ?? ''),
            'release_status_tone' => (string) ($release['release_status_tone'] ?? 'neutral'),
            'release_date' => (string) ($release['release_date'] ?? ''),
            'release_channel' => (string) ($release['release_channel'] ?? ''),
            'release_notes' => (string) ($release['release_notes'] ?? ''),
            'rollback_ready' => (bool) ($release['rollback_ready'] ?? false),
            'waiting_release' => (bool) ($release['release_waiting'] ?? false),
            'release_attention' => (bool) ($release['release_attention'] ?? false),
            'notes' => (string) ($ticket['notes'] ?? ''),
            'overdue' => is_overdue((string) ($ticket['due_date'] ?? ''), $status, ['released', 'closed']),
        ];

        $rows[] = array_merge($row, record_audit_fields($ticket, $userLookup));
    }

    usort($rows, static function (array $left, array $right): int {
        $severitySort = [
            'blocker' => 4,
            'high' => 3,
            'medium' => 2,
            'low' => 1,
        ];

        $leftSeverity = $severitySort[(string) ($left['severity'] ?? '')] ?? 0;
        $rightSeverity = $severitySort[(string) ($right['severity'] ?? '')] ?? 0;

        if ($leftSeverity !== $rightSeverity) {
            return $rightSeverity <=> $leftSeverity;
        }

        if ((bool) ($left['overdue'] ?? false) !== (bool) ($right['overdue'] ?? false)) {
            return (bool) ($left['overdue'] ?? false) ? -1 : 1;
        }

        return strcmp((string) ($left['due_date'] ?? ''), (string) ($right['due_date'] ?? ''));
    });

    return $rows;
}

function tech_summary(array $data): array
{
    $rows = tech_ticket_rows($data);
    $apps = [];
    $openTotal = 0;
    $openBugs = 0;
    $pendingUpgrades = 0;
    $doingTotal = 0;
    $overdueTotal = 0;
    $releaseQueue = 0;

    foreach ($rows as $row) {
        $appName = (string) ($row['app_name'] ?? '');

        if ($appName !== '') {
            $apps[$appName] = true;
        }

        if (!in_array((string) ($row['status'] ?? ''), ['released', 'closed'], true)) {
            $openTotal++;
        }

        if ((string) ($row['type'] ?? '') === 'bug' && !in_array((string) ($row['status'] ?? ''), ['released', 'closed'], true)) {
            $openBugs++;
        }

        if (in_array((string) ($row['type'] ?? ''), ['feature', 'improvement'], true) && !in_array((string) ($row['status'] ?? ''), ['released', 'closed'], true)) {
            $pendingUpgrades++;
        }

        if (in_array((string) ($row['status'] ?? ''), ['doing', 'testing'], true)) {
            $doingTotal++;
        }

        if ((bool) ($row['overdue'] ?? false)) {
            $overdueTotal++;
        }

        if ((string) ($row['status'] ?? '') === 'testing') {
            $releaseQueue++;
        }
    }

    return [
        'app_count' => count($apps),
        'open_total' => $openTotal,
        'open_bugs' => $openBugs,
        'pending_upgrades' => $pendingUpgrades,
        'doing_total' => $doingTotal,
        'overdue_total' => $overdueTotal,
        'release_queue' => $releaseQueue,
    ];
}
