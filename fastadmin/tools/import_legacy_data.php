<?php

declare(strict_types=1);

if (!function_exists('input_string')) {
    function input_string(array $source, string $key, string $default = ''): string
    {
        return trim((string) ($source[$key] ?? $default));
    }
}

if (!function_exists('normalize_login_identity')) {
    function normalize_login_identity(string $value): string
    {
        return strtolower(trim($value));
    }
}

if (!function_exists('default_initial_password')) {
    function default_initial_password(): string
    {
        return 'Start@123';
    }
}

if (!function_exists('hash_user_password')) {
    function hash_user_password(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT) ?: '';
    }
}

require dirname(__DIR__, 2) . '/src/storage.php';

$root = dirname(__DIR__);
$env = parse_ini_file($root . '/.env', true, INI_SCANNER_RAW) ?: [];
$db = $env['database'] ?? [];
$pdo = new PDO(
    sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $db['hostname'] ?? '127.0.0.1',
        $db['hostport'] ?? '3306',
        $db['database'] ?? ''
    ),
    (string) ($db['username'] ?? ''),
    (string) ($db['password'] ?? ''),
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

$data = load_legacy_data(dirname(__DIR__, 2));
$ctx = [
    'user_ids' => [],
    'project_ids' => [],
    'app_project_ids' => [],
    'tech_ticket_ids' => [],
    'issue_ids' => [],
    'material_ids' => [],
    'users' => $data['users'] ?? [],
];

$pdo->beginTransaction();

try {
    ensure_auth_groups($pdo);
    import_admins($pdo, $data['users'] ?? [], $ctx);
    import_staff_profiles($pdo, $data['users'] ?? [], $ctx);
    import_generic($pdo, $data['projects'] ?? [], 'fa_project', 'legacy_id', function (array $row) use (&$ctx): array {
        $legacyId = s($row['id'] ?? '');
        return [
            'legacy_id' => $legacyId,
            'name' => s($row['name'] ?? ''),
            'client' => s($row['client'] ?? ''),
            'owner' => s($row['owner'] ?? ''),
            'owner_admin_id' => admin_id_by_name($ctx, $row['owner'] ?? ''),
            'status' => enumv($row['status'] ?? 'planning', ['planning','active','delivery','completed','paused','closed'], 'planning'),
            'priority' => enumv($row['priority'] ?? 'medium', ['low','medium','high','urgent'], 'medium'),
            'budget' => money($row['budget'] ?? 0),
            'start_date' => d($row['start_date'] ?? null),
            'due_date' => d($row['due_date'] ?? null),
            'description' => s($row['description'] ?? ''),
            'createtime' => time(),
            'updatetime' => time(),
        ];
    }, $ctx['project_ids']);
    import_generic($pdo, $data['tasks'] ?? [], 'fa_project_task', 'legacy_id', function (array $row) use (&$ctx): array {
        $projectLegacyId = s($row['project_id'] ?? '');
        return [
            'legacy_id' => s($row['id'] ?? ''),
            'project_legacy_id' => $projectLegacyId,
            'project_id' => (int) ($ctx['project_ids'][$projectLegacyId] ?? 0),
            'title' => s($row['title'] ?? ''),
            'assignee' => s($row['assignee'] ?? ''),
            'assignee_admin_id' => admin_id_by_name($ctx, $row['assignee'] ?? ''),
            'status' => enumv($row['status'] ?? 'todo', ['todo','doing','review','done','blocked','overdue'], 'todo'),
            'priority' => enumv($row['priority'] ?? 'medium', ['low','medium','high','urgent'], 'medium'),
            'due_date' => d($row['due_date'] ?? null),
            'estimate_hours' => money($row['estimate_hours'] ?? 0),
            'actual_hours' => money($row['actual_hours'] ?? 0),
            'notes' => s($row['notes'] ?? ''),
            'createtime' => time(),
            'updatetime' => time(),
        ];
    });
    import_generic($pdo, $data['transactions'] ?? [], 'fa_finance_transaction', 'legacy_id', function (array $row) use (&$ctx): array {
        $projectLegacyId = s($row['project_id'] ?? '');
        $createdBy = s($row['created_by'] ?? '');
        $updatedBy = s($row['updated_by'] ?? '');
        return [
            'legacy_id' => s($row['id'] ?? ''),
            'transaction_date' => d($row['date'] ?? null),
            'type' => enumv($row['type'] ?? 'expense', ['income','expense'], 'expense'),
            'category' => s($row['category'] ?? ''),
            'counterparty' => s($row['counterparty'] ?? ''),
            'amount' => money($row['amount'] ?? 0),
            'payment_method' => enumv($row['payment_method'] ?? 'bank', ['bank','wechat','alipay','cash','other'], 'bank'),
            'project_legacy_id' => $projectLegacyId,
            'project_id' => (int) ($ctx['project_ids'][$projectLegacyId] ?? 0),
            'notes' => s($row['notes'] ?? ''),
            'attachment_ids_json' => jsons($row['attachments'] ?? []),
            'record_created_at' => dt($row['created_at'] ?? null),
            'record_updated_at' => dt($row['updated_at'] ?? null),
            'created_by_legacy_id' => $createdBy,
            'created_by_admin_id' => (int) ($ctx['user_ids'][$createdBy] ?? 0),
            'created_by_name' => s($row['created_by_name'] ?? ''),
            'updated_by_legacy_id' => $updatedBy,
            'updated_by_admin_id' => (int) ($ctx['user_ids'][$updatedBy] ?? 0),
            'updated_by_name' => s($row['updated_by_name'] ?? ''),
            'createtime' => time(),
            'updatetime' => time(),
        ];
    });
    import_generic($pdo, $data['invoices'] ?? [], 'fa_finance_invoice', 'legacy_id', function (array $row) use (&$ctx): array {
        $projectLegacyId = s($row['project_id'] ?? '');
        $createdBy = s($row['created_by'] ?? '');
        $updatedBy = s($row['updated_by'] ?? '');
        return [
            'legacy_id' => s($row['id'] ?? ''),
            'kind' => enumv($row['kind'] ?? 'receivable', ['receivable','payable'], 'receivable'),
            'title' => s($row['title'] ?? ''),
            'counterparty' => s($row['counterparty'] ?? ''),
            'amount' => money($row['amount'] ?? 0),
            'due_date' => d($row['due_date'] ?? null),
            'status' => enumv($row['status'] ?? 'pending', ['pending','partial','paid','overdue','cancelled'], 'pending'),
            'project_legacy_id' => $projectLegacyId,
            'project_id' => (int) ($ctx['project_ids'][$projectLegacyId] ?? 0),
            'notes' => s($row['notes'] ?? ''),
            'attachment_ids_json' => jsons($row['attachments'] ?? []),
            'record_created_at' => dt($row['created_at'] ?? null),
            'record_updated_at' => dt($row['updated_at'] ?? null),
            'created_by_legacy_id' => $createdBy,
            'created_by_admin_id' => (int) ($ctx['user_ids'][$createdBy] ?? 0),
            'created_by_name' => s($row['created_by_name'] ?? ''),
            'updated_by_legacy_id' => $updatedBy,
            'updated_by_admin_id' => (int) ($ctx['user_ids'][$updatedBy] ?? 0),
            'updated_by_name' => s($row['updated_by_name'] ?? ''),
            'createtime' => time(),
            'updatetime' => time(),
        ];
    });
    import_generic($pdo, $data['ops_projects'] ?? [], 'fa_app_project', 'legacy_id', function (array $row) use (&$ctx): array {
        $projectLegacyId = s($row['project_id'] ?? '');
        return [
            'legacy_id' => s($row['id'] ?? ''),
            'name' => s($row['name'] ?? ''),
            'app_name' => s($row['app_name'] ?? ''),
            'app_version' => s($row['app_version'] ?? ''),
            'lifecycle_stage' => enumv($row['lifecycle_stage'] ?? 'validation', ['idea','validation','launch','growth','retention','mature','sunset'], 'validation'),
            'business_line' => s($row['business_line'] ?? ''),
            'manager' => s($row['manager'] ?? ''),
            'manager_admin_id' => admin_id_by_name($ctx, $row['manager'] ?? ''),
            'client_owner' => s($row['client_owner'] ?? ''),
            'core_metric' => s($row['core_metric'] ?? ''),
            'status' => enumv($row['status'] ?? 'planning', ['planning','running','paused','completed','archived'], 'planning'),
            'priority' => enumv($row['priority'] ?? 'medium', ['low','medium','high','urgent'], 'medium'),
            'budget' => money($row['budget'] ?? 0),
            'actual_cost' => money($row['actual_cost'] ?? 0),
            'start_date' => d($row['start_date'] ?? null),
            'end_date' => d($row['end_date'] ?? null),
            'target' => s($row['target'] ?? ''),
            'channel' => s($row['channel'] ?? ''),
            'project_legacy_id' => $projectLegacyId,
            'project_id' => (int) ($ctx['project_ids'][$projectLegacyId] ?? 0),
            'description' => s($row['description'] ?? ''),
            'createtime' => time(),
            'updatetime' => time(),
        ];
    }, $ctx['app_project_ids']);
    import_generic($pdo, $data['ops_milestones'] ?? [], 'fa_app_milestone', 'legacy_id', function (array $row) use (&$ctx): array {
        $appLegacyId = s($row['ops_project_id'] ?? '');
        return [
            'legacy_id' => s($row['id'] ?? ''),
            'app_project_legacy_id' => $appLegacyId,
            'app_project_id' => (int) ($ctx['app_project_ids'][$appLegacyId] ?? 0),
            'title' => s($row['title'] ?? ''),
            'owner' => s($row['owner'] ?? ''),
            'owner_admin_id' => admin_id_by_name($ctx, $row['owner'] ?? ''),
            'due_date' => d($row['due_date'] ?? null),
            'status' => enumv($row['status'] ?? 'pending', ['pending','doing','review','done','blocked'], 'pending'),
            'progress' => (int) ($row['progress'] ?? 0),
            'deliverable' => s($row['deliverable'] ?? ''),
            'notes' => s($row['notes'] ?? ''),
            'createtime' => time(),
            'updatetime' => time(),
        ];
    });
    import_generic($pdo, $data['ops_updates'] ?? [], 'fa_app_report', 'legacy_id', function (array $row) use (&$ctx): array {
        $appLegacyId = s($row['ops_project_id'] ?? '');
        $createdBy = s($row['created_by'] ?? '');
        $updatedBy = s($row['updated_by'] ?? '');
        return [
            'legacy_id' => s($row['id'] ?? ''),
            'app_project_legacy_id' => $appLegacyId,
            'app_project_id' => (int) ($ctx['app_project_ids'][$appLegacyId] ?? 0),
            'report_date' => d($row['report_date'] ?? null),
            'owner' => s($row['owner'] ?? ''),
            'owner_admin_id' => admin_id_by_name($ctx, $row['owner'] ?? ''),
            'summary' => s($row['summary'] ?? ''),
            'result' => s($row['result'] ?? ''),
            'next_actions' => s($row['next_actions'] ?? ''),
            'blockers' => s($row['blockers'] ?? ''),
            'record_created_at' => dt($row['created_at'] ?? null),
            'record_updated_at' => dt($row['updated_at'] ?? null),
            'created_by_legacy_id' => $createdBy,
            'created_by_admin_id' => (int) ($ctx['user_ids'][$createdBy] ?? 0),
            'created_by_name' => s($row['created_by_name'] ?? ''),
            'updated_by_legacy_id' => $updatedBy,
            'updated_by_admin_id' => (int) ($ctx['user_ids'][$updatedBy] ?? 0),
            'updated_by_name' => s($row['updated_by_name'] ?? ''),
            'createtime' => time(),
            'updatetime' => time(),
        ];
    });
    import_generic($pdo, $data['ops_risks'] ?? [], 'fa_app_risk', 'legacy_id', function (array $row) use (&$ctx): array {
        $appLegacyId = s($row['ops_project_id'] ?? '');
        return [
            'legacy_id' => s($row['id'] ?? ''),
            'app_project_legacy_id' => $appLegacyId,
            'app_project_id' => (int) ($ctx['app_project_ids'][$appLegacyId] ?? 0),
            'title' => s($row['title'] ?? ''),
            'type' => enumv($row['type'] ?? 'risk', ['risk','issue','change','dependency'], 'risk'),
            'level' => enumv($row['level'] ?? 'medium', ['low','medium','high','critical'], 'medium'),
            'status' => enumv($row['status'] ?? 'open', ['open','tracking','resolved','closed'], 'open'),
            'owner' => s($row['owner'] ?? ''),
            'owner_admin_id' => admin_id_by_name($ctx, $row['owner'] ?? ''),
            'due_date' => d($row['due_date'] ?? null),
            'impact' => s($row['impact'] ?? ''),
            'action_plan' => s($row['action_plan'] ?? ''),
            'createtime' => time(),
            'updatetime' => time(),
        ];
    });
    import_generic($pdo, $data['tech_tickets'] ?? [], 'fa_app_tech_ticket', 'legacy_id', function (array $row) use (&$ctx): array {
        $appLegacyId = s($row['ops_project_id'] ?? '');
        $projectLegacyId = s($row['project_id'] ?? '');
        return [
            'legacy_id' => s($row['id'] ?? ''),
            'app_project_legacy_id' => $appLegacyId,
            'app_project_id' => (int) ($ctx['app_project_ids'][$appLegacyId] ?? 0),
            'project_legacy_id' => $projectLegacyId,
            'project_id' => (int) ($ctx['project_ids'][$projectLegacyId] ?? 0),
            'title' => s($row['title'] ?? ''),
            'type' => enumv($row['type'] ?? 'bug', ['bug','improvement','upgrade','task'], 'bug'),
            'status' => enumv($row['status'] ?? 'pending', ['pending','processing','testing','ready','done','closed'], 'pending'),
            'priority' => enumv($row['priority'] ?? 'medium', ['low','medium','high','urgent'], 'medium'),
            'severity' => enumv($row['severity'] ?? 'medium', ['low','medium','high','blocker'], 'medium'),
            'source' => enumv($row['source'] ?? 'operations', ['operations','product','customer','sales','service'], 'operations'),
            'app_module' => s($row['app_module'] ?? ''),
            'app_version' => s($row['app_version'] ?? ''),
            'owner' => s($row['owner'] ?? ''),
            'owner_admin_id' => admin_id_by_name($ctx, $row['owner'] ?? ''),
            'reporter' => s($row['reporter'] ?? ''),
            'reporter_admin_id' => admin_id_by_name($ctx, $row['reporter'] ?? ''),
            'due_date' => d($row['due_date'] ?? null),
            'impact' => s($row['impact'] ?? ''),
            'solution_plan' => s($row['solution_plan'] ?? ''),
            'estimate_hours' => money($row['estimate_hours'] ?? 0),
            'actual_hours' => money($row['actual_hours'] ?? 0),
            'notes' => s($row['notes'] ?? ''),
            'createtime' => time(),
            'updatetime' => time(),
        ];
    }, $ctx['tech_ticket_ids']);
    import_generic($pdo, $data['service_tickets'] ?? [], 'fa_app_issue', 'legacy_id', function (array $row) use (&$ctx): array {
        $appLegacyId = s($row['ops_project_id'] ?? '');
        $projectLegacyId = s($row['project_id'] ?? '');
        $techLegacyId = s($row['tech_ticket_id'] ?? '');
        $createdBy = s($row['created_by'] ?? '');
        $updatedBy = s($row['updated_by'] ?? '');
        return [
            'legacy_id' => s($row['id'] ?? ''),
            'ticket_no' => s($row['ticket_no'] ?? ''),
            'source' => enumv($row['source'] ?? 'customer', ['customer','training','sales','operations','other'], 'customer'),
            'customer' => s($row['customer'] ?? ''),
            'contact_name' => s($row['contact_name'] ?? ''),
            'contact_phone' => s($row['contact_phone'] ?? ''),
            'channel' => enumv($row['channel'] ?? 'wechat', ['wechat','phone','email','app','onsite','other'], 'wechat'),
            'category' => enumv($row['category'] ?? 'usage', ['bug','usage','billing','feature','training','other'], 'usage'),
            'title' => s($row['title'] ?? ''),
            'summary' => s($row['summary'] ?? ''),
            'status' => enumv($row['status'] ?? 'new', ['new','processing','waiting_customer','escalated','resolved','closed'], 'new'),
            'priority' => enumv($row['priority'] ?? 'medium', ['low','medium','high','urgent'], 'medium'),
            'assignee' => s($row['assignee'] ?? ''),
            'assignee_admin_id' => admin_id_by_name($ctx, $row['assignee'] ?? ''),
            'opened_at' => dt($row['opened_at'] ?? null),
            'last_follow_up_at' => dt($row['last_follow_up_at'] ?? null),
            'resolve_due_at' => dt($row['resolve_due_at'] ?? null),
            'next_action' => s($row['next_action'] ?? ''),
            'customer_notified' => flag($row['customer_notified'] ?? false),
            'customer_notified_to' => s($row['customer_notified_to'] ?? ''),
            'customer_notified_channel' => s($row['customer_notified_channel'] ?? ''),
            'customer_notified_at' => dt($row['customer_notified_at'] ?? null),
            'customer_feedback_result' => s($row['customer_feedback_result'] ?? ''),
            'customer_confirmed' => flag($row['customer_confirmed'] ?? false),
            'customer_confirmed_at' => dt($row['customer_confirmed_at'] ?? null),
            'customer_confirmation_note' => s($row['customer_confirmation_note'] ?? ''),
            'app_project_legacy_id' => $appLegacyId,
            'app_project_id' => (int) ($ctx['app_project_ids'][$appLegacyId] ?? 0),
            'project_legacy_id' => $projectLegacyId,
            'project_id' => (int) ($ctx['project_ids'][$projectLegacyId] ?? 0),
            'tech_ticket_legacy_id' => $techLegacyId,
            'tech_ticket_id' => (int) ($ctx['tech_ticket_ids'][$techLegacyId] ?? 0),
            'notes' => s($row['notes'] ?? ''),
            'record_created_at' => dt($row['created_at'] ?? null),
            'record_updated_at' => dt($row['updated_at'] ?? null),
            'created_by_legacy_id' => $createdBy,
            'created_by_admin_id' => (int) ($ctx['user_ids'][$createdBy] ?? 0),
            'created_by_name' => s($row['created_by_name'] ?? ''),
            'updated_by_legacy_id' => $updatedBy,
            'updated_by_admin_id' => (int) ($ctx['user_ids'][$updatedBy] ?? 0),
            'updated_by_name' => s($row['updated_by_name'] ?? ''),
            'createtime' => time(),
            'updatetime' => time(),
        ];
    }, $ctx['issue_ids']);
    import_generic($pdo, $data['service_ticket_updates'] ?? [], 'fa_app_issue_followup', 'legacy_id', function (array $row) use (&$ctx): array {
        $issueLegacyId = s($row['service_ticket_id'] ?? '');
        $createdBy = s($row['created_by'] ?? '');
        $updatedBy = s($row['updated_by'] ?? '');
        return [
            'legacy_id' => s($row['id'] ?? ''),
            'issue_legacy_id' => $issueLegacyId,
            'issue_id' => (int) ($ctx['issue_ids'][$issueLegacyId] ?? 0),
            'type' => enumv($row['type'] ?? 'follow_up', ['status','follow_up','internal','leader','release'], 'follow_up'),
            'visibility' => enumv($row['visibility'] ?? 'internal', ['internal','customer','leader'], 'internal'),
            'content' => s($row['content'] ?? ''),
            'status' => s($row['status'] ?? ''),
            'next_action' => s($row['next_action'] ?? ''),
            'record_created_at' => dt($row['created_at'] ?? null),
            'record_updated_at' => dt($row['updated_at'] ?? null),
            'created_by_legacy_id' => $createdBy,
            'created_by_admin_id' => (int) ($ctx['user_ids'][$createdBy] ?? 0),
            'created_by_name' => s($row['created_by_name'] ?? ''),
            'updated_by_legacy_id' => $updatedBy,
            'updated_by_admin_id' => (int) ($ctx['user_ids'][$updatedBy] ?? 0),
            'updated_by_name' => s($row['updated_by_name'] ?? ''),
            'createtime' => time(),
            'updatetime' => time(),
        ];
    });
    import_generic($pdo, $data['ops_releases'] ?? [], 'fa_app_release', 'legacy_id', function (array $row) use (&$ctx): array {
        $appLegacyId = s($row['ops_project_id'] ?? '');
        $createdBy = s($row['created_by'] ?? '');
        $updatedBy = s($row['updated_by'] ?? '');
        return [
            'legacy_id' => s($row['id'] ?? ''),
            'app_project_legacy_id' => $appLegacyId,
            'app_project_id' => (int) ($ctx['app_project_ids'][$appLegacyId] ?? 0),
            'version' => s($row['version'] ?? ''),
            'title' => s($row['title'] ?? ''),
            'status' => enumv($row['status'] ?? 'planned', ['planned','ready','testing','released','rollback','closed'], 'planned'),
            'owner' => s($row['owner'] ?? ''),
            'owner_admin_id' => admin_id_by_name($ctx, $row['owner'] ?? ''),
            'release_date' => d($row['release_date'] ?? null),
            'channel' => s($row['channel'] ?? ''),
            'tech_ticket_ids_json' => jsons($row['tech_ticket_ids'] ?? []),
            'service_ticket_ids_json' => jsons($row['service_ticket_ids'] ?? []),
            'verification_summary' => s($row['verification_summary'] ?? ''),
            'customer_sync_status' => enumv($row['customer_sync_status'] ?? 'pending', ['pending','done','skip'], 'pending'),
            'customer_sync_note' => s($row['customer_sync_note'] ?? ''),
            'release_result' => s($row['release_result'] ?? ''),
            'release_notes' => s($row['release_notes'] ?? ''),
            'rollback_plan' => s($row['rollback_plan'] ?? ''),
            'rollback_ready' => flag($row['rollback_ready'] ?? false),
            'notes' => s($row['notes'] ?? ''),
            'record_created_at' => dt($row['created_at'] ?? null),
            'record_updated_at' => dt($row['updated_at'] ?? null),
            'created_by_legacy_id' => $createdBy,
            'created_by_admin_id' => (int) ($ctx['user_ids'][$createdBy] ?? 0),
            'created_by_name' => s($row['created_by_name'] ?? ''),
            'updated_by_legacy_id' => $updatedBy,
            'updated_by_admin_id' => (int) ($ctx['user_ids'][$updatedBy] ?? 0),
            'updated_by_name' => s($row['updated_by_name'] ?? ''),
            'createtime' => time(),
            'updatetime' => time(),
        ];
    });
    import_generic($pdo, $data['ops_materials'] ?? [], 'fa_app_material', 'legacy_id', function (array $row) use (&$ctx): array {
        $appLegacyId = s($row['ops_project_id'] ?? '');
        $createdBy = s($row['created_by'] ?? '');
        $updatedBy = s($row['updated_by'] ?? '');
        return [
            'legacy_id' => s($row['id'] ?? ''),
            'app_project_legacy_id' => $appLegacyId,
            'app_project_id' => (int) ($ctx['app_project_ids'][$appLegacyId] ?? 0),
            'title' => s($row['title'] ?? ''),
            'category' => enumv($row['category'] ?? 'manual', ['manual','faq','training','script','report','other'], 'manual'),
            'owner' => s($row['owner'] ?? ''),
            'owner_admin_id' => admin_id_by_name($ctx, $row['owner'] ?? ''),
            'version_tag' => s($row['version_tag'] ?? ''),
            'applicable_versions' => s($row['applicable_versions'] ?? ''),
            'expires_on' => d($row['expires_on'] ?? null),
            'archive_status' => enumv($row['archive_status'] ?? 'active', ['active','archived'], 'active'),
            'replacement_material_legacy_id' => s($row['replacement_material_id'] ?? ''),
            'replacement_material_id' => 0,
            'download_name' => s($row['download_name'] ?? ''),
            'download_url' => s($row['download_url'] ?? ''),
            'file_path' => s($row['file_path'] ?? ''),
            'file_size' => (int) ($row['file_size'] ?? 0),
            'file_mime' => s($row['file_mime'] ?? ''),
            'updated_on' => d($row['updated_on'] ?? null),
            'notes' => s($row['notes'] ?? ''),
            'record_created_at' => dt($row['created_at'] ?? null),
            'record_updated_at' => dt($row['updated_at'] ?? null),
            'created_by_legacy_id' => $createdBy,
            'created_by_admin_id' => (int) ($ctx['user_ids'][$createdBy] ?? 0),
            'created_by_name' => s($row['created_by_name'] ?? ''),
            'updated_by_legacy_id' => $updatedBy,
            'updated_by_admin_id' => (int) ($ctx['user_ids'][$updatedBy] ?? 0),
            'updated_by_name' => s($row['updated_by_name'] ?? ''),
            'createtime' => time(),
            'updatetime' => time(),
        ];
    }, $ctx['material_ids']);
    link_replacement_materials($pdo, $data['ops_materials'] ?? [], $ctx['material_ids']);
    import_ai($pdo, $data['ai'] ?? []);
    import_audit($pdo, $data['audit_logs'] ?? [], $ctx);
    sync_group_rules($pdo);
    sync_menu_titles($pdo);
    $pdo->commit();
    echo "Legacy data imported successfully.\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}

function load_legacy_data(string $projectRoot): array { $path = $projectRoot . '/storage/app-data.json'; if (is_file($path)) { $raw = file_get_contents($path); $json = $raw ? json_decode($raw, true) : null; if (is_array($json)) { return migrate_data(default_data(), $json); } } return default_data(); }
function ensure_auth_groups(PDO $pdo): void { foreach (['ERP 财务组','ERP 项目组','ERP 运营组','ERP 客服组','ERP 技术组','ERP 只读组'] as $name) { if (!value($pdo, 'SELECT id FROM fa_auth_group WHERE name=? LIMIT 1', [$name])) { execs($pdo, 'INSERT INTO fa_auth_group (pid,name,rules,createtime,updatetime,status) VALUES (0,?,?,?,?,"normal")', [$name,'',time(),time()]); } } }
function import_admins(PDO $pdo, array $users, array &$ctx): void { execs($pdo, 'DELETE FROM fa_auth_group_access WHERE uid>1'); foreach ($users as $row) { $username = s($row['account'] ?? ''); if ($username === '') { continue; } $salt = substr(bin2hex(random_bytes(8)), 0, 8); $password = md5(md5($username === 'admin' ? 'Admin@123' : 'Start@123') . $salt); $status = ($row['status'] ?? 'active') === 'active' ? 'normal' : 'hidden'; $id = value($pdo, 'SELECT id FROM fa_admin WHERE username=? LIMIT 1', [$username]); $payload = [$username, s($row['name'] ?? ''), $password, $salt, s($row['email'] ?? ''), s($row['phone'] ?? ''), ts($row['last_login_at'] ?? null), time(), $status]; if ($id) { execs($pdo, 'UPDATE fa_admin SET username=?,nickname=?,password=?,salt=?,email=?,mobile=?,logintime=?,updatetime=?,status=? WHERE id=' . (int) $id, $payload); } else { execs($pdo, 'INSERT INTO fa_admin (username,nickname,password,salt,email,mobile,logintime,updatetime,status,createtime,loginfailure,avatar,loginip,token) VALUES (?,?,?,?,?,?,?,?,?,?,0,"","","")', [...$payload, time()]); $id = (int) $pdo->lastInsertId(); } $legacyId = s($row['id'] ?? ''); $ctx['user_ids'][$legacyId] = (int) $id; $group = match (enumv($row['role'] ?? 'viewer', ['admin','finance','project','operations','service','tech','viewer'], 'viewer')) { 'admin' => 'Admin group', 'finance' => 'ERP 财务组', 'project' => 'ERP 项目组', 'operations' => 'ERP 运营组', 'service' => 'ERP 客服组', 'tech' => 'ERP 技术组', default => 'ERP 只读组', }; $groupId = (int) value($pdo, 'SELECT id FROM fa_auth_group WHERE name=? LIMIT 1', [$group]); if ($groupId > 0 && (int) $id > 1) { execs($pdo, 'INSERT INTO fa_auth_group_access (uid,group_id) VALUES (?,?)', [(int) $id, $groupId]); } } }
function import_staff_profiles(PDO $pdo, array $users, array &$ctx): void { foreach ($users as $row) { $legacyId = s($row['id'] ?? ''); $payload = ['legacy_id' => $legacyId, 'admin_id' => (int) ($ctx['user_ids'][$legacyId] ?? 0), 'account' => s($row['account'] ?? ''), 'employee_no' => s($row['employee_no'] ?? ''), 'name' => s($row['name'] ?? ''), 'title' => s($row['title'] ?? ''), 'department' => s($row['department'] ?? ''), 'role_key' => enumv($row['role'] ?? 'viewer', ['admin','finance','project','operations','service','tech','viewer'], 'viewer'), 'permissions_json' => jsons($row['permissions'] ?? []), 'phone' => s($row['phone'] ?? ''), 'email' => s($row['email'] ?? ''), 'hire_date' => d($row['hire_date'] ?? null), 'manager_legacy_id' => s($row['manager_id'] ?? ''), 'manager_admin_id' => 0, 'status' => ($row['status'] ?? 'active') === 'active' ? 'active' : 'inactive', 'last_login_at' => dt($row['last_login_at'] ?? null), 'notes' => '', 'createtime' => time(), 'updatetime' => time()]; upsert($pdo, 'fa_staff_profile', 'legacy_id', $payload); } foreach ($users as $row) { execs($pdo, 'UPDATE fa_staff_profile SET manager_admin_id=? WHERE legacy_id=?', [(int) ($ctx['user_ids'][s($row['manager_id'] ?? '')] ?? 0), s($row['id'] ?? '')]); } }
function import_generic(PDO $pdo, array $rows, string $table, string $key, callable $map, ?array &$idMap = null): void { foreach ($rows as $row) { if (!is_array($row)) { continue; } $payload = $map($row); $id = upsert($pdo, $table, $key, $payload); if (is_array($idMap) && isset($payload[$key])) { $idMap[(string) $payload[$key]] = $id; } } }
function link_replacement_materials(PDO $pdo, array $rows, array $idMap): void { foreach ($rows as $row) { if (!is_array($row)) { continue; } execs($pdo, 'UPDATE fa_app_material SET replacement_material_id=? WHERE legacy_id=?', [(int) ($idMap[s($row['replacement_material_id'] ?? '')] ?? 0), s($row['id'] ?? '')]); } }
function import_ai(PDO $pdo, array $ai): void { execs($pdo, 'DELETE FROM fa_ai_setting'); execs($pdo, 'DELETE FROM fa_ai_conversation'); $settings = $ai['settings'] ?? []; execs($pdo, 'INSERT INTO fa_ai_setting (provider_name,base_url,api_key,model,temperature,system_prompt,workspace_json,createtime,updatetime) VALUES (?,?,?,?,?,?,?,?,?)', [s($settings['provider_name'] ?? ''), s($settings['base_url'] ?? ''), s($settings['api_key'] ?? ''), s($settings['model'] ?? ''), money($settings['temperature'] ?? 0.2), s($settings['system_prompt'] ?? ''), jsons($ai['workspace'] ?? []), time(), time()]); foreach (($ai['conversation'] ?? []) as $row) { if (!is_array($row)) { continue; } execs($pdo, 'INSERT INTO fa_ai_conversation (role,content,message_at,createtime,updatetime) VALUES (?,?,?,?,?)', [enumv($row['role'] ?? 'assistant', ['system','user','assistant'], 'assistant'), s($row['content'] ?? ''), dt($row['created_at'] ?? null), time(), time()]); } }
function import_audit(PDO $pdo, array $rows, array $ctx): void { execs($pdo, 'DELETE FROM fa_staff_audit'); foreach ($rows as $row) { if (!is_array($row)) { continue; } $legacy = s($row['actor_id'] ?? ''); execs($pdo, 'INSERT INTO fa_staff_audit (legacy_id,admin_id,actor_admin_id,actor_name,module,action,object_type,object_legacy_id,content,ip,useragent,happened_at,createtime,updatetime) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [s($row['id'] ?? ('audit-' . uniqid('', true))), (int) ($ctx['user_ids'][$legacy] ?? 0), (int) ($ctx['user_ids'][$legacy] ?? 0), s($row['actor_name'] ?? ''), s($row['module'] ?? ''), s($row['action'] ?? ''), s($row['object_type'] ?? ''), s($row['object_id'] ?? ''), s($row['content'] ?? ''), s($row['ip'] ?? ''), s($row['useragent'] ?? ''), dt($row['created_at'] ?? null), time(), time()]); } }
function sync_group_rules(PDO $pdo): void { $rules = $pdo->query('SELECT id,name FROM fa_auth_rule')->fetchAll(); $groupMap = []; foreach ($pdo->query('SELECT id,name FROM fa_auth_group')->fetchAll() as $row) { $groupMap[$row['name']] = (int) $row['id']; } $sets = ['ERP 财务组' => ['dashboard','general/profile','finance'], 'ERP 项目组' => ['dashboard','general/profile','project'], 'ERP 运营组' => ['dashboard','general/profile','app/project','app/milestone','app/report','app/risk','app/release','app/material','app/issue'], 'ERP 客服组' => ['dashboard','general/profile','app/project','app/issue','app/issue_followup','app/material','app/release'], 'ERP 技术组' => ['dashboard','general/profile','project','app/project','app/tech_ticket','app/release','app/issue'], 'ERP 只读组' => ['dashboard','general/profile','finance','project','app/project','app/report','app/release','app/material']]; foreach ($sets as $group => $prefixes) { $ids = []; foreach ($rules as $rule) { foreach ($prefixes as $prefix) { if ($rule['name'] === $prefix || str_starts_with($rule['name'], $prefix . '/')) { $ids[] = (int) $rule['id']; break; } } } sort($ids); if (isset($groupMap[$group])) { execs($pdo, 'UPDATE fa_auth_group SET rules=?,updatetime=? WHERE id=?', [implode(',', array_unique($ids)), time(), $groupMap[$group]]); } } }
function sync_menu_titles(PDO $pdo): void { $top = ['finance' => ['财务中心','fa fa-rmb'], 'project' => ['项目交付','fa fa-briefcase'], 'app' => ['APP运营','fa fa-mobile'], 'staff' => ['人员与权限','fa fa-users'], 'ai' => ['AI助手','fa fa-comments-o']]; foreach ($top as $name => $meta) { execs($pdo, 'UPDATE fa_auth_rule SET title=?,icon=? WHERE name=?', [$meta[0], $meta[1], $name]); } if ($staffId = value($pdo, 'SELECT id FROM fa_auth_rule WHERE name=? LIMIT 1', ['staff'])) { foreach (['auth/admin','auth/adminlog','auth/group','auth/rule'] as $name) { execs($pdo, 'UPDATE fa_auth_rule SET pid=? WHERE name=?', [(int) $staffId, $name]); } execs($pdo, 'UPDATE fa_auth_rule SET status=? WHERE name=?', ['hidden', 'auth']); } }
function upsert(PDO $pdo, string $table, string $key, array $payload): int { $id = value($pdo, "SELECT id FROM {$table} WHERE {$key}=? LIMIT 1", [$payload[$key]]); $cols = array_keys($payload); if ($id) { $sets = implode(',', array_map(fn($c) => "{$c}=:$c", $cols)); $sql = "UPDATE {$table} SET {$sets} WHERE {$key}=:__key"; $stmt = $pdo->prepare($sql); $payload['__key'] = $payload[$key]; $stmt->execute($payload); return (int) $id; } $fields = implode(',', $cols); $marks = implode(',', array_map(fn($c) => ":$c", $cols)); $stmt = $pdo->prepare("INSERT INTO {$table} ({$fields}) VALUES ({$marks})"); $stmt->execute($payload); return (int) $pdo->lastInsertId(); }
function value(PDO $pdo, string $sql, array $params = []): mixed { $stmt = $pdo->prepare($sql); $stmt->execute($params); return $stmt->fetchColumn(); }
function execs(PDO $pdo, string $sql, array $params = []): void { $stmt = $pdo->prepare($sql); $stmt->execute($params); }
function admin_id_by_name(array $ctx, mixed $name): int { $target = s($name); foreach ($ctx['users'] as $user) { if (is_array($user) && s($user['name'] ?? '') === $target) { return (int) ($ctx['user_ids'][s($user['id'] ?? '')] ?? 0); } } return 0; }
function s(mixed $v): string { return is_scalar($v) ? trim((string) $v) : ''; }
function d(mixed $v): ?string { $v = s($v); return $v === '' ? null : substr($v, 0, 10); }
function dt(mixed $v): ?string { $v = s($v); return $v === '' ? null : str_replace('T', ' ', substr($v, 0, 19)); }
function ts(mixed $v): ?int { $v = dt($v); $t = $v ? strtotime($v) : false; return $t === false ? null : $t; }
function money(mixed $v): string { return number_format((float) ($v ?: 0), 2, '.', ''); }
function jsons(mixed $v): string { return json_encode(is_array($v) ? array_values($v) : $v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]'; }
function flag(mixed $v): int { return filter_var($v, FILTER_VALIDATE_BOOL) ? 1 : 0; }
function enumv(mixed $v, array $allowed, string $fallback): string { $v = strtolower(s($v)); return in_array($v, $allowed, true) ? $v : $fallback; }
