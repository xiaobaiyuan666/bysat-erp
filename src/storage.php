<?php

declare(strict_types=1);

function root_path(string $path = ''): string
{
    $root = dirname(__DIR__);

    if ($path === '') {
        return $root;
    }

    return $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
}

function storage_file_path(): string
{
    return root_path('storage/app-data.json');
}

function default_data(): array
{
    return require root_path('src/default-data.php');
}

function merge_default_structure(array $defaults, array $actual): array
{
    foreach ($defaults as $key => $value) {
        if (!array_key_exists($key, $actual)) {
            $actual[$key] = $value;
            continue;
        }

        if (is_array($value) && is_array($actual[$key]) && is_associative_array($value)) {
            $actual[$key] = merge_default_structure($value, $actual[$key]);
        }
    }

    return $actual;
}

function is_associative_array(array $value): bool
{
    return array_keys($value) !== range(0, count($value) - 1);
}

function merge_record_defaults(array $defaults, array $actual): array
{
    $lookup = [];

    foreach ($defaults as $row) {
        if (!is_array($row)) {
            continue;
        }

        $id = (string) ($row['id'] ?? '');
        if ($id !== '') {
            $lookup[$id] = $row;
        }
    }

    foreach ($actual as $index => $row) {
        if (!is_array($row)) {
            continue;
        }

        $id = (string) ($row['id'] ?? '');
        if ($id !== '' && isset($lookup[$id])) {
            $actual[$index] = merge_default_structure($lookup[$id], $row);
        }
    }

    return $actual;
}

function append_missing_default_records(array $defaults, array $actual): array
{
    $existingIds = [];

    foreach ($actual as $row) {
        if (!is_array($row)) {
            continue;
        }

        $id = (string) ($row['id'] ?? '');

        if ($id !== '') {
            $existingIds[$id] = true;
        }
    }

    foreach ($defaults as $row) {
        if (!is_array($row)) {
            continue;
        }

        $id = (string) ($row['id'] ?? '');

        if ($id === '' || isset($existingIds[$id])) {
            continue;
        }

        $actual[] = $row;
    }

    return $actual;
}

function migrate_users(array $users): array
{
    foreach ($users as $index => $user) {
        if (!is_array($user)) {
            continue;
        }

        $id = (string) ($user['id'] ?? '');
        $employeeNo = input_string($user, 'employee_no');
        $account = normalize_login_identity((string) ($user['account'] ?? ''));

        if ($account === '') {
            if ($employeeNo !== '') {
                $account = normalize_login_identity($employeeNo);
            } else {
                $account = 'user' . preg_replace('/[^a-zA-Z0-9]/', '', $id);
            }
        }

        if ($employeeNo === '') {
            $employeeNo = 'EMP' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
        }

        $users[$index]['account'] = $account;
        $users[$index]['employee_no'] = $employeeNo;
        $users[$index]['phone'] = (string) ($user['phone'] ?? '');
        $users[$index]['email'] = normalize_login_identity((string) ($user['email'] ?? ''));
        $users[$index]['hire_date'] = (string) ($user['hire_date'] ?? '');
        $users[$index]['manager_id'] = (string) ($user['manager_id'] ?? '');
        $users[$index]['last_login_at'] = (string) ($user['last_login_at'] ?? '');

        if ((string) ($user['role'] ?? '') === 'supervisor') {
            $users[$index]['role'] = 'operations';

            if ((string) ($user['title'] ?? '') === '业务主管') {
                $users[$index]['title'] = '经营负责人';
            }

            if ($account === 'supervisor.he') {
                $users[$index]['account'] = 'leader.he';
            }

            if ((string) ($user['email'] ?? '') === 'supervisor.he@yfsoft.local') {
                $users[$index]['email'] = 'leader.he@yfsoft.local';
            }
        }

        if ((string) ($user['password_hash'] ?? '') === '') {
            $users[$index]['password_hash'] = hash_user_password(default_initial_password());
        }
    }

    return $users;
}

function migrate_ops_projects(array $projects): array
{
    foreach ($projects as $index => $project) {
        if (!is_array($project)) {
            continue;
        }

        $projects[$index]['app_name'] = (string) ($project['app_name'] ?? ($project['name'] ?? ''));
        $projects[$index]['app_version'] = (string) ($project['app_version'] ?? '');
        $projects[$index]['lifecycle_stage'] = (string) ($project['lifecycle_stage'] ?? 'validation');
        $projects[$index]['core_metric'] = (string) ($project['core_metric'] ?? '');
    }

    return $projects;
}

function migrate_ops_releases(array $releases): array
{
    foreach ($releases as $index => $release) {
        if (!is_array($release)) {
            continue;
        }

        $releases[$index]['version'] = (string) ($release['version'] ?? '');
        $releases[$index]['title'] = (string) ($release['title'] ?? '');
        $releases[$index]['status'] = (string) ($release['status'] ?? 'planned');
        $releases[$index]['owner'] = (string) ($release['owner'] ?? '');
        $releases[$index]['release_date'] = (string) ($release['release_date'] ?? '');
        $releases[$index]['channel'] = (string) ($release['channel'] ?? '');
        $releases[$index]['tech_ticket_ids'] = is_array($release['tech_ticket_ids'] ?? null) ? array_values($release['tech_ticket_ids']) : [];
        $releases[$index]['service_ticket_ids'] = is_array($release['service_ticket_ids'] ?? null) ? array_values($release['service_ticket_ids']) : [];
        $releases[$index]['release_notes'] = (string) ($release['release_notes'] ?? '');
        $releases[$index]['verification_summary'] = (string) ($release['verification_summary'] ?? '');
        $releases[$index]['customer_sync_status'] = (string) ($release['customer_sync_status'] ?? '');
        $releases[$index]['customer_sync_note'] = (string) ($release['customer_sync_note'] ?? '');
        $releases[$index]['release_result'] = (string) ($release['release_result'] ?? '');
        $releases[$index]['rollback_plan'] = (string) ($release['rollback_plan'] ?? '');
        $releases[$index]['rollback_ready'] = (bool) ($release['rollback_ready'] ?? false);
        $releases[$index]['notes'] = (string) ($release['notes'] ?? '');
    }

    return $releases;
}

function migrate_ops_materials(array $materials): array
{
    foreach ($materials as $index => $material) {
        if (!is_array($material)) {
            continue;
        }

        $materials[$index]['ops_project_id'] = (string) ($material['ops_project_id'] ?? '');
        $materials[$index]['title'] = (string) ($material['title'] ?? '');
        $materials[$index]['category'] = (string) ($material['category'] ?? 'manual');
        $materials[$index]['owner'] = (string) ($material['owner'] ?? '');
        $materials[$index]['version_tag'] = (string) ($material['version_tag'] ?? '');
        $materials[$index]['applicable_versions'] = (string) ($material['applicable_versions'] ?? '');
        $materials[$index]['expires_on'] = (string) ($material['expires_on'] ?? '');
        $materials[$index]['archive_status'] = (string) ($material['archive_status'] ?? 'active');
        $materials[$index]['replacement_material_id'] = (string) ($material['replacement_material_id'] ?? '');
        $materials[$index]['download_name'] = (string) ($material['download_name'] ?? ($material['title'] ?? ''));
        $materials[$index]['download_url'] = (string) ($material['download_url'] ?? '');
        $materials[$index]['file_path'] = ltrim((string) ($material['file_path'] ?? ''), '/');
        $materials[$index]['file_size'] = (int) ($material['file_size'] ?? 0);
        $materials[$index]['file_mime'] = (string) ($material['file_mime'] ?? '');
        $materials[$index]['updated_on'] = (string) ($material['updated_on'] ?? date('Y-m-d'));
        $materials[$index]['notes'] = (string) ($material['notes'] ?? '');
    }

    return $materials;
}

function migrate_service_ticket_updates(array $updates): array
{
    foreach ($updates as $index => $update) {
        if (!is_array($update)) {
            continue;
        }

        $type = (string) ($update['type'] ?? 'follow_up');
        $defaultVisibility = match ($type) {
            'leader' => 'leader',
            'release' => 'customer',
            default => 'internal',
        };

        $updates[$index]['service_ticket_id'] = (string) ($update['service_ticket_id'] ?? '');
        $updates[$index]['type'] = $type;
        $updates[$index]['visibility'] = (string) ($update['visibility'] ?? $defaultVisibility);
        $updates[$index]['content'] = (string) ($update['content'] ?? '');
        $updates[$index]['status'] = (string) ($update['status'] ?? '');
        $updates[$index]['next_action'] = (string) ($update['next_action'] ?? '');
        $updates[$index]['created_at'] = (string) ($update['created_at'] ?? date('Y-m-d H:i:s'));
    }

    return $updates;
}

function migrate_tech_tickets(array $tickets, array $opsProjects): array
{
    $opsLookup = [];

    foreach ($opsProjects as $project) {
        if (!is_array($project)) {
            continue;
        }

        $opsLookup[(string) ($project['id'] ?? '')] = $project;
    }

    foreach ($tickets as $index => $ticket) {
        if (!is_array($ticket)) {
            continue;
        }

        $opsProjectId = (string) ($ticket['ops_project_id'] ?? '');
        $opsProject = $opsLookup[$opsProjectId] ?? [];

        $tickets[$index]['type'] = (string) ($ticket['type'] ?? 'bug');
        $tickets[$index]['status'] = (string) ($ticket['status'] ?? 'pending');
        $tickets[$index]['priority'] = (string) ($ticket['priority'] ?? 'medium');
        $tickets[$index]['severity'] = (string) ($ticket['severity'] ?? 'medium');
        $tickets[$index]['source'] = (string) ($ticket['source'] ?? 'operations');
        $tickets[$index]['project_id'] = (string) ($ticket['project_id'] ?? ($opsProject['project_id'] ?? ''));
        $tickets[$index]['app_module'] = (string) ($ticket['app_module'] ?? '');
        $tickets[$index]['app_version'] = (string) ($ticket['app_version'] ?? ($opsProject['app_version'] ?? ''));
        $tickets[$index]['owner'] = (string) ($ticket['owner'] ?? '');
        $tickets[$index]['reporter'] = (string) ($ticket['reporter'] ?? '');
        $tickets[$index]['due_date'] = (string) ($ticket['due_date'] ?? '');
        $tickets[$index]['impact'] = (string) ($ticket['impact'] ?? '');
        $tickets[$index]['solution_plan'] = (string) ($ticket['solution_plan'] ?? '');
        $tickets[$index]['estimate_hours'] = (float) ($ticket['estimate_hours'] ?? 0);
        $tickets[$index]['actual_hours'] = (float) ($ticket['actual_hours'] ?? 0);
        $tickets[$index]['notes'] = (string) ($ticket['notes'] ?? '');
    }

    return $tickets;
}

function migrate_service_tickets(array $tickets): array
{
    foreach ($tickets as $index => $ticket) {
        if (!is_array($ticket)) {
            continue;
        }

        $tickets[$index]['ticket_no'] = (string) ($ticket['ticket_no'] ?? ('CS' . date('YmdHis') . mt_rand(10, 99)));
        $tickets[$index]['source'] = (string) ($ticket['source'] ?? 'customer');
        $tickets[$index]['customer'] = (string) ($ticket['customer'] ?? '');
        $tickets[$index]['contact_name'] = (string) ($ticket['contact_name'] ?? '');
        $tickets[$index]['contact_phone'] = (string) ($ticket['contact_phone'] ?? '');
        $tickets[$index]['channel'] = (string) ($ticket['channel'] ?? 'app');
        $tickets[$index]['category'] = (string) ($ticket['category'] ?? 'usage');
        $tickets[$index]['title'] = (string) ($ticket['title'] ?? '');
        $tickets[$index]['summary'] = (string) ($ticket['summary'] ?? '');
        $tickets[$index]['status'] = (string) ($ticket['status'] ?? 'new');
        $tickets[$index]['priority'] = (string) ($ticket['priority'] ?? 'medium');
        $tickets[$index]['assignee'] = (string) ($ticket['assignee'] ?? '');
        $tickets[$index]['opened_at'] = (string) ($ticket['opened_at'] ?? date('Y-m-d H:i:s'));
        $tickets[$index]['last_follow_up_at'] = (string) ($ticket['last_follow_up_at'] ?? (string) ($tickets[$index]['opened_at'] ?? ''));
        $tickets[$index]['resolve_due_at'] = (string) ($ticket['resolve_due_at'] ?? '');
        $tickets[$index]['next_action'] = (string) ($ticket['next_action'] ?? '');
        $tickets[$index]['customer_notified'] = (bool) ($ticket['customer_notified'] ?? false);
        $tickets[$index]['customer_notified_to'] = (string) ($ticket['customer_notified_to'] ?? '');
        $tickets[$index]['customer_notified_channel'] = (string) ($ticket['customer_notified_channel'] ?? '');
        $tickets[$index]['customer_notified_at'] = (string) ($ticket['customer_notified_at'] ?? '');
        $tickets[$index]['customer_feedback_result'] = (string) ($ticket['customer_feedback_result'] ?? '');
        $tickets[$index]['customer_confirmed'] = (bool) ($ticket['customer_confirmed'] ?? false);
        $tickets[$index]['customer_confirmed_at'] = (string) ($ticket['customer_confirmed_at'] ?? '');
        $tickets[$index]['customer_confirmation_note'] = (string) ($ticket['customer_confirmation_note'] ?? '');
        $tickets[$index]['ops_project_id'] = (string) ($ticket['ops_project_id'] ?? '');
        $tickets[$index]['project_id'] = (string) ($ticket['project_id'] ?? '');
        $tickets[$index]['tech_ticket_id'] = (string) ($ticket['tech_ticket_id'] ?? '');
        $tickets[$index]['notes'] = (string) ($ticket['notes'] ?? '');
    }

    return $tickets;
}

function migrate_data(array $defaults, array $actual): array
{
    $currentVersion = (string) ($actual['meta']['version'] ?? '0.0.0');
    $merged = merge_default_structure($defaults, $actual);

    foreach (['users', 'transactions', 'invoices', 'projects', 'tasks', 'ops_projects', 'ops_milestones', 'ops_updates', 'ops_risks', 'ops_releases', 'ops_materials', 'tech_tickets', 'service_tickets', 'service_ticket_updates'] as $key) {
        if (isset($defaults[$key], $merged[$key]) && is_array($defaults[$key]) && is_array($merged[$key])) {
            $merged[$key] = merge_record_defaults($defaults[$key], $merged[$key]);
        }
    }

    if (version_compare($currentVersion, '0.7.0', '<') && isset($defaults['users'], $merged['users'])) {
        $merged['users'] = append_missing_default_records($defaults['users'], $merged['users']);
    }

    if (version_compare($currentVersion, '0.9.0', '<') && isset($defaults['ops_materials'], $merged['ops_materials'])) {
        $merged['ops_materials'] = append_missing_default_records($defaults['ops_materials'], $merged['ops_materials']);
    }

    if (version_compare($currentVersion, '1.1.0', '<') && isset($defaults['service_ticket_updates'], $merged['service_ticket_updates'])) {
        $merged['service_ticket_updates'] = append_missing_default_records($defaults['service_ticket_updates'], $merged['service_ticket_updates']);
    }

    if (isset($merged['users']) && is_array($merged['users'])) {
        $merged['users'] = migrate_users($merged['users']);
    }

    if (isset($merged['ops_projects']) && is_array($merged['ops_projects'])) {
        $merged['ops_projects'] = migrate_ops_projects($merged['ops_projects']);
    }

    if (isset($merged['ops_releases']) && is_array($merged['ops_releases'])) {
        $merged['ops_releases'] = migrate_ops_releases($merged['ops_releases']);
    }

    if (isset($merged['ops_materials']) && is_array($merged['ops_materials'])) {
        $merged['ops_materials'] = migrate_ops_materials($merged['ops_materials']);
    }

    if (isset($merged['tech_tickets']) && is_array($merged['tech_tickets'])) {
        $merged['tech_tickets'] = migrate_tech_tickets($merged['tech_tickets'], $merged['ops_projects'] ?? []);
    }

    if (isset($merged['service_tickets']) && is_array($merged['service_tickets'])) {
        $merged['service_tickets'] = migrate_service_tickets($merged['service_tickets']);
    }

    if (isset($merged['service_ticket_updates']) && is_array($merged['service_ticket_updates'])) {
        $merged['service_ticket_updates'] = migrate_service_ticket_updates($merged['service_ticket_updates']);
    }

    if (isset($defaults['meta']['version'])) {
        $merged['meta']['version'] = (string) $defaults['meta']['version'];
    }

    return $merged;
}

function load_data(): array
{
    $path = storage_file_path();
    $defaults = default_data();

    if (!is_file($path)) {
        save_data($defaults);

        return $defaults;
    }

    $contents = file_get_contents($path);

    if ($contents === false || trim($contents) === '') {
        save_data($defaults);

        return $defaults;
    }

    $decoded = json_decode($contents, true);

    if (!is_array($decoded)) {
        save_data($defaults);

        return $defaults;
    }

    $merged = migrate_data($defaults, $decoded);

    if ($merged !== $decoded) {
        save_data($merged);
    }

    return $merged;
}

function save_data(array $data): void
{
    $directory = dirname(storage_file_path());

    if (!is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    file_put_contents(
        storage_file_path(),
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        LOCK_EX
    );
}

function next_id(string $prefix): string
{
    return $prefix . '-' . date('YmdHis') . '-' . mt_rand(1000, 9999);
}
