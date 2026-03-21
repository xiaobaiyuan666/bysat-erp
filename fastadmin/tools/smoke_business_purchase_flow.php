<?php

declare(strict_types=1);

$base = 'http://127.0.0.1:8092/MWDObBuRlr.php';
$cookie = __DIR__ . '/../tmp-fastadmin-cookie-purchase-smoke.txt';
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
$supplierName = 'smoke-po-supplier-' . $stamp;
$purchaseTitle = 'smoke-purchase-' . $stamp;
$cleanup = [
    'supplier_id' => 0,
    'purchase_order_id' => 0,
    'approval_id' => 0,
    'payment_plan_id' => 0,
];

try {
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
        'row[tax_no]' => 'SMOKE-TAX-PO-001',
        'row[owner_admin_id]' => (string) $ownerId,
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
    $cleanup['supplier_id'] = (int) ($supplierRows['rows'][0]['id'] ?? 0);
    if ($cleanup['supplier_id'] <= 0) {
        throw new RuntimeException('supplier lookup failed');
    }

    $purchaseAdd = ajaxPost('business/purchase_order/add', [
        'row[title]' => $purchaseTitle,
        'row[supplier_id]' => (string) $cleanup['supplier_id'],
        'row[contract_id]' => (string) $contractId,
        'row[customer_id]' => (string) $customerId,
        'row[purchase_type]' => 'service',
        'row[order_amount]' => '456.78',
        'row[ordered_at]' => date('Y-m-d H:i:s'),
        'row[expected_delivery_date]' => date('Y-m-d', strtotime('+5 day')),
        'row[owner_admin_id]' => (string) $ownerId,
        'row[purchase_content]' => 'smoke purchase order',
        'row[notes]' => 'smoke-test',
    ], $base, $cookie);
    if (($purchaseAdd['code'] ?? 0) != 1) {
        throw new RuntimeException('purchase order add failed: ' . json_encode($purchaseAdd, JSON_UNESCAPED_UNICODE));
    }

    $purchaseRows = ajaxGet('business/purchase_order/index', [
        'offset' => 0,
        'limit' => 10,
        'sort' => 'id',
        'order' => 'desc',
        'filter' => json_encode(['title' => $purchaseTitle], JSON_UNESCAPED_UNICODE),
        'op' => json_encode(['title' => '='], JSON_UNESCAPED_UNICODE),
    ], $base, $cookie);
    $purchaseOrder = $purchaseRows['rows'][0] ?? null;
    if (!$purchaseOrder) {
        throw new RuntimeException('purchase order lookup failed');
    }
    $cleanup['purchase_order_id'] = (int) $purchaseOrder['id'];

    $approvalAdd = ajaxPost('business/approval/add', [
        'row[object_type]' => 'purchase_order',
        'row[purchase_order_id]' => (string) $cleanup['purchase_order_id'],
        'row[approver_admin_id]' => (string) $ownerId,
        'row[submit_reason]' => 'smoke approval request',
    ], $base, $cookie);
    if (($approvalAdd['code'] ?? 0) != 1) {
        throw new RuntimeException('approval add failed: ' . json_encode($approvalAdd, JSON_UNESCAPED_UNICODE));
    }

    $approvalRows = ajaxGet('business/approval/index', [
        'offset' => 0,
        'limit' => 10,
        'sort' => 'id',
        'order' => 'desc',
        'filter' => json_encode(['object_type' => 'purchase_order', 'object_id' => $cleanup['purchase_order_id']], JSON_UNESCAPED_UNICODE),
        'op' => json_encode(['object_type' => '=', 'object_id' => '='], JSON_UNESCAPED_UNICODE),
    ], $base, $cookie);
    $approval = $approvalRows['rows'][0] ?? null;
    if (!$approval) {
        throw new RuntimeException('approval lookup failed');
    }
    $cleanup['approval_id'] = (int) $approval['id'];

    $approvalPass = ajaxPost('business/approval/approve/ids/' . $cleanup['approval_id'], [
        'note' => 'smoke pass',
    ], $base, $cookie);
    if (($approvalPass['code'] ?? 0) != 1) {
        throw new RuntimeException('approval pass failed: ' . json_encode($approvalPass, JSON_UNESCAPED_UNICODE));
    }

    $purchaseRows = ajaxGet('business/purchase_order/index', [
        'offset' => 0,
        'limit' => 10,
        'sort' => 'id',
        'order' => 'desc',
        'filter' => json_encode(['id' => $cleanup['purchase_order_id']], JSON_UNESCAPED_UNICODE),
        'op' => json_encode(['id' => '='], JSON_UNESCAPED_UNICODE),
    ], $base, $cookie);
    $purchaseOrder = $purchaseRows['rows'][0] ?? null;
    if (!$purchaseOrder || ($purchaseOrder['approval_status'] ?? '') !== 'approved' || ($purchaseOrder['status'] ?? '') !== 'approved') {
        throw new RuntimeException('purchase order approve sync failed: ' . json_encode($purchaseOrder, JSON_UNESCAPED_UNICODE));
    }

    $planCreate = ajaxPost('business/purchase_order/createpaymentplan/ids/' . $cleanup['purchase_order_id'], [], $base, $cookie);
    if (($planCreate['code'] ?? 0) != 1) {
        throw new RuntimeException('payment plan create failed: ' . json_encode($planCreate, JSON_UNESCAPED_UNICODE));
    }

    $purchaseRows = ajaxGet('business/purchase_order/index', [
        'offset' => 0,
        'limit' => 10,
        'sort' => 'id',
        'order' => 'desc',
        'filter' => json_encode(['id' => $cleanup['purchase_order_id']], JSON_UNESCAPED_UNICODE),
        'op' => json_encode(['id' => '='], JSON_UNESCAPED_UNICODE),
    ], $base, $cookie);
    $purchaseOrder = $purchaseRows['rows'][0] ?? null;
    $cleanup['payment_plan_id'] = (int) ($purchaseOrder['payment_plan_id'] ?? 0);
    if (!$purchaseOrder || $cleanup['payment_plan_id'] <= 0 || ($purchaseOrder['status'] ?? '') !== 'processing') {
        throw new RuntimeException('payment plan link failed: ' . json_encode($purchaseOrder, JSON_UNESCAPED_UNICODE));
    }

    $paymentDel = ajaxPost('business/payment_plan/del', ['ids' => (string) $cleanup['payment_plan_id']], $base, $cookie);
    if (($paymentDel['code'] ?? 0) != 1) {
        throw new RuntimeException('payment plan delete failed: ' . json_encode($paymentDel, JSON_UNESCAPED_UNICODE));
    }
    $cleanup['payment_plan_id'] = 0;

    $purchaseRows = ajaxGet('business/purchase_order/index', [
        'offset' => 0,
        'limit' => 10,
        'sort' => 'id',
        'order' => 'desc',
        'filter' => json_encode(['id' => $cleanup['purchase_order_id']], JSON_UNESCAPED_UNICODE),
        'op' => json_encode(['id' => '='], JSON_UNESCAPED_UNICODE),
    ], $base, $cookie);
    $purchaseOrder = $purchaseRows['rows'][0] ?? null;
    if (!$purchaseOrder || (int) ($purchaseOrder['payment_plan_id'] ?? 0) !== 0 || ($purchaseOrder['status'] ?? '') !== 'approved') {
        throw new RuntimeException('purchase rollback after payment delete failed: ' . json_encode($purchaseOrder, JSON_UNESCAPED_UNICODE));
    }

    $approvalDel = ajaxPost('business/approval/del', ['ids' => (string) $cleanup['approval_id']], $base, $cookie);
    if (($approvalDel['code'] ?? 0) != 1) {
        throw new RuntimeException('approval delete failed: ' . json_encode($approvalDel, JSON_UNESCAPED_UNICODE));
    }
    $cleanup['approval_id'] = 0;

    $purchaseDel = ajaxPost('business/purchase_order/del', ['ids' => (string) $cleanup['purchase_order_id']], $base, $cookie);
    if (($purchaseDel['code'] ?? 0) != 1) {
        throw new RuntimeException('purchase delete failed: ' . json_encode($purchaseDel, JSON_UNESCAPED_UNICODE));
    }
    $cleanup['purchase_order_id'] = 0;

    $supplierDel = ajaxPost('business/supplier/del', ['ids' => (string) $cleanup['supplier_id']], $base, $cookie);
    if (($supplierDel['code'] ?? 0) != 1) {
        throw new RuntimeException('supplier delete failed: ' . json_encode($supplierDel, JSON_UNESCAPED_UNICODE));
    }
    $cleanup['supplier_id'] = 0;

    echo json_encode([
        'supplier_added' => true,
        'purchase_order_added' => true,
        'approval_added' => true,
        'approval_passed' => true,
        'payment_plan_created' => true,
        'payment_plan_deleted' => true,
        'purchase_order_rolled_back_to_approved' => true,
        'cleanup_complete' => true,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
} finally {
    if ($cleanup['payment_plan_id'] > 0) {
        try {
            ajaxPost('business/payment_plan/del', ['ids' => (string) $cleanup['payment_plan_id']], $base, $cookie);
        } catch (Throwable $e) {
        }
    }
    if ($cleanup['approval_id'] > 0) {
        try {
            ajaxPost('business/approval/del', ['ids' => (string) $cleanup['approval_id']], $base, $cookie);
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
}

@unlink($cookie);
