<?php

declare(strict_types=1);

$base = 'http://127.0.0.1:8092/MWDObBuRlr.php';
$cookie = __DIR__ . '/../tmp-fastadmin-cookie-multistep-smoke.txt';
@unlink($cookie);

function request(string $method, string $url, ?string $cookie = null, $postFields = null, array $headers = []): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HEADER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_COOKIEJAR => $cookie,
        CURLOPT_COOKIEFILE => $cookie,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30,
    ]);

    if ($postFields !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    }

    $response = curl_exec($ch);
    if ($response === false) {
        throw new RuntimeException(curl_error($ch));
    }

    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $body = substr($response, $headerSize);
    curl_close($ch);

    return [$status, $body];
}

function decodeJsonBody(string $body): array
{
    $trimmed = ltrim($body, "\xEF\xBB\xBF \t\r\n");
    if ($trimmed === '') {
        throw new RuntimeException('empty response body');
    }

    $jsonStart = strcspn($trimmed, '{[');
    if ($jsonStart > 0) {
        $trimmed = substr($trimmed, $jsonStart);
    }

    $decoded = json_decode($trimmed, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('invalid json: ' . substr($trimmed, 0, 400));
    }

    return $decoded;
}

function ajaxGet(string $path, array $query, string $base, string $cookie): array
{
    [$status, $body] = request(
        'GET',
        $base . '/' . $path . '?' . http_build_query($query),
        $cookie,
        null,
        [
            'X-Requested-With: XMLHttpRequest',
            'Accept: application/json, text/javascript, */*; q=0.01',
        ]
    );

    if ($status !== 200) {
        throw new RuntimeException($path . ' http ' . $status);
    }

    return decodeJsonBody($body);
}

function ajaxPost(string $path, array $data, string $base, string $cookie): array
{
    [$status, $body] = request(
        'POST',
        $base . '/' . $path,
        $cookie,
        http_build_query($data),
        [
            'X-Requested-With: XMLHttpRequest',
            'Accept: application/json, text/javascript, */*; q=0.01',
            'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
        ]
    );

    if ($status !== 200) {
        throw new RuntimeException($path . ' http ' . $status . ' body=' . substr($body, 0, 400));
    }

    return decodeJsonBody($body);
}

function login(string $base, string $cookie): void
{
    [$status, $loginHtml] = request('GET', $base . '/index/login', $cookie);
    if ($status !== 200) {
        throw new RuntimeException('login page http ' . $status);
    }
    if (!preg_match('/name="__token__"\s+value="([^"]+)"/', $loginHtml, $m)) {
        throw new RuntimeException('login token missing');
    }

    request(
        'POST',
        $base . '/index/login',
        $cookie,
        http_build_query([
            'username' => 'admin',
            'password' => 'Admin@123',
            'keeplogin' => '0',
            '__token__' => $m[1],
        ]),
        ['Content-Type: application/x-www-form-urlencoded']
    );
}

login($base, $cookie);

$stamp = date('YmdHis');
$templateName = 'smoke-multistep-template-' . $stamp;
$supplierName = 'smoke-multistep-supplier-' . $stamp;
$expenseTitle = 'smoke-multistep-expense-' . $stamp;
$cleanup = [
    'template_id' => 0,
    'template_step_ids' => [],
    'supplier_id' => 0,
    'expense_id' => 0,
    'approval_id' => 0,
];

try {
    $templateAdd = ajaxPost('business/approval_template/add', [
        'row[name]' => $templateName,
        'row[object_type]' => 'expense_request',
        'row[status]' => 'active',
        'row[is_default]' => '0',
        'row[min_amount]' => '300',
        'row[max_amount]' => '500',
        'row[description]' => 'smoke multi-step template',
    ], $base, $cookie);
    if (($templateAdd['code'] ?? 0) != 1) {
        throw new RuntimeException('template add failed: ' . json_encode($templateAdd, JSON_UNESCAPED_UNICODE));
    }

    $templateRows = ajaxGet('business/approval_template/index', [
        'offset' => 0,
        'limit' => 10,
        'sort' => 'id',
        'order' => 'desc',
        'filter' => json_encode(['name' => $templateName], JSON_UNESCAPED_UNICODE),
        'op' => json_encode(['name' => '='], JSON_UNESCAPED_UNICODE),
    ], $base, $cookie);
    $template = $templateRows['rows'][0] ?? null;
    if (!$template) {
        throw new RuntimeException('template lookup failed');
    }
    $cleanup['template_id'] = (int) $template['id'];

    foreach ([1 => '一级复核', 2 => '二级终审'] as $stepNo => $stepName) {
        $stepAdd = ajaxPost('business/approval_template_step/add', [
            'row[template_id]' => (string) $cleanup['template_id'],
            'row[step_no]' => (string) $stepNo,
            'row[step_name]' => $stepName,
            'row[approver_admin_id]' => '1',
            'row[status]' => 'active',
            'row[notes]' => 'smoke step ' . $stepNo,
        ], $base, $cookie);
        if (($stepAdd['code'] ?? 0) != 1) {
            throw new RuntimeException('template step add failed: ' . json_encode($stepAdd, JSON_UNESCAPED_UNICODE));
        }
    }

    $stepRows = ajaxGet('business/approval_template_step/index', [
        'offset' => 0,
        'limit' => 20,
        'sort' => 'id',
        'order' => 'asc',
        'filter' => json_encode(['template_id' => $cleanup['template_id']], JSON_UNESCAPED_UNICODE),
        'op' => json_encode(['template_id' => '='], JSON_UNESCAPED_UNICODE),
    ], $base, $cookie);
    foreach (($stepRows['rows'] ?? []) as $row) {
        $cleanup['template_step_ids'][] = (int) $row['id'];
    }

    $supplierAdd = ajaxPost('business/supplier/add', [
        'row[supplier_name]' => $supplierName,
        'row[short_name]' => 'smoke',
        'row[category]' => 'service',
        'row[level]' => 'normal',
        'row[status]' => 'active',
        'row[settlement_cycle]' => 'monthly',
        'row[contact_name]' => '测试联系人',
        'row[contact_phone]' => '13800009999',
        'row[contact_email]' => 'smoke@example.com',
        'row[city]' => '上海',
        'row[bank_name]' => '中国银行',
        'row[bank_account]' => '6222020202020202',
        'row[tax_no]' => 'SMOKE-TAX-MS-001',
        'row[owner_admin_id]' => '1',
        'row[notes]' => 'smoke-test',
    ], $base, $cookie);
    if (($supplierAdd['code'] ?? 0) != 1) {
        throw new RuntimeException('supplier add failed: ' . json_encode($supplierAdd, JSON_UNESCAPED_UNICODE));
    }

    $supplierRows = ajaxGet('business/supplier/index', [
        'offset' => 0,
        'limit' => 10,
        'sort' => 'id',
        'order' => 'desc',
        'filter' => json_encode(['supplier_name' => $supplierName], JSON_UNESCAPED_UNICODE),
        'op' => json_encode(['supplier_name' => '='], JSON_UNESCAPED_UNICODE),
    ], $base, $cookie);
    $cleanup['supplier_id'] = (int) (($supplierRows['rows'][0]['id'] ?? 0));
    if ($cleanup['supplier_id'] <= 0) {
        throw new RuntimeException('supplier lookup failed');
    }

    $expenseAdd = ajaxPost('business/expense_request/add', [
        'row[title]' => $expenseTitle,
        'row[supplier_id]' => (string) $cleanup['supplier_id'],
        'row[contract_id]' => '1',
        'row[customer_id]' => '1',
        'row[expense_type]' => 'procurement',
        'row[request_amount]' => '321.00',
        'row[requested_at]' => date('Y-m-d H:i:s'),
        'row[expected_pay_date]' => date('Y-m-d', strtotime('+2 day')),
        'row[owner_admin_id]' => '1',
        'row[reason]' => 'smoke multi-step expense request',
        'row[notes]' => 'smoke-test',
    ], $base, $cookie);
    if (($expenseAdd['code'] ?? 0) != 1) {
        throw new RuntimeException('expense add failed: ' . json_encode($expenseAdd, JSON_UNESCAPED_UNICODE));
    }

    $expenseRows = ajaxGet('business/expense_request/index', [
        'offset' => 0,
        'limit' => 10,
        'sort' => 'id',
        'order' => 'desc',
        'filter' => json_encode(['title' => $expenseTitle], JSON_UNESCAPED_UNICODE),
        'op' => json_encode(['title' => '='], JSON_UNESCAPED_UNICODE),
    ], $base, $cookie);
    $expense = $expenseRows['rows'][0] ?? null;
    if (!$expense) {
        throw new RuntimeException('expense lookup failed');
    }
    $cleanup['expense_id'] = (int) $expense['id'];

    $approvalAdd = ajaxPost('business/approval/add', [
        'row[object_type]' => 'expense_request',
        'row[expense_request_id]' => (string) $cleanup['expense_id'],
        'row[submit_reason]' => 'smoke multi-step approval request',
    ], $base, $cookie);
    if (($approvalAdd['code'] ?? 0) != 1) {
        throw new RuntimeException('approval add failed: ' . json_encode($approvalAdd, JSON_UNESCAPED_UNICODE));
    }

    $approvalRows = ajaxGet('business/approval/index', [
        'offset' => 0,
        'limit' => 10,
        'sort' => 'id',
        'order' => 'desc',
        'filter' => json_encode(['object_type' => 'expense_request', 'object_id' => $cleanup['expense_id']], JSON_UNESCAPED_UNICODE),
        'op' => json_encode(['object_type' => '=', 'object_id' => '='], JSON_UNESCAPED_UNICODE),
    ], $base, $cookie);
    $approval = $approvalRows['rows'][0] ?? null;
    if (!$approval) {
        throw new RuntimeException('approval lookup failed');
    }
    $cleanup['approval_id'] = (int) $approval['id'];
    if ((int) ($approval['template_id'] ?? 0) !== $cleanup['template_id']) {
        throw new RuntimeException('amount template match failed');
    }
    if ((int) ($approval['current_step'] ?? 0) !== 1 || (int) ($approval['total_steps'] ?? 0) !== 2 || ($approval['status'] ?? '') !== 'pending') {
        throw new RuntimeException('initial approval flow invalid: ' . json_encode($approval, JSON_UNESCAPED_UNICODE));
    }

    $approveFirst = ajaxPost('business/approval/approve/ids/' . $cleanup['approval_id'], ['note' => 'smoke step 1'], $base, $cookie);
    if (($approveFirst['code'] ?? 0) != 1) {
        throw new RuntimeException('approval step 1 failed: ' . json_encode($approveFirst, JSON_UNESCAPED_UNICODE));
    }

    $approvalRows = ajaxGet('business/approval/index', [
        'offset' => 0,
        'limit' => 10,
        'sort' => 'id',
        'order' => 'desc',
        'filter' => json_encode(['id' => $cleanup['approval_id']], JSON_UNESCAPED_UNICODE),
        'op' => json_encode(['id' => '='], JSON_UNESCAPED_UNICODE),
    ], $base, $cookie);
    $approval = $approvalRows['rows'][0] ?? null;
    if (!$approval || ($approval['status'] ?? '') !== 'pending' || (int) ($approval['current_step'] ?? 0) !== 2) {
        throw new RuntimeException('approval did not advance to step 2: ' . json_encode($approval, JSON_UNESCAPED_UNICODE));
    }

    $approveSecond = ajaxPost('business/approval/approve/ids/' . $cleanup['approval_id'], ['note' => 'smoke step 2'], $base, $cookie);
    if (($approveSecond['code'] ?? 0) != 1) {
        throw new RuntimeException('approval step 2 failed: ' . json_encode($approveSecond, JSON_UNESCAPED_UNICODE));
    }

    $approvalRows = ajaxGet('business/approval/index', [
        'offset' => 0,
        'limit' => 10,
        'sort' => 'id',
        'order' => 'desc',
        'filter' => json_encode(['id' => $cleanup['approval_id']], JSON_UNESCAPED_UNICODE),
        'op' => json_encode(['id' => '='], JSON_UNESCAPED_UNICODE),
    ], $base, $cookie);
    $approval = $approvalRows['rows'][0] ?? null;
    if (!$approval || ($approval['status'] ?? '') !== 'approved') {
        throw new RuntimeException('approval did not finish: ' . json_encode($approval, JSON_UNESCAPED_UNICODE));
    }

    $expenseRows = ajaxGet('business/expense_request/index', [
        'offset' => 0,
        'limit' => 10,
        'sort' => 'id',
        'order' => 'desc',
        'filter' => json_encode(['id' => $cleanup['expense_id']], JSON_UNESCAPED_UNICODE),
        'op' => json_encode(['id' => '='], JSON_UNESCAPED_UNICODE),
    ], $base, $cookie);
    $expense = $expenseRows['rows'][0] ?? null;
    if (!$expense || ($expense['status'] ?? '') !== 'approved' || ($expense['approval_status'] ?? '') !== 'approved') {
        throw new RuntimeException('expense did not sync after approval: ' . json_encode($expense, JSON_UNESCAPED_UNICODE));
    }

    echo json_encode([
        'template_added' => true,
        'template_steps_added' => count($cleanup['template_step_ids']) === 2,
        'approval_created' => true,
        'approval_step_1_passed' => true,
        'approval_step_2_passed' => true,
        'expense_synced' => true,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
} finally {
    if ($cleanup['approval_id'] > 0) {
        try {
            ajaxPost('business/approval/del', ['ids' => (string) $cleanup['approval_id']], $base, $cookie);
        } catch (Throwable $e) {
        }
    }
    if ($cleanup['expense_id'] > 0) {
        try {
            ajaxPost('business/expense_request/del', ['ids' => (string) $cleanup['expense_id']], $base, $cookie);
        } catch (Throwable $e) {
        }
    }
    if ($cleanup['supplier_id'] > 0) {
        try {
            ajaxPost('business/supplier/del', ['ids' => (string) $cleanup['supplier_id']], $base, $cookie);
        } catch (Throwable $e) {
        }
    }
    if (!empty($cleanup['template_step_ids'])) {
        try {
            ajaxPost('business/approval_template_step/del', ['ids' => implode(',', $cleanup['template_step_ids'])], $base, $cookie);
        } catch (Throwable $e) {
        }
    }
    if ($cleanup['template_id'] > 0) {
        try {
            ajaxPost('business/approval_template/del', ['ids' => (string) $cleanup['template_id']], $base, $cookie);
        } catch (Throwable $e) {
        }
    }
}

@unlink($cookie);
