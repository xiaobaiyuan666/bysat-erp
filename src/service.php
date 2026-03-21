<?php

declare(strict_types=1);

function service_ticket_source_options(): array
{
    return [
        'customer' => '客户反馈',
        'leader' => '领导反馈',
        'sales' => '销售反馈',
        'operations' => '运营反馈',
        'internal' => '内部巡检',
        'training' => '培训收集',
    ];
}

function service_ticket_channel_options(): array
{
    return [
        'app' => 'APP 在线反馈',
        'wechat' => '企业微信',
        'phone' => '电话',
        'email' => '邮件',
        'meeting' => '会议记录',
    ];
}

function service_ticket_category_options(): array
{
    return [
        'usage' => '使用咨询',
        'bug' => '问题反馈',
        'billing' => '账单与续费',
        'feature' => '功能建议',
        'complaint' => '投诉与异常',
        'feedback' => '综合反馈',
    ];
}

function service_ticket_status_options(): array
{
    return [
        'new' => '待响应',
        'processing' => '处理中',
        'waiting_customer' => '待反馈回告',
        'escalated' => '已升级技术',
        'resolved' => '已解决',
        'closed' => '已关闭',
    ];
}

function service_ticket_update_type_options(): array
{
    return [
        'follow_up' => '跟进记录',
        'status' => '状态变更',
        'release' => '发版回告',
        'leader' => '领导反馈',
        'internal' => '内部同步',
    ];
}

function service_ticket_update_visibility_options(): array
{
    return [
        'internal' => '内部备注',
        'customer' => '客户回告',
        'leader' => '领导同步',
    ];
}

function service_ticket_source_label(string $source): string
{
    return (string) (service_ticket_source_options()[$source] ?? '未知来源');
}

function service_ticket_channel_label(string $channel): string
{
    return (string) (service_ticket_channel_options()[$channel] ?? '未知渠道');
}

function service_ticket_category_label(string $category): string
{
    return (string) (service_ticket_category_options()[$category] ?? '未知分类');
}

function service_ticket_status_label(string $status): string
{
    return (string) (service_ticket_status_options()[$status] ?? '未知状态');
}

function service_ticket_status_tone(string $status): string
{
    return [
        'new' => 'warning',
        'processing' => 'info',
        'waiting_customer' => 'neutral',
        'escalated' => 'danger',
        'resolved' => 'success',
        'closed' => 'neutral',
    ][$status] ?? 'neutral';
}

function service_ticket_update_type_label(string $type): string
{
    return (string) (service_ticket_update_type_options()[$type] ?? '跟进记录');
}

function service_ticket_update_visibility_label(string $visibility): string
{
    return (string) (service_ticket_update_visibility_options()[$visibility] ?? '内部备注');
}

function service_ticket_update_visibility_tone(string $visibility): string
{
    return [
        'internal' => 'neutral',
        'customer' => 'success',
        'leader' => 'warning',
    ][$visibility] ?? 'neutral';
}

function service_ticket_customer_notified_label(bool $notified): string
{
    return $notified ? '已回告客户' : '待回告客户';
}

function service_ticket_customer_notified_tone(bool $notified): string
{
    return $notified ? 'success' : 'warning';
}

function service_ticket_customer_confirmed_label(bool $confirmed): string
{
    return $confirmed ? '客户已确认' : '待客户确认';
}

function service_ticket_customer_confirmed_tone(bool $confirmed): string
{
    return $confirmed ? 'success' : 'neutral';
}

function service_ticket_number(array $current = []): string
{
    if ((string) ($current['ticket_no'] ?? '') !== '') {
        return (string) $current['ticket_no'];
    }

    return 'SR' . date('YmdHis') . mt_rand(10, 99);
}

function service_ticket_payload_from_request(array $source, array $current = []): array
{
    return [
        'id' => (string) ($current['id'] ?? next_id('service')),
        'ticket_no' => service_ticket_number($current),
        'source' => input_string($source, 'source', (string) ($current['source'] ?? 'customer')),
        'customer' => input_string($source, 'customer', (string) ($current['customer'] ?? '')),
        'contact_name' => input_string($source, 'contact_name', (string) ($current['contact_name'] ?? '')),
        'contact_phone' => input_string($source, 'contact_phone', (string) ($current['contact_phone'] ?? '')),
        'channel' => input_string($source, 'channel', (string) ($current['channel'] ?? 'app')),
        'category' => input_string($source, 'category', (string) ($current['category'] ?? 'usage')),
        'title' => input_string($source, 'title', (string) ($current['title'] ?? '')),
        'summary' => input_string($source, 'summary', (string) ($current['summary'] ?? '')),
        'status' => input_string($source, 'status', (string) ($current['status'] ?? 'new')),
        'priority' => input_string($source, 'priority', (string) ($current['priority'] ?? 'medium')),
        'assignee' => input_string($source, 'assignee', (string) ($current['assignee'] ?? '')),
        'opened_at' => input_string($source, 'opened_at', (string) ($current['opened_at'] ?? date('Y-m-d H:i:s'))),
        'last_follow_up_at' => input_string($source, 'last_follow_up_at', (string) ($current['last_follow_up_at'] ?? date('Y-m-d H:i:s'))),
        'resolve_due_at' => input_string($source, 'resolve_due_at', (string) ($current['resolve_due_at'] ?? '')),
        'next_action' => input_string($source, 'next_action', (string) ($current['next_action'] ?? '')),
        'customer_notified' => filter_var($source['customer_notified'] ?? ($current['customer_notified'] ?? false), FILTER_VALIDATE_BOOL),
        'customer_notified_to' => input_string($source, 'customer_notified_to', (string) ($current['customer_notified_to'] ?? '')),
        'customer_notified_channel' => input_string($source, 'customer_notified_channel', (string) ($current['customer_notified_channel'] ?? '')),
        'customer_notified_at' => input_string($source, 'customer_notified_at', (string) ($current['customer_notified_at'] ?? '')),
        'customer_feedback_result' => input_string($source, 'customer_feedback_result', (string) ($current['customer_feedback_result'] ?? '')),
        'customer_confirmed' => filter_var($source['customer_confirmed'] ?? ($current['customer_confirmed'] ?? false), FILTER_VALIDATE_BOOL),
        'customer_confirmed_at' => input_string($source, 'customer_confirmed_at', (string) ($current['customer_confirmed_at'] ?? '')),
        'customer_confirmation_note' => input_string($source, 'customer_confirmation_note', (string) ($current['customer_confirmation_note'] ?? '')),
        'ops_project_id' => input_string($source, 'ops_project_id', (string) ($current['ops_project_id'] ?? '')),
        'project_id' => input_string($source, 'project_id', (string) ($current['project_id'] ?? '')),
        'tech_ticket_id' => input_string($source, 'tech_ticket_id', (string) ($current['tech_ticket_id'] ?? '')),
        'notes' => input_string($source, 'notes', (string) ($current['notes'] ?? '')),
    ];
}

function service_ticket_update_payload_from_request(array $source, array $current = []): array
{
    return [
        'id' => (string) ($current['id'] ?? next_id('service-update')),
        'service_ticket_id' => input_string($source, 'service_ticket_id', (string) ($current['service_ticket_id'] ?? '')),
        'type' => input_string($source, 'type', (string) ($current['type'] ?? 'follow_up')),
        'visibility' => input_string($source, 'visibility', (string) ($current['visibility'] ?? 'internal')),
        'content' => input_string($source, 'content', (string) ($current['content'] ?? '')),
        'status' => input_string($source, 'status', (string) ($current['status'] ?? '')),
        'next_action' => input_string($source, 'next_action', (string) ($current['next_action'] ?? '')),
        'created_at' => input_string($source, 'created_at', (string) ($current['created_at'] ?? date('Y-m-d H:i:s'))),
    ];
}

function service_release_priority(array $release): int
{
    if (function_exists('tech_release_priority')) {
        return tech_release_priority($release);
    }

    return match ((string) ($release['release_status'] ?? '')) {
        'rolled_back' => 6,
        'ready' => 5,
        'testing' => 4,
        'planned' => 3,
        'released' => 2,
        default => 1,
    };
}

function service_ticket_release_lookup(array $releases): array
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
        $candidate['release_priority'] = service_release_priority($candidate);

        foreach (ops_string_array($release['service_ticket_ids'] ?? []) as $ticketId) {
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

function service_ticket_update_rows(array $data): array
{
    $userLookup = user_lookup($data['users'] ?? []);
    $ticketLookup = [];
    $rows = [];

    foreach ($data['service_tickets'] ?? [] as $ticket) {
        $ticketId = (string) ($ticket['id'] ?? '');

        if ($ticketId !== '') {
            $ticketLookup[$ticketId] = $ticket;
        }
    }

    foreach ($data['service_ticket_updates'] ?? [] as $update) {
        $ticketId = (string) ($update['service_ticket_id'] ?? '');
        $ticket = $ticketLookup[$ticketId] ?? [];
        $type = (string) ($update['type'] ?? 'follow_up');
        $visibility = (string) ($update['visibility'] ?? 'internal');
        $status = (string) ($update['status'] ?? '');

        $rows[] = array_merge([
            'id' => (string) ($update['id'] ?? ''),
            'service_ticket_id' => $ticketId,
            'service_ticket_title' => (string) ($ticket['title'] ?? ''),
            'service_ticket_no' => (string) ($ticket['ticket_no'] ?? ''),
            'type' => $type,
            'type_label' => service_ticket_update_type_label($type),
            'visibility' => $visibility,
            'visibility_label' => service_ticket_update_visibility_label($visibility),
            'visibility_tone' => service_ticket_update_visibility_tone($visibility),
            'content' => (string) ($update['content'] ?? ''),
            'status' => $status,
            'status_label' => $status !== '' ? service_ticket_status_label($status) : '',
            'status_tone' => $status !== '' ? service_ticket_status_tone($status) : 'neutral',
            'next_action' => (string) ($update['next_action'] ?? ''),
            'occurred_at' => (string) ($update['created_at'] ?? ''),
        ], record_audit_fields($update, $userLookup));
    }

    usort($rows, static function (array $left, array $right): int {
        return strcmp((string) ($left['occurred_at'] ?? ''), (string) ($right['occurred_at'] ?? ''));
    });

    return $rows;
}

function service_ticket_rows(array $data): array
{
    $opsLookup = ops_project_lookup($data['ops_projects'] ?? []);
    $projectLookup = project_lookup($data['projects'] ?? []);
    $userLookup = user_lookup($data['users'] ?? []);
    $directReleaseLookup = service_ticket_release_lookup($data['ops_releases'] ?? []);
    $techLookup = [];
    $updatesByTicket = [];
    $rows = [];

    foreach (service_ticket_update_rows($data) as $updateRow) {
        $ticketId = (string) ($updateRow['service_ticket_id'] ?? '');

        if ($ticketId === '') {
            continue;
        }

        $updatesByTicket[$ticketId] ??= [];
        $updatesByTicket[$ticketId][] = $updateRow;
    }

    foreach (tech_ticket_rows($data) as $ticket) {
        $ticketId = (string) ($ticket['id'] ?? '');

        if ($ticketId !== '') {
            $techLookup[$ticketId] = $ticket;
        }
    }

    foreach ($data['service_tickets'] ?? [] as $ticket) {
        $opsProjectId = (string) ($ticket['ops_project_id'] ?? '');
        $projectId = (string) ($ticket['project_id'] ?? '');
        $techTicketId = (string) ($ticket['tech_ticket_id'] ?? '');
        $opsProject = $opsLookup[$opsProjectId] ?? [];
        $techTicket = $techLookup[$techTicketId] ?? [];
        $release = (string) ($techTicket['release_id'] ?? '') !== ''
            ? [
                'release_id' => (string) ($techTicket['release_id'] ?? ''),
                'release_version' => (string) ($techTicket['release_version'] ?? ''),
                'release_title' => (string) ($techTicket['release_title'] ?? ''),
                'release_status' => (string) ($techTicket['release_status'] ?? ''),
                'release_status_label' => (string) ($techTicket['release_status_label'] ?? ''),
                'release_status_tone' => (string) ($techTicket['release_status_tone'] ?? 'neutral'),
                'release_date' => (string) ($techTicket['release_date'] ?? ''),
                'release_notes' => (string) ($techTicket['release_notes'] ?? ''),
                'release_waiting' => (bool) ($techTicket['waiting_release'] ?? false),
                'release_attention' => (bool) ($techTicket['release_attention'] ?? false),
            ]
            : ($directReleaseLookup[(string) ($ticket['id'] ?? '')] ?? []);
        $status = (string) ($ticket['status'] ?? 'new');
        $category = (string) ($ticket['category'] ?? 'usage');
        $priority = (string) ($ticket['priority'] ?? 'medium');
        $source = (string) ($ticket['source'] ?? 'customer');
        $releaseVersion = (string) ($release['release_version'] ?? '');
        $releaseTitle = (string) ($release['release_title'] ?? '');
        $releaseDisplay = trim($releaseVersion . ($releaseTitle !== '' ? ' / ' . $releaseTitle : ''), ' /');
        $ticketUpdates = $updatesByTicket[(string) ($ticket['id'] ?? '')] ?? [];
        $customerReplyCount = 0;
        $leaderSyncCount = 0;
        $internalNoteCount = 0;
        $latestCustomerUpdate = null;

        foreach ($ticketUpdates as $updateRow) {
            $visibility = (string) ($updateRow['visibility'] ?? 'internal');

            if ($visibility === 'customer') {
                $customerReplyCount++;
                $latestCustomerUpdate = $updateRow;
                continue;
            }

            if ($visibility === 'leader') {
                $leaderSyncCount++;
                continue;
            }

            $internalNoteCount++;
        }

        $initialTimeline = [
            'id' => 'initial-' . (string) ($ticket['id'] ?? ''),
            'service_ticket_id' => (string) ($ticket['id'] ?? ''),
            'type' => 'internal',
            'type_label' => '问题登记',
            'visibility' => 'internal',
            'visibility_label' => service_ticket_update_visibility_label('internal'),
            'visibility_tone' => service_ticket_update_visibility_tone('internal'),
            'content' => '问题已登记，等待处理人与运营继续跟进。',
            'status' => $status,
            'status_label' => service_ticket_status_label($status),
            'status_tone' => service_ticket_status_tone($status),
            'next_action' => (string) ($ticket['next_action'] ?? ''),
            'occurred_at' => (string) ($ticket['opened_at'] ?? ($ticket['created_at'] ?? '')),
            'created_by' => (string) ($ticket['created_by'] ?? ''),
            'created_by_name' => (string) ($ticket['created_by_name'] ?? '系统'),
            'updated_by' => (string) ($ticket['updated_by'] ?? ''),
            'updated_by_name' => (string) ($ticket['updated_by_name'] ?? ''),
            'created_at' => (string) ($ticket['created_at'] ?? ($ticket['opened_at'] ?? '')),
            'updated_at' => (string) ($ticket['updated_at'] ?? ($ticket['created_at'] ?? '')),
        ];
        $timeline = array_merge([$initialTimeline], $ticketUpdates);
        $latestTimeline = $timeline === [] ? null : $timeline[array_key_last($timeline)];
        $customerConfirmed = (bool) ($ticket['customer_confirmed'] ?? false);
        $customerNotified = (bool) ($ticket['customer_notified'] ?? false) || $customerReplyCount > 0 || $customerConfirmed;
        $customerNotifiedTo = (string) ($ticket['customer_notified_to'] ?? '');
        $customerNotifiedChannel = (string) ($ticket['customer_notified_channel'] ?? '');
        $customerNotifiedAt = (string) ($ticket['customer_notified_at'] ?? '');
        $customerFeedbackResult = (string) ($ticket['customer_feedback_result'] ?? '');
        $customerConfirmedAt = (string) ($ticket['customer_confirmed_at'] ?? '');
        $customerConfirmationNote = (string) ($ticket['customer_confirmation_note'] ?? '');

        if ($customerNotifiedAt === '' && is_array($latestCustomerUpdate)) {
            $customerNotifiedAt = (string) ($latestCustomerUpdate['occurred_at'] ?? '');
        }

        if ($customerFeedbackResult === '' && is_array($latestCustomerUpdate)) {
            $customerFeedbackResult = (string) ($latestCustomerUpdate['content'] ?? '');
        }

        if ($customerNotifiedTo === '') {
            $customerNotifiedTo = (string) ($ticket['contact_name'] ?? ($ticket['customer'] ?? ''));
        }

        if ($customerNotifiedChannel === '') {
            $customerNotifiedChannel = (string) ($ticket['channel'] ?? '');
        }

        if ($customerConfirmed && $customerConfirmedAt === '') {
            $customerConfirmedAt = $customerNotifiedAt;
        }

        $row = [
            'id' => (string) ($ticket['id'] ?? ''),
            'ticket_no' => (string) ($ticket['ticket_no'] ?? ''),
            'source' => $source,
            'source_label' => service_ticket_source_label($source),
            'customer' => (string) ($ticket['customer'] ?? ''),
            'contact_name' => (string) ($ticket['contact_name'] ?? ''),
            'contact_phone' => (string) ($ticket['contact_phone'] ?? ''),
            'channel' => (string) ($ticket['channel'] ?? 'app'),
            'channel_label' => service_ticket_channel_label((string) ($ticket['channel'] ?? 'app')),
            'category' => $category,
            'category_label' => service_ticket_category_label($category),
            'title' => (string) ($ticket['title'] ?? ''),
            'summary' => (string) ($ticket['summary'] ?? ''),
            'status' => $status,
            'status_label' => service_ticket_status_label($status),
            'status_tone' => service_ticket_status_tone($status),
            'priority' => $priority,
            'priority_label' => priority_label($priority),
            'priority_tone' => priority_tone($priority),
            'assignee' => (string) ($ticket['assignee'] ?? ''),
            'opened_at' => (string) ($ticket['opened_at'] ?? ''),
            'last_follow_up_at' => (string) ($ticket['last_follow_up_at'] ?? ''),
            'resolve_due_at' => (string) ($ticket['resolve_due_at'] ?? ''),
            'next_action' => (string) ($ticket['next_action'] ?? ''),
            'customer_notified' => $customerNotified,
            'customer_notified_label' => service_ticket_customer_notified_label($customerNotified),
            'customer_notified_tone' => service_ticket_customer_notified_tone($customerNotified),
            'customer_notified_to' => $customerNotifiedTo,
            'customer_notified_channel' => $customerNotifiedChannel,
            'customer_notified_channel_label' => $customerNotifiedChannel !== '' ? service_ticket_channel_label($customerNotifiedChannel) : '',
            'customer_notified_at' => $customerNotifiedAt,
            'customer_feedback_result' => $customerFeedbackResult,
            'customer_confirmed' => $customerConfirmed,
            'customer_confirmed_label' => service_ticket_customer_confirmed_label($customerConfirmed),
            'customer_confirmed_tone' => service_ticket_customer_confirmed_tone($customerConfirmed),
            'customer_confirmed_at' => $customerConfirmedAt,
            'customer_confirmation_note' => $customerConfirmationNote,
            'ops_project_id' => $opsProjectId,
            'ops_project_name' => (string) ($opsProject['name'] ?? '未关联 APP 项目'),
            'app_name' => (string) ($opsProject['app_name'] ?? ''),
            'project_id' => $projectId,
            'project_name' => project_name($projectLookup, $projectId),
            'tech_ticket_id' => $techTicketId,
            'tech_ticket_title' => (string) ($techTicket['title'] ?? ''),
            'release_id' => (string) ($release['release_id'] ?? ''),
            'release_version' => $releaseVersion,
            'release_title' => $releaseTitle,
            'release_display' => $releaseDisplay,
            'release_status' => (string) ($release['release_status'] ?? ''),
            'release_status_label' => (string) ($release['release_status_label'] ?? ''),
            'release_status_tone' => (string) ($release['release_status_tone'] ?? 'neutral'),
            'release_date' => (string) ($release['release_date'] ?? ''),
            'release_notes' => (string) ($release['release_notes'] ?? ''),
            'waiting_release' => (bool) ($release['release_waiting'] ?? false),
            'release_attention' => (bool) ($release['release_attention'] ?? false),
            'leader_feedback' => $source === 'leader',
            'needs_escalation' => $status === 'escalated' || ($techTicketId === '' && in_array($category, ['bug', 'feature', 'complaint', 'feedback'], true)),
            'resolve_overdue' => is_overdue((string) ($ticket['resolve_due_at'] ?? ''), $status, ['resolved', 'closed']),
            'timeline' => $timeline,
            'timeline_count' => count($timeline),
            'customer_reply_count' => $customerReplyCount,
            'leader_sync_count' => $leaderSyncCount,
            'internal_note_count' => $internalNoteCount,
            'last_timeline_at' => (string) ($latestTimeline['occurred_at'] ?? ''),
            'last_timeline_content' => (string) ($latestTimeline['content'] ?? ''),
            'notes' => (string) ($ticket['notes'] ?? ''),
        ];

        $rows[] = array_merge($row, record_audit_fields($ticket, $userLookup));
    }

    usort($rows, static function (array $left, array $right): int {
        if ((bool) ($left['resolve_overdue'] ?? false) !== (bool) ($right['resolve_overdue'] ?? false)) {
            return (bool) ($left['resolve_overdue'] ?? false) ? -1 : 1;
        }

        if ((bool) ($left['leader_feedback'] ?? false) !== (bool) ($right['leader_feedback'] ?? false)) {
            return (bool) ($left['leader_feedback'] ?? false) ? -1 : 1;
        }

        if ((bool) ($left['release_attention'] ?? false) !== (bool) ($right['release_attention'] ?? false)) {
            return (bool) ($left['release_attention'] ?? false) ? -1 : 1;
        }

        if ((string) ($left['status'] ?? '') !== (string) ($right['status'] ?? '')) {
            return strcmp((string) ($left['status'] ?? ''), (string) ($right['status'] ?? ''));
        }

        return strcmp((string) ($right['opened_at'] ?? ''), (string) ($left['opened_at'] ?? ''));
    });

    return $rows;
}

function service_summary(array $data): array
{
    $rows = service_ticket_rows($data);
    $summary = [
        'open_total' => 0,
        'new_total' => 0,
        'waiting_customer_total' => 0,
        'escalated_total' => 0,
        'overdue_total' => 0,
        'waiting_release_total' => 0,
        'release_attention_total' => 0,
        'leader_feedback_total' => 0,
        'resolved_today' => 0,
    ];
    $today = date('Y-m-d');

    foreach ($rows as $row) {
        $status = (string) ($row['status'] ?? '');
        $isOpen = !in_array($status, ['resolved', 'closed'], true);

        if ($isOpen) {
            $summary['open_total']++;
        }

        if ($status === 'new') {
            $summary['new_total']++;
        }

        if ($status === 'waiting_customer') {
            $summary['waiting_customer_total']++;
        }

        if ($status === 'escalated') {
            $summary['escalated_total']++;
        }

        if ((bool) ($row['resolve_overdue'] ?? false)) {
            $summary['overdue_total']++;
        }

        if ($isOpen && (bool) ($row['waiting_release'] ?? false)) {
            $summary['waiting_release_total']++;
        }

        if ($isOpen && (bool) ($row['release_attention'] ?? false)) {
            $summary['release_attention_total']++;
        }

        if ($isOpen && (bool) ($row['leader_feedback'] ?? false)) {
            $summary['leader_feedback_total']++;
        }

        if ($status === 'resolved' && str_starts_with((string) ($row['updated_at'] ?? ''), $today)) {
            $summary['resolved_today']++;
        }
    }

    return $summary;
}
