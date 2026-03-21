<?php

declare(strict_types=1);

function handle_post(): void
{
    $action = input_string($_POST, 'action');
    $data = load_data();

    switch ($action) {
        case 'smart_bookkeeping':
            smart_bookkeeping_action($data);
            return;
        case 'add_transaction':
            add_transaction_action($data);
            return;
        case 'update_transaction':
            update_transaction_action($data);
            return;
        case 'append_transaction_attachments':
            append_transaction_attachments_action($data);
            return;
        case 'delete_transaction':
            delete_transaction_action($data);
            return;
        case 'add_invoice':
            add_invoice_action($data);
            return;
        case 'update_invoice':
            update_invoice_action($data);
            return;
        case 'append_invoice_attachments':
            append_invoice_attachments_action($data);
            return;
        case 'update_invoice_status':
            update_invoice_status_action($data);
            return;
        case 'delete_invoice':
            delete_invoice_action($data);
            return;
        case 'add_project':
            add_project_action($data);
            return;
        case 'update_project':
            update_project_action($data);
            return;
        case 'update_project_status':
            update_project_status_action($data);
            return;
        case 'delete_project':
            delete_project_action($data);
            return;
        case 'add_task':
            add_task_action($data);
            return;
        case 'update_task':
            update_task_action($data);
            return;
        case 'update_task_status':
            update_task_status_action($data);
            return;
        case 'delete_task':
            delete_task_action($data);
            return;
        case 'save_ai_settings':
            save_ai_settings_action($data);
            return;
        case 'ask_ai':
            ask_ai_action($data);
            return;
        case 'clear_ai_conversation':
            clear_ai_conversation_action($data);
            return;
        default:
            redirect_with_flash('dashboard', 'error', '未识别的表单操作。');
    }
}

function finance_query(array $extra = []): array
{
    $base = [
        'tab' => input_string($_POST, 'return_tab', 'transactions'),
    ];

    return array_merge($base, $extra);
}

function projects_query(array $extra = []): array
{
    $base = [
        'tab' => input_string($_POST, 'return_tab', 'projects'),
    ];

    return array_merge($base, $extra);
}

function transaction_payload_from_request(array $source, array $current = []): array
{
    return [
        'id' => (string) ($current['id'] ?? next_id('tx')),
        'date' => input_string($source, 'date', (string) ($current['date'] ?? date('Y-m-d'))),
        'type' => input_string($source, 'type', (string) ($current['type'] ?? 'expense')),
        'category' => input_string($source, 'category', (string) ($current['category'] ?? '')),
        'counterparty' => input_string($source, 'counterparty', (string) ($current['counterparty'] ?? '')),
        'amount' => input_float($source, 'amount', (float) ($current['amount'] ?? 0)),
        'payment_method' => input_string($source, 'payment_method', (string) ($current['payment_method'] ?? 'other')),
        'project_id' => input_string($source, 'project_id', (string) ($current['project_id'] ?? '')),
        'notes' => input_string($source, 'notes', (string) ($current['notes'] ?? '')),
        'attachments' => record_attachments($current),
    ];
}

function invoice_payload_from_request(array $source, array $current = []): array
{
    return [
        'id' => (string) ($current['id'] ?? next_id('inv')),
        'kind' => input_string($source, 'kind', (string) ($current['kind'] ?? 'receivable')),
        'title' => input_string($source, 'title', (string) ($current['title'] ?? '')),
        'counterparty' => input_string($source, 'counterparty', (string) ($current['counterparty'] ?? '')),
        'amount' => input_float($source, 'amount', (float) ($current['amount'] ?? 0)),
        'due_date' => input_string($source, 'due_date', (string) ($current['due_date'] ?? '')),
        'status' => input_string($source, 'status', (string) ($current['status'] ?? 'pending')),
        'project_id' => input_string($source, 'project_id', (string) ($current['project_id'] ?? '')),
        'notes' => input_string($source, 'notes', (string) ($current['notes'] ?? '')),
        'attachments' => record_attachments($current),
    ];
}

function project_payload_from_request(array $source, array $current = []): array
{
    return [
        'id' => (string) ($current['id'] ?? next_id('prj')),
        'name' => input_string($source, 'name', (string) ($current['name'] ?? '')),
        'client' => input_string($source, 'client', (string) ($current['client'] ?? '')),
        'owner' => input_string($source, 'owner', (string) ($current['owner'] ?? '')),
        'status' => input_string($source, 'status', (string) ($current['status'] ?? 'planning')),
        'priority' => input_string($source, 'priority', (string) ($current['priority'] ?? 'medium')),
        'budget' => input_float($source, 'budget', (float) ($current['budget'] ?? 0)),
        'start_date' => input_string($source, 'start_date', (string) ($current['start_date'] ?? '')),
        'due_date' => input_string($source, 'due_date', (string) ($current['due_date'] ?? '')),
        'description' => input_string($source, 'description', (string) ($current['description'] ?? '')),
    ];
}

function task_payload_from_request(array $source, array $current = []): array
{
    return [
        'id' => (string) ($current['id'] ?? next_id('task')),
        'project_id' => input_string($source, 'project_id', (string) ($current['project_id'] ?? '')),
        'title' => input_string($source, 'title', (string) ($current['title'] ?? '')),
        'assignee' => input_string($source, 'assignee', (string) ($current['assignee'] ?? '')),
        'status' => input_string($source, 'status', (string) ($current['status'] ?? 'todo')),
        'priority' => input_string($source, 'priority', (string) ($current['priority'] ?? 'medium')),
        'due_date' => input_string($source, 'due_date', (string) ($current['due_date'] ?? '')),
        'estimate_hours' => input_float($source, 'estimate_hours', (float) ($current['estimate_hours'] ?? 0)),
        'actual_hours' => input_float($source, 'actual_hours', (float) ($current['actual_hours'] ?? 0)),
    ];
}

function smart_bookkeeping_flash_message(array $parsed, array $parseResult): string
{
    $message = '已智能记账：' . ((string) $parsed['type'] === 'income' ? '收入 ' : '支出 ') .
        money((float) $parsed['amount']) . '，往来方 ' . (string) $parsed['counterparty'] . '。';

    if (($parseResult['source'] ?? '') === 'ai') {
        $message .= ' 本次由大模型结构化解析。';
    } elseif (($parseResult['fallback_from'] ?? '') === 'ai') {
        $message .= ' 模型未返回可写入结果，已按规则兜底入账。';
    } else {
        $message .= ' 当前未配置模型，已按规则入账。';
    }

    if ((bool) ($parseResult['needs_review'] ?? false)) {
        $message .= ' 有字段使用了兜底值，建议再补充校对。';
    }

    return $message;
}

function smart_bookkeeping_action(array $data): void
{
    $text = input_string($_POST, 'smart_text');
    $returnQuery = ['tab' => 'transactions'];

    if ($text === '') {
        redirect_with_flash('finance', 'error', '请输入一句话记账内容。', $returnQuery);
    }

    $parseResult = smart_bookkeeping_parse($data, $text);
    if (!$parseResult['ok']) {
        redirect_with_flash('finance', 'error', (string) $parseResult['error'], $returnQuery);
    }

    $parsed = $parseResult['parsed'];
    $manualProjectId = input_string($_POST, 'project_id');
    if ($manualProjectId !== '') {
        $parsed['project_id'] = $manualProjectId;
    }

    $attachmentsResult = store_uploaded_image_attachments($_FILES['attachments'] ?? []);
    if (!$attachmentsResult['ok']) {
        redirect_with_flash('finance', 'error', (string) $attachmentsResult['error'], $returnQuery);
    }

    $data['transactions'][] = [
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
    ];

    save_data($data);
    redirect_with_flash('finance', 'success', smart_bookkeeping_flash_message($parsed, $parseResult), $returnQuery);

    $message = '已智能记账：' . ((string) $parsed['type'] === 'income' ? '收入 ' : '支出 ') .
        money((float) $parsed['amount']) . '，往来方 ' . (string) $parsed['counterparty'] . '。';

    if (($parseResult['source'] ?? '') === 'ai') {
        $message .= ' 本次由 AI 解析。';
    } else {
        $message .= ' 本次由规则解析。';
    }

    if ((bool) ($parseResult['needs_review'] ?? false)) {
        $message .= ' 有部分字段为兜底值，建议进入编辑补全。';
    }

    redirect_with_flash('finance', 'success', $message, $returnQuery);
}

function add_transaction_action(array $data): void
{
    $query = finance_query(['tab' => 'transactions']);
    $payload = transaction_payload_from_request($_POST);

    if ($payload['category'] === '' || $payload['counterparty'] === '' || $payload['amount'] <= 0) {
        redirect_with_flash('finance', 'error', '流水信息不完整，请补全分类、往来方和金额。', $query);
    }

    if (!array_key_exists($payload['type'], transaction_type_options())) {
        redirect_with_flash('finance', 'error', '流水类型无效。', $query);
    }

    $attachmentsResult = store_uploaded_image_attachments($_FILES['attachments'] ?? []);
    if (!$attachmentsResult['ok']) {
        redirect_with_flash('finance', 'error', (string) $attachmentsResult['error'], $query);
    }

    $payload['attachments'] = $attachmentsResult['attachments'];
    $data['transactions'][] = $payload;

    save_data($data);
    redirect_with_flash('finance', 'success', '已新增一笔财务流水。', $query);
}

function update_transaction_action(array $data): void
{
    $query = finance_query(['tab' => 'transactions']);
    $transactionId = input_string($_POST, 'transaction_id');
    $index = find_record_index_by_id($data['transactions'], $transactionId);

    if ($index === null) {
        redirect_with_flash('finance', 'error', '未找到对应的流水记录。', $query);
    }

    $current = $data['transactions'][$index];
    $payload = transaction_payload_from_request($_POST, $current);

    if ($payload['category'] === '' || $payload['counterparty'] === '' || $payload['amount'] <= 0) {
        redirect_with_flash('finance', 'error', '流水信息不完整，请补全分类、往来方和金额。', $query);
    }

    if (!array_key_exists($payload['type'], transaction_type_options())) {
        redirect_with_flash('finance', 'error', '流水类型无效。', $query);
    }

    $payload['attachments'] = filter_remaining_attachments(
        record_attachments($current),
        $_POST['remove_attachment_ids'] ?? []
    );

    $attachmentsResult = store_uploaded_image_attachments($_FILES['attachments'] ?? []);
    if (!$attachmentsResult['ok']) {
        redirect_with_flash('finance', 'error', (string) $attachmentsResult['error'], array_merge($query, ['edit_transaction_id' => $transactionId]));
    }

    $payload['attachments'] = array_merge($payload['attachments'], $attachmentsResult['attachments']);
    $data['transactions'][$index] = $payload;

    save_data($data);
    redirect_with_flash('finance', 'success', '流水记录已更新。', $query);
}

function append_transaction_attachments_action(array $data): void
{
    $query = finance_query(['tab' => 'transactions']);
    $transactionId = input_string($_POST, 'transaction_id');
    $index = find_record_index_by_id($data['transactions'], $transactionId);

    if ($index === null) {
        redirect_with_flash('finance', 'error', '未找到对应的流水记录。', $query);
    }

    $removeIds = $_POST['remove_attachment_ids'] ?? [];
    $hasNewFiles = uploaded_files_present($_FILES['attachments'] ?? []);

    if ($removeIds === [] && !$hasNewFiles) {
        redirect_with_flash('finance', 'error', '请先选择要补传或删除的附件。', array_merge($query, ['attach_transaction_id' => $transactionId]));
    }

    $current = $data['transactions'][$index];
    $attachments = filter_remaining_attachments(record_attachments($current), $removeIds);
    $attachmentsResult = store_uploaded_image_attachments($_FILES['attachments'] ?? []);

    if (!$attachmentsResult['ok']) {
        redirect_with_flash('finance', 'error', (string) $attachmentsResult['error'], array_merge($query, ['attach_transaction_id' => $transactionId]));
    }

    $data['transactions'][$index]['attachments'] = array_merge($attachments, $attachmentsResult['attachments']);
    save_data($data);

    redirect_with_flash('finance', 'success', '流水附件已更新。', $query);
}

function delete_transaction_action(array $data): void
{
    $query = finance_query(['tab' => 'transactions']);
    $transactionId = input_string($_POST, 'transaction_id');
    $index = find_record_index_by_id($data['transactions'], $transactionId);

    if ($index === null) {
        redirect_with_flash('finance', 'error', '未找到对应的流水记录。', $query);
    }

    purge_attachments(record_attachments($data['transactions'][$index]));
    array_splice($data['transactions'], $index, 1);
    save_data($data);

    redirect_with_flash('finance', 'success', '流水记录已删除。', $query);
}

function add_invoice_action(array $data): void
{
    $query = finance_query(['tab' => 'invoices']);
    $payload = invoice_payload_from_request($_POST);

    if ($payload['title'] === '' || $payload['counterparty'] === '' || $payload['due_date'] === '' || $payload['amount'] <= 0) {
        redirect_with_flash('finance', 'error', '应收应付信息不完整，请检查标题、往来方、金额和到期日。', $query);
    }

    if (!array_key_exists($payload['kind'], invoice_kind_options()) || !array_key_exists($payload['status'], invoice_status_options($payload['kind']))) {
        redirect_with_flash('finance', 'error', '应收应付状态无效。', $query);
    }

    $attachmentsResult = store_uploaded_image_attachments($_FILES['attachments'] ?? []);
    if (!$attachmentsResult['ok']) {
        redirect_with_flash('finance', 'error', (string) $attachmentsResult['error'], $query);
    }

    $payload['attachments'] = $attachmentsResult['attachments'];
    $data['invoices'][] = $payload;

    save_data($data);
    redirect_with_flash('finance', 'success', '已新增一条应收/应付记录。', $query);
}

function update_invoice_action(array $data): void
{
    $query = finance_query(['tab' => 'invoices']);
    $invoiceId = input_string($_POST, 'invoice_id');
    $index = find_record_index_by_id($data['invoices'], $invoiceId);

    if ($index === null) {
        redirect_with_flash('finance', 'error', '未找到对应的应收/应付记录。', $query);
    }

    $current = $data['invoices'][$index];
    $payload = invoice_payload_from_request($_POST, $current);

    if ($payload['title'] === '' || $payload['counterparty'] === '' || $payload['due_date'] === '' || $payload['amount'] <= 0) {
        redirect_with_flash('finance', 'error', '应收应付信息不完整，请检查标题、往来方、金额和到期日。', array_merge($query, ['edit_invoice_id' => $invoiceId]));
    }

    if (!array_key_exists($payload['kind'], invoice_kind_options()) || !array_key_exists($payload['status'], invoice_status_options($payload['kind']))) {
        redirect_with_flash('finance', 'error', '应收应付状态无效。', array_merge($query, ['edit_invoice_id' => $invoiceId]));
    }

    $payload['attachments'] = filter_remaining_attachments(
        record_attachments($current),
        $_POST['remove_attachment_ids'] ?? []
    );

    $attachmentsResult = store_uploaded_image_attachments($_FILES['attachments'] ?? []);
    if (!$attachmentsResult['ok']) {
        redirect_with_flash('finance', 'error', (string) $attachmentsResult['error'], array_merge($query, ['edit_invoice_id' => $invoiceId]));
    }

    $payload['attachments'] = array_merge($payload['attachments'], $attachmentsResult['attachments']);
    $data['invoices'][$index] = $payload;

    save_data($data);
    redirect_with_flash('finance', 'success', '应收/应付记录已更新。', $query);
}

function append_invoice_attachments_action(array $data): void
{
    $query = finance_query(['tab' => 'invoices']);
    $invoiceId = input_string($_POST, 'invoice_id');
    $index = find_record_index_by_id($data['invoices'], $invoiceId);

    if ($index === null) {
        redirect_with_flash('finance', 'error', '未找到对应的应收/应付记录。', $query);
    }

    $removeIds = $_POST['remove_attachment_ids'] ?? [];
    $hasNewFiles = uploaded_files_present($_FILES['attachments'] ?? []);

    if ($removeIds === [] && !$hasNewFiles) {
        redirect_with_flash('finance', 'error', '请先选择要补传或删除的附件。', array_merge($query, ['attach_invoice_id' => $invoiceId]));
    }

    $current = $data['invoices'][$index];
    $attachments = filter_remaining_attachments(record_attachments($current), $removeIds);
    $attachmentsResult = store_uploaded_image_attachments($_FILES['attachments'] ?? []);

    if (!$attachmentsResult['ok']) {
        redirect_with_flash('finance', 'error', (string) $attachmentsResult['error'], array_merge($query, ['attach_invoice_id' => $invoiceId]));
    }

    $data['invoices'][$index]['attachments'] = array_merge($attachments, $attachmentsResult['attachments']);
    save_data($data);

    redirect_with_flash('finance', 'success', '应收/应付附件已更新。', $query);
}

function update_invoice_status_action(array $data): void
{
    $query = finance_query(['tab' => 'invoices']);
    $invoiceId = input_string($_POST, 'invoice_id');
    $kind = input_string($_POST, 'kind', 'receivable');
    $status = input_string($_POST, 'status', 'pending');
    $index = find_record_index_by_id($data['invoices'], $invoiceId);

    if ($index === null) {
        redirect_with_flash('finance', 'error', '未找到对应的应收/应付记录。', $query);
    }

    if (!array_key_exists($status, invoice_status_options($kind))) {
        redirect_with_flash('finance', 'error', '应收应付状态无效。', $query);
    }

    $data['invoices'][$index]['status'] = $status;
    save_data($data);

    redirect_with_flash('finance', 'success', '应收/应付状态已更新。', $query);
}

function delete_invoice_action(array $data): void
{
    $query = finance_query(['tab' => 'invoices']);
    $invoiceId = input_string($_POST, 'invoice_id');
    $index = find_record_index_by_id($data['invoices'], $invoiceId);

    if ($index === null) {
        redirect_with_flash('finance', 'error', '未找到对应的应收/应付记录。', $query);
    }

    purge_attachments(record_attachments($data['invoices'][$index]));
    array_splice($data['invoices'], $index, 1);
    save_data($data);

    redirect_with_flash('finance', 'success', '应收/应付记录已删除。', $query);
}

function add_project_action(array $data): void
{
    $query = projects_query(['tab' => 'projects']);
    $payload = project_payload_from_request($_POST);

    if ($payload['name'] === '' || $payload['owner'] === '' || $payload['start_date'] === '' || $payload['due_date'] === '' || $payload['budget'] <= 0) {
        redirect_with_flash('projects', 'error', '项目信息不完整，请检查名称、负责人、预算和日期。', $query);
    }

    if (!array_key_exists($payload['status'], project_status_options()) || !array_key_exists($payload['priority'], priority_options())) {
        redirect_with_flash('projects', 'error', '项目状态或优先级无效。', $query);
    }

    if ($payload['client'] === '') {
        $payload['client'] = '内部项目';
    }

    $data['projects'][] = $payload;
    save_data($data);

    redirect_with_flash('projects', 'success', '新项目已创建。', $query);
}

function update_project_action(array $data): void
{
    $query = projects_query(['tab' => 'projects']);
    $projectId = input_string($_POST, 'project_id');
    $index = find_record_index_by_id($data['projects'], $projectId);

    if ($index === null) {
        redirect_with_flash('projects', 'error', '未找到对应项目。', $query);
    }

    $payload = project_payload_from_request($_POST, $data['projects'][$index]);

    if ($payload['name'] === '' || $payload['owner'] === '' || $payload['start_date'] === '' || $payload['due_date'] === '' || $payload['budget'] <= 0) {
        redirect_with_flash('projects', 'error', '项目信息不完整，请检查名称、负责人、预算和日期。', array_merge($query, ['edit_project_id' => $projectId]));
    }

    if (!array_key_exists($payload['status'], project_status_options()) || !array_key_exists($payload['priority'], priority_options())) {
        redirect_with_flash('projects', 'error', '项目状态或优先级无效。', array_merge($query, ['edit_project_id' => $projectId]));
    }

    if ($payload['client'] === '') {
        $payload['client'] = '内部项目';
    }

    $data['projects'][$index] = $payload;
    save_data($data);

    redirect_with_flash('projects', 'success', '项目资料已更新。', $query);
}

function update_project_status_action(array $data): void
{
    $query = projects_query(['tab' => 'projects']);
    $projectId = input_string($_POST, 'project_id');
    $status = input_string($_POST, 'status', 'planning');
    $index = find_record_index_by_id($data['projects'], $projectId);

    if ($index === null) {
        redirect_with_flash('projects', 'error', '未找到对应项目。', $query);
    }

    if (!array_key_exists($status, project_status_options())) {
        redirect_with_flash('projects', 'error', '项目状态无效。', $query);
    }

    $data['projects'][$index]['status'] = $status;
    save_data($data);

    redirect_with_flash('projects', 'success', '项目状态已更新。', $query);
}

function delete_project_action(array $data): void
{
    $query = projects_query(['tab' => 'projects']);
    $projectId = input_string($_POST, 'project_id');
    $index = find_record_index_by_id($data['projects'], $projectId);

    if ($index === null) {
        redirect_with_flash('projects', 'error', '未找到对应项目。', $query);
    }

    foreach ($data['tasks'] as $task) {
        if ((string) ($task['project_id'] ?? '') === $projectId) {
            redirect_with_flash('projects', 'error', '该项目下仍有关联任务，请先清理任务后再删除项目。', $query);
        }
    }

    foreach ($data['transactions'] as $transaction) {
        if ((string) ($transaction['project_id'] ?? '') === $projectId) {
            redirect_with_flash('projects', 'error', '该项目下仍有关联财务流水，请先处理后再删除项目。', $query);
        }
    }

    foreach ($data['invoices'] as $invoice) {
        if ((string) ($invoice['project_id'] ?? '') === $projectId) {
            redirect_with_flash('projects', 'error', '该项目下仍有关联应收应付，请先处理后再删除项目。', $query);
        }
    }

    array_splice($data['projects'], $index, 1);
    save_data($data);

    redirect_with_flash('projects', 'success', '项目已删除。', $query);
}

function add_task_action(array $data): void
{
    $query = projects_query(['tab' => 'tasks']);
    $payload = task_payload_from_request($_POST);

    if ($payload['project_id'] === '' || $payload['title'] === '' || $payload['assignee'] === '' || $payload['due_date'] === '') {
        redirect_with_flash('projects', 'error', '任务信息不完整，请检查所属项目、标题、负责人和截止日期。', $query);
    }

    if (!array_key_exists($payload['status'], task_status_options()) || !array_key_exists($payload['priority'], priority_options())) {
        redirect_with_flash('projects', 'error', '任务状态或优先级无效。', $query);
    }

    $data['tasks'][] = $payload;
    save_data($data);

    redirect_with_flash('projects', 'success', '新任务已加入项目。', $query);
}

function update_task_action(array $data): void
{
    $query = projects_query(['tab' => 'tasks']);
    $taskId = input_string($_POST, 'task_id');
    $index = find_record_index_by_id($data['tasks'], $taskId);

    if ($index === null) {
        redirect_with_flash('projects', 'error', '未找到对应任务。', $query);
    }

    $payload = task_payload_from_request($_POST, $data['tasks'][$index]);

    if ($payload['project_id'] === '' || $payload['title'] === '' || $payload['assignee'] === '' || $payload['due_date'] === '') {
        redirect_with_flash('projects', 'error', '任务信息不完整，请检查所属项目、标题、负责人和截止日期。', array_merge($query, ['edit_task_id' => $taskId]));
    }

    if (!array_key_exists($payload['status'], task_status_options()) || !array_key_exists($payload['priority'], priority_options())) {
        redirect_with_flash('projects', 'error', '任务状态或优先级无效。', array_merge($query, ['edit_task_id' => $taskId]));
    }

    $data['tasks'][$index] = $payload;
    save_data($data);

    redirect_with_flash('projects', 'success', '任务资料已更新。', $query);
}

function update_task_status_action(array $data): void
{
    $query = projects_query(['tab' => 'tasks']);
    $taskId = input_string($_POST, 'task_id');
    $status = input_string($_POST, 'status', 'todo');
    $index = find_record_index_by_id($data['tasks'], $taskId);

    if ($index === null) {
        redirect_with_flash('projects', 'error', '未找到对应任务。', $query);
    }

    if (!array_key_exists($status, task_status_options())) {
        redirect_with_flash('projects', 'error', '任务状态无效。', $query);
    }

    $data['tasks'][$index]['status'] = $status;
    save_data($data);

    redirect_with_flash('projects', 'success', '任务状态已更新。', $query);
}

function delete_task_action(array $data): void
{
    $query = projects_query(['tab' => 'tasks']);
    $taskId = input_string($_POST, 'task_id');
    $index = find_record_index_by_id($data['tasks'], $taskId);

    if ($index === null) {
        redirect_with_flash('projects', 'error', '未找到对应任务。', $query);
    }

    array_splice($data['tasks'], $index, 1);
    save_data($data);

    redirect_with_flash('projects', 'success', '任务已删除。', $query);
}

function save_ai_settings_action(array $data): void
{
    $providerName = input_string($_POST, 'provider_name', 'OpenAI Compatible');

    $data['ai']['settings'] = [
        'provider_name' => $providerName === '' ? 'OpenAI Compatible' : $providerName,
        'base_url' => input_string($_POST, 'base_url'),
        'api_key' => input_string($_POST, 'api_key'),
        'model' => input_string($_POST, 'model'),
        'temperature' => input_float($_POST, 'temperature', 0.2),
        'system_prompt' => input_string($_POST, 'system_prompt', ai_default_system_prompt()),
    ];

    save_data($data);
    redirect_with_flash('dashboard', 'success', '模型设置已保存。');
}

function ask_ai_action(array $data): void
{
    $question = input_string($_POST, 'question');

    if ($question === '') {
        redirect_with_flash('dashboard', 'error', '请输入要提问的内容。');
    }

    ai_append_message($data, 'user', $question);

    $result = ai_ask($data, $question);

    if (!$result['ok']) {
        ai_append_message($data, 'assistant', '请求失败：' . (string) $result['error']);
        save_data($data);
        redirect_with_flash('dashboard', 'error', (string) $result['error']);
    }

    ai_append_message($data, 'assistant', (string) $result['content']);
    save_data($data);
    redirect_with_flash('dashboard', 'success', 'AI 分析已完成。');
}

function clear_ai_conversation_action(array $data): void
{
    $data['ai']['conversation'] = [
        [
            'role' => 'assistant',
            'content' => '对话已清空。你可以继续询问现金流、回款计划、项目预算、任务优先级或经营分析相关问题。',
            'created_at' => date('Y-m-d H:i:s'),
        ],
    ];

    save_data($data);
    redirect_with_flash('dashboard', 'success', 'AI 对话已清空。');
}
