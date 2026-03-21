<?php

declare(strict_types=1);

function ops_project_status_options(): array
{
    return [
        'planning' => '筹备中',
        'running' => '执行中',
        'paused' => '已暂停',
        'review' => '复盘中',
        'closed' => '已结项',
    ];
}

function ops_lifecycle_stage_options(): array
{
    return [
        'validation' => '立项验证',
        'launch' => '上线爬坡',
        'growth' => '拉新增长',
        'retention' => '留存活跃',
        'monetization' => '商业化优化',
        'iteration' => '版本迭代',
    ];
}

function ops_milestone_status_options(): array
{
    return [
        'pending' => '待启动',
        'doing' => '推进中',
        'review' => '待验收',
        'done' => '已完成',
        'delayed' => '已延期',
    ];
}

function ops_release_status_options(): array
{
    return [
        'planned' => '待发布',
        'testing' => '测试中',
        'ready' => '待上线',
        'released' => '已发布',
        'rolled_back' => '已回滚',
    ];
}

function ops_release_customer_sync_status_options(): array
{
    return [
        'pending' => '待回告',
        'synced' => '已回告',
        'not_needed' => '无需回告',
    ];
}

function ops_material_category_options(): array
{
    return [
        'manual' => '使用手册',
        'faq' => 'FAQ 话术',
        'release' => '发版说明',
        'training' => '培训资料',
        'script' => '沟通脚本',
        'report' => '运营复盘',
    ];
}

function ops_material_archive_status_options(): array
{
    return [
        'active' => '在用',
        'archived' => '已归档',
    ];
}

function ops_risk_type_options(): array
{
    return [
        'risk' => '风险',
        'issue' => '问题',
        'change' => '变更',
    ];
}

function ops_risk_level_options(): array
{
    return [
        'high' => '高',
        'medium' => '中',
        'low' => '低',
    ];
}

function ops_risk_status_options(): array
{
    return [
        'open' => '待处理',
        'tracking' => '跟进中',
        'resolved' => '已解决',
        'closed' => '已关闭',
    ];
}

function ops_project_lookup(array $projects): array
{
    $lookup = [];

    foreach ($projects as $project) {
        $id = (string) ($project['id'] ?? '');

        if ($id !== '') {
            $lookup[$id] = $project;
        }
    }

    return $lookup;
}

function ops_project_name(array $lookup, string $projectId): string
{
    if ($projectId === '' || !isset($lookup[$projectId])) {
        return '未关联交付项目';
    }

    return (string) ($lookup[$projectId]['name'] ?? '未关联交付项目');
}

function ops_project_status_label(string $status): string
{
    return (string) (ops_project_status_options()[$status] ?? '未知状态');
}

function ops_project_status_tone(string $status): string
{
    return [
        'planning' => 'neutral',
        'running' => 'info',
        'paused' => 'danger',
        'review' => 'warning',
        'closed' => 'success',
    ][$status] ?? 'neutral';
}

function ops_lifecycle_stage_label(string $stage): string
{
    return (string) (ops_lifecycle_stage_options()[$stage] ?? '未知阶段');
}

function ops_lifecycle_stage_tone(string $stage): string
{
    return [
        'validation' => 'warning',
        'launch' => 'info',
        'growth' => 'success',
        'retention' => 'primary',
        'monetization' => 'danger',
        'iteration' => 'neutral',
    ][$stage] ?? 'neutral';
}

function ops_milestone_status_label(string $status): string
{
    return (string) (ops_milestone_status_options()[$status] ?? '未知状态');
}

function ops_milestone_status_tone(string $status): string
{
    return [
        'pending' => 'neutral',
        'doing' => 'info',
        'review' => 'warning',
        'done' => 'success',
        'delayed' => 'danger',
    ][$status] ?? 'neutral';
}

function ops_release_status_label(string $status): string
{
    return (string) (ops_release_status_options()[$status] ?? '未知状态');
}

function ops_release_status_tone(string $status): string
{
    return [
        'planned' => 'neutral',
        'testing' => 'warning',
        'ready' => 'info',
        'released' => 'success',
        'rolled_back' => 'danger',
    ][$status] ?? 'neutral';
}

function ops_release_customer_sync_status_label(string $status): string
{
    return (string) (ops_release_customer_sync_status_options()[$status] ?? '待回告');
}

function ops_release_customer_sync_status_tone(string $status): string
{
    return [
        'pending' => 'warning',
        'synced' => 'success',
        'not_needed' => 'neutral',
    ][$status] ?? 'warning';
}

function ops_material_category_label(string $category): string
{
    return (string) (ops_material_category_options()[$category] ?? '未知分类');
}

function ops_material_archive_status_label(string $status): string
{
    return (string) (ops_material_archive_status_options()[$status] ?? '在用');
}

function ops_material_archive_status_tone(string $status): string
{
    return [
        'active' => 'success',
        'archived' => 'neutral',
    ][$status] ?? 'neutral';
}

function ops_material_preview_type(string $downloadUrl, string $fileMime = ''): string
{
    $path = (string) (parse_url($downloadUrl, PHP_URL_PATH) ?? '');
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $imageExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    if (str_starts_with($fileMime, 'image/') || in_array($extension, $imageExtensions, true)) {
        return 'image';
    }

    if ($fileMime === 'application/pdf' || $extension === 'pdf') {
        return 'pdf';
    }

    return '';
}

function ops_risk_type_label(string $type): string
{
    return (string) (ops_risk_type_options()[$type] ?? '未知类型');
}

function ops_risk_level_label(string $level): string
{
    return (string) (ops_risk_level_options()[$level] ?? '未知等级');
}

function ops_risk_level_tone(string $level): string
{
    return [
        'high' => 'danger',
        'medium' => 'warning',
        'low' => 'success',
    ][$level] ?? 'neutral';
}

function ops_risk_status_label(string $status): string
{
    return (string) (ops_risk_status_options()[$status] ?? '未知状态');
}

function ops_risk_status_tone(string $status): string
{
    return [
        'open' => 'danger',
        'tracking' => 'warning',
        'resolved' => 'success',
        'closed' => 'neutral',
    ][$status] ?? 'neutral';
}

function ops_string_array(mixed $value): array
{
    if (is_string($value)) {
        $value = array_map('trim', explode(',', $value));
    }

    if (!is_array($value)) {
        return [];
    }

    $rows = [];

    foreach ($value as $item) {
        $string = trim((string) $item);

        if ($string !== '') {
            $rows[] = $string;
        }
    }

    return array_values(array_unique($rows));
}

function ops_project_payload_from_request(array $source, array $current = []): array
{
    return [
        'id' => (string) ($current['id'] ?? next_id('ops')),
        'name' => input_string($source, 'name', (string) ($current['name'] ?? '')),
        'app_name' => input_string($source, 'app_name', (string) ($current['app_name'] ?? '')),
        'app_version' => input_string($source, 'app_version', (string) ($current['app_version'] ?? '')),
        'lifecycle_stage' => input_string($source, 'lifecycle_stage', (string) ($current['lifecycle_stage'] ?? 'validation')),
        'business_line' => input_string($source, 'business_line', (string) ($current['business_line'] ?? '')),
        'manager' => input_string($source, 'manager', (string) ($current['manager'] ?? '')),
        'client_owner' => input_string($source, 'client_owner', (string) ($current['client_owner'] ?? '')),
        'core_metric' => input_string($source, 'core_metric', (string) ($current['core_metric'] ?? '')),
        'status' => input_string($source, 'status', (string) ($current['status'] ?? 'planning')),
        'priority' => input_string($source, 'priority', (string) ($current['priority'] ?? 'medium')),
        'budget' => input_float($source, 'budget', (float) ($current['budget'] ?? 0)),
        'actual_cost' => input_float($source, 'actual_cost', (float) ($current['actual_cost'] ?? 0)),
        'start_date' => input_string($source, 'start_date', (string) ($current['start_date'] ?? '')),
        'end_date' => input_string($source, 'end_date', (string) ($current['end_date'] ?? '')),
        'target' => input_string($source, 'target', (string) ($current['target'] ?? '')),
        'channel' => input_string($source, 'channel', (string) ($current['channel'] ?? '')),
        'project_id' => input_string($source, 'project_id', (string) ($current['project_id'] ?? '')),
        'description' => input_string($source, 'description', (string) ($current['description'] ?? '')),
    ];
}

function ops_milestone_payload_from_request(array $source, array $current = []): array
{
    return [
        'id' => (string) ($current['id'] ?? next_id('milestone')),
        'ops_project_id' => input_string($source, 'ops_project_id', (string) ($current['ops_project_id'] ?? '')),
        'title' => input_string($source, 'title', (string) ($current['title'] ?? '')),
        'owner' => input_string($source, 'owner', (string) ($current['owner'] ?? '')),
        'due_date' => input_string($source, 'due_date', (string) ($current['due_date'] ?? '')),
        'status' => input_string($source, 'status', (string) ($current['status'] ?? 'pending')),
        'progress' => max(0, min(100, (int) input_float($source, 'progress', (float) ($current['progress'] ?? 0)))),
        'deliverable' => input_string($source, 'deliverable', (string) ($current['deliverable'] ?? '')),
        'notes' => input_string($source, 'notes', (string) ($current['notes'] ?? '')),
    ];
}

function ops_update_payload_from_request(array $source, array $current = []): array
{
    return [
        'id' => (string) ($current['id'] ?? next_id('report')),
        'ops_project_id' => input_string($source, 'ops_project_id', (string) ($current['ops_project_id'] ?? '')),
        'report_date' => input_string($source, 'report_date', (string) ($current['report_date'] ?? date('Y-m-d'))),
        'owner' => input_string($source, 'owner', (string) ($current['owner'] ?? '')),
        'summary' => input_string($source, 'summary', (string) ($current['summary'] ?? '')),
        'result' => input_string($source, 'result', (string) ($current['result'] ?? '')),
        'next_actions' => input_string($source, 'next_actions', (string) ($current['next_actions'] ?? '')),
        'blockers' => input_string($source, 'blockers', (string) ($current['blockers'] ?? '')),
    ];
}

function ops_release_payload_from_request(array $source, array $current = []): array
{
    return [
        'id' => (string) ($current['id'] ?? next_id('release')),
        'ops_project_id' => input_string($source, 'ops_project_id', (string) ($current['ops_project_id'] ?? '')),
        'version' => input_string($source, 'version', (string) ($current['version'] ?? '')),
        'title' => input_string($source, 'title', (string) ($current['title'] ?? '')),
        'status' => input_string($source, 'status', (string) ($current['status'] ?? 'planned')),
        'owner' => input_string($source, 'owner', (string) ($current['owner'] ?? '')),
        'release_date' => input_string($source, 'release_date', (string) ($current['release_date'] ?? '')),
        'channel' => input_string($source, 'channel', (string) ($current['channel'] ?? '')),
        'tech_ticket_ids' => ops_string_array($source['tech_ticket_ids'] ?? ($current['tech_ticket_ids'] ?? [])),
        'service_ticket_ids' => ops_string_array($source['service_ticket_ids'] ?? ($current['service_ticket_ids'] ?? [])),
        'release_notes' => input_string($source, 'release_notes', (string) ($current['release_notes'] ?? '')),
        'verification_summary' => input_string($source, 'verification_summary', (string) ($current['verification_summary'] ?? '')),
        'customer_sync_status' => input_string($source, 'customer_sync_status', (string) ($current['customer_sync_status'] ?? 'pending')),
        'customer_sync_note' => input_string($source, 'customer_sync_note', (string) ($current['customer_sync_note'] ?? '')),
        'release_result' => input_string($source, 'release_result', (string) ($current['release_result'] ?? '')),
        'rollback_plan' => input_string($source, 'rollback_plan', (string) ($current['rollback_plan'] ?? '')),
        'rollback_ready' => filter_var($source['rollback_ready'] ?? ($current['rollback_ready'] ?? false), FILTER_VALIDATE_BOOL),
        'notes' => input_string($source, 'notes', (string) ($current['notes'] ?? '')),
    ];
}

function ops_material_payload_from_request(array $source, array $current = []): array
{
    return [
        'id' => (string) ($current['id'] ?? next_id('material')),
        'ops_project_id' => input_string($source, 'ops_project_id', (string) ($current['ops_project_id'] ?? '')),
        'title' => input_string($source, 'title', (string) ($current['title'] ?? '')),
        'category' => input_string($source, 'category', (string) ($current['category'] ?? 'manual')),
        'owner' => input_string($source, 'owner', (string) ($current['owner'] ?? '')),
        'version_tag' => input_string($source, 'version_tag', (string) ($current['version_tag'] ?? '')),
        'applicable_versions' => input_string($source, 'applicable_versions', (string) ($current['applicable_versions'] ?? '')),
        'expires_on' => input_string($source, 'expires_on', (string) ($current['expires_on'] ?? '')),
        'archive_status' => input_string($source, 'archive_status', (string) ($current['archive_status'] ?? 'active')),
        'replacement_material_id' => input_string($source, 'replacement_material_id', (string) ($current['replacement_material_id'] ?? '')),
        'download_name' => input_string($source, 'download_name', (string) ($current['download_name'] ?? '')),
        'download_url' => input_string($source, 'download_url', (string) ($current['download_url'] ?? '')),
        'file_path' => input_string($source, 'file_path', (string) ($current['file_path'] ?? '')),
        'file_size' => (int) input_float($source, 'file_size', (float) ($current['file_size'] ?? 0)),
        'file_mime' => input_string($source, 'file_mime', (string) ($current['file_mime'] ?? '')),
        'updated_on' => input_string($source, 'updated_on', (string) ($current['updated_on'] ?? date('Y-m-d'))),
        'notes' => input_string($source, 'notes', (string) ($current['notes'] ?? '')),
    ];
}

function ops_risk_payload_from_request(array $source, array $current = []): array
{
    return [
        'id' => (string) ($current['id'] ?? next_id('risk')),
        'ops_project_id' => input_string($source, 'ops_project_id', (string) ($current['ops_project_id'] ?? '')),
        'title' => input_string($source, 'title', (string) ($current['title'] ?? '')),
        'type' => input_string($source, 'type', (string) ($current['type'] ?? 'risk')),
        'level' => input_string($source, 'level', (string) ($current['level'] ?? 'medium')),
        'status' => input_string($source, 'status', (string) ($current['status'] ?? 'open')),
        'owner' => input_string($source, 'owner', (string) ($current['owner'] ?? '')),
        'due_date' => input_string($source, 'due_date', (string) ($current['due_date'] ?? '')),
        'impact' => input_string($source, 'impact', (string) ($current['impact'] ?? '')),
        'action_plan' => input_string($source, 'action_plan', (string) ($current['action_plan'] ?? '')),
    ];
}

function ops_project_rows(array $data): array
{
    $deliveryLookup = project_lookup($data['projects'] ?? []);
    $userLookup = user_lookup($data['users'] ?? []);
    $milestoneStats = [];
    $latestMilestone = [];
    $riskStats = [];
    $updateStats = [];
    $materialStats = [];
    $sevenDaysAgo = date('Y-m-d', strtotime('-7 days'));

    foreach ($data['ops_milestones'] ?? [] as $milestone) {
        $opsProjectId = (string) ($milestone['ops_project_id'] ?? '');

        if ($opsProjectId === '') {
            continue;
        }

        $milestoneStats[$opsProjectId] ??= [
            'total' => 0,
            'done' => 0,
            'due' => 0,
        ];

        $milestoneStats[$opsProjectId]['total']++;

        if ((string) ($milestone['status'] ?? '') === 'done') {
            $milestoneStats[$opsProjectId]['done']++;
        }

        if (is_overdue((string) ($milestone['due_date'] ?? ''), (string) ($milestone['status'] ?? ''), ['done'])) {
            $milestoneStats[$opsProjectId]['due']++;
        }

        $dueDate = (string) ($milestone['due_date'] ?? '');

        if ($dueDate === '' || (string) ($milestone['status'] ?? '') === 'done') {
            continue;
        }

        if (!isset($latestMilestone[$opsProjectId]) || $dueDate < (string) $latestMilestone[$opsProjectId]['due_date']) {
            $latestMilestone[$opsProjectId] = [
                'title' => (string) ($milestone['title'] ?? ''),
                'due_date' => $dueDate,
            ];
        }
    }

    foreach ($data['ops_risks'] ?? [] as $risk) {
        $opsProjectId = (string) ($risk['ops_project_id'] ?? '');

        if ($opsProjectId === '') {
            continue;
        }

        $riskStats[$opsProjectId] ??= [
            'open' => 0,
            'high' => 0,
        ];

        $status = (string) ($risk['status'] ?? '');

        if (!in_array($status, ['resolved', 'closed'], true)) {
            $riskStats[$opsProjectId]['open']++;

            if ((string) ($risk['level'] ?? '') === 'high') {
                $riskStats[$opsProjectId]['high']++;
            }
        }
    }

    foreach ($data['ops_updates'] ?? [] as $update) {
        $opsProjectId = (string) ($update['ops_project_id'] ?? '');
        $reportDate = (string) ($update['report_date'] ?? '');

        if ($opsProjectId === '') {
            continue;
        }

        $updateStats[$opsProjectId] ??= [
            'last' => '',
            'recent_count' => 0,
        ];

        if ($reportDate !== '' && $reportDate > (string) $updateStats[$opsProjectId]['last']) {
            $updateStats[$opsProjectId]['last'] = $reportDate;
        }

        if ($reportDate !== '' && $reportDate >= $sevenDaysAgo) {
            $updateStats[$opsProjectId]['recent_count']++;
        }
    }

    foreach ($data['ops_materials'] ?? [] as $material) {
        $opsProjectId = (string) ($material['ops_project_id'] ?? '');

        if ($opsProjectId === '') {
            continue;
        }

        $materialStats[$opsProjectId] ??= [
            'total' => 0,
            'last_updated_on' => '',
        ];

        $materialStats[$opsProjectId]['total']++;

        $updatedOn = (string) ($material['updated_on'] ?? '');
        if ($updatedOn !== '' && $updatedOn > (string) $materialStats[$opsProjectId]['last_updated_on']) {
            $materialStats[$opsProjectId]['last_updated_on'] = $updatedOn;
        }
    }

    $rows = [];

    foreach ($data['ops_projects'] ?? [] as $project) {
        $id = (string) ($project['id'] ?? '');
        $milestones = $milestoneStats[$id] ?? ['total' => 0, 'done' => 0, 'due' => 0];
        $risks = $riskStats[$id] ?? ['open' => 0, 'high' => 0];
        $updates = $updateStats[$id] ?? ['last' => '', 'recent_count' => 0];
        $materials = $materialStats[$id] ?? ['total' => 0, 'last_updated_on' => ''];
        $totalMilestones = (int) $milestones['total'];
        $completion = $totalMilestones > 0 ? round(((int) $milestones['done'] / $totalMilestones) * 100) : 0;
        $lastUpdateDate = (string) $updates['last'];
        $needsUpdate = $lastUpdateDate === '' || $lastUpdateDate < $sevenDaysAgo;
        $status = (string) ($project['status'] ?? '');
        $priority = (string) ($project['priority'] ?? 'medium');
        $lifecycleStage = (string) ($project['lifecycle_stage'] ?? 'validation');
        $budget = (float) ($project['budget'] ?? 0);
        $actualCost = (float) ($project['actual_cost'] ?? 0);
        $projectId = (string) ($project['project_id'] ?? '');

        $project['status_label'] = ops_project_status_label($status);
        $project['status_tone'] = ops_project_status_tone($status);
        $project['lifecycle_stage_label'] = ops_lifecycle_stage_label($lifecycleStage);
        $project['lifecycle_stage_tone'] = ops_lifecycle_stage_tone($lifecycleStage);
        $project['priority_label'] = priority_label($priority);
        $project['priority_tone'] = priority_tone($priority);
        $project['cost_usage'] = percent($actualCost, $budget, 100.0);
        $project['completion'] = $completion;
        $project['milestone_total'] = $totalMilestones;
        $project['milestone_done'] = (int) $milestones['done'];
        $project['milestone_due'] = (int) $milestones['due'];
        $project['open_risks'] = (int) $risks['open'];
        $project['high_risks'] = (int) $risks['high'];
        $project['last_update_date'] = $lastUpdateDate;
        $project['recent_update_count'] = (int) $updates['recent_count'];
        $project['needs_update'] = $needsUpdate;
        $project['material_total'] = (int) $materials['total'];
        $project['materials_updated_on'] = (string) ($materials['last_updated_on'] ?? '');
        $project['next_milestone_title'] = (string) ($latestMilestone[$id]['title'] ?? '');
        $project['next_milestone_due_date'] = (string) ($latestMilestone[$id]['due_date'] ?? '');
        $project['delivery_project_name'] = ops_project_name($deliveryLookup, $projectId);
        $rows[] = array_merge($project, record_audit_fields($project, $userLookup));
    }

    usort($rows, static function (array $left, array $right): int {
        if ((int) ($left['high_risks'] ?? 0) !== (int) ($right['high_risks'] ?? 0)) {
            return (int) ($right['high_risks'] ?? 0) <=> (int) ($left['high_risks'] ?? 0);
        }

        if ((bool) ($left['needs_update'] ?? false) !== (bool) ($right['needs_update'] ?? false)) {
            return (bool) ($left['needs_update'] ?? false) ? -1 : 1;
        }

        return strcmp((string) ($left['end_date'] ?? ''), (string) ($right['end_date'] ?? ''));
    });

    return $rows;
}

function ops_milestone_rows(array $data): array
{
    $lookup = ops_project_lookup($data['ops_projects'] ?? []);
    $userLookup = user_lookup($data['users'] ?? []);
    $rows = [];

    foreach ($data['ops_milestones'] ?? [] as $row) {
        $status = (string) ($row['status'] ?? 'pending');
        $row['project_name'] = (string) ($lookup[(string) ($row['ops_project_id'] ?? '')]['name'] ?? '未指定项目');
        $row['status_label'] = ops_milestone_status_label($status);
        $row['status_tone'] = ops_milestone_status_tone($status);
        $row['overdue'] = is_overdue((string) ($row['due_date'] ?? ''), $status, ['done']);
        $rows[] = array_merge($row, record_audit_fields($row, $userLookup));
    }

    usort($rows, static function (array $left, array $right): int {
        if ((bool) ($left['overdue'] ?? false) !== (bool) ($right['overdue'] ?? false)) {
            return (bool) ($left['overdue'] ?? false) ? -1 : 1;
        }

        return strcmp((string) ($left['due_date'] ?? ''), (string) ($right['due_date'] ?? ''));
    });

    return $rows;
}

function ops_update_rows(array $data): array
{
    $lookup = ops_project_lookup($data['ops_projects'] ?? []);
    $userLookup = user_lookup($data['users'] ?? []);
    $rows = [];

    foreach ($data['ops_updates'] ?? [] as $row) {
        $row['project_name'] = (string) ($lookup[(string) ($row['ops_project_id'] ?? '')]['name'] ?? '未指定项目');
        $rows[] = array_merge($row, record_audit_fields($row, $userLookup));
    }

    usort($rows, static function (array $left, array $right): int {
        return strcmp((string) ($right['report_date'] ?? ''), (string) ($left['report_date'] ?? ''));
    });

    return $rows;
}

function ops_release_rows(array $data): array
{
    $lookup = ops_project_lookup($data['ops_projects'] ?? []);
    $userLookup = user_lookup($data['users'] ?? []);
    $ticketLookup = [];
    $serviceTicketLookup = [];
    $rows = [];

    foreach ($data['tech_tickets'] ?? [] as $ticket) {
        $id = (string) ($ticket['id'] ?? '');

        if ($id !== '') {
            $ticketLookup[$id] = $ticket;
        }
    }

    foreach ($data['service_tickets'] ?? [] as $ticket) {
        $id = (string) ($ticket['id'] ?? '');

        if ($id !== '') {
            $serviceTicketLookup[$id] = $ticket;
        }
    }

    foreach ($data['ops_releases'] ?? [] as $row) {
        $status = (string) ($row['status'] ?? 'planned');
        $ticketIds = ops_string_array($row['tech_ticket_ids'] ?? []);
        $serviceTicketIds = ops_string_array($row['service_ticket_ids'] ?? []);
        $ticketTitles = [];
        $serviceTicketTitles = [];

        foreach ($ticketIds as $ticketId) {
            $title = (string) ($ticketLookup[$ticketId]['title'] ?? '');

            if ($title !== '') {
                $ticketTitles[] = $title;
            }
        }

        foreach ($serviceTicketIds as $ticketId) {
            $title = (string) ($serviceTicketLookup[$ticketId]['title'] ?? '');

            if ($title !== '') {
                $serviceTicketTitles[] = $title;
            }
        }

        $opsProjectId = (string) ($row['ops_project_id'] ?? '');
        $project = $lookup[$opsProjectId] ?? [];
        $customerSyncStatus = (string) ($row['customer_sync_status'] ?? '');

        if ($customerSyncStatus === '') {
            $customerSyncStatus = $serviceTicketIds === [] ? 'not_needed' : 'pending';
        }
        $row['project_name'] = (string) ($project['name'] ?? '未指定 APP 项目');
        $row['app_name'] = (string) ($project['app_name'] ?? '');
        $row['status_label'] = ops_release_status_label($status);
        $row['status_tone'] = ops_release_status_tone($status);
        $row['tech_ticket_ids'] = $ticketIds;
        $row['linked_ticket_count'] = count($ticketIds);
        $row['linked_ticket_titles'] = $ticketTitles;
        $row['linked_ticket_summary'] = implode(' / ', array_slice($ticketTitles, 0, 3));
        $row['service_ticket_ids'] = $serviceTicketIds;
        $row['linked_service_count'] = count($serviceTicketIds);
        $row['linked_service_titles'] = $serviceTicketTitles;
        $row['linked_service_summary'] = implode(' / ', array_slice($serviceTicketTitles, 0, 3));
        $row['verification_summary'] = (string) ($row['verification_summary'] ?? '');
        $row['customer_sync_status'] = $customerSyncStatus;
        $row['customer_sync_status_label'] = ops_release_customer_sync_status_label($customerSyncStatus);
        $row['customer_sync_status_tone'] = ops_release_customer_sync_status_tone($customerSyncStatus);
        $row['customer_sync_note'] = (string) ($row['customer_sync_note'] ?? '');
        $row['release_result'] = (string) ($row['release_result'] ?? '');
        $row['needs_customer_sync'] = $serviceTicketIds !== []
            && in_array($status, ['released', 'rolled_back'], true)
            && $customerSyncStatus === 'pending';
        $rows[] = array_merge($row, record_audit_fields($row, $userLookup));
    }

    usort($rows, static function (array $left, array $right): int {
        return strcmp((string) ($right['release_date'] ?? ''), (string) ($left['release_date'] ?? ''));
    });

    return $rows;
}

function ops_material_rows(array $data): array
{
    $lookup = ops_project_lookup($data['ops_projects'] ?? []);
    $userLookup = user_lookup($data['users'] ?? []);
    $materialLookup = [];
    $rows = [];

    foreach ($data['ops_materials'] ?? [] as $material) {
        $materialId = (string) ($material['id'] ?? '');

        if ($materialId !== '') {
            $materialLookup[$materialId] = $material;
        }
    }

    foreach ($data['ops_materials'] ?? [] as $row) {
        $opsProjectId = (string) ($row['ops_project_id'] ?? '');
        $project = $lookup[$opsProjectId] ?? [];
        $category = (string) ($row['category'] ?? 'manual');
        $archiveStatus = (string) ($row['archive_status'] ?? 'active');
        $replacementMaterialId = (string) ($row['replacement_material_id'] ?? '');
        $replacementMaterial = $materialLookup[$replacementMaterialId] ?? [];
        $filePath = ltrim((string) ($row['file_path'] ?? ''), '/');
        $downloadUrl = $filePath !== '' ? '/' . $filePath : (string) ($row['download_url'] ?? '');
        $previewType = ops_material_preview_type($downloadUrl, (string) ($row['file_mime'] ?? ''));
        $expiresOn = (string) ($row['expires_on'] ?? '');
        $expired = $expiresOn !== '' && $expiresOn < date('Y-m-d');
        $expiresSoon = !$expired
            && $expiresOn !== ''
            && $expiresOn <= date('Y-m-d', strtotime('+30 days'));

        $row['project_name'] = (string) ($project['name'] ?? '未指定 APP 项目');
        $row['app_name'] = (string) ($project['app_name'] ?? '');
        $row['app_version'] = (string) ($project['app_version'] ?? '');
        $row['category_label'] = ops_material_category_label($category);
        $row['version_tag'] = (string) ($row['version_tag'] ?? '');
        $row['applicable_versions'] = (string) ($row['applicable_versions'] ?? '');
        $row['expires_on'] = $expiresOn;
        $row['expired'] = $expired;
        $row['expires_soon'] = $expiresSoon;
        $row['archive_status'] = $archiveStatus;
        $row['archive_status_label'] = ops_material_archive_status_label($archiveStatus);
        $row['archive_status_tone'] = ops_material_archive_status_tone($archiveStatus);
        $row['is_archived'] = $archiveStatus === 'archived';
        $row['replacement_material_id'] = $replacementMaterialId;
        $row['replacement_material_title'] = (string) ($replacementMaterial['title'] ?? '');
        $row['replacement_material_version'] = (string) ($replacementMaterial['version_tag'] ?? '');
        $row['replacement_display'] = trim(
            (string) ($replacementMaterial['title'] ?? '')
            . ((string) ($replacementMaterial['version_tag'] ?? '') !== '' ? ' / ' . (string) $replacementMaterial['version_tag'] : '')
        );
        $row['has_replacement'] = $replacementMaterialId !== '' && $row['replacement_display'] !== '';
        $row['download_name'] = (string) ($row['download_name'] ?? ($row['title'] ?? ''));
        $row['download_url'] = $downloadUrl;
        $row['file_path'] = $filePath;
        $row['file_size'] = (int) ($row['file_size'] ?? 0);
        $row['file_mime'] = (string) ($row['file_mime'] ?? '');
        $row['is_uploaded'] = $filePath !== '';
        $row['storage_label'] = $filePath !== '' ? '已上传文件' : '外部链接';
        $row['downloadable'] = $downloadUrl !== '';
        $row['preview_type'] = $previewType;
        $row['previewable'] = $previewType !== '';
        $row['preview_url'] = $previewType !== '' ? $downloadUrl : '';
        $rows[] = array_merge($row, record_audit_fields($row, $userLookup));
    }

    usort($rows, static function (array $left, array $right): int {
        return strcmp((string) ($right['updated_on'] ?? ''), (string) ($left['updated_on'] ?? ''));
    });

    return $rows;
}

function ops_risk_rows(array $data): array
{
    $lookup = ops_project_lookup($data['ops_projects'] ?? []);
    $userLookup = user_lookup($data['users'] ?? []);
    $rows = [];

    foreach ($data['ops_risks'] ?? [] as $row) {
        $type = (string) ($row['type'] ?? '');
        $level = (string) ($row['level'] ?? '');
        $status = (string) ($row['status'] ?? '');
        $row['project_name'] = (string) ($lookup[(string) ($row['ops_project_id'] ?? '')]['name'] ?? '未指定项目');
        $row['type_label'] = ops_risk_type_label($type);
        $row['level_label'] = ops_risk_level_label($level);
        $row['level_tone'] = ops_risk_level_tone($level);
        $row['status_label'] = ops_risk_status_label($status);
        $row['status_tone'] = ops_risk_status_tone($status);
        $row['overdue'] = is_overdue((string) ($row['due_date'] ?? ''), $status, ['resolved', 'closed']);
        $rows[] = array_merge($row, record_audit_fields($row, $userLookup));
    }

    usort($rows, static function (array $left, array $right): int {
        $levelSort = ['high' => 3, 'medium' => 2, 'low' => 1];
        $leftLevel = $levelSort[(string) ($left['level'] ?? '')] ?? 0;
        $rightLevel = $levelSort[(string) ($right['level'] ?? '')] ?? 0;

        if ($leftLevel !== $rightLevel) {
            return $rightLevel <=> $leftLevel;
        }

        return strcmp((string) ($left['due_date'] ?? ''), (string) ($right['due_date'] ?? ''));
    });

    return $rows;
}

function operations_summary(array $data): array
{
    $projectRows = ops_project_rows($data);
    $milestoneRows = ops_milestone_rows($data);
    $riskRows = ops_risk_rows($data);
    $updateRows = ops_update_rows($data);
    $releaseRows = ops_release_rows($data);
    $materialRows = ops_material_rows($data);
    $techStats = function_exists('tech_summary') ? tech_summary($data) : [];

    $activeProjects = 0;
    $needsUpdateProjects = 0;
    $launchingProjects = 0;
    $growthProjects = 0;
    $dueMilestones = 0;
    $openRisks = 0;
    $highRisks = 0;
    $weeklyUpdates = 0;
    $pendingReleases = 0;
    $pendingCustomerSync = 0;
    $weekStart = date('Y-m-d', strtotime('-7 days'));

    foreach ($projectRows as $row) {
        if (in_array((string) ($row['status'] ?? ''), ['planning', 'running', 'review'], true)) {
            $activeProjects++;
        }

        if ((bool) ($row['needs_update'] ?? false)) {
            $needsUpdateProjects++;
        }

        if ((string) ($row['lifecycle_stage'] ?? '') === 'launch') {
            $launchingProjects++;
        }

        if ((string) ($row['lifecycle_stage'] ?? '') === 'growth') {
            $growthProjects++;
        }
    }

    foreach ($milestoneRows as $row) {
        if ((string) ($row['status'] ?? '') !== 'done' && ((bool) ($row['overdue'] ?? false) || (string) ($row['due_date'] ?? '') <= date('Y-m-d', strtotime('+7 days')))) {
            $dueMilestones++;
        }
    }

    foreach ($riskRows as $row) {
        if (in_array((string) ($row['status'] ?? ''), ['resolved', 'closed'], true)) {
            continue;
        }

        $openRisks++;

        if ((string) ($row['level'] ?? '') === 'high') {
            $highRisks++;
        }
    }

    foreach ($updateRows as $row) {
        if ((string) ($row['report_date'] ?? '') >= $weekStart) {
            $weeklyUpdates++;
        }
    }

    foreach ($releaseRows as $row) {
        if (in_array((string) ($row['status'] ?? ''), ['planned', 'testing', 'ready'], true)) {
            $pendingReleases++;
        }

        if ((bool) ($row['needs_customer_sync'] ?? false)) {
            $pendingCustomerSync++;
        }
    }

    return [
        'active_projects' => $activeProjects,
        'needs_update_projects' => $needsUpdateProjects,
        'launching_projects' => $launchingProjects,
        'growth_projects' => $growthProjects,
        'due_milestones' => $dueMilestones,
        'open_risks' => $openRisks,
        'high_risks' => $highRisks,
        'weekly_updates' => $weeklyUpdates,
        'pending_releases' => $pendingReleases,
        'pending_customer_sync' => $pendingCustomerSync,
        'materials_total' => count($materialRows),
        'open_tech_tickets' => (int) ($techStats['open_total'] ?? 0),
        'open_bug_tickets' => (int) ($techStats['open_bugs'] ?? 0),
    ];
}

function operations_alerts(array $data): array
{
    $projectRows = ops_project_rows($data);
    $milestoneRows = ops_milestone_rows($data);
    $riskRows = ops_risk_rows($data);
    $releaseRows = ops_release_rows($data);
    $techRows = function_exists('tech_ticket_rows') ? tech_ticket_rows($data) : [];
    $alerts = [];

    foreach ($projectRows as $row) {
        if ((bool) ($row['needs_update'] ?? false)) {
            $alerts[] = '项目 "' . (string) ($row['name'] ?? '') . '" 已超过 7 天未更新进展。';
        }

        if ((int) ($row['high_risks'] ?? 0) > 0) {
            $alerts[] = '项目 "' . (string) ($row['name'] ?? '') . '" 存在 ' . (int) ($row['high_risks'] ?? 0) . ' 条高等级风险。';
        }
    }

    foreach ($milestoneRows as $row) {
        if ((bool) ($row['overdue'] ?? false)) {
            $alerts[] = '里程碑 "' . (string) ($row['title'] ?? '') . '" 已逾期，需要立即重排节奏。';
        }
    }

    foreach ($riskRows as $row) {
        if ((string) ($row['level'] ?? '') === 'high' && !in_array((string) ($row['status'] ?? ''), ['resolved', 'closed'], true)) {
            $alerts[] = '高等级' . (string) ($row['type_label'] ?? '风险') . ' "' . (string) ($row['title'] ?? '') . '" 仍未关闭。';
        }
    }

    foreach ($techRows as $row) {
        if ((bool) ($row['overdue'] ?? false)) {
            $alerts[] = 'APP "' . (string) ($row['app_name'] ?? '') . '" 的研发待办 "' . (string) ($row['title'] ?? '') . '" 已超期。';
        }
    }

    foreach ($releaseRows as $row) {
        if ((string) ($row['status'] ?? '') === 'ready') {
            $alerts[] = 'APP "' . (string) ($row['app_name'] ?? '') . '" 版本 ' . (string) ($row['version'] ?? '') . ' 已待上线，请确认发布时间窗口。';
        }

        if ((string) ($row['status'] ?? '') === 'rolled_back') {
            $alerts[] = 'APP "' . (string) ($row['app_name'] ?? '') . '" 版本 ' . (string) ($row['version'] ?? '') . ' 已回滚，需要立即跟进后续动作。';
        }
    }

    if ($alerts === []) {
        $alerts[] = '当前 APP 运营推进正常，可继续按周更新里程碑、问题记录和版本动作。';
    }

    foreach ($releaseRows as $row) {
        if ((bool) ($row['needs_customer_sync'] ?? false)) {
            $alerts[] = 'APP "' . (string) ($row['app_name'] ?? '') . '" 版本 ' . (string) ($row['version'] ?? '') . ' 已发布，但关联问题仍待客户回告。';
        }
    }

    return array_slice($alerts, 0, 6);
}
