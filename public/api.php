<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/storage.php';
require_once __DIR__ . '/../src/metrics.php';
require_once __DIR__ . '/../src/operations.php';
require_once __DIR__ . '/../src/service.php';
require_once __DIR__ . '/../src/tech.php';
require_once __DIR__ . '/../src/ai.php';
require_once __DIR__ . '/../src/finance_tools.php';
require_once __DIR__ . '/../src/actions.php';

session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

try {
    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

    if ($method === 'GET') {
        api_handle_get();
    }

    if ($method === 'POST') {
        api_handle_post();
    }

    api_fail('不支持的请求方式。', 405);
} catch (Throwable $exception) {
    api_fail('服务端异常：' . $exception->getMessage(), 500);
}

function api_handle_get(): void
{
    $resource = input_string($_GET, 'resource', 'bootstrap');

    if ($resource !== 'bootstrap') {
        api_fail('未识别的资源。', 404);
    }

    api_json([
        'ok' => true,
        'data' => api_bootstrap_payload(load_data()),
    ]);
}

function api_handle_post(): void
{
    $request = api_request_payload();
    $action = input_string($request, 'action');

    if ($action === '') {
        api_fail('缺少 action 参数。', 422);
    }

    $data = load_data();

    if ($action === 'login') {
        api_login($data, $request);
        return;
    }

    if ($action === 'logout') {
        api_logout($data);
        return;
    }

    if ($action === 'switch_current_user') {
        api_switch_current_user($data, $request);
        return;
    }

    if (authenticated_user_or_null($data) === null) {
        api_fail('请先登录系统。', 401, api_bootstrap_payload($data));
    }

    $actor = current_user($data, input_string($request, 'current_user_id'));
    set_session_current_user_id((string) ($actor['id'] ?? ''));
    api_set_runtime_actor($actor);
    api_require_action_permission($actor, $action);

    switch ($action) {
        case 'smart_bookkeeping':
            api_smart_bookkeeping($data, $request);
            return;
        case 'add_transaction':
            api_add_transaction($data, $request);
            return;
        case 'update_transaction':
            api_update_transaction($data, $request);
            return;
        case 'append_transaction_attachments':
            api_append_transaction_attachments($data, $request);
            return;
        case 'delete_transaction':
            api_delete_transaction($data, $request);
            return;
        case 'add_invoice':
            api_add_invoice($data, $request);
            return;
        case 'update_invoice':
            api_update_invoice($data, $request);
            return;
        case 'append_invoice_attachments':
            api_append_invoice_attachments($data, $request);
            return;
        case 'update_invoice_status':
            api_update_invoice_status($data, $request);
            return;
        case 'delete_invoice':
            api_delete_invoice($data, $request);
            return;
        case 'add_project':
            api_add_project($data, $request);
            return;
        case 'update_project':
            api_update_project($data, $request);
            return;
        case 'update_project_status':
            api_update_project_status($data, $request);
            return;
        case 'delete_project':
            api_delete_project($data, $request);
            return;
        case 'add_task':
            api_add_task($data, $request);
            return;
        case 'update_task':
            api_update_task($data, $request);
            return;
        case 'update_task_status':
            api_update_task_status($data, $request);
            return;
        case 'delete_task':
            api_delete_task($data, $request);
            return;
        case 'add_ops_project':
            api_add_ops_project($data, $request);
            return;
        case 'update_ops_project':
            api_update_ops_project($data, $request);
            return;
        case 'delete_ops_project':
            api_delete_ops_project($data, $request);
            return;
        case 'add_ops_milestone':
            api_add_ops_milestone($data, $request);
            return;
        case 'update_ops_milestone':
            api_update_ops_milestone($data, $request);
            return;
        case 'update_ops_milestone_status':
            api_update_ops_milestone_status($data, $request);
            return;
        case 'delete_ops_milestone':
            api_delete_ops_milestone($data, $request);
            return;
        case 'add_ops_update':
            api_add_ops_update($data, $request);
            return;
        case 'update_ops_update':
            api_update_ops_update($data, $request);
            return;
        case 'delete_ops_update':
            api_delete_ops_update($data, $request);
            return;
        case 'add_ops_release':
            api_add_ops_release($data, $request);
            return;
        case 'update_ops_release':
            api_update_ops_release($data, $request);
            return;
        case 'update_ops_release_status':
            api_update_ops_release_status($data, $request);
            return;
        case 'delete_ops_release':
            api_delete_ops_release($data, $request);
            return;
        case 'add_ops_material':
            api_add_ops_material($data, $request);
            return;
        case 'update_ops_material':
            api_update_ops_material($data, $request);
            return;
        case 'delete_ops_material':
            api_delete_ops_material($data, $request);
            return;
        case 'add_ops_risk':
            api_add_ops_risk($data, $request);
            return;
        case 'update_ops_risk':
            api_update_ops_risk($data, $request);
            return;
        case 'update_ops_risk_status':
            api_update_ops_risk_status($data, $request);
            return;
        case 'delete_ops_risk':
            api_delete_ops_risk($data, $request);
            return;
        case 'add_service_ticket':
            api_add_service_ticket($data, $request);
            return;
        case 'update_service_ticket':
            api_update_service_ticket($data, $request);
            return;
        case 'update_service_ticket_status':
            api_update_service_ticket_status($data, $request);
            return;
        case 'add_service_ticket_update':
            api_add_service_ticket_update($data, $request);
            return;
        case 'delete_service_ticket':
            api_delete_service_ticket($data, $request);
            return;
        case 'add_tech_ticket':
            api_add_tech_ticket($data, $request);
            return;
        case 'update_tech_ticket':
            api_update_tech_ticket($data, $request);
            return;
        case 'update_tech_ticket_status':
            api_update_tech_ticket_status($data, $request);
            return;
        case 'delete_tech_ticket':
            api_delete_tech_ticket($data, $request);
            return;
        case 'add_user':
            api_add_user($data, $request);
            return;
        case 'update_user':
            api_update_user($data, $request);
            return;
        case 'delete_user':
            api_delete_user($data, $request);
            return;
        case 'save_ai_settings':
            api_save_ai_settings($data, $request);
            return;
        case 'ask_ai':
            api_ask_ai($data, $request);
            return;
        case 'clear_ai_conversation':
            api_clear_ai_conversation($data);
            return;
        default:
            api_fail('未识别的操作。', 404);
    }
}

function api_request_payload(): array
{
    $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));

    if (str_contains($contentType, 'application/json')) {
        $raw = file_get_contents('php://input');
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    return $_POST;
}

function api_set_runtime_actor(array $actor): void
{
    $GLOBALS['api_runtime_actor'] = $actor;
}

function api_current_actor(): array
{
    $actor = $GLOBALS['api_runtime_actor'] ?? null;

    if (!is_array($actor) || $actor === []) {
        throw new RuntimeException('当前操作人未初始化。');
    }

    return $actor;
}

function api_login(array $data, array $request): void
{
    $account = input_string($request, 'account');
    $password = input_string($request, 'password');
    $user = find_user_by_login_identity($data, $account);

    if ($user === null || (string) ($user['status'] ?? 'inactive') !== 'active' || !verify_user_password($user, $password)) {
        api_fail('账号或密码错误。', 422, api_public_bootstrap_payload($data));
    }

    $userId = (string) ($user['id'] ?? '');
    $index = find_record_index_by_id($data['users'] ?? [], $userId);

    if ($index !== null) {
        $data['users'][$index]['last_login_at'] = date('Y-m-d H:i:s');
    }

    session_regenerate_id(true);
    set_session_auth_user_id($userId);
    set_session_current_user_id($userId);
    audit_log($data, $user, 'auth', 'login', 'user', $userId, '登录系统：' . (string) ($user['name'] ?? ''), [
        'account' => (string) ($user['account'] ?? ''),
    ]);
    save_data($data);
    api_success('登录成功。', $data);
}

function api_logout(array $data): void
{
    $sessionUser = authenticated_user_or_null($data);

    if ($sessionUser !== null) {
        audit_log($data, $sessionUser, 'auth', 'logout', 'user', (string) ($sessionUser['id'] ?? ''), '退出系统：' . (string) ($sessionUser['name'] ?? ''));
        save_data($data);
    }

    clear_session_identity();
    session_regenerate_id(true);
    api_success('已退出登录。', $data);
}

function api_require_action_permission(array $actor, string $action): void
{
    $permission = api_action_permission($action);

    if ($permission === '' || $permission === []) {
        return;
    }

    if (is_array($permission)) {
        foreach ($permission as $item) {
            if (is_string($item) && $item !== '' && user_has_permission($actor, $item)) {
                return;
            }
        }

        api_fail('当前操作人没有执行此操作的权限。', 403);
    }

    if (!is_string($permission) || !user_has_permission($actor, $permission)) {
        api_fail('当前操作人没有执行此操作的权限。', 403);
    }
}

function api_action_permission(string $action): string|array
{
    return match ($action) {
        'smart_bookkeeping',
        'add_transaction',
        'update_transaction',
        'append_transaction_attachments',
        'delete_transaction',
        'add_invoice',
        'update_invoice',
        'append_invoice_attachments',
        'update_invoice_status',
        'delete_invoice' => 'finance.edit',
        'add_project',
        'update_project',
        'update_project_status',
        'delete_project',
        'add_task',
        'update_task',
        'update_task_status',
        'delete_task' => 'projects.edit',
        'add_ops_project',
        'update_ops_project',
        'delete_ops_project',
        'add_ops_milestone',
        'update_ops_milestone',
        'update_ops_milestone_status',
        'delete_ops_milestone',
        'add_ops_update',
        'update_ops_update',
        'delete_ops_update',
        'add_ops_release',
        'update_ops_release',
        'update_ops_release_status',
        'delete_ops_release',
        'add_ops_material',
        'update_ops_material',
        'delete_ops_material',
        'add_ops_risk',
        'update_ops_risk',
        'update_ops_risk_status',
        'delete_ops_risk' => 'operations.edit',
        'add_service_ticket',
        'update_service_ticket',
        'update_service_ticket_status',
        'add_service_ticket_update',
        'delete_service_ticket' => ['service.edit', 'operations.edit'],
        'add_tech_ticket',
        'update_tech_ticket',
        'update_tech_ticket_status',
        'delete_tech_ticket' => ['tech.edit', 'operations.edit'],
        'add_user',
        'update_user',
        'delete_user' => 'staff.manage',
        'save_ai_settings' => 'ai.manage',
        'ask_ai',
        'clear_ai_conversation' => 'ai.use',
        default => '',
    };
}

function api_bootstrap_payload(array $data): array
{
    $sessionUser = authenticated_user_or_null($data);

    if ($sessionUser === null) {
        return api_public_bootstrap_payload($data);
    }

    $transactionRows = api_transaction_rows($data);
    $invoiceRows = api_invoice_rows($data);
    $projectRows = api_project_rows($data);
    $taskRows = api_task_rows($data);
    $opsProjectRows = ops_project_rows($data);
    $opsMilestoneRows = ops_milestone_rows($data);
    $opsUpdateRows = ops_update_rows($data);
    $opsReleaseRows = ops_release_rows($data);
    $opsMaterialRows = ops_material_rows($data);
    $opsRiskRows = ops_risk_rows($data);
    $serviceTicketRows = service_ticket_rows($data);
    $techTicketRows = tech_ticket_rows($data);
    $aiSettings = ai_settings($data);
    $currentUser = current_user($data);
    $sessionUserRows = user_rows([
        'users' => [$sessionUser],
    ]);
    $sessionUserRow = $sessionUserRows[0] ?? [];
    $currentUserRows = user_rows([
        'users' => [$currentUser],
    ]);
    $currentUserRow = $currentUserRows[0] ?? [];

    return [
        'meta' => [
            'company' => (string) ($data['meta']['company'] ?? ''),
            'currency' => (string) ($data['meta']['currency'] ?? 'CNY'),
            'version' => (string) ($data['meta']['version'] ?? '0.0.0'),
            'generated_at' => date('Y-m-d H:i:s'),
        ],
        'authenticated' => true,
        'sessionUserId' => (string) ($sessionUserRow['id'] ?? ''),
        'sessionUser' => $sessionUserRow,
        'canImpersonate' => session_user_can_impersonate($data),
        'loginAccounts' => api_login_account_rows($data),
        'dashboard' => dashboard_metrics($data),
        'transactionRows' => $transactionRows,
        'invoiceRows' => $invoiceRows,
        'projectRows' => $projectRows,
        'taskRows' => $taskRows,
        'opsProjectRows' => $opsProjectRows,
        'opsMilestoneRows' => $opsMilestoneRows,
        'opsUpdateRows' => $opsUpdateRows,
        'opsReleaseRows' => $opsReleaseRows,
        'opsMaterialRows' => $opsMaterialRows,
        'opsRiskRows' => $opsRiskRows,
        'serviceTicketRows' => $serviceTicketRows,
        'serviceSummary' => service_summary($data),
        'techTicketRows' => $techTicketRows,
        'techSummary' => tech_summary($data),
        'operationsSummary' => operations_summary($data),
        'operationsAlerts' => operations_alerts($data),
        'recentTransactions' => array_slice($transactionRows, 0, 8),
        'cashflowRows' => api_cashflow_rows(monthly_cashflow($data['transactions'], 6)),
        'incomeRows' => api_breakdown_rows(category_breakdown($data['transactions'], 'income', 6)),
        'expenseRows' => api_breakdown_rows(category_breakdown($data['transactions'], 'expense', 6)),
        'invoiceSummary' => invoice_status_summary(invoice_rows($data)),
        'taskSummary' => task_status_summary(task_rows($data)),
        'projectHealthRows' => api_project_health_rows(project_health_rows(project_summaries($data), 8)),
        'assigneeLoadRows' => array_values(assignee_load_rows(task_rows($data), 8)),
        'dueInvoiceRows' => api_invoice_due_rows(due_invoice_rows(invoice_rows($data), 15)),
        'businessAlerts' => business_alerts($data),
        'currentUserId' => (string) ($currentUserRow['id'] ?? ''),
        'currentUser' => $currentUserRow,
        'userRows' => user_rows($data),
        'roleRows' => role_rows($data),
        'auditLogRows' => audit_log_rows($data),
        'aiSettings' => $aiSettings,
        'aiConfigured' => ai_is_configured($aiSettings),
        'aiConversation' => ai_conversation($data),
        'aiPresets' => ai_prompt_presets(),
        'workspace' => $data['ai']['workspace'] ?? default_data()['ai']['workspace'],
        'lookups' => [
            'projects' => api_select_rows($data['projects'], 'id', 'name'),
            'opsProjects' => api_select_rows($data['ops_projects'] ?? [], 'id', 'name'),
            'opsMaterials' => array_values(array_map(static function (array $row): array {
                $label = (string) ($row['title'] ?? '');
                $version = (string) ($row['version_tag'] ?? '');
                $project = (string) ($row['project_name'] ?? '');

                if ($version !== '') {
                    $label .= ' / ' . $version;
                }

                if ($project !== '') {
                    $label .= ' / ' . $project;
                }

                return [
                    'value' => (string) ($row['id'] ?? ''),
                    'label' => $label,
                ];
            }, array_values(array_filter($opsMaterialRows, static function (array $row): bool {
                return (string) ($row['id'] ?? '') !== '' && (string) ($row['title'] ?? '') !== '';
            })))),
            'serviceTickets' => api_select_rows($serviceTicketRows, 'id', 'title'),
            'users' => api_select_rows($data['users'] ?? [], 'id', 'name'),
            'techTickets' => api_select_rows($techTicketRows, 'id', 'title'),
            'techOwners' => api_select_rows(
                array_values(array_filter($data['users'] ?? [], static function (array $user): bool {
                    return (string) ($user['status'] ?? 'active') === 'active';
                })),
                'name',
                'name'
            ),
            'serviceAssignees' => api_select_rows(
                array_values(array_filter($data['users'] ?? [], static function (array $user): bool {
                    return (string) ($user['status'] ?? 'active') === 'active';
                })),
                'name',
                'name'
            ),
        ],
        'options' => [
            'transactionTypes' => api_option_rows(transaction_type_options()),
            'paymentMethods' => api_option_rows(payment_method_options()),
            'invoiceKinds' => api_option_rows(invoice_kind_options()),
            'invoiceStatuses' => [
                'receivable' => api_option_rows(invoice_status_options('receivable')),
                'payable' => api_option_rows(invoice_status_options('payable')),
            ],
            'projectStatuses' => api_option_rows(project_status_options()),
            'taskStatuses' => api_option_rows(task_status_options()),
            'priorities' => api_option_rows(priority_options()),
            'opsProjectStatuses' => api_option_rows(ops_project_status_options()),
            'opsLifecycleStages' => api_option_rows(ops_lifecycle_stage_options()),
            'opsMilestoneStatuses' => api_option_rows(ops_milestone_status_options()),
            'opsReleaseStatuses' => api_option_rows(ops_release_status_options()),
            'opsReleaseCustomerSyncStatuses' => api_option_rows(ops_release_customer_sync_status_options()),
            'opsMaterialCategories' => api_option_rows(ops_material_category_options()),
            'opsMaterialArchiveStatuses' => api_option_rows(ops_material_archive_status_options()),
            'opsRiskTypes' => api_option_rows(ops_risk_type_options()),
            'opsRiskLevels' => api_option_rows(ops_risk_level_options()),
            'opsRiskStatuses' => api_option_rows(ops_risk_status_options()),
            'serviceTicketSources' => api_option_rows(service_ticket_source_options()),
            'serviceTicketChannels' => api_option_rows(service_ticket_channel_options()),
            'serviceTicketCategories' => api_option_rows(service_ticket_category_options()),
            'serviceTicketStatuses' => api_option_rows(service_ticket_status_options()),
            'serviceTicketUpdateTypes' => api_option_rows(service_ticket_update_type_options()),
            'serviceTicketUpdateVisibilities' => api_option_rows(service_ticket_update_visibility_options()),
            'techTicketTypes' => api_option_rows(tech_ticket_type_options()),
            'techTicketStatuses' => api_option_rows(tech_ticket_status_options()),
            'techTicketSeverities' => api_option_rows(tech_ticket_severity_options()),
            'techTicketSources' => api_option_rows(tech_ticket_source_options()),
            'userStatuses' => api_option_rows(user_status_options()),
            'roles' => api_option_rows(role_options()),
            'permissionGroups' => permission_group_rows(),
            'permissions' => permission_rows(),
            'transactionCategories' => array_map(static function (string $category): array {
                return [
                    'value' => $category,
                    'label' => $category,
                ];
            }, transaction_category_suggestions()),
        ],
    ];
}

function api_public_bootstrap_payload(array $data): array
{
    return [
        'meta' => [
            'company' => (string) ($data['meta']['company'] ?? ''),
            'currency' => (string) ($data['meta']['currency'] ?? 'CNY'),
            'version' => (string) ($data['meta']['version'] ?? '0.0.0'),
            'generated_at' => date('Y-m-d H:i:s'),
        ],
        'authenticated' => false,
        'sessionUserId' => '',
        'sessionUser' => null,
        'canImpersonate' => false,
        'currentUserId' => '',
        'currentUser' => null,
        'loginAccounts' => api_login_account_rows($data),
        'userRows' => [],
        'roleRows' => [],
        'auditLogRows' => [],
        'aiConfigured' => false,
        'workspace' => $data['ai']['workspace'] ?? default_data()['ai']['workspace'],
        'lookups' => [
            'projects' => [],
            'opsProjects' => [],
            'opsMaterials' => [],
            'serviceTickets' => [],
            'users' => [],
            'techTickets' => [],
            'techOwners' => [],
            'serviceAssignees' => [],
        ],
        'options' => [
            'roles' => api_option_rows(role_options()),
            'permissionGroups' => permission_group_rows(),
            'permissions' => permission_rows(),
            'opsReleaseCustomerSyncStatuses' => api_option_rows(ops_release_customer_sync_status_options()),
        ],
    ];
}

function api_login_account_rows(array $data): array
{
    $rows = [];

    foreach (user_rows($data) as $user) {
        if ((string) ($user['status'] ?? 'inactive') !== 'active') {
            continue;
        }

        $rows[] = [
            'id' => (string) ($user['id'] ?? ''),
            'account' => (string) ($user['account'] ?? ''),
            'employee_no' => (string) ($user['employee_no'] ?? ''),
            'name' => (string) ($user['name'] ?? ''),
            'department' => (string) ($user['department'] ?? ''),
            'role_label' => (string) ($user['role_label'] ?? ''),
        ];
    }

    return $rows;
}

function api_option_rows(array $options): array
{
    $rows = [];

    foreach ($options as $value => $label) {
        $rows[] = [
            'value' => (string) $value,
            'label' => (string) $label,
        ];
    }

    return $rows;
}

function api_select_rows(array $records, string $valueKey, string $labelKey): array
{
    $rows = [];

    foreach ($records as $record) {
        $value = (string) ($record[$valueKey] ?? '');
        $label = (string) ($record[$labelKey] ?? '');

        if ($value === '' || $label === '') {
            continue;
        }

        $rows[] = [
            'value' => $value,
            'label' => $label,
        ];
    }

    return $rows;
}

function api_attachment_rows(array $record): array
{
    $rows = [];

    foreach (record_attachments($record) as $attachment) {
        $path = ltrim((string) ($attachment['path'] ?? ''), '/');
        $rows[] = [
            'id' => (string) ($attachment['id'] ?? ''),
            'name' => (string) ($attachment['name'] ?? ''),
            'path' => $path,
            'url' => $path === '' ? '' : '/' . $path,
            'mime' => (string) ($attachment['mime'] ?? ''),
            'size' => (int) ($attachment['size'] ?? 0),
            'uploaded_at' => (string) ($attachment['uploaded_at'] ?? ''),
        ];
    }

    return $rows;
}

function api_ops_material_uploaded_attachment(array $material): array
{
    $path = ltrim((string) ($material['file_path'] ?? ''), '/');

    return [
        'path' => $path,
    ];
}

function api_purge_ops_material_upload(array $material): void
{
    $path = ltrim((string) ($material['file_path'] ?? ''), '/');

    if ($path === '') {
        return;
    }

    delete_attachment_file(api_ops_material_uploaded_attachment($material));
}

function api_store_ops_material_upload(array $fileField): array
{
    return store_uploaded_files($fileField, [
        'bucket' => 'ops-materials',
        'max_size' => 20 * 1024 * 1024,
        'allowed_mime_map' => [
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'text/plain' => 'txt',
            'application/zip' => 'zip',
            'application/x-zip-compressed' => 'zip',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ],
        'upload_failed_message' => '资料上传失败，请重新选择文件后再试。',
        'invalid_file_message' => '资料文件无效，请重新上传。',
        'too_large_message' => '单个资料文件不能超过 20MB。',
        'invalid_type_message' => '资料只支持 PDF、Office 文档、TXT、ZIP、JPG、PNG、WEBP。',
        'save_failed_message' => '资料保存失败，请检查目录权限。',
    ]);
}

function api_apply_ops_material_upload(array $payload, array $request): array
{
    $removeExistingUpload = input_bool($request, 'remove_existing_upload', false);
    $hasUpload = uploaded_files_present($_FILES['material_file'] ?? []);

    $result = [
        'payload' => $payload,
        'remove_existing_upload' => $removeExistingUpload,
        'has_upload' => $hasUpload,
        'uploaded_attachment' => null,
    ];

    if (!$hasUpload && !$removeExistingUpload) {
        return $result;
    }

    $result['payload']['file_path'] = '';
    $result['payload']['file_size'] = 0;
    $result['payload']['file_mime'] = '';

    if (!$hasUpload) {
        return $result;
    }

    $upload = api_store_ops_material_upload($_FILES['material_file'] ?? []);

    if (!(bool) ($upload['ok'] ?? false)) {
        api_fail((string) ($upload['error'] ?? '资料上传失败。'), 422);
    }

    $attachment = $upload['attachments'][0] ?? null;

    if (!is_array($attachment)) {
        api_fail('资料上传失败，请重新选择文件后再试。', 422);
    }

    $result['payload']['download_name'] = (string) ($attachment['name'] ?? ($payload['download_name'] ?? ''));
    $result['payload']['download_url'] = '/' . ltrim((string) ($attachment['path'] ?? ''), '/');
    $result['payload']['file_path'] = ltrim((string) ($attachment['path'] ?? ''), '/');
    $result['payload']['file_size'] = (int) ($attachment['size'] ?? 0);
    $result['payload']['file_mime'] = (string) ($attachment['mime'] ?? '');
    $result['uploaded_attachment'] = $attachment;

    return $result;
}

function api_append_service_ticket_update_record(array &$data, array $payload, array $actor): void
{
    $payload = apply_created_audit_fields($payload, $actor);
    $data['service_ticket_updates'] ??= [];
    $data['service_ticket_updates'][] = $payload;
}

function api_transaction_rows(array $data): array
{
    $lookup = project_lookup($data['projects']);
    $userLookup = user_lookup($data['users'] ?? []);
    $rows = [];

    foreach (recent_transactions($data['transactions'], 200) as $row) {
        $type = (string) ($row['type'] ?? '');
        $paymentMethod = (string) ($row['payment_method'] ?? '');
        $attachments = api_attachment_rows($row);
        $projectId = (string) ($row['project_id'] ?? '');

        $rows[] = array_merge([
            'id' => (string) ($row['id'] ?? ''),
            'date' => (string) ($row['date'] ?? ''),
            'type' => $type,
            'type_label' => transaction_type_options()[$type] ?? $type,
            'category' => (string) ($row['category'] ?? ''),
            'counterparty' => (string) ($row['counterparty'] ?? ''),
            'amount' => (float) ($row['amount'] ?? 0.0),
            'payment_method' => $paymentMethod,
            'payment_method_label' => payment_method_options()[$paymentMethod] ?? $paymentMethod,
            'project_id' => $projectId,
            'project_name' => project_name($lookup, $projectId),
            'notes' => (string) ($row['notes'] ?? ''),
            'attachments' => $attachments,
            'attachment_count' => count($attachments),
        ], record_audit_fields($row, $userLookup));
    }

    return $rows;
}

function api_invoice_rows(array $data): array
{
    $userLookup = user_lookup($data['users'] ?? []);
    $rows = [];

    foreach (invoice_rows($data) as $row) {
        $kind = (string) ($row['kind'] ?? '');
        $status = (string) ($row['status'] ?? '');
        $attachments = api_attachment_rows($row);

        $rows[] = array_merge([
            'id' => (string) ($row['id'] ?? ''),
            'kind' => $kind,
            'kind_label' => invoice_kind_label($kind),
            'title' => (string) ($row['title'] ?? ''),
            'counterparty' => (string) ($row['counterparty'] ?? ''),
            'amount' => (float) ($row['amount'] ?? 0.0),
            'due_date' => (string) ($row['due_date'] ?? ''),
            'status' => $status,
            'status_label' => invoice_status_label($kind, $status),
            'status_tone' => invoice_status_tone($status, (bool) ($row['overdue'] ?? false)),
            'project_id' => (string) ($row['project_id'] ?? ''),
            'project_name' => (string) ($row['project_name'] ?? ''),
            'notes' => (string) ($row['notes'] ?? ''),
            'overdue' => (bool) ($row['overdue'] ?? false),
            'attachments' => $attachments,
            'attachment_count' => count($attachments),
        ], record_audit_fields($row, $userLookup));
    }

    return $rows;
}

function api_project_rows(array $data): array
{
    $userLookup = user_lookup($data['users'] ?? []);
    $rows = [];

    foreach (project_summaries($data) as $row) {
        $status = (string) ($row['status'] ?? '');
        $priority = (string) ($row['priority'] ?? '');

        $rows[] = array_merge([
            'id' => (string) ($row['id'] ?? ''),
            'name' => (string) ($row['name'] ?? ''),
            'client' => (string) ($row['client'] ?? ''),
            'owner' => (string) ($row['owner'] ?? ''),
            'status' => $status,
            'status_label' => project_status_label($status),
            'status_tone' => project_status_tone($status),
            'priority' => $priority,
            'priority_label' => priority_label($priority),
            'priority_tone' => priority_tone($priority),
            'budget' => (float) ($row['budget'] ?? 0.0),
            'start_date' => (string) ($row['start_date'] ?? ''),
            'due_date' => (string) ($row['due_date'] ?? ''),
            'description' => (string) ($row['description'] ?? ''),
            'spent' => (float) ($row['spent'] ?? 0.0),
            'income' => (float) ($row['income'] ?? 0.0),
            'margin' => (float) ($row['margin'] ?? 0.0),
            'budget_usage' => (float) ($row['budget_usage'] ?? 0.0),
            'progress' => (int) ($row['progress'] ?? 0),
            'task_total' => (int) ($row['task_total'] ?? 0),
            'task_done' => (int) ($row['task_done'] ?? 0),
            'task_open' => (int) ($row['task_open'] ?? 0),
            'task_overdue' => (int) ($row['task_overdue'] ?? 0),
        ], record_audit_fields($row, $userLookup));
    }

    return $rows;
}

function api_task_rows(array $data): array
{
    $userLookup = user_lookup($data['users'] ?? []);
    $rows = [];

    foreach (task_rows($data) as $row) {
        $status = (string) ($row['status'] ?? '');
        $priority = (string) ($row['priority'] ?? '');

        $rows[] = array_merge([
            'id' => (string) ($row['id'] ?? ''),
            'project_id' => (string) ($row['project_id'] ?? ''),
            'project_name' => (string) ($row['project_name'] ?? ''),
            'title' => (string) ($row['title'] ?? ''),
            'assignee' => (string) ($row['assignee'] ?? ''),
            'status' => $status,
            'status_label' => task_status_label($status),
            'status_tone' => task_status_tone($status),
            'priority' => $priority,
            'priority_label' => priority_label($priority),
            'priority_tone' => priority_tone($priority),
            'due_date' => (string) ($row['due_date'] ?? ''),
            'estimate_hours' => (float) ($row['estimate_hours'] ?? 0.0),
            'actual_hours' => (float) ($row['actual_hours'] ?? 0.0),
            'overdue' => (bool) ($row['overdue'] ?? false),
        ], record_audit_fields($row, $userLookup));
    }

    return $rows;
}

function api_cashflow_rows(array $rows): array
{
    return array_map(static function (array $row): array {
        return [
            'month' => (string) ($row['month'] ?? ''),
            'label' => short_month_label((string) ($row['month'] ?? '')),
            'income' => (float) ($row['income'] ?? 0.0),
            'expense' => (float) ($row['expense'] ?? 0.0),
            'net' => (float) ($row['net'] ?? 0.0),
        ];
    }, $rows);
}

function api_breakdown_rows(array $rows): array
{
    $total = 0.0;

    foreach ($rows as $row) {
        $total += (float) ($row['amount'] ?? 0.0);
    }

    return array_map(static function (array $row) use ($total): array {
        $amount = (float) ($row['amount'] ?? 0.0);

        return [
            'category' => (string) ($row['category'] ?? ''),
            'amount' => $amount,
            'percent' => $total > 0 ? round(($amount / $total) * 100, 1) : 0.0,
        ];
    }, $rows);
}

function api_project_health_rows(array $rows): array
{
    return array_map(static function (array $row): array {
        return [
            'id' => (string) ($row['id'] ?? ''),
            'name' => (string) ($row['name'] ?? ''),
            'owner' => (string) ($row['owner'] ?? ''),
            'client' => (string) ($row['client'] ?? ''),
            'status' => (string) ($row['status'] ?? ''),
            'status_label' => project_status_label((string) ($row['status'] ?? '')),
            'budget_usage' => (float) ($row['budget_usage'] ?? 0.0),
            'progress' => (int) ($row['progress'] ?? 0),
            'task_overdue' => (int) ($row['task_overdue'] ?? 0),
            'risk_score' => (int) ($row['risk_score'] ?? 0),
            'due_date' => (string) ($row['due_date'] ?? ''),
            'due_soon' => (bool) ($row['due_soon'] ?? false),
        ];
    }, $rows);
}

function api_invoice_due_rows(array $rows): array
{
    return array_map(static function (array $row): array {
        return [
            'id' => (string) ($row['id'] ?? ''),
            'kind' => (string) ($row['kind'] ?? ''),
            'kind_label' => invoice_kind_label((string) ($row['kind'] ?? '')),
            'title' => (string) ($row['title'] ?? ''),
            'counterparty' => (string) ($row['counterparty'] ?? ''),
            'amount' => (float) ($row['amount'] ?? 0.0),
            'due_date' => (string) ($row['due_date'] ?? ''),
            'status' => (string) ($row['status'] ?? ''),
            'status_label' => invoice_status_label((string) ($row['kind'] ?? ''), (string) ($row['status'] ?? '')),
            'overdue' => (bool) ($row['overdue'] ?? false),
        ];
    }, $rows);
}

function api_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function api_success(string $message, array $data): void
{
    api_json([
        'ok' => true,
        'message' => $message,
        'data' => api_bootstrap_payload($data),
    ]);
}

function api_fail(string $message, int $status = 422, ?array $data = null): void
{
    $payload = [
        'ok' => false,
        'message' => $message,
    ];

    if ($data !== null) {
        $payload['data'] = $data;
    }

    api_json($payload, $status);
}

function api_smart_bookkeeping(array $data, array $request): void
{
    $actor = api_current_actor();
    $text = input_string($request, 'smart_text');

    if ($text === '') {
        api_fail('请输入要记账的内容。', 422);
    }

    $parseResult = smart_bookkeeping_parse($data, $text);

    if (!(bool) ($parseResult['ok'] ?? false)) {
        api_fail((string) ($parseResult['error'] ?? 'Unable to parse bookkeeping text.'), 422);
    }

    $parsed = $parseResult['parsed'];
    $manualProjectId = input_string($request, 'project_id');

    if ($manualProjectId !== '') {
        $parsed['project_id'] = $manualProjectId;
    }

    $attachmentsResult = store_uploaded_image_attachments($_FILES['attachments'] ?? []);
    if (!(bool) ($attachmentsResult['ok'] ?? false)) {
        api_fail((string) ($attachmentsResult['error'] ?? 'Attachment upload failed.'), 422);
    }

    $record = apply_created_audit_fields([
        'id' => next_id('tx'),
        'date' => (string) $parsed['date'],
        'type' => (string) $parsed['type'],
        'category' => (string) $parsed['category'],
        'counterparty' => (string) $parsed['counterparty'],
        'amount' => (float) $parsed['amount'],
        'payment_method' => (string) $parsed['payment_method'],
        'project_id' => (string) ($parsed['project_id'] ?? ''),
        'notes' => (string) $parsed['notes'],
        'attachments' => $attachmentsResult['attachments'],
    ], $actor);

    $data['transactions'][] = $record;
    audit_log($data, $actor, 'finance', 'create', 'transaction', (string) $record['id'], '智能记账写入流水：' . (string) $record['counterparty']);

    save_data($data);
    api_success(smart_bookkeeping_flash_message($parsed, $parseResult), $data);
}

function api_add_transaction(array $data, array $request): void
{
    $actor = api_current_actor();
    $payload = transaction_payload_from_request($request);
    api_validate_transaction_payload($payload);

    $attachmentsResult = store_uploaded_image_attachments($_FILES['attachments'] ?? []);
    if (!(bool) ($attachmentsResult['ok'] ?? false)) {
        api_fail((string) ($attachmentsResult['error'] ?? 'Attachment upload failed.'), 422);
    }

    $payload['attachments'] = $attachmentsResult['attachments'];
    $payload = apply_created_audit_fields($payload, $actor);
    $data['transactions'][] = $payload;
    audit_log($data, $actor, 'finance', 'create', 'transaction', (string) $payload['id'], '新增流水：' . (string) $payload['counterparty']);

    save_data($data);
    api_success('流水已新增。', $data);
}

function api_update_transaction(array $data, array $request): void
{
    $actor = api_current_actor();
    $transactionId = input_string($request, 'transaction_id');
    $index = find_record_index_by_id($data['transactions'], $transactionId);

    if ($index === null) {
        api_fail('未找到对应流水。', 404);
    }

    $current = $data['transactions'][$index];
    $payload = transaction_payload_from_request($request, $current);
    api_validate_transaction_payload($payload);

    $payload['attachments'] = filter_remaining_attachments(
        record_attachments($current),
        api_string_array($request['remove_attachment_ids'] ?? [])
    );

    $attachmentsResult = store_uploaded_image_attachments($_FILES['attachments'] ?? []);
    if (!(bool) ($attachmentsResult['ok'] ?? false)) {
        api_fail((string) ($attachmentsResult['error'] ?? 'Attachment upload failed.'), 422);
    }

    $payload['attachments'] = array_merge($payload['attachments'], $attachmentsResult['attachments']);
    $payload = apply_updated_audit_fields($current, $payload, $actor);
    $data['transactions'][$index] = $payload;
    audit_log($data, $actor, 'finance', 'update', 'transaction', (string) $payload['id'], '编辑流水：' . (string) $payload['counterparty']);

    save_data($data);
    api_success('流水已更新。', $data);
}

function api_append_transaction_attachments(array $data, array $request): void
{
    $actor = api_current_actor();
    $transactionId = input_string($request, 'transaction_id');
    $index = find_record_index_by_id($data['transactions'], $transactionId);

    if ($index === null) {
        api_fail('未找到对应流水。', 404);
    }

    $removeIds = api_string_array($request['remove_attachment_ids'] ?? []);
    $hasNewFiles = uploaded_files_present($_FILES['attachments'] ?? []);

    if ($removeIds === [] && !$hasNewFiles) {
        api_fail('请先选择要上传或移除的附件。', 422);
    }

    $current = $data['transactions'][$index];
    $attachments = filter_remaining_attachments(record_attachments($current), $removeIds);
    $attachmentsResult = store_uploaded_image_attachments($_FILES['attachments'] ?? []);

    if (!(bool) ($attachmentsResult['ok'] ?? false)) {
        api_fail((string) ($attachmentsResult['error'] ?? 'Attachment upload failed.'), 422);
    }

    $data['transactions'][$index]['attachments'] = array_merge($attachments, $attachmentsResult['attachments']);
    $data['transactions'][$index] = touch_record_audit_fields($data['transactions'][$index], $actor);
    audit_log($data, $actor, 'finance', 'attachment', 'transaction', (string) ($data['transactions'][$index]['id'] ?? ''), '更新流水附件：' . (string) ($data['transactions'][$index]['counterparty'] ?? ''));

    save_data($data);
    api_success('流水附件已更新。', $data);
}

function api_delete_transaction(array $data, array $request): void
{
    $actor = api_current_actor();
    $transactionId = input_string($request, 'transaction_id');
    $index = find_record_index_by_id($data['transactions'], $transactionId);

    if ($index === null) {
        api_fail('未找到对应流水。', 404);
    }

    $current = $data['transactions'][$index];
    purge_attachments(record_attachments($current));
    array_splice($data['transactions'], $index, 1);
    audit_log($data, $actor, 'finance', 'delete', 'transaction', $transactionId, '删除流水：' . (string) ($current['counterparty'] ?? ''));

    save_data($data);
    api_success('流水已删除。', $data);
}

function api_add_invoice(array $data, array $request): void
{
    $actor = api_current_actor();
    $payload = invoice_payload_from_request($request);
    api_validate_invoice_payload($payload);

    $attachmentsResult = store_uploaded_image_attachments($_FILES['attachments'] ?? []);
    if (!(bool) ($attachmentsResult['ok'] ?? false)) {
        api_fail((string) ($attachmentsResult['error'] ?? 'Attachment upload failed.'), 422);
    }

    $payload['attachments'] = $attachmentsResult['attachments'];
    $payload = apply_created_audit_fields($payload, $actor);
    $data['invoices'][] = $payload;
    audit_log($data, $actor, 'finance', 'create', 'invoice', (string) $payload['id'], '新增单据：' . (string) $payload['title']);

    save_data($data);
    api_success('单据已新增。', $data);
}

function api_update_invoice(array $data, array $request): void
{
    $actor = api_current_actor();
    $invoiceId = input_string($request, 'invoice_id');
    $index = find_record_index_by_id($data['invoices'], $invoiceId);

    if ($index === null) {
        api_fail('未找到对应单据。', 404);
    }

    $current = $data['invoices'][$index];
    $payload = invoice_payload_from_request($request, $current);
    api_validate_invoice_payload($payload);

    $payload['attachments'] = filter_remaining_attachments(
        record_attachments($current),
        api_string_array($request['remove_attachment_ids'] ?? [])
    );

    $attachmentsResult = store_uploaded_image_attachments($_FILES['attachments'] ?? []);
    if (!(bool) ($attachmentsResult['ok'] ?? false)) {
        api_fail((string) ($attachmentsResult['error'] ?? 'Attachment upload failed.'), 422);
    }

    $payload['attachments'] = array_merge($payload['attachments'], $attachmentsResult['attachments']);
    $payload = apply_updated_audit_fields($current, $payload, $actor);
    $data['invoices'][$index] = $payload;
    audit_log($data, $actor, 'finance', 'update', 'invoice', (string) $payload['id'], '编辑单据：' . (string) $payload['title']);

    save_data($data);
    api_success('单据已更新。', $data);
}

function api_append_invoice_attachments(array $data, array $request): void
{
    $actor = api_current_actor();
    $invoiceId = input_string($request, 'invoice_id');
    $index = find_record_index_by_id($data['invoices'], $invoiceId);

    if ($index === null) {
        api_fail('未找到对应单据。', 404);
    }

    $removeIds = api_string_array($request['remove_attachment_ids'] ?? []);
    $hasNewFiles = uploaded_files_present($_FILES['attachments'] ?? []);

    if ($removeIds === [] && !$hasNewFiles) {
        api_fail('请先选择要上传或移除的附件。', 422);
    }

    $current = $data['invoices'][$index];
    $attachments = filter_remaining_attachments(record_attachments($current), $removeIds);
    $attachmentsResult = store_uploaded_image_attachments($_FILES['attachments'] ?? []);

    if (!(bool) ($attachmentsResult['ok'] ?? false)) {
        api_fail((string) ($attachmentsResult['error'] ?? 'Attachment upload failed.'), 422);
    }

    $data['invoices'][$index]['attachments'] = array_merge($attachments, $attachmentsResult['attachments']);
    $data['invoices'][$index] = touch_record_audit_fields($data['invoices'][$index], $actor);
    audit_log($data, $actor, 'finance', 'attachment', 'invoice', (string) ($data['invoices'][$index]['id'] ?? ''), '更新单据附件：' . (string) ($data['invoices'][$index]['title'] ?? ''));

    save_data($data);
    api_success('单据附件已更新。', $data);
}

function api_update_invoice_status(array $data, array $request): void
{
    $actor = api_current_actor();
    $invoiceId = input_string($request, 'invoice_id');
    $kind = input_string($request, 'kind', 'receivable');
    $status = input_string($request, 'status', 'pending');
    $index = find_record_index_by_id($data['invoices'], $invoiceId);

    if ($index === null) {
        api_fail('未找到对应单据。', 404);
    }

    if (!array_key_exists($status, invoice_status_options($kind))) {
        api_fail('单据状态无效。', 422);
    }

    $data['invoices'][$index]['status'] = $status;
    $data['invoices'][$index] = touch_record_audit_fields($data['invoices'][$index], $actor);
    audit_log($data, $actor, 'finance', 'status', 'invoice', $invoiceId, '更新单据状态：' . (string) ($data['invoices'][$index]['title'] ?? ''));
    save_data($data);
    api_success('单据状态已更新。', $data);
}

function api_delete_invoice(array $data, array $request): void
{
    $actor = api_current_actor();
    $invoiceId = input_string($request, 'invoice_id');
    $index = find_record_index_by_id($data['invoices'], $invoiceId);

    if ($index === null) {
        api_fail('未找到对应单据。', 404);
    }

    $current = $data['invoices'][$index];
    purge_attachments(record_attachments($current));
    array_splice($data['invoices'], $index, 1);
    audit_log($data, $actor, 'finance', 'delete', 'invoice', $invoiceId, '删除单据：' . (string) ($current['title'] ?? ''));

    save_data($data);
    api_success('单据已删除。', $data);
}

function api_add_project(array $data, array $request): void
{
    $actor = api_current_actor();
    $payload = project_payload_from_request($request);
    api_validate_project_payload($payload);

    if ($payload['client'] === '') {
        $payload['client'] = '内部项目';
    }

    $payload = apply_created_audit_fields($payload, $actor);
    $data['projects'][] = $payload;
    audit_log($data, $actor, 'projects', 'create', 'project', (string) $payload['id'], '新增项目：' . (string) $payload['name']);
    save_data($data);
    api_success('项目已新增。', $data);
}

function api_update_project(array $data, array $request): void
{
    $actor = api_current_actor();
    $projectId = input_string($request, 'project_id');
    $index = find_record_index_by_id($data['projects'], $projectId);

    if ($index === null) {
        api_fail('未找到对应项目。', 404);
    }

    $payload = project_payload_from_request($request, $data['projects'][$index]);
    api_validate_project_payload($payload);

    if ($payload['client'] === '') {
        $payload['client'] = '内部项目';
    }

    $payload = apply_updated_audit_fields($data['projects'][$index], $payload, $actor);
    $data['projects'][$index] = $payload;
    audit_log($data, $actor, 'projects', 'update', 'project', (string) $payload['id'], '编辑项目：' . (string) $payload['name']);
    save_data($data);
    api_success('项目已更新。', $data);
}

function api_update_project_status(array $data, array $request): void
{
    $actor = api_current_actor();
    $projectId = input_string($request, 'project_id');
    $status = input_string($request, 'status', 'planning');
    $index = find_record_index_by_id($data['projects'], $projectId);

    if ($index === null) {
        api_fail('未找到对应项目。', 404);
    }

    if (!array_key_exists($status, project_status_options())) {
        api_fail('项目状态无效。', 422);
    }

    $data['projects'][$index]['status'] = $status;
    $data['projects'][$index] = touch_record_audit_fields($data['projects'][$index], $actor);
    audit_log($data, $actor, 'projects', 'status', 'project', $projectId, '更新项目状态：' . (string) ($data['projects'][$index]['name'] ?? ''));
    save_data($data);
    api_success('项目状态已更新。', $data);
}

function api_delete_project(array $data, array $request): void
{
    $actor = api_current_actor();
    $projectId = input_string($request, 'project_id');
    $index = find_record_index_by_id($data['projects'], $projectId);

    if ($index === null) {
        api_fail('未找到对应项目。', 404);
    }

    foreach ($data['tasks'] as $task) {
        if ((string) ($task['project_id'] ?? '') === $projectId) {
            api_fail('该项目下仍有关联任务，请先处理任务。', 422);
        }
    }

    foreach ($data['transactions'] as $transaction) {
        if ((string) ($transaction['project_id'] ?? '') === $projectId) {
            api_fail('该项目下仍有关联流水，请先处理财务记录。', 422);
        }
    }

    foreach ($data['invoices'] as $invoice) {
        if ((string) ($invoice['project_id'] ?? '') === $projectId) {
            api_fail('该项目下仍有关联单据，请先处理应收应付。', 422);
        }
    }

    $current = $data['projects'][$index];
    array_splice($data['projects'], $index, 1);
    audit_log($data, $actor, 'projects', 'delete', 'project', $projectId, '删除项目：' . (string) ($current['name'] ?? ''));
    save_data($data);
    api_success('项目已删除。', $data);
}

function api_add_task(array $data, array $request): void
{
    $actor = api_current_actor();
    $payload = task_payload_from_request($request);
    api_validate_task_payload($payload);

    $payload = apply_created_audit_fields($payload, $actor);
    $data['tasks'][] = $payload;
    audit_log($data, $actor, 'projects', 'create', 'task', (string) $payload['id'], '新增任务：' . (string) $payload['title']);
    save_data($data);
    api_success('任务已新增。', $data);
}

function api_update_task(array $data, array $request): void
{
    $actor = api_current_actor();
    $taskId = input_string($request, 'task_id');
    $index = find_record_index_by_id($data['tasks'], $taskId);

    if ($index === null) {
        api_fail('未找到对应任务。', 404);
    }

    $payload = task_payload_from_request($request, $data['tasks'][$index]);
    api_validate_task_payload($payload);

    $payload = apply_updated_audit_fields($data['tasks'][$index], $payload, $actor);
    $data['tasks'][$index] = $payload;
    audit_log($data, $actor, 'projects', 'update', 'task', (string) $payload['id'], '编辑任务：' . (string) $payload['title']);
    save_data($data);
    api_success('任务已更新。', $data);
}

function api_update_task_status(array $data, array $request): void
{
    $actor = api_current_actor();
    $taskId = input_string($request, 'task_id');
    $status = input_string($request, 'status', 'todo');
    $index = find_record_index_by_id($data['tasks'], $taskId);

    if ($index === null) {
        api_fail('未找到对应任务。', 404);
    }

    if (!array_key_exists($status, task_status_options())) {
        api_fail('任务状态无效。', 422);
    }

    $data['tasks'][$index]['status'] = $status;
    $data['tasks'][$index] = touch_record_audit_fields($data['tasks'][$index], $actor);
    audit_log($data, $actor, 'projects', 'status', 'task', $taskId, '更新任务状态：' . (string) ($data['tasks'][$index]['title'] ?? ''));
    save_data($data);
    api_success('任务状态已更新。', $data);
}

function api_delete_task(array $data, array $request): void
{
    $actor = api_current_actor();
    $taskId = input_string($request, 'task_id');
    $index = find_record_index_by_id($data['tasks'], $taskId);

    if ($index === null) {
        api_fail('未找到对应任务。', 404);
    }

    $current = $data['tasks'][$index];
    array_splice($data['tasks'], $index, 1);
    audit_log($data, $actor, 'projects', 'delete', 'task', $taskId, '删除任务：' . (string) ($current['title'] ?? ''));
    save_data($data);
    api_success('任务已删除。', $data);
}

function api_add_ops_project(array $data, array $request): void
{
    $actor = api_current_actor();
    $payload = ops_project_payload_from_request($request);
    api_validate_ops_project_payload($data, $payload);

    $payload = apply_created_audit_fields($payload, $actor);
    $data['ops_projects'][] = $payload;
    audit_log($data, $actor, 'operations', 'create', 'ops_project', (string) $payload['id'], '新增运营项目：' . (string) $payload['name']);
    save_data($data);
    api_success('运营项目已新增。', $data);
}

function api_update_ops_project(array $data, array $request): void
{
    $actor = api_current_actor();
    $projectId = input_string($request, 'ops_project_id');
    $index = find_record_index_by_id($data['ops_projects'], $projectId);

    if ($index === null) {
        api_fail('未找到对应运营项目。', 404);
    }

    $payload = ops_project_payload_from_request($request, $data['ops_projects'][$index]);
    api_validate_ops_project_payload($data, $payload);

    $payload = apply_updated_audit_fields($data['ops_projects'][$index], $payload, $actor);
    $data['ops_projects'][$index] = $payload;
    audit_log($data, $actor, 'operations', 'update', 'ops_project', (string) $payload['id'], '编辑运营项目：' . (string) $payload['name']);
    save_data($data);
    api_success('运营项目已更新。', $data);
}

function api_delete_ops_project(array $data, array $request): void
{
    $actor = api_current_actor();
    $projectId = input_string($request, 'ops_project_id');
    $index = find_record_index_by_id($data['ops_projects'], $projectId);

    if ($index === null) {
        api_fail('未找到对应运营项目。', 404);
    }

    foreach ($data['ops_milestones'] ?? [] as $milestone) {
        if ((string) ($milestone['ops_project_id'] ?? '') === $projectId) {
            api_fail('该运营项目下仍有关联里程碑，请先处理。', 422);
        }
    }

    foreach ($data['ops_updates'] ?? [] as $update) {
        if ((string) ($update['ops_project_id'] ?? '') === $projectId) {
            api_fail('该运营项目下仍有关联周报，请先处理。', 422);
        }
    }

    foreach ($data['ops_risks'] ?? [] as $risk) {
        if ((string) ($risk['ops_project_id'] ?? '') === $projectId) {
            api_fail('该运营项目下仍有关联风险问题，请先处理。', 422);
        }
    }

    foreach ($data['ops_releases'] ?? [] as $release) {
        if ((string) ($release['ops_project_id'] ?? '') === $projectId) {
            api_fail('该 APP 运营项目下仍有关联版本发布，请先处理。', 422);
        }
    }

    foreach ($data['ops_materials'] ?? [] as $material) {
        if ((string) ($material['ops_project_id'] ?? '') === $projectId) {
            api_fail('该 APP 运营项目下仍有关联内部资料，请先处理。', 422);
        }
    }

    foreach ($data['tech_tickets'] ?? [] as $ticket) {
        if ((string) ($ticket['ops_project_id'] ?? '') === $projectId) {
            api_fail('该 APP 运营项目下仍有关联研发待办，请先处理。', 422);
        }
    }

    foreach ($data['service_tickets'] ?? [] as $ticket) {
        if ((string) ($ticket['ops_project_id'] ?? '') === $projectId) {
            api_fail('该 APP 运营项目下仍有关联问题记录，请先处理。', 422);
        }
    }

    $current = $data['ops_projects'][$index];
    array_splice($data['ops_projects'], $index, 1);
    audit_log($data, $actor, 'operations', 'delete', 'ops_project', $projectId, '删除运营项目：' . (string) ($current['name'] ?? ''));
    save_data($data);
    api_success('运营项目已删除。', $data);
}

function api_add_ops_milestone(array $data, array $request): void
{
    $actor = api_current_actor();
    $payload = ops_milestone_payload_from_request($request);
    api_validate_ops_milestone_payload($data, $payload);

    $payload = apply_created_audit_fields($payload, $actor);
    $data['ops_milestones'][] = $payload;
    audit_log($data, $actor, 'operations', 'create', 'ops_milestone', (string) $payload['id'], '新增里程碑：' . (string) $payload['title']);
    save_data($data);
    api_success('里程碑已新增。', $data);
}

function api_update_ops_milestone(array $data, array $request): void
{
    $actor = api_current_actor();
    $milestoneId = input_string($request, 'ops_milestone_id');
    $index = find_record_index_by_id($data['ops_milestones'], $milestoneId);

    if ($index === null) {
        api_fail('未找到对应里程碑。', 404);
    }

    $payload = ops_milestone_payload_from_request($request, $data['ops_milestones'][$index]);
    api_validate_ops_milestone_payload($data, $payload);

    $payload = apply_updated_audit_fields($data['ops_milestones'][$index], $payload, $actor);
    $data['ops_milestones'][$index] = $payload;
    audit_log($data, $actor, 'operations', 'update', 'ops_milestone', (string) $payload['id'], '编辑里程碑：' . (string) $payload['title']);
    save_data($data);
    api_success('里程碑已更新。', $data);
}

function api_update_ops_milestone_status(array $data, array $request): void
{
    $actor = api_current_actor();
    $milestoneId = input_string($request, 'ops_milestone_id');
    $status = input_string($request, 'status', 'pending');
    $index = find_record_index_by_id($data['ops_milestones'], $milestoneId);

    if ($index === null) {
        api_fail('未找到对应里程碑。', 404);
    }

    if (!array_key_exists($status, ops_milestone_status_options())) {
        api_fail('里程碑状态无效。', 422);
    }

    $data['ops_milestones'][$index]['status'] = $status;

    if ($status === 'done') {
        $data['ops_milestones'][$index]['progress'] = 100;
    }

    $data['ops_milestones'][$index] = touch_record_audit_fields($data['ops_milestones'][$index], $actor);
    audit_log($data, $actor, 'operations', 'status', 'ops_milestone', $milestoneId, '更新里程碑状态：' . (string) ($data['ops_milestones'][$index]['title'] ?? ''));
    save_data($data);
    api_success('里程碑状态已更新。', $data);
}

function api_delete_ops_milestone(array $data, array $request): void
{
    $actor = api_current_actor();
    $milestoneId = input_string($request, 'ops_milestone_id');
    $index = find_record_index_by_id($data['ops_milestones'], $milestoneId);

    if ($index === null) {
        api_fail('未找到对应里程碑。', 404);
    }

    $current = $data['ops_milestones'][$index];
    array_splice($data['ops_milestones'], $index, 1);
    audit_log($data, $actor, 'operations', 'delete', 'ops_milestone', $milestoneId, '删除里程碑：' . (string) ($current['title'] ?? ''));
    save_data($data);
    api_success('里程碑已删除。', $data);
}

function api_add_ops_update(array $data, array $request): void
{
    $actor = api_current_actor();
    $payload = ops_update_payload_from_request($request);
    api_validate_ops_update_payload($data, $payload);

    $payload = apply_created_audit_fields($payload, $actor);
    $data['ops_updates'][] = $payload;
    audit_log($data, $actor, 'operations', 'create', 'ops_update', (string) $payload['id'], '新增运营周报：' . (string) $payload['report_date']);
    save_data($data);
    api_success('运营周报已新增。', $data);
}

function api_update_ops_update(array $data, array $request): void
{
    $actor = api_current_actor();
    $updateId = input_string($request, 'ops_update_id');
    $index = find_record_index_by_id($data['ops_updates'], $updateId);

    if ($index === null) {
        api_fail('未找到对应周报。', 404);
    }

    $payload = ops_update_payload_from_request($request, $data['ops_updates'][$index]);
    api_validate_ops_update_payload($data, $payload);

    $payload = apply_updated_audit_fields($data['ops_updates'][$index], $payload, $actor);
    $data['ops_updates'][$index] = $payload;
    audit_log($data, $actor, 'operations', 'update', 'ops_update', (string) $payload['id'], '编辑运营周报：' . (string) $payload['report_date']);
    save_data($data);
    api_success('运营周报已更新。', $data);
}

function api_delete_ops_update(array $data, array $request): void
{
    $actor = api_current_actor();
    $updateId = input_string($request, 'ops_update_id');
    $index = find_record_index_by_id($data['ops_updates'], $updateId);

    if ($index === null) {
        api_fail('未找到对应周报。', 404);
    }

    $current = $data['ops_updates'][$index];
    array_splice($data['ops_updates'], $index, 1);
    audit_log($data, $actor, 'operations', 'delete', 'ops_update', $updateId, '删除运营周报：' . (string) ($current['report_date'] ?? ''));
    save_data($data);
    api_success('运营周报已删除。', $data);
}

function api_add_ops_release(array $data, array $request): void
{
    $actor = api_current_actor();
    $payload = ops_release_payload_from_request($request);
    api_validate_ops_release_payload($data, $payload);

    $payload = apply_created_audit_fields($payload, $actor);
    $data['ops_releases'][] = $payload;
    audit_log($data, $actor, 'operations', 'create', 'ops_release', (string) $payload['id'], '新增版本发布：' . (string) $payload['version']);
    save_data($data);
    api_success('版本发布台账已新增。', $data);
}

function api_update_ops_release(array $data, array $request): void
{
    $actor = api_current_actor();
    $releaseId = input_string($request, 'ops_release_id');
    $index = find_record_index_by_id($data['ops_releases'] ?? [], $releaseId);

    if ($index === null) {
        api_fail('未找到对应版本发布记录。', 404);
    }

    $payload = ops_release_payload_from_request($request, $data['ops_releases'][$index]);
    api_validate_ops_release_payload($data, $payload);

    $payload = apply_updated_audit_fields($data['ops_releases'][$index], $payload, $actor);
    $data['ops_releases'][$index] = $payload;
    audit_log($data, $actor, 'operations', 'update', 'ops_release', $releaseId, '编辑版本发布：' . (string) $payload['version']);
    save_data($data);
    api_success('版本发布台账已更新。', $data);
}

function api_update_ops_release_status(array $data, array $request): void
{
    $actor = api_current_actor();
    $releaseId = input_string($request, 'ops_release_id');
    $status = input_string($request, 'status', 'planned');
    $index = find_record_index_by_id($data['ops_releases'] ?? [], $releaseId);

    if ($index === null) {
        api_fail('未找到对应版本发布记录。', 404);
    }

    if (!array_key_exists($status, ops_release_status_options())) {
        api_fail('版本发布状态无效。', 422);
    }

    $data['ops_releases'][$index]['status'] = $status;
    $data['ops_releases'][$index] = touch_record_audit_fields($data['ops_releases'][$index], $actor);
    audit_log($data, $actor, 'operations', 'status', 'ops_release', $releaseId, '更新版本发布状态：' . (string) ($data['ops_releases'][$index]['version'] ?? ''));
    save_data($data);
    api_success('版本发布状态已更新。', $data);
}

function api_delete_ops_release(array $data, array $request): void
{
    $actor = api_current_actor();
    $releaseId = input_string($request, 'ops_release_id');
    $index = find_record_index_by_id($data['ops_releases'] ?? [], $releaseId);

    if ($index === null) {
        api_fail('未找到对应版本发布记录。', 404);
    }

    $current = $data['ops_releases'][$index];
    array_splice($data['ops_releases'], $index, 1);
    audit_log($data, $actor, 'operations', 'delete', 'ops_release', $releaseId, '删除版本发布：' . (string) ($current['version'] ?? ''));
    save_data($data);
    api_success('版本发布台账已删除。', $data);
}

function api_add_ops_material(array $data, array $request): void
{
    $actor = api_current_actor();
    $payload = ops_material_payload_from_request($request);

    if (!array_key_exists((string) ($payload['archive_status'] ?? ''), ops_material_archive_status_options())) {
        api_fail('内部资料归档状态无效。', 422);
    }

    api_validate_ops_material_payload($data, $payload, true);
    api_validate_ops_material_replacement($data, $payload);
    $uploadState = api_apply_ops_material_upload($payload, $request);
    $payload = $uploadState['payload'];
    api_validate_ops_material_payload($data, $payload);
    api_validate_ops_material_replacement($data, $payload);

    $payload = apply_created_audit_fields($payload, $actor);
    $data['ops_materials'][] = $payload;
    audit_log($data, $actor, 'operations', 'create', 'ops_material', (string) $payload['id'], '新增内部资料：' . (string) $payload['title']);
    save_data($data);
    api_success('内部资料已新增。', $data);
}

function api_update_ops_material(array $data, array $request): void
{
    $actor = api_current_actor();
    $materialId = input_string($request, 'ops_material_id');
    $index = find_record_index_by_id($data['ops_materials'] ?? [], $materialId);

    if ($index === null) {
        api_fail('未找到对应内部资料。', 404);
    }

    $payload = ops_material_payload_from_request($request, $data['ops_materials'][$index]);

    if (!array_key_exists((string) ($payload['archive_status'] ?? ''), ops_material_archive_status_options())) {
        api_fail('内部资料归档状态无效。', 422);
    }

    api_validate_ops_material_payload($data, $payload, true);
    api_validate_ops_material_replacement($data, $payload);
    $uploadState = api_apply_ops_material_upload($payload, $request);
    $payload = $uploadState['payload'];
    api_validate_ops_material_payload($data, $payload);
    api_validate_ops_material_replacement($data, $payload);

    if (((bool) ($uploadState['has_upload'] ?? false) || (bool) ($uploadState['remove_existing_upload'] ?? false))
        && ltrim((string) ($data['ops_materials'][$index]['file_path'] ?? ''), '/') !== '') {
        api_purge_ops_material_upload($data['ops_materials'][$index]);
    }

    $payload = apply_updated_audit_fields($data['ops_materials'][$index], $payload, $actor);
    $data['ops_materials'][$index] = $payload;
    audit_log($data, $actor, 'operations', 'update', 'ops_material', $materialId, '编辑内部资料：' . (string) $payload['title']);
    save_data($data);
    api_success('内部资料已更新。', $data);
}

function api_delete_ops_material(array $data, array $request): void
{
    $actor = api_current_actor();
    $materialId = input_string($request, 'ops_material_id');
    $index = find_record_index_by_id($data['ops_materials'] ?? [], $materialId);

    if ($index === null) {
        api_fail('未找到对应内部资料。', 404);
    }

    $current = $data['ops_materials'][$index];
    api_purge_ops_material_upload($current);
    array_splice($data['ops_materials'], $index, 1);
    audit_log($data, $actor, 'operations', 'delete', 'ops_material', $materialId, '删除内部资料：' . (string) ($current['title'] ?? ''));
    save_data($data);
    api_success('内部资料已删除。', $data);
}

function api_add_ops_risk(array $data, array $request): void
{
    $actor = api_current_actor();
    $payload = ops_risk_payload_from_request($request);
    api_validate_ops_risk_payload($data, $payload);

    $payload = apply_created_audit_fields($payload, $actor);
    $data['ops_risks'][] = $payload;
    audit_log($data, $actor, 'operations', 'create', 'ops_risk', (string) $payload['id'], '新增风险问题：' . (string) $payload['title']);
    save_data($data);
    api_success('风险问题已新增。', $data);
}

function api_update_ops_risk(array $data, array $request): void
{
    $actor = api_current_actor();
    $riskId = input_string($request, 'ops_risk_id');
    $index = find_record_index_by_id($data['ops_risks'], $riskId);

    if ($index === null) {
        api_fail('未找到对应风险问题。', 404);
    }

    $payload = ops_risk_payload_from_request($request, $data['ops_risks'][$index]);
    api_validate_ops_risk_payload($data, $payload);

    $payload = apply_updated_audit_fields($data['ops_risks'][$index], $payload, $actor);
    $data['ops_risks'][$index] = $payload;
    audit_log($data, $actor, 'operations', 'update', 'ops_risk', (string) $payload['id'], '编辑风险问题：' . (string) $payload['title']);
    save_data($data);
    api_success('风险问题已更新。', $data);
}

function api_update_ops_risk_status(array $data, array $request): void
{
    $actor = api_current_actor();
    $riskId = input_string($request, 'ops_risk_id');
    $status = input_string($request, 'status', 'open');
    $index = find_record_index_by_id($data['ops_risks'], $riskId);

    if ($index === null) {
        api_fail('未找到对应风险问题。', 404);
    }

    if (!array_key_exists($status, ops_risk_status_options())) {
        api_fail('风险状态无效。', 422);
    }

    $data['ops_risks'][$index]['status'] = $status;
    $data['ops_risks'][$index] = touch_record_audit_fields($data['ops_risks'][$index], $actor);
    audit_log($data, $actor, 'operations', 'status', 'ops_risk', $riskId, '更新风险状态：' . (string) ($data['ops_risks'][$index]['title'] ?? ''));
    save_data($data);
    api_success('风险状态已更新。', $data);
}

function api_delete_ops_risk(array $data, array $request): void
{
    $actor = api_current_actor();
    $riskId = input_string($request, 'ops_risk_id');
    $index = find_record_index_by_id($data['ops_risks'], $riskId);

    if ($index === null) {
        api_fail('未找到对应风险问题。', 404);
    }

    $current = $data['ops_risks'][$index];
    array_splice($data['ops_risks'], $index, 1);
    audit_log($data, $actor, 'operations', 'delete', 'ops_risk', $riskId, '删除风险问题：' . (string) ($current['title'] ?? ''));
    save_data($data);
    api_success('风险问题已删除。', $data);
}

function api_switch_current_user(array $data, array $request): void
{
    if (authenticated_user_or_null($data) === null) {
        api_fail('请先登录系统。', 401, api_bootstrap_payload($data));
    }

    $sessionUser = authenticated_user($data);

    if (!session_user_can_impersonate($data)) {
        api_fail('当前账号没有切换工作身份的权限。', 403, api_bootstrap_payload($data));
    }

    $targetUserId = input_string($request, 'current_user_id');
    $lookup = user_lookup($data['users'] ?? []);

    if ($targetUserId === '' || !isset($lookup[$targetUserId]) || (string) ($lookup[$targetUserId]['status'] ?? 'inactive') !== 'active') {
        api_fail('目标工作身份不存在或已停用。', 404, api_bootstrap_payload($data));
    }

    $targetUser = $lookup[$targetUserId];
    set_session_current_user_id((string) ($targetUser['id'] ?? ''));
    audit_log($data, $sessionUser, 'staff', 'switch_user', 'user', (string) ($targetUser['id'] ?? ''), '切换当前工作身份为：' . (string) ($targetUser['name'] ?? ''), [
        'acting_as' => (string) ($targetUser['name'] ?? ''),
    ]);
    save_data($data);
    api_success('当前工作身份已切换。', $data);
}

function api_normalize_service_ticket_customer_feedback(array $payload): array
{
    $payload['customer_notified'] = (bool) ($payload['customer_notified'] ?? false);
    $payload['customer_confirmed'] = (bool) ($payload['customer_confirmed'] ?? false);

    if ((bool) $payload['customer_confirmed']) {
        $payload['customer_notified'] = true;
    }

    if (!(bool) $payload['customer_notified']) {
        $payload['customer_notified_to'] = '';
        $payload['customer_notified_channel'] = '';
        $payload['customer_notified_at'] = '';
        $payload['customer_feedback_result'] = '';
        $payload['customer_confirmed'] = false;
        $payload['customer_confirmed_at'] = '';
        $payload['customer_confirmation_note'] = '';

        return $payload;
    }

    if ((string) ($payload['customer_notified_to'] ?? '') === '') {
        $payload['customer_notified_to'] = (string) ($payload['contact_name'] ?? ($payload['customer'] ?? ''));
    }

    if ((string) ($payload['customer_notified_channel'] ?? '') === '') {
        $payload['customer_notified_channel'] = (string) ($payload['channel'] ?? '');
    }

    if ((string) ($payload['customer_notified_at'] ?? '') === '') {
        $payload['customer_notified_at'] = (string) ($payload['last_follow_up_at'] ?? ($payload['opened_at'] ?? date('Y-m-d H:i:s')));
    }

    if (!(bool) $payload['customer_confirmed']) {
        $payload['customer_confirmed_at'] = '';
        $payload['customer_confirmation_note'] = '';

        return $payload;
    }

    if ((string) ($payload['customer_confirmed_at'] ?? '') === '') {
        $payload['customer_confirmed_at'] = (string) ($payload['customer_notified_at'] ?? date('Y-m-d H:i:s'));
    }

    return $payload;
}

function api_validate_ops_material_replacement(array $data, array $payload): void
{
    $replacementId = (string) ($payload['replacement_material_id'] ?? '');

    if ($replacementId === '') {
        return;
    }

    if ($replacementId === (string) ($payload['id'] ?? '')) {
        api_fail('替代资料不能选择自己。', 422);
    }

    if (find_record_index_by_id($data['ops_materials'] ?? [], $replacementId) === null) {
        api_fail('替代资料不存在。', 422);
    }
}

function api_add_service_ticket(array $data, array $request): void
{
    $actor = api_current_actor();
    $payload = service_ticket_payload_from_request($request);
    $payload = api_normalize_service_ticket_customer_feedback($payload);
    api_validate_service_ticket_payload($data, $payload);

    $payload = apply_created_audit_fields($payload, $actor);
    $data['service_tickets'][] = $payload;
    audit_log($data, $actor, 'service', 'create', 'service_ticket', (string) $payload['id'], '新增问题记录：' . (string) $payload['title']);
    save_data($data);
    api_success('问题记录已新增。', $data);
}

function api_update_service_ticket(array $data, array $request): void
{
    $actor = api_current_actor();
    $ticketId = input_string($request, 'service_ticket_id');
    $index = find_record_index_by_id($data['service_tickets'] ?? [], $ticketId);

    if ($index === null) {
        api_fail('未找到对应问题记录。', 404);
    }

    $payload = service_ticket_payload_from_request($request, $data['service_tickets'][$index]);
    $payload = api_normalize_service_ticket_customer_feedback($payload);
    api_validate_service_ticket_payload($data, $payload);

    $payload = apply_updated_audit_fields($data['service_tickets'][$index], $payload, $actor);
    $data['service_tickets'][$index] = $payload;
    audit_log($data, $actor, 'service', 'update', 'service_ticket', $ticketId, '编辑问题记录：' . (string) $payload['title']);
    save_data($data);
    api_success('问题记录已更新。', $data);
}

function api_update_service_ticket_status(array $data, array $request): void
{
    $actor = api_current_actor();
    $ticketId = input_string($request, 'service_ticket_id');
    $status = input_string($request, 'status', 'new');
    $index = find_record_index_by_id($data['service_tickets'] ?? [], $ticketId);

    if ($index === null) {
        api_fail('未找到对应问题记录。', 404);
    }

    if (!array_key_exists($status, service_ticket_status_options())) {
        api_fail('问题记录状态无效。', 422);
    }

    $data['service_tickets'][$index]['status'] = $status;
    $data['service_tickets'][$index]['last_follow_up_at'] = date('Y-m-d H:i:s');
    $data['service_tickets'][$index] = touch_record_audit_fields($data['service_tickets'][$index], $actor);
    api_append_service_ticket_update_record($data, service_ticket_update_payload_from_request([
        'service_ticket_id' => $ticketId,
        'type' => 'status',
        'visibility' => 'internal',
        'content' => '状态已更新为“' . service_ticket_status_label($status) . '”。',
        'status' => $status,
        'next_action' => (string) ($data['service_tickets'][$index]['next_action'] ?? ''),
        'created_at' => date('Y-m-d H:i:s'),
    ]), $actor);
    audit_log($data, $actor, 'service', 'status', 'service_ticket', $ticketId, '更新问题记录状态：' . (string) ($data['service_tickets'][$index]['title'] ?? ''));
    save_data($data);
    api_success('问题记录状态已更新。', $data);
}

function api_add_service_ticket_update(array $data, array $request): void
{
    $actor = api_current_actor();
    $payload = service_ticket_update_payload_from_request($request);
    $ticketId = (string) ($payload['service_ticket_id'] ?? '');
    $ticketIndex = find_record_index_by_id($data['service_tickets'] ?? [], $ticketId);

    if ($ticketIndex === null) {
        api_fail('未找到对应问题记录。', 404);
    }

    if ($payload['content'] === '') {
        api_fail('请填写本次跟进内容。', 422);
    }

    if (!array_key_exists((string) $payload['type'], service_ticket_update_type_options())) {
        api_fail('跟进类型无效。', 422);
    }

    if (!array_key_exists((string) ($payload['visibility'] ?? ''), service_ticket_update_visibility_options())) {
        api_fail('跟进对象无效。', 422);
    }

    $status = (string) ($payload['status'] ?? '');
    if ($status !== '' && !array_key_exists($status, service_ticket_status_options())) {
        api_fail('问题记录状态无效。', 422);
    }

    $data['service_tickets'][$ticketIndex]['last_follow_up_at'] = (string) ($payload['created_at'] ?? date('Y-m-d H:i:s'));

    if ($status !== '') {
        $data['service_tickets'][$ticketIndex]['status'] = $status;
    }

    if ((string) ($payload['next_action'] ?? '') !== '') {
        $data['service_tickets'][$ticketIndex]['next_action'] = (string) $payload['next_action'];
    }

    if ((string) ($payload['visibility'] ?? '') === 'customer') {
        $data['service_tickets'][$ticketIndex]['customer_notified'] = true;
        $data['service_tickets'][$ticketIndex]['customer_notified_to'] = (string) ($data['service_tickets'][$ticketIndex]['customer_notified_to'] ?? ($data['service_tickets'][$ticketIndex]['contact_name'] ?? ($data['service_tickets'][$ticketIndex]['customer'] ?? '')));
        $data['service_tickets'][$ticketIndex]['customer_notified_channel'] = (string) ($data['service_tickets'][$ticketIndex]['customer_notified_channel'] ?? ($data['service_tickets'][$ticketIndex]['channel'] ?? ''));
        $data['service_tickets'][$ticketIndex]['customer_notified_at'] = (string) ($payload['created_at'] ?? date('Y-m-d H:i:s'));
        $data['service_tickets'][$ticketIndex]['customer_feedback_result'] = (string) ($payload['content'] ?? '');
    }

    $data['service_tickets'][$ticketIndex] = touch_record_audit_fields($data['service_tickets'][$ticketIndex], $actor);
    api_append_service_ticket_update_record($data, $payload, $actor);
    audit_log($data, $actor, 'service', 'update', 'service_ticket', $ticketId, '新增问题跟进：' . (string) ($data['service_tickets'][$ticketIndex]['title'] ?? ''));
    save_data($data);
    api_success('问题跟进已记录。', $data);
}

function api_delete_service_ticket(array $data, array $request): void
{
    $actor = api_current_actor();
    $ticketId = input_string($request, 'service_ticket_id');
    $index = find_record_index_by_id($data['service_tickets'] ?? [], $ticketId);

    if ($index === null) {
        api_fail('未找到对应问题记录。', 404);
    }

    $current = $data['service_tickets'][$index];
    array_splice($data['service_tickets'], $index, 1);
    $data['service_ticket_updates'] = array_values(array_filter(
        $data['service_ticket_updates'] ?? [],
        static fn(array $row): bool => (string) ($row['service_ticket_id'] ?? '') !== $ticketId
    ));
    audit_log($data, $actor, 'service', 'delete', 'service_ticket', $ticketId, '删除问题记录：' . (string) ($current['title'] ?? ''));
    save_data($data);
    api_success('问题记录已删除。', $data);
}

function api_add_tech_ticket(array $data, array $request): void
{
    $actor = api_current_actor();
    $payload = tech_ticket_payload_from_request($request);
    api_validate_tech_ticket_payload($data, $payload);

    $payload = apply_created_audit_fields($payload, $actor);
    $data['tech_tickets'][] = $payload;
    audit_log($data, $actor, 'tech', 'create', 'tech_ticket', (string) $payload['id'], '新增研发待办：' . (string) $payload['title']);
    save_data($data);
    api_success('研发待办已新增。', $data);
}

function api_update_tech_ticket(array $data, array $request): void
{
    $actor = api_current_actor();
    $ticketId = input_string($request, 'tech_ticket_id');
    $index = find_record_index_by_id($data['tech_tickets'] ?? [], $ticketId);

    if ($index === null) {
        api_fail('未找到对应研发待办。', 404);
    }

    $payload = tech_ticket_payload_from_request($request, $data['tech_tickets'][$index]);
    api_validate_tech_ticket_payload($data, $payload);

    $payload = apply_updated_audit_fields($data['tech_tickets'][$index], $payload, $actor);
    $data['tech_tickets'][$index] = $payload;
    audit_log($data, $actor, 'tech', 'update', 'tech_ticket', $ticketId, '编辑研发待办：' . (string) $payload['title']);
    save_data($data);
    api_success('研发待办已更新。', $data);
}

function api_update_tech_ticket_status(array $data, array $request): void
{
    $actor = api_current_actor();
    $ticketId = input_string($request, 'tech_ticket_id');
    $status = input_string($request, 'status', 'pending');
    $index = find_record_index_by_id($data['tech_tickets'] ?? [], $ticketId);

    if ($index === null) {
        api_fail('未找到对应研发待办。', 404);
    }

    if (!array_key_exists($status, tech_ticket_status_options())) {
        api_fail('研发待办状态无效。', 422);
    }

    $data['tech_tickets'][$index]['status'] = $status;
    $data['tech_tickets'][$index] = touch_record_audit_fields($data['tech_tickets'][$index], $actor);
    audit_log($data, $actor, 'tech', 'status', 'tech_ticket', $ticketId, '更新研发待办状态：' . (string) ($data['tech_tickets'][$index]['title'] ?? ''));
    save_data($data);
    api_success('研发待办状态已更新。', $data);
}

function api_delete_tech_ticket(array $data, array $request): void
{
    $actor = api_current_actor();
    $ticketId = input_string($request, 'tech_ticket_id');
    $index = find_record_index_by_id($data['tech_tickets'] ?? [], $ticketId);

    if ($index === null) {
        api_fail('未找到对应研发待办。', 404);
    }

    $current = $data['tech_tickets'][$index];
    array_splice($data['tech_tickets'], $index, 1);
    audit_log($data, $actor, 'tech', 'delete', 'tech_ticket', $ticketId, '删除研发待办：' . (string) ($current['title'] ?? ''));
    save_data($data);
    api_success('研发待办已删除。', $data);
}

function api_add_user(array $data, array $request): void
{
    $actor = api_current_actor();
    $payload = user_payload_from_request($request);
    api_validate_user_payload($data, $payload);

    $payload = apply_created_audit_fields($payload, $actor);
    $data['users'][] = $payload;
    audit_log($data, $actor, 'staff', 'create', 'user', (string) $payload['id'], '新增工作人员：' . (string) $payload['name']);

    save_data($data);
    api_success('工作人员已新增。', $data);
}

function api_update_user(array $data, array $request): void
{
    $actor = api_current_actor();
    $userId = input_string($request, 'user_id');
    $index = find_record_index_by_id($data['users'] ?? [], $userId);

    if ($index === null) {
        api_fail('未找到对应工作人员。', 404);
    }

    $payload = user_payload_from_request($request, $data['users'][$index]);
    api_validate_user_payload($data, $payload, $userId);
    $payload = apply_updated_audit_fields($data['users'][$index], $payload, $actor);
    $data['users'][$index] = $payload;

    if ((string) $payload['status'] !== 'active' && session_auth_user_id() === $userId) {
        api_fail('不能停用当前登录账号。', 422);
    }

    if ((string) $payload['status'] !== 'active' && session_current_user_id() === $userId) {
        set_session_current_user_id(session_auth_user_id());
    }

    audit_log($data, $actor, 'staff', 'update', 'user', $userId, '编辑工作人员：' . (string) $payload['name']);
    save_data($data);
    api_success('工作人员已更新。', $data);
}

function api_delete_user(array $data, array $request): void
{
    $actor = api_current_actor();
    $userId = input_string($request, 'user_id');
    $index = find_record_index_by_id($data['users'] ?? [], $userId);

    if ($index === null) {
        api_fail('未找到对应工作人员。', 404);
    }

    if (session_auth_user_id() === $userId || session_current_user_id() === $userId) {
        api_fail('不能删除当前登录或当前工作身份账号。', 422);
    }

    $current = $data['users'][$index];
    $activeAdmins = 0;

    foreach ($data['users'] ?? [] as $user) {
        if ((string) ($user['status'] ?? 'active') === 'active' && (string) ($user['role'] ?? '') === 'admin') {
            $activeAdmins++;
        }
    }

    if ((string) ($current['status'] ?? 'active') === 'active' && (string) ($current['role'] ?? '') === 'admin' && $activeAdmins <= 1) {
        api_fail('至少保留一名在岗系统管理员。', 422);
    }

    array_splice($data['users'], $index, 1);
    audit_log($data, $actor, 'staff', 'delete', 'user', $userId, '删除工作人员：' . (string) ($current['name'] ?? ''));
    save_data($data);
    api_success('工作人员已删除。', $data);
}

function api_save_ai_settings(array $data, array $request): void
{
    $actor = api_current_actor();
    $providerName = input_string($request, 'provider_name', 'OpenAI Compatible');

    $data['ai']['settings'] = [
        'provider_name' => $providerName === '' ? 'OpenAI Compatible' : $providerName,
        'base_url' => input_string($request, 'base_url'),
        'api_key' => input_string($request, 'api_key'),
        'model' => input_string($request, 'model'),
        'temperature' => input_float($request, 'temperature', 0.2),
        'system_prompt' => input_string($request, 'system_prompt', ai_default_system_prompt()),
    ];

    audit_log($data, $actor, 'ai', 'settings', 'ai_settings', 'default', '更新 AI 配置：' . ($providerName === '' ? 'OpenAI Compatible' : $providerName));
    save_data($data);
    api_success('模型配置已保存。', $data);
}

function api_ask_ai(array $data, array $request): void
{
    $actor = api_current_actor();
    $question = input_string($request, 'question');

    if ($question === '') {
        api_fail('请输入要提问的内容。', 422);
    }

    ai_append_message($data, 'user', $question);
    $result = ai_ask($data, $question);

    if (!(bool) ($result['ok'] ?? false)) {
        ai_append_message($data, 'assistant', '请求失败：' . (string) ($result['error'] ?? '未知错误。'));
        save_data($data);
        api_fail((string) ($result['error'] ?? 'AI 请求失败。'), 422, api_bootstrap_payload($data));
    }

    ai_append_message($data, 'assistant', (string) $result['content']);
    audit_log($data, $actor, 'ai', 'ask_ai', 'conversation', 'default', 'AI 提问：' . trim_text($question, 40));
    save_data($data);
    api_success('AI 分析已完成。', $data);
}

function api_clear_ai_conversation(array $data): void
{
    $actor = api_current_actor();
    $data['ai']['conversation'] = [
        [
            'role' => 'assistant',
            'content' => '对话已清空，你可以继续追问现金流、回款、项目风险或成本问题。',
            'created_at' => date('Y-m-d H:i:s'),
        ],
    ];

    audit_log($data, $actor, 'ai', 'update', 'conversation', 'default', '清空 AI 对话记录');
    save_data($data);
    api_success('AI 对话已清空。', $data);
}

function api_validate_transaction_payload(array $payload): void
{
    if ($payload['category'] === '' || $payload['counterparty'] === '' || $payload['amount'] <= 0) {
        api_fail('请补全科目、往来方和金额。', 422);
    }

    if (!array_key_exists((string) $payload['type'], transaction_type_options())) {
        api_fail('流水类型无效。', 422);
    }
}

function api_validate_invoice_payload(array $payload): void
{
    if ($payload['title'] === '' || $payload['counterparty'] === '' || $payload['due_date'] === '' || $payload['amount'] <= 0) {
        api_fail('请补全标题、往来方、金额和到期日期。', 422);
    }

    if (
        !array_key_exists((string) $payload['kind'], invoice_kind_options())
        || !array_key_exists((string) $payload['status'], invoice_status_options((string) $payload['kind']))
    ) {
        api_fail('单据类型或状态无效。', 422);
    }
}

function api_validate_project_payload(array $payload): void
{
    if ($payload['name'] === '' || $payload['owner'] === '' || $payload['start_date'] === '' || $payload['due_date'] === '' || $payload['budget'] <= 0) {
        api_fail('请补全项目名称、负责人、预算和日期。', 422);
    }

    if (
        !array_key_exists((string) $payload['status'], project_status_options())
        || !array_key_exists((string) $payload['priority'], priority_options())
    ) {
        api_fail('项目状态或优先级无效。', 422);
    }
}

function api_validate_task_payload(array $payload): void
{
    if ($payload['project_id'] === '' || $payload['title'] === '' || $payload['assignee'] === '' || $payload['due_date'] === '') {
        api_fail('请补全所属项目、标题、负责人和截止日期。', 422);
    }

    if (
        !array_key_exists((string) $payload['status'], task_status_options())
        || !array_key_exists((string) $payload['priority'], priority_options())
    ) {
        api_fail('任务状态或优先级无效。', 422);
    }
}

function api_validate_user_payload(array $data, array $payload, string $currentUserId = ''): void
{
    if ($payload['account'] === '' || $payload['employee_no'] === '' || $payload['name'] === '' || $payload['department'] === '' || $payload['role'] === '') {
        api_fail('请补全账号、工号、姓名、部门和角色。', 422);
    }

    if (!array_key_exists((string) $payload['role'], role_options())) {
        api_fail('工作人员角色无效。', 422);
    }

    if (!array_key_exists((string) $payload['status'], user_status_options())) {
        api_fail('工作人员状态无效。', 422);
    }

    $validPermissions = array_keys(permission_catalog());
    foreach (auth_string_array($payload['permissions'] ?? []) as $permission) {
        if (!in_array($permission, $validPermissions, true)) {
            api_fail('存在无效的权限项。', 422);
        }
    }

    foreach ($data['users'] ?? [] as $user) {
        $userId = (string) ($user['id'] ?? '');
        $name = (string) ($user['name'] ?? '');
        $account = normalize_login_identity((string) ($user['account'] ?? ''));
        $employeeNo = (string) ($user['employee_no'] ?? '');
        $email = normalize_login_identity((string) ($user['email'] ?? ''));

        if ($userId !== $currentUserId && $name !== '' && $name === (string) $payload['name']) {
            api_fail('工作人员姓名已存在，请避免重名。', 422);
        }

        if ($userId !== $currentUserId && $account !== '' && $account === normalize_login_identity((string) $payload['account'])) {
            api_fail('登录账号已存在。', 422);
        }

        if ($userId !== $currentUserId && $employeeNo !== '' && $employeeNo === (string) $payload['employee_no']) {
            api_fail('工号已存在。', 422);
        }

        if (
            $userId !== $currentUserId
            && $email !== ''
            && normalize_login_identity((string) $payload['email']) !== ''
            && $email === normalize_login_identity((string) $payload['email'])
        ) {
            api_fail('邮箱已存在。', 422);
        }
    }

    if ((string) $payload['manager_id'] !== '' && find_record_index_by_id($data['users'] ?? [], (string) $payload['manager_id']) === null) {
        api_fail('直属上级不存在。', 422);
    }

    if ((string) $payload['manager_id'] !== '' && (string) $payload['manager_id'] === (string) $payload['id']) {
        api_fail('直属上级不能选择自己。', 422);
    }
}

function api_validate_service_ticket_payload(array $data, array $payload): void
{
    if (
        $payload['source'] === ''
        || $payload['title'] === ''
        || $payload['summary'] === ''
        || $payload['assignee'] === ''
        || $payload['resolve_due_at'] === ''
        || $payload['ops_project_id'] === ''
    ) {
        api_fail('请补全来源、标题、问题描述、负责人、处理时限和关联 APP 项目。', 422);
    }

    if (
        !array_key_exists((string) $payload['source'], service_ticket_source_options())
        || !array_key_exists((string) $payload['channel'], service_ticket_channel_options())
        || !array_key_exists((string) $payload['category'], service_ticket_category_options())
        || !array_key_exists((string) $payload['status'], service_ticket_status_options())
        || !array_key_exists((string) $payload['priority'], priority_options())
    ) {
        api_fail('问题记录的渠道、分类、状态或优先级无效。', 422);
    }

    if (
        (string) ($payload['customer_notified_channel'] ?? '') !== ''
        && !array_key_exists((string) $payload['customer_notified_channel'], service_ticket_channel_options())
    ) {
        api_fail('客户回告方式无效。', 422);
    }

    if (find_record_index_by_id($data['ops_projects'] ?? [], (string) $payload['ops_project_id']) === null) {
        api_fail('关联的 APP 运营项目不存在。', 422);
    }

    if ($payload['project_id'] !== '' && find_record_index_by_id($data['projects'] ?? [], (string) $payload['project_id']) === null) {
        api_fail('关联的交付项目不存在。', 422);
    }

    if ($payload['tech_ticket_id'] !== '' && find_record_index_by_id($data['tech_tickets'] ?? [], (string) $payload['tech_ticket_id']) === null) {
        api_fail('关联的研发待办不存在。', 422);
    }
}

function api_validate_tech_ticket_payload(array $data, array $payload): void
{
    if (
        $payload['ops_project_id'] === ''
        || $payload['title'] === ''
        || $payload['owner'] === ''
        || $payload['reporter'] === ''
        || $payload['due_date'] === ''
        || $payload['impact'] === ''
        || $payload['solution_plan'] === ''
    ) {
        api_fail('请补全关联 APP 项目、标题、负责人、提出人、截止日期、影响说明和处理方案。', 422);
    }

    if (
        !array_key_exists((string) $payload['type'], tech_ticket_type_options())
        || !array_key_exists((string) $payload['status'], tech_ticket_status_options())
        || !array_key_exists((string) $payload['severity'], tech_ticket_severity_options())
        || !array_key_exists((string) $payload['priority'], priority_options())
        || !array_key_exists((string) $payload['source'], tech_ticket_source_options())
    ) {
        api_fail('研发待办类型、状态、优先级、严重度或来源无效。', 422);
    }

    if (find_record_index_by_id($data['ops_projects'] ?? [], (string) $payload['ops_project_id']) === null) {
        api_fail('关联的 APP 运营项目不存在。', 422);
    }

    if ($payload['project_id'] !== '' && find_record_index_by_id($data['projects'] ?? [], (string) $payload['project_id']) === null) {
        api_fail('关联的交付项目不存在。', 422);
    }
}

function api_validate_ops_project_payload(array $data, array $payload): void
{
    if (
        $payload['name'] === ''
        || $payload['app_name'] === ''
        || $payload['manager'] === ''
        || $payload['start_date'] === ''
        || $payload['end_date'] === ''
        || $payload['target'] === ''
        || $payload['budget'] <= 0
    ) {
        api_fail('请补全运营项目名称、APP 名称、负责人、预算、起止日期和目标。', 422);
    }

    if (
        !array_key_exists((string) $payload['status'], ops_project_status_options())
        || !array_key_exists((string) $payload['lifecycle_stage'], ops_lifecycle_stage_options())
        || !array_key_exists((string) $payload['priority'], priority_options())
    ) {
        api_fail('运营项目状态、生命周期阶段或优先级无效。', 422);
    }

    if ($payload['end_date'] < $payload['start_date']) {
        api_fail('运营项目结束日期不能早于开始日期。', 422);
    }

    if ($payload['project_id'] !== '' && find_record_index_by_id($data['projects'] ?? [], (string) $payload['project_id']) === null) {
        api_fail('关联交付项目不存在。', 422);
    }
}

function api_validate_ops_milestone_payload(array $data, array $payload): void
{
    if (
        $payload['ops_project_id'] === ''
        || $payload['title'] === ''
        || $payload['owner'] === ''
        || $payload['due_date'] === ''
    ) {
        api_fail('请补全所属运营项目、里程碑标题、负责人和截止日期。', 422);
    }

    if (!array_key_exists((string) $payload['status'], ops_milestone_status_options())) {
        api_fail('里程碑状态无效。', 422);
    }

    if (find_record_index_by_id($data['ops_projects'] ?? [], (string) $payload['ops_project_id']) === null) {
        api_fail('所属运营项目不存在。', 422);
    }
}

function api_validate_ops_update_payload(array $data, array $payload): void
{
    if (
        $payload['ops_project_id'] === ''
        || $payload['report_date'] === ''
        || $payload['owner'] === ''
        || $payload['summary'] === ''
        || $payload['next_actions'] === ''
    ) {
        api_fail('请补全所属运营项目、汇报日期、负责人、进展概述和下周动作。', 422);
    }

    if (find_record_index_by_id($data['ops_projects'] ?? [], (string) $payload['ops_project_id']) === null) {
        api_fail('所属运营项目不存在。', 422);
    }
}

function api_validate_ops_release_payload(array $data, array $payload): void
{
    if (!array_key_exists((string) ($payload['customer_sync_status'] ?? ''), ops_release_customer_sync_status_options())) {
        api_fail('版本回告状态无效。', 422);
    }

    if (
        $payload['ops_project_id'] === ''
        || $payload['version'] === ''
        || $payload['title'] === ''
        || $payload['owner'] === ''
        || $payload['release_date'] === ''
        || $payload['release_notes'] === ''
        || $payload['rollback_plan'] === ''
    ) {
        api_fail('请补全所属 APP、版本号、发布标题、负责人、发布时间、发布说明和回滚预案。', 422);
    }

    if (!array_key_exists((string) $payload['status'], ops_release_status_options())) {
        api_fail('版本发布状态无效。', 422);
    }

    if (find_record_index_by_id($data['ops_projects'] ?? [], (string) $payload['ops_project_id']) === null) {
        api_fail('所属 APP 运营项目不存在。', 422);
    }

    foreach (ops_string_array($payload['tech_ticket_ids'] ?? []) as $ticketId) {
        if (find_record_index_by_id($data['tech_tickets'] ?? [], $ticketId) === null) {
            api_fail('存在无效的研发待办关联。', 422);
        }
    }
    foreach (ops_string_array($payload['service_ticket_ids'] ?? []) as $ticketId) {
        if (find_record_index_by_id($data['service_tickets'] ?? [], $ticketId) === null) {
            api_fail('存在无效的问题记录关联。', 422);
        }
    }
}

function api_validate_ops_material_payload(array $data, array $payload, bool $skipDownloadValidation = false): void
{
    if (
        $payload['ops_project_id'] === ''
        || $payload['title'] === ''
        || $payload['owner'] === ''
    ) {
        api_fail('请补全所属 APP、资料标题和负责人。', 422);
    }

    if (
        !$skipDownloadValidation
        && ($payload['download_name'] === '' || $payload['download_url'] === '')
    ) {
        api_fail('请补全下载文件名和下载地址，或改为上传文件。', 422);
    }

    if (!array_key_exists((string) $payload['category'], ops_material_category_options())) {
        api_fail('内部资料分类无效。', 422);
    }

    if (find_record_index_by_id($data['ops_projects'] ?? [], (string) $payload['ops_project_id']) === null) {
        api_fail('所属 APP 运营项目不存在。', 422);
    }
}

function api_validate_ops_risk_payload(array $data, array $payload): void
{
    if (
        $payload['ops_project_id'] === ''
        || $payload['title'] === ''
        || $payload['owner'] === ''
        || $payload['due_date'] === ''
        || $payload['impact'] === ''
        || $payload['action_plan'] === ''
    ) {
        api_fail('请补全所属运营项目、标题、负责人、应对截止日期、影响说明和处理动作。', 422);
    }

    if (
        !array_key_exists((string) $payload['type'], ops_risk_type_options())
        || !array_key_exists((string) $payload['level'], ops_risk_level_options())
        || !array_key_exists((string) $payload['status'], ops_risk_status_options())
    ) {
        api_fail('风险问题类型、等级或状态无效。', 422);
    }

    if (find_record_index_by_id($data['ops_projects'] ?? [], (string) $payload['ops_project_id']) === null) {
        api_fail('所属运营项目不存在。', 422);
    }
}

function api_string_array(mixed $value): array
{
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

    return array_values($rows);
}
