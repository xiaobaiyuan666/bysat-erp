<?php

declare(strict_types=1);

$base = 'http://127.0.0.1:8092/MWDObBuRlr.php';
$cookie = __DIR__ . '/../tmp-fastadmin-cookie-payment-request-smoke.txt';
@unlink($cookie);

function parseEnvFile(string $path): array
{
    $result = [];
    $section = '';
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || $line[0] === ';') {
            continue;
        }
        if ($line[0] === '[') {
            $section = trim($line, '[]');
            continue;
        }
        if (strpos($line, '=') === false) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $result[$section . '.' . $key] = trim($value, "\"'");
    }

    return $result;
}

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

function pageStatus(string $path, string $base, string $cookie): int
{
    [$status] = request('GET', $base . '/' . ltrim($path, '/'), $cookie);
    return $status;
}

function fetchSingleRow(string $path, array $filter, array $op, string $base, string $cookie): ?array
{
    $rows = ajaxGet($path, [
        'offset' => 0,
        'limit' => 10,
        'sort' => 'id',
        'order' => 'desc',
        'filter' => json_encode($filter, JSON_UNESCAPED_UNICODE),
        'op' => json_encode($op, JSON_UNESCAPED_UNICODE),
    ], $base, $cookie);

    return $rows['rows'][0] ?? null;
}

function approveUntilDone(int $approvalId, string $base, string $cookie): array
{
    for ($i = 0; $i < 5; $i++) {
        $approval = fetchSingleRow('business/approval/index', ['id' => $approvalId], ['id' => '='], $base, $cookie);
        if (!$approval) {
            throw new RuntimeException('approval lookup failed: ' . $approvalId);
        }
        if (($approval['status'] ?? '') !== 'pending') {
            return $approval;
        }

        $result = ajaxPost('business/approval/approve/ids/' . $approvalId, [
            'note' => 'smoke auto approve step ' . ($i + 1),
        ], $base, $cookie);
        if (($result['code'] ?? 0) != 1) {
            throw new RuntimeException('approval approve failed: ' . json_encode($result, JSON_UNESCAPED_UNICODE));
        }
    }

    $approval = fetchSingleRow('business/approval/index', ['id' => $approvalId], ['id' => '='], $base, $cookie);
    if (!$approval || ($approval['status'] ?? '') === 'pending') {
        throw new RuntimeException('approval still pending after max retries');
    }

    return $approval;
}

$env = parseEnvFile(dirname(__DIR__) . '/.env');
$prefix = $env['database.prefix'] ?? 'fa_';
$pdo = new PDO(
    sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $env['database.hostname'] ?? '127.0.0.1',
        $env['database.hostport'] ?? '3306',
        $env['database.database'] ?? 'fastadmin'
    ),
    $env['database.username'] ?? 'root',
    $env['database.password'] ?? '',
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

login($base, $cookie);

$contract = $pdo->query("SELECT id, customer_id, owner_admin_id FROM {$prefix}business_contract ORDER BY id ASC LIMIT 1")->fetch();
if (!$contract) {
    throw new RuntimeException('no contract fixture');
}

$ownerId = (int) ($contract['owner_admin_id'] ?: 1);
$customerId = (int) ($contract['customer_id'] ?: 0);
$contractId = (int) $contract['id'];
$stamp = date('YmdHis');
$supplierName = 'smoke-payment-request-supplier-' . $stamp;
$purchaseTitle = 'smoke-payment-request-order-' . $stamp;
$reconciliationTitle = 'smoke-payment-request-reconciliation-' . $stamp;
$settlementTitle = 'smoke-payment-request-settlement-' . $stamp;
$invoiceTitle = 'smoke-payment-request-invoice-' . $stamp;
$invoiceNo = 'PRI-SMOKE-' . $stamp;
$requestTitle = 'smoke-payment-request-' . $stamp;

$cleanup = [
    'supplier_id' => 0,
    'purchase_order_id' => 0,
    'purchase_approval_id' => 0,
    'payment_plan_id' => 0,
    'reconciliation_id' => 0,
    'settlement_id' => 0,
    'invoice_id' => 0,
    'payment_request_id' => 0,
    'payment_request_approval_id' => 0,
];

try {
    $pageChecks = [
        'business_workbench' => pageStatus('business/workbench/index', $base, $cookie),
        'payment_request_index' => pageStatus('business/payment_request/index', $base, $cookie),
        'payment_request_add' => pageStatus('business/payment_request/add', $base, $cookie),
        'approval_add' => pageStatus('business/approval/add?object_type=payment_request', $base, $cookie),
    ];
    foreach ($pageChecks as $name => $status) {
        if ($status !== 200) {
            throw new RuntimeException($name . ' page http ' . $status);
        }
    }

    $supplierAdd = ajaxPost('business/supplier/add', [
        'row[supplier_name]' => $supplierName,
        'row[short_name]' => 'payreq',
        'row[category]' => 'service',
        'row[level]' => 'normal',
        'row[status]' => 'active',
        'row[settlement_cycle]' => 'monthly',
        'row[contact_name]' => '付款申请测试',
        'row[contact_phone]' => '13800007777',
        'row[contact_email]' => 'payreq@example.com',
        'row[city]' => '上海',
        'row[bank_name]' => '中国银行',
        'row[bank_account]' => '6222020202020209',
        'row[tax_no]' => 'PAYREQ-TAX-001',
        'row[owner_admin_id]' => (string) $ownerId,
        'row[notes]' => 'smoke-payment-request',
    ], $base, $cookie);
    if (($supplierAdd['code'] ?? 0) != 1) {
        throw new RuntimeException('supplier add failed: ' . json_encode($supplierAdd, JSON_UNESCAPED_UNICODE));
    }

    $supplier = fetchSingleRow('business/supplier/index', ['supplier_name' => $supplierName], ['supplier_name' => '='], $base, $cookie);
    if (!$supplier) {
        throw new RuntimeException('supplier lookup failed');
    }
    $cleanup['supplier_id'] = (int) $supplier['id'];

    $purchaseAdd = ajaxPost('business/purchase_order/add', [
        'row[title]' => $purchaseTitle,
        'row[supplier_id]' => (string) $cleanup['supplier_id'],
        'row[contract_id]' => (string) $contractId,
        'row[customer_id]' => (string) $customerId,
        'row[purchase_type]' => 'service',
        'row[order_amount]' => '900.00',
        'row[ordered_at]' => date('Y-m-d H:i:s'),
        'row[expected_delivery_date]' => date('Y-m-d', strtotime('+7 day')),
        'row[owner_admin_id]' => (string) $ownerId,
        'row[purchase_content]' => 'smoke payment request flow',
        'row[notes]' => 'smoke-payment-request',
    ], $base, $cookie);
    if (($purchaseAdd['code'] ?? 0) != 1) {
        throw new RuntimeException('purchase order add failed: ' . json_encode($purchaseAdd, JSON_UNESCAPED_UNICODE));
    }

    $purchaseOrder = fetchSingleRow('business/purchase_order/index', ['title' => $purchaseTitle], ['title' => '='], $base, $cookie);
    if (!$purchaseOrder) {
        throw new RuntimeException('purchase order lookup failed');
    }
    $cleanup['purchase_order_id'] = (int) $purchaseOrder['id'];

    $approvalAdd = ajaxPost('business/approval/add', [
        'row[object_type]' => 'purchase_order',
        'row[purchase_order_id]' => (string) $cleanup['purchase_order_id'],
        'row[submit_reason]' => 'smoke purchase approval',
    ], $base, $cookie);
    if (($approvalAdd['code'] ?? 0) != 1) {
        throw new RuntimeException('purchase approval add failed: ' . json_encode($approvalAdd, JSON_UNESCAPED_UNICODE));
    }

    $purchaseApproval = fetchSingleRow(
        'business/approval/index',
        ['object_type' => 'purchase_order', 'object_id' => $cleanup['purchase_order_id']],
        ['object_type' => '=', 'object_id' => '='],
        $base,
        $cookie
    );
    if (!$purchaseApproval) {
        throw new RuntimeException('purchase approval lookup failed');
    }
    $cleanup['purchase_approval_id'] = (int) $purchaseApproval['id'];

    $purchaseApproval = approveUntilDone($cleanup['purchase_approval_id'], $base, $cookie);
    if (($purchaseApproval['status'] ?? '') !== 'approved') {
        throw new RuntimeException('purchase approval did not finish: ' . json_encode($purchaseApproval, JSON_UNESCAPED_UNICODE));
    }

    $planCreate = ajaxPost('business/purchase_order/createpaymentplan/ids/' . $cleanup['purchase_order_id'], [], $base, $cookie);
    if (($planCreate['code'] ?? 0) != 1) {
        throw new RuntimeException('payment plan create failed: ' . json_encode($planCreate, JSON_UNESCAPED_UNICODE));
    }

    $purchaseOrder = fetchSingleRow('business/purchase_order/index', ['id' => $cleanup['purchase_order_id']], ['id' => '='], $base, $cookie);
    $cleanup['payment_plan_id'] = (int) ($purchaseOrder['payment_plan_id'] ?? 0);
    if (!$purchaseOrder || $cleanup['payment_plan_id'] <= 0) {
        throw new RuntimeException('payment plan link failed: ' . json_encode($purchaseOrder, JSON_UNESCAPED_UNICODE));
    }

    $reconciliationAdd = ajaxPost('business/purchase_reconciliation/add', [
        'row[title]' => $reconciliationTitle,
        'row[purchase_order_id]' => (string) $cleanup['purchase_order_id'],
        'row[payment_plan_id]' => (string) $cleanup['payment_plan_id'],
        'row[order_amount]' => '900.00',
        'row[confirmed_amount]' => '900.00',
        'row[variance_amount]' => '0.00',
        'row[reconciled_at]' => date('Y-m-d H:i:s'),
        'row[status]' => 'confirmed',
        'row[owner_admin_id]' => (string) $ownerId,
        'row[notes]' => 'smoke reconciliation',
    ], $base, $cookie);
    if (($reconciliationAdd['code'] ?? 0) != 1) {
        throw new RuntimeException('reconciliation add failed: ' . json_encode($reconciliationAdd, JSON_UNESCAPED_UNICODE));
    }

    $reconciliation = fetchSingleRow('business/purchase_reconciliation/index', ['title' => $reconciliationTitle], ['title' => '='], $base, $cookie);
    if (!$reconciliation) {
        throw new RuntimeException('reconciliation lookup failed');
    }
    $cleanup['reconciliation_id'] = (int) $reconciliation['id'];

    $settlementAdd = ajaxPost('business/purchase_settlement/add', [
        'row[title]' => $settlementTitle,
        'row[purchase_order_id]' => (string) $cleanup['purchase_order_id'],
        'row[payment_plan_id]' => (string) $cleanup['payment_plan_id'],
        'row[settlement_amount]' => '900.00',
        'row[paid_amount]' => '0.00',
        'row[invoiced_amount]' => '0.00',
        'row[balance_amount]' => '900.00',
        'row[invoice_status]' => 'none',
        'row[invoice_no]' => '',
        'row[invoiced_at]' => '',
        'row[status]' => 'confirmed',
        'row[owner_admin_id]' => (string) $ownerId,
        'row[settled_at]' => '',
        'row[notes]' => 'smoke settlement',
    ], $base, $cookie);
    if (($settlementAdd['code'] ?? 0) != 1) {
        throw new RuntimeException('settlement add failed: ' . json_encode($settlementAdd, JSON_UNESCAPED_UNICODE));
    }

    $settlement = fetchSingleRow('business/purchase_settlement/index', ['title' => $settlementTitle], ['title' => '='], $base, $cookie);
    if (!$settlement) {
        throw new RuntimeException('settlement lookup failed');
    }
    $cleanup['settlement_id'] = (int) $settlement['id'];

    $invoiceAdd = ajaxPost('business/purchase_invoice/add', [
        'row[invoice_no]' => $invoiceNo,
        'row[title]' => $invoiceTitle,
        'row[purchase_order_id]' => (string) $cleanup['purchase_order_id'],
        'row[settlement_id]' => (string) $cleanup['settlement_id'],
        'row[invoice_type]' => 'electronic',
        'row[invoice_amount]' => '900.00',
        'row[untaxed_amount]' => '825.69',
        'row[tax_amount]' => '74.31',
        'row[invoiced_at]' => date('Y-m-d'),
        'row[received_at]' => date('Y-m-d H:i:s'),
        'row[status]' => 'received',
        'row[owner_admin_id]' => (string) $ownerId,
        'row[notes]' => 'smoke invoice',
    ], $base, $cookie);
    if (($invoiceAdd['code'] ?? 0) != 1) {
        throw new RuntimeException('invoice add failed: ' . json_encode($invoiceAdd, JSON_UNESCAPED_UNICODE));
    }

    $invoice = fetchSingleRow('business/purchase_invoice/index', ['invoice_no' => $invoiceNo], ['invoice_no' => '='], $base, $cookie);
    if (!$invoice) {
        throw new RuntimeException('invoice lookup failed');
    }
    $cleanup['invoice_id'] = (int) $invoice['id'];

    $requestAdd = ajaxPost('business/payment_request/add', [
        'row[title]' => $requestTitle,
        'row[purchase_order_id]' => (string) $cleanup['purchase_order_id'],
        'row[settlement_id]' => (string) $cleanup['settlement_id'],
        'row[payment_plan_id]' => (string) $cleanup['payment_plan_id'],
        'row[request_amount]' => '900.00',
        'row[requested_at]' => date('Y-m-d H:i:s'),
        'row[status]' => 'draft',
        'row[owner_admin_id]' => (string) $ownerId,
        'row[notes]' => 'smoke payment request',
    ], $base, $cookie);
    if (($requestAdd['code'] ?? 0) != 1) {
        throw new RuntimeException('payment request add failed: ' . json_encode($requestAdd, JSON_UNESCAPED_UNICODE));
    }

    $paymentRequest = fetchSingleRow('business/payment_request/index', ['title' => $requestTitle], ['title' => '='], $base, $cookie);
    if (!$paymentRequest) {
        throw new RuntimeException('payment request lookup failed');
    }
    $cleanup['payment_request_id'] = (int) $paymentRequest['id'];

    $settlementDeleteBlocked = ajaxPost('business/purchase_settlement/del', ['ids' => (string) $cleanup['settlement_id']], $base, $cookie);
    if (($settlementDeleteBlocked['code'] ?? 0) == 1) {
        throw new RuntimeException('settlement delete should be blocked by payment request');
    }

    $paymentPlanDeleteBlocked = ajaxPost('business/payment_plan/del', ['ids' => (string) $cleanup['payment_plan_id']], $base, $cookie);
    if (($paymentPlanDeleteBlocked['code'] ?? 0) == 1) {
        throw new RuntimeException('payment plan delete should be blocked by payment request');
    }

    $requestApprovalAdd = ajaxPost('business/approval/add', [
        'row[object_type]' => 'payment_request',
        'row[payment_request_id]' => (string) $cleanup['payment_request_id'],
        'row[submit_reason]' => 'smoke payment request approval',
    ], $base, $cookie);
    if (($requestApprovalAdd['code'] ?? 0) != 1) {
        throw new RuntimeException('payment request approval add failed: ' . json_encode($requestApprovalAdd, JSON_UNESCAPED_UNICODE));
    }

    $requestApproval = fetchSingleRow(
        'business/approval/index',
        ['object_type' => 'payment_request', 'object_id' => $cleanup['payment_request_id']],
        ['object_type' => '=', 'object_id' => '='],
        $base,
        $cookie
    );
    if (!$requestApproval) {
        throw new RuntimeException('payment request approval lookup failed');
    }
    $cleanup['payment_request_approval_id'] = (int) $requestApproval['id'];

    $requestApproval = approveUntilDone($cleanup['payment_request_approval_id'], $base, $cookie);
    if (($requestApproval['status'] ?? '') !== 'approved') {
        throw new RuntimeException('payment request approval did not finish: ' . json_encode($requestApproval, JSON_UNESCAPED_UNICODE));
    }

    $markPaid = ajaxPost('business/payment_request/markpaid/ids/' . $cleanup['payment_request_id'], [], $base, $cookie);
    if (($markPaid['code'] ?? 0) != 1) {
        throw new RuntimeException('mark paid failed: ' . json_encode($markPaid, JSON_UNESCAPED_UNICODE));
    }

    $paymentRequest = fetchSingleRow('business/payment_request/index', ['id' => $cleanup['payment_request_id']], ['id' => '='], $base, $cookie);
    $paymentPlan = fetchSingleRow('business/payment_plan/index', ['id' => $cleanup['payment_plan_id']], ['id' => '='], $base, $cookie);
    $settlement = fetchSingleRow('business/purchase_settlement/index', ['id' => $cleanup['settlement_id']], ['id' => '='], $base, $cookie);
    $purchaseOrder = fetchSingleRow('business/purchase_order/index', ['id' => $cleanup['purchase_order_id']], ['id' => '='], $base, $cookie);

    if (
        !$paymentRequest ||
        ($paymentRequest['status'] ?? '') !== 'paid' ||
        ($paymentRequest['approval_status'] ?? '') !== 'approved'
    ) {
        throw new RuntimeException('payment request paid status failed: ' . json_encode($paymentRequest, JSON_UNESCAPED_UNICODE));
    }

    if (
        !$paymentPlan ||
        ($paymentPlan['status'] ?? '') !== 'paid'
    ) {
        throw new RuntimeException('payment plan paid status failed: ' . json_encode($paymentPlan, JSON_UNESCAPED_UNICODE));
    }

    if (
        !$settlement ||
        ($settlement['status'] ?? '') !== 'settled' ||
        round((float) ($settlement['paid_amount'] ?? 0), 2) !== 900.00 ||
        round((float) ($settlement['balance_amount'] ?? 0), 2) !== 0.00
    ) {
        throw new RuntimeException('settlement paid status failed: ' . json_encode($settlement, JSON_UNESCAPED_UNICODE));
    }

    if (
        !$purchaseOrder ||
        ($purchaseOrder['status'] ?? '') !== 'completed'
    ) {
        throw new RuntimeException('purchase order completed status failed: ' . json_encode($purchaseOrder, JSON_UNESCAPED_UNICODE));
    }

    $requestDel = ajaxPost('business/payment_request/del', ['ids' => (string) $cleanup['payment_request_id']], $base, $cookie);
    if (($requestDel['code'] ?? 0) != 1) {
        throw new RuntimeException('payment request delete failed: ' . json_encode($requestDel, JSON_UNESCAPED_UNICODE));
    }
    $cleanup['payment_request_id'] = 0;

    $requestApprovalDel = ajaxPost('business/approval/del', ['ids' => (string) $cleanup['payment_request_approval_id']], $base, $cookie);
    if (($requestApprovalDel['code'] ?? 0) != 1) {
        throw new RuntimeException('payment request approval delete failed: ' . json_encode($requestApprovalDel, JSON_UNESCAPED_UNICODE));
    }
    $cleanup['payment_request_approval_id'] = 0;

    $invoiceDel = ajaxPost('business/purchase_invoice/del', ['ids' => (string) $cleanup['invoice_id']], $base, $cookie);
    if (($invoiceDel['code'] ?? 0) != 1) {
        throw new RuntimeException('invoice delete failed: ' . json_encode($invoiceDel, JSON_UNESCAPED_UNICODE));
    }
    $cleanup['invoice_id'] = 0;

    $settlementDel = ajaxPost('business/purchase_settlement/del', ['ids' => (string) $cleanup['settlement_id']], $base, $cookie);
    if (($settlementDel['code'] ?? 0) != 1) {
        throw new RuntimeException('settlement delete failed: ' . json_encode($settlementDel, JSON_UNESCAPED_UNICODE));
    }
    $cleanup['settlement_id'] = 0;

    $reconciliationDel = ajaxPost('business/purchase_reconciliation/del', ['ids' => (string) $cleanup['reconciliation_id']], $base, $cookie);
    if (($reconciliationDel['code'] ?? 0) != 1) {
        throw new RuntimeException('reconciliation delete failed: ' . json_encode($reconciliationDel, JSON_UNESCAPED_UNICODE));
    }
    $cleanup['reconciliation_id'] = 0;

    $paymentPlanDel = ajaxPost('business/payment_plan/del', ['ids' => (string) $cleanup['payment_plan_id']], $base, $cookie);
    if (($paymentPlanDel['code'] ?? 0) != 1) {
        throw new RuntimeException('payment plan delete failed: ' . json_encode($paymentPlanDel, JSON_UNESCAPED_UNICODE));
    }
    $cleanup['payment_plan_id'] = 0;

    $purchaseApprovalDel = ajaxPost('business/approval/del', ['ids' => (string) $cleanup['purchase_approval_id']], $base, $cookie);
    if (($purchaseApprovalDel['code'] ?? 0) != 1) {
        throw new RuntimeException('purchase approval delete failed: ' . json_encode($purchaseApprovalDel, JSON_UNESCAPED_UNICODE));
    }
    $cleanup['purchase_approval_id'] = 0;

    $purchaseDel = ajaxPost('business/purchase_order/del', ['ids' => (string) $cleanup['purchase_order_id']], $base, $cookie);
    if (($purchaseDel['code'] ?? 0) != 1) {
        throw new RuntimeException('purchase order delete failed: ' . json_encode($purchaseDel, JSON_UNESCAPED_UNICODE));
    }
    $cleanup['purchase_order_id'] = 0;

    $supplierDel = ajaxPost('business/supplier/del', ['ids' => (string) $cleanup['supplier_id']], $base, $cookie);
    if (($supplierDel['code'] ?? 0) != 1) {
        throw new RuntimeException('supplier delete failed: ' . json_encode($supplierDel, JSON_UNESCAPED_UNICODE));
    }
    $cleanup['supplier_id'] = 0;

    echo json_encode([
        'page_checks' => $pageChecks,
        'supplier_added' => true,
        'purchase_order_added' => true,
        'purchase_approval_finished' => true,
        'payment_plan_created' => true,
        'reconciliation_added' => true,
        'settlement_added' => true,
        'invoice_added' => true,
        'payment_request_added' => true,
        'settlement_delete_blocked' => true,
        'payment_plan_delete_blocked' => true,
        'payment_request_approval_finished' => true,
        'payment_request_marked_paid' => true,
        'payment_plan_status_synced' => true,
        'settlement_status_synced' => true,
        'purchase_order_status_synced' => true,
        'cleanup_complete' => true,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
} finally {
    if ($cleanup['payment_request_id'] > 0) {
        try {
            ajaxPost('business/payment_request/del', ['ids' => (string) $cleanup['payment_request_id']], $base, $cookie);
        } catch (Throwable $e) {
        }
    }
    if ($cleanup['payment_request_approval_id'] > 0) {
        try {
            ajaxPost('business/approval/del', ['ids' => (string) $cleanup['payment_request_approval_id']], $base, $cookie);
        } catch (Throwable $e) {
        }
    }
    if ($cleanup['invoice_id'] > 0) {
        try {
            ajaxPost('business/purchase_invoice/del', ['ids' => (string) $cleanup['invoice_id']], $base, $cookie);
        } catch (Throwable $e) {
        }
    }
    if ($cleanup['settlement_id'] > 0) {
        try {
            ajaxPost('business/purchase_settlement/del', ['ids' => (string) $cleanup['settlement_id']], $base, $cookie);
        } catch (Throwable $e) {
        }
    }
    if ($cleanup['reconciliation_id'] > 0) {
        try {
            ajaxPost('business/purchase_reconciliation/del', ['ids' => (string) $cleanup['reconciliation_id']], $base, $cookie);
        } catch (Throwable $e) {
        }
    }
    if ($cleanup['payment_plan_id'] > 0) {
        try {
            ajaxPost('business/payment_plan/del', ['ids' => (string) $cleanup['payment_plan_id']], $base, $cookie);
        } catch (Throwable $e) {
        }
    }
    if ($cleanup['purchase_approval_id'] > 0) {
        try {
            ajaxPost('business/approval/del', ['ids' => (string) $cleanup['purchase_approval_id']], $base, $cookie);
        } catch (Throwable $e) {
        }
    }
    if ($cleanup['purchase_order_id'] > 0) {
        try {
            ajaxPost('business/purchase_order/del', ['ids' => (string) $cleanup['purchase_order_id']], $base, $cookie);
        } catch (Throwable $e) {
        }
    }
    if ($cleanup['supplier_id'] > 0) {
        try {
            ajaxPost('business/supplier/del', ['ids' => (string) $cleanup['supplier_id']], $base, $cookie);
        } catch (Throwable $e) {
        }
    }
    @unlink($cookie);
}
