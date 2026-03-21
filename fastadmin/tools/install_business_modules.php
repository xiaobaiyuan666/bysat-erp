<?php

declare(strict_types=1);

$rootPath = dirname(__DIR__);
$env = parseSimpleEnv($rootPath . DIRECTORY_SEPARATOR . '.env');
$host = $env['database.hostname'] ?? '127.0.0.1';
$port = $env['database.hostport'] ?? '3306';
$database = $env['database.database'] ?? 'fastadmin';
$username = $env['database.username'] ?? 'root';
$password = $env['database.password'] ?? '';
$prefix = $env['database.prefix'] ?? 'fa_';

$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database),
    $username,
    $password,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

foreach (buildSqlTables($prefix) as $sql) {
    $pdo->exec($sql);
}

ensureBusinessApprovalColumns($pdo, $prefix);
ensureBusinessPurchaseSettlementColumns($pdo, $prefix);
ensureBusinessPurchaseTraceColumns($pdo, $prefix);
ensureBusinessPaymentRequestColumns($pdo, $prefix);
seedBusinessData($pdo, $prefix);

$counts = [
    'customer' => countTable($pdo, $prefix . 'business_customer'),
    'contract' => countTable($pdo, $prefix . 'business_contract'),
    'supplier' => countTable($pdo, $prefix . 'business_supplier'),
    'receivable_plan' => countTable($pdo, $prefix . 'business_receivable_plan'),
    'customer_followup' => countTable($pdo, $prefix . 'business_customer_followup'),
    'expense_request' => countTable($pdo, $prefix . 'business_expense_request'),
    'purchase_order' => countTable($pdo, $prefix . 'business_purchase_order'),
    'purchase_reconciliation' => countTable($pdo, $prefix . 'business_purchase_reconciliation'),
    'purchase_settlement' => countTable($pdo, $prefix . 'business_purchase_settlement'),
    'purchase_invoice' => countTable($pdo, $prefix . 'business_purchase_invoice'),
    'payment_request' => countTable($pdo, $prefix . 'business_payment_request'),
    'payment_plan' => countTable($pdo, $prefix . 'business_payment_plan'),
    'approval' => countTable($pdo, $prefix . 'business_approval'),
    'approval_template' => countTable($pdo, $prefix . 'business_approval_template'),
    'approval_template_step' => countTable($pdo, $prefix . 'business_approval_template_step'),
];

echo 'Business modules installed: ' . json_encode($counts, JSON_UNESCAPED_UNICODE) . PHP_EOL;

function parseSimpleEnv(string $path): array
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

function buildSqlTables(string $prefix): array
{
    return [
        "CREATE TABLE IF NOT EXISTS `{$prefix}business_customer` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `legacy_id` varchar(64) NOT NULL DEFAULT '',
            `company_name` varchar(150) NOT NULL DEFAULT '',
            `short_name` varchar(100) NOT NULL DEFAULT '',
            `industry` varchar(100) NOT NULL DEFAULT '',
            `customer_level` enum('a','b','c','d') NOT NULL DEFAULT 'b',
            `source` enum('direct','referral','channel','existing','other') NOT NULL DEFAULT 'direct',
            `stage` enum('lead','proposal','contracted','delivery','repeat','lost') NOT NULL DEFAULT 'lead',
            `status` enum('active','paused','lost') NOT NULL DEFAULT 'active',
            `owner` varchar(50) NOT NULL DEFAULT '',
            `owner_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `contact_name` varchar(50) NOT NULL DEFAULT '',
            `contact_phone` varchar(20) NOT NULL DEFAULT '',
            `contact_email` varchar(100) NOT NULL DEFAULT '',
            `city` varchar(50) NOT NULL DEFAULT '',
            `last_follow_up_at` datetime DEFAULT NULL,
            `notes` text,
            `record_created_at` datetime DEFAULT NULL,
            `record_updated_at` datetime DEFAULT NULL,
            `created_by_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `created_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `created_by_name` varchar(50) NOT NULL DEFAULT '',
            `updated_by_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `updated_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `updated_by_name` varchar(50) NOT NULL DEFAULT '',
            `createtime` bigint(16) DEFAULT NULL,
            `updatetime` bigint(16) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_business_customer_legacy_id` (`legacy_id`),
            KEY `idx_business_customer_owner_admin_id` (`owner_admin_id`),
            KEY `idx_business_customer_status` (`status`),
            KEY `idx_business_customer_company_name` (`company_name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='客户档案'",

        "CREATE TABLE IF NOT EXISTS `{$prefix}business_contract` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `legacy_id` varchar(64) NOT NULL DEFAULT '',
            `customer_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `customer_id` int(10) unsigned NOT NULL DEFAULT '0',
            `customer_name` varchar(150) NOT NULL DEFAULT '',
            `contract_no` varchar(50) NOT NULL DEFAULT '',
            `name` varchar(150) NOT NULL DEFAULT '',
            `category` enum('implementation','subscription','maintenance','custom','service','other') NOT NULL DEFAULT 'service',
            `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
            `signed_at` date DEFAULT NULL,
            `start_date` date DEFAULT NULL,
            `end_date` date DEFAULT NULL,
            `status` enum('draft','review','active','delivering','completed','cancelled','expired') NOT NULL DEFAULT 'draft',
            `approval_status` enum('none','pending','approved','rejected','cancelled') NOT NULL DEFAULT 'none',
            `approval_updated_at` datetime DEFAULT NULL,
            `owner` varchar(50) NOT NULL DEFAULT '',
            `owner_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `project_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `project_id` int(10) unsigned NOT NULL DEFAULT '0',
            `app_project_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `app_project_id` int(10) unsigned NOT NULL DEFAULT '0',
            `invoice_total` decimal(12,2) NOT NULL DEFAULT '0.00',
            `received_total` decimal(12,2) NOT NULL DEFAULT '0.00',
            `pending_total` decimal(12,2) NOT NULL DEFAULT '0.00',
            `attachment_ids_json` text,
            `notes` text,
            `record_created_at` datetime DEFAULT NULL,
            `record_updated_at` datetime DEFAULT NULL,
            `created_by_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `created_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `created_by_name` varchar(50) NOT NULL DEFAULT '',
            `updated_by_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `updated_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `updated_by_name` varchar(50) NOT NULL DEFAULT '',
            `createtime` bigint(16) DEFAULT NULL,
            `updatetime` bigint(16) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_business_contract_legacy_id` (`legacy_id`),
            UNIQUE KEY `uk_business_contract_contract_no` (`contract_no`),
            KEY `idx_business_contract_customer_id` (`customer_id`),
            KEY `idx_business_contract_owner_admin_id` (`owner_admin_id`),
            KEY `idx_business_contract_project_id` (`project_id`),
            KEY `idx_business_contract_app_project_id` (`app_project_id`),
            KEY `idx_business_contract_status` (`status`),
            KEY `idx_business_contract_approval_status` (`approval_status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='合同台账'",

        "CREATE TABLE IF NOT EXISTS `{$prefix}business_receivable_plan` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `legacy_id` varchar(64) NOT NULL DEFAULT '',
            `contract_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `contract_id` int(10) unsigned NOT NULL DEFAULT '0',
            `contract_name` varchar(150) NOT NULL DEFAULT '',
            `customer_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `customer_id` int(10) unsigned NOT NULL DEFAULT '0',
            `customer_name` varchar(150) NOT NULL DEFAULT '',
            `title` varchar(150) NOT NULL DEFAULT '',
            `due_date` date DEFAULT NULL,
            `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
            `status` enum('pending','processing','received','overdue','cancelled') NOT NULL DEFAULT 'pending',
            `owner` varchar(50) NOT NULL DEFAULT '',
            `owner_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `actual_received_at` datetime DEFAULT NULL,
            `notes` text,
            `record_created_at` datetime DEFAULT NULL,
            `record_updated_at` datetime DEFAULT NULL,
            `created_by_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `created_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `created_by_name` varchar(50) NOT NULL DEFAULT '',
            `updated_by_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `updated_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `updated_by_name` varchar(50) NOT NULL DEFAULT '',
            `createtime` bigint(16) DEFAULT NULL,
            `updatetime` bigint(16) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_business_receivable_plan_legacy_id` (`legacy_id`),
            KEY `idx_business_receivable_plan_contract_id` (`contract_id`),
            KEY `idx_business_receivable_plan_customer_id` (`customer_id`),
            KEY `idx_business_receivable_plan_owner_admin_id` (`owner_admin_id`),
            KEY `idx_business_receivable_plan_due_date` (`due_date`),
            KEY `idx_business_receivable_plan_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='回款计划'",

        "CREATE TABLE IF NOT EXISTS `{$prefix}business_customer_followup` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `legacy_id` varchar(64) NOT NULL DEFAULT '',
            `customer_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `customer_id` int(10) unsigned NOT NULL DEFAULT '0',
            `customer_name` varchar(150) NOT NULL DEFAULT '',
            `contract_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `contract_id` int(10) unsigned NOT NULL DEFAULT '0',
            `contract_name` varchar(150) NOT NULL DEFAULT '',
            `title` varchar(150) NOT NULL DEFAULT '',
            `followup_type` enum('call','wechat','meeting','visit','proposal','payment','service','other') NOT NULL DEFAULT 'meeting',
            `follow_up_at` datetime DEFAULT NULL,
            `next_follow_up_at` datetime DEFAULT NULL,
            `status` enum('planned','done','waiting','closed') NOT NULL DEFAULT 'done',
            `owner` varchar(50) NOT NULL DEFAULT '',
            `owner_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `contact_name` varchar(50) NOT NULL DEFAULT '',
            `result_summary` text,
            `notes` text,
            `record_created_at` datetime DEFAULT NULL,
            `record_updated_at` datetime DEFAULT NULL,
            `created_by_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `created_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `created_by_name` varchar(50) NOT NULL DEFAULT '',
            `updated_by_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `updated_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `updated_by_name` varchar(50) NOT NULL DEFAULT '',
            `createtime` bigint(16) DEFAULT NULL,
            `updatetime` bigint(16) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_business_customer_followup_legacy_id` (`legacy_id`),
            KEY `idx_business_customer_followup_customer_id` (`customer_id`),
            KEY `idx_business_customer_followup_contract_id` (`contract_id`),
            KEY `idx_business_customer_followup_owner_admin_id` (`owner_admin_id`),
            KEY `idx_business_customer_followup_follow_up_at` (`follow_up_at`),
            KEY `idx_business_customer_followup_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='客户跟进记录'",

        "CREATE TABLE IF NOT EXISTS `{$prefix}business_purchase_order` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `legacy_id` varchar(64) NOT NULL DEFAULT '',
            `order_no` varchar(50) NOT NULL DEFAULT '',
            `title` varchar(150) NOT NULL DEFAULT '',
            `purchase_type` enum('software','cloud','service','outsourcing','marketing','hardware','office','other') NOT NULL DEFAULT 'service',
            `supplier_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `supplier_id` int(10) unsigned NOT NULL DEFAULT '0',
            `supplier_name` varchar(150) NOT NULL DEFAULT '',
            `customer_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `customer_id` int(10) unsigned NOT NULL DEFAULT '0',
            `customer_name` varchar(150) NOT NULL DEFAULT '',
            `contract_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `contract_id` int(10) unsigned NOT NULL DEFAULT '0',
            `contract_name` varchar(150) NOT NULL DEFAULT '',
            `order_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
            `ordered_at` datetime DEFAULT NULL,
            `expected_delivery_date` date DEFAULT NULL,
            `actual_delivery_at` datetime DEFAULT NULL,
            `status` enum('draft','pending_approval','approved','processing','completed','rejected','cancelled') NOT NULL DEFAULT 'draft',
            `approval_status` enum('none','pending','approved','rejected','cancelled') NOT NULL DEFAULT 'none',
            `approval_updated_at` datetime DEFAULT NULL,
            `owner` varchar(50) NOT NULL DEFAULT '',
            `owner_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `payment_plan_id` int(10) unsigned NOT NULL DEFAULT '0',
            `payment_plan_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `payment_plan_title` varchar(150) NOT NULL DEFAULT '',
            `reconciliation_id` int(10) unsigned NOT NULL DEFAULT '0',
            `reconciliation_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `reconciliation_title` varchar(150) NOT NULL DEFAULT '',
            `settlement_id` int(10) unsigned NOT NULL DEFAULT '0',
            `settlement_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `settlement_title` varchar(150) NOT NULL DEFAULT '',
            `attachment_ids_json` text,
            `purchase_content` text,
            `notes` text,
            `record_created_at` datetime DEFAULT NULL,
            `record_updated_at` datetime DEFAULT NULL,
            `created_by_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `created_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `created_by_name` varchar(50) NOT NULL DEFAULT '',
            `updated_by_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `updated_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `updated_by_name` varchar(50) NOT NULL DEFAULT '',
            `createtime` bigint(16) DEFAULT NULL,
            `updatetime` bigint(16) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_business_purchase_order_legacy_id` (`legacy_id`),
            UNIQUE KEY `uk_business_purchase_order_order_no` (`order_no`),
            KEY `idx_business_purchase_order_supplier_id` (`supplier_id`),
            KEY `idx_business_purchase_order_customer_id` (`customer_id`),
            KEY `idx_business_purchase_order_contract_id` (`contract_id`),
            KEY `idx_business_purchase_order_owner_admin_id` (`owner_admin_id`),
            KEY `idx_business_purchase_order_status` (`status`),
            KEY `idx_business_purchase_order_approval_status` (`approval_status`),
            KEY `idx_business_purchase_order_reconciliation_id` (`reconciliation_id`),
            KEY `idx_business_purchase_order_settlement_id` (`settlement_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='采购单'",

        "CREATE TABLE IF NOT EXISTS `{$prefix}business_purchase_reconciliation` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `legacy_id` varchar(64) NOT NULL DEFAULT '',
            `reconcile_no` varchar(50) NOT NULL DEFAULT '',
            `title` varchar(150) NOT NULL DEFAULT '',
            `purchase_order_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `purchase_order_id` int(10) unsigned NOT NULL DEFAULT '0',
            `purchase_order_title` varchar(150) NOT NULL DEFAULT '',
            `payment_plan_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `payment_plan_id` int(10) unsigned NOT NULL DEFAULT '0',
            `payment_plan_title` varchar(150) NOT NULL DEFAULT '',
            `supplier_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `supplier_id` int(10) unsigned NOT NULL DEFAULT '0',
            `supplier_name` varchar(150) NOT NULL DEFAULT '',
            `customer_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `customer_id` int(10) unsigned NOT NULL DEFAULT '0',
            `customer_name` varchar(150) NOT NULL DEFAULT '',
            `contract_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `contract_id` int(10) unsigned NOT NULL DEFAULT '0',
            `contract_name` varchar(150) NOT NULL DEFAULT '',
            `order_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
            `confirmed_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
            `variance_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
            `reconciled_at` datetime DEFAULT NULL,
            `status` enum('draft','reconciling','confirmed','disputed','closed') NOT NULL DEFAULT 'draft',
            `owner` varchar(50) NOT NULL DEFAULT '',
            `owner_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `attachment_ids_json` text,
            `notes` text,
            `record_created_at` datetime DEFAULT NULL,
            `record_updated_at` datetime DEFAULT NULL,
            `created_by_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `created_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `created_by_name` varchar(50) NOT NULL DEFAULT '',
            `updated_by_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `updated_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `updated_by_name` varchar(50) NOT NULL DEFAULT '',
            `createtime` bigint(16) DEFAULT NULL,
            `updatetime` bigint(16) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_business_purchase_reconciliation_legacy_id` (`legacy_id`),
            UNIQUE KEY `uk_business_purchase_reconciliation_reconcile_no` (`reconcile_no`),
            KEY `idx_business_purchase_reconciliation_purchase_order_id` (`purchase_order_id`),
            KEY `idx_business_purchase_reconciliation_payment_plan_id` (`payment_plan_id`),
            KEY `idx_business_purchase_reconciliation_supplier_id` (`supplier_id`),
            KEY `idx_business_purchase_reconciliation_status` (`status`),
            KEY `idx_business_purchase_reconciliation_owner_admin_id` (`owner_admin_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='采购对账'",

        "CREATE TABLE IF NOT EXISTS `{$prefix}business_payment_plan` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `legacy_id` varchar(64) NOT NULL DEFAULT '',
            `contract_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `contract_id` int(10) unsigned NOT NULL DEFAULT '0',
            `contract_name` varchar(150) NOT NULL DEFAULT '',
            `customer_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `customer_id` int(10) unsigned NOT NULL DEFAULT '0',
            `customer_name` varchar(150) NOT NULL DEFAULT '',
            `purchase_order_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `purchase_order_id` int(10) unsigned NOT NULL DEFAULT '0',
            `purchase_order_title` varchar(150) NOT NULL DEFAULT '',
            `title` varchar(150) NOT NULL DEFAULT '',
            `payee_name` varchar(150) NOT NULL DEFAULT '',
            `plan_type` enum('supplier','implementation','commission','service','refund','other') NOT NULL DEFAULT 'supplier',
            `due_date` date DEFAULT NULL,
            `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
            `status` enum('pending','processing','paid','overdue','cancelled') NOT NULL DEFAULT 'pending',
            `approval_status` enum('none','pending','approved','rejected','cancelled') NOT NULL DEFAULT 'none',
            `approval_updated_at` datetime DEFAULT NULL,
            `owner` varchar(50) NOT NULL DEFAULT '',
            `owner_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `actual_paid_at` datetime DEFAULT NULL,
            `notes` text,
            `record_created_at` datetime DEFAULT NULL,
            `record_updated_at` datetime DEFAULT NULL,
            `created_by_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `created_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `created_by_name` varchar(50) NOT NULL DEFAULT '',
            `updated_by_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `updated_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `updated_by_name` varchar(50) NOT NULL DEFAULT '',
            `createtime` bigint(16) DEFAULT NULL,
            `updatetime` bigint(16) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_business_payment_plan_legacy_id` (`legacy_id`),
            KEY `idx_business_payment_plan_contract_id` (`contract_id`),
            KEY `idx_business_payment_plan_customer_id` (`customer_id`),
            KEY `idx_business_payment_plan_purchase_order_id` (`purchase_order_id`),
            KEY `idx_business_payment_plan_owner_admin_id` (`owner_admin_id`),
            KEY `idx_business_payment_plan_due_date` (`due_date`),
            KEY `idx_business_payment_plan_status` (`status`),
            KEY `idx_business_payment_plan_approval_status` (`approval_status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='付款计划'",
        "CREATE TABLE IF NOT EXISTS `{$prefix}business_approval_template` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `legacy_id` varchar(64) NOT NULL DEFAULT '',
            `name` varchar(150) NOT NULL DEFAULT '',
            `object_type` enum('contract','payment_plan','expense_request','purchase_order','payment_request') NOT NULL DEFAULT 'contract',
            `status` enum('active','inactive') NOT NULL DEFAULT 'active',
            `is_default` tinyint(1) unsigned NOT NULL DEFAULT '0',
            `min_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
            `max_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
            `step_count` int(10) unsigned NOT NULL DEFAULT '0',
            `description` text,
            `record_created_at` datetime DEFAULT NULL,
            `record_updated_at` datetime DEFAULT NULL,
            `created_by_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `created_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `created_by_name` varchar(50) NOT NULL DEFAULT '',
            `updated_by_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `updated_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `updated_by_name` varchar(50) NOT NULL DEFAULT '',
            `createtime` bigint(16) DEFAULT NULL,
            `updatetime` bigint(16) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_business_approval_template_legacy_id` (`legacy_id`),
            KEY `idx_business_approval_template_object_type` (`object_type`),
            KEY `idx_business_approval_template_status` (`status`),
            KEY `idx_business_approval_template_is_default` (`is_default`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='审批模板'",
        "CREATE TABLE IF NOT EXISTS `{$prefix}business_approval_template_step` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `legacy_id` varchar(64) NOT NULL DEFAULT '',
            `template_id` int(10) unsigned NOT NULL DEFAULT '0',
            `template_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `template_name` varchar(150) NOT NULL DEFAULT '',
            `object_type` enum('contract','payment_plan','expense_request','purchase_order') NOT NULL DEFAULT 'contract',
            `step_no` int(10) unsigned NOT NULL DEFAULT '1',
            `step_name` varchar(100) NOT NULL DEFAULT '',
            `approver_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `approver_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `approver_name` varchar(50) NOT NULL DEFAULT '',
            `status` enum('active','inactive') NOT NULL DEFAULT 'active',
            `notes` text,
            `record_created_at` datetime DEFAULT NULL,
            `record_updated_at` datetime DEFAULT NULL,
            `created_by_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `created_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `created_by_name` varchar(50) NOT NULL DEFAULT '',
            `updated_by_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `updated_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `updated_by_name` varchar(50) NOT NULL DEFAULT '',
            `createtime` bigint(16) DEFAULT NULL,
            `updatetime` bigint(16) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_business_approval_template_step_legacy_id` (`legacy_id`),
            UNIQUE KEY `uk_business_approval_template_step_order` (`template_id`,`step_no`),
            KEY `idx_business_approval_template_step_template_id` (`template_id`),
            KEY `idx_business_approval_template_step_status` (`status`),
            KEY `idx_business_approval_template_step_approver_admin_id` (`approver_admin_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='审批模板节点'",
        "CREATE TABLE IF NOT EXISTS `{$prefix}business_purchase_settlement` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `legacy_id` varchar(64) NOT NULL DEFAULT '',
            `settlement_no` varchar(50) NOT NULL DEFAULT '',
            `title` varchar(150) NOT NULL DEFAULT '',
            `purchase_order_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `purchase_order_id` int(10) unsigned NOT NULL DEFAULT '0',
            `purchase_order_title` varchar(150) NOT NULL DEFAULT '',
            `payment_plan_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `payment_plan_id` int(10) unsigned NOT NULL DEFAULT '0',
            `payment_plan_title` varchar(150) NOT NULL DEFAULT '',
            `supplier_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `supplier_id` int(10) unsigned NOT NULL DEFAULT '0',
            `supplier_name` varchar(150) NOT NULL DEFAULT '',
            `customer_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `customer_id` int(10) unsigned NOT NULL DEFAULT '0',
            `customer_name` varchar(150) NOT NULL DEFAULT '',
            `contract_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `contract_id` int(10) unsigned NOT NULL DEFAULT '0',
            `contract_name` varchar(150) NOT NULL DEFAULT '',
            `settlement_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
            `paid_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
            `invoiced_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
            `balance_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
            `invoice_status` enum('none','partial','received') NOT NULL DEFAULT 'none',
            `invoice_no` varchar(100) NOT NULL DEFAULT '',
            `invoiced_at` date DEFAULT NULL,
            `status` enum('draft','reconciling','confirmed','settled','cancelled') NOT NULL DEFAULT 'draft',
            `owner` varchar(50) NOT NULL DEFAULT '',
            `owner_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `settled_at` datetime DEFAULT NULL,
            `attachment_ids_json` text,
            `notes` text,
            `record_created_at` datetime DEFAULT NULL,
            `record_updated_at` datetime DEFAULT NULL,
            `created_by_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `created_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `created_by_name` varchar(50) NOT NULL DEFAULT '',
            `updated_by_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `updated_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `updated_by_name` varchar(50) NOT NULL DEFAULT '',
            `createtime` bigint(16) DEFAULT NULL,
            `updatetime` bigint(16) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_business_purchase_settlement_legacy_id` (`legacy_id`),
            UNIQUE KEY `uk_business_purchase_settlement_settlement_no` (`settlement_no`),
            KEY `idx_business_purchase_settlement_purchase_order_id` (`purchase_order_id`),
            KEY `idx_business_purchase_settlement_payment_plan_id` (`payment_plan_id`),
            KEY `idx_business_purchase_settlement_supplier_id` (`supplier_id`),
            KEY `idx_business_purchase_settlement_status` (`status`),
            KEY `idx_business_purchase_settlement_invoice_status` (`invoice_status`),
            KEY `idx_business_purchase_settlement_owner_admin_id` (`owner_admin_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='采购结算'",

        "CREATE TABLE IF NOT EXISTS `{$prefix}business_purchase_invoice` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `legacy_id` varchar(64) NOT NULL DEFAULT '',
            `invoice_no` varchar(100) NOT NULL DEFAULT '',
            `title` varchar(150) NOT NULL DEFAULT '',
            `purchase_order_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `purchase_order_id` int(10) unsigned NOT NULL DEFAULT '0',
            `purchase_order_title` varchar(150) NOT NULL DEFAULT '',
            `settlement_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `settlement_id` int(10) unsigned NOT NULL DEFAULT '0',
            `settlement_title` varchar(150) NOT NULL DEFAULT '',
            `supplier_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `supplier_id` int(10) unsigned NOT NULL DEFAULT '0',
            `supplier_name` varchar(150) NOT NULL DEFAULT '',
            `customer_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `customer_id` int(10) unsigned NOT NULL DEFAULT '0',
            `customer_name` varchar(150) NOT NULL DEFAULT '',
            `contract_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `contract_id` int(10) unsigned NOT NULL DEFAULT '0',
            `contract_name` varchar(150) NOT NULL DEFAULT '',
            `invoice_type` enum('vat_special','vat_normal','service','electronic','other') NOT NULL DEFAULT 'vat_normal',
            `invoice_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
            `untaxed_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
            `tax_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
            `invoiced_at` date DEFAULT NULL,
            `received_at` datetime DEFAULT NULL,
            `status` enum('pending','received','verified','returned','cancelled') NOT NULL DEFAULT 'pending',
            `owner` varchar(50) NOT NULL DEFAULT '',
            `owner_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `attachment_ids_json` text,
            `notes` text,
            `record_created_at` datetime DEFAULT NULL,
            `record_updated_at` datetime DEFAULT NULL,
            `created_by_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `created_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `created_by_name` varchar(50) NOT NULL DEFAULT '',
            `updated_by_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `updated_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `updated_by_name` varchar(50) NOT NULL DEFAULT '',
            `createtime` bigint(16) DEFAULT NULL,
            `updatetime` bigint(16) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_business_purchase_invoice_legacy_id` (`legacy_id`),
            KEY `idx_business_purchase_invoice_invoice_no` (`invoice_no`),
            KEY `idx_business_purchase_invoice_purchase_order_id` (`purchase_order_id`),
            KEY `idx_business_purchase_invoice_settlement_id` (`settlement_id`),
            KEY `idx_business_purchase_invoice_supplier_id` (`supplier_id`),
            KEY `idx_business_purchase_invoice_status` (`status`),
            KEY `idx_business_purchase_invoice_owner_admin_id` (`owner_admin_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='采购发票'",
        "CREATE TABLE IF NOT EXISTS `{$prefix}business_supplier` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `legacy_id` varchar(64) NOT NULL DEFAULT '',
            `supplier_name` varchar(150) NOT NULL DEFAULT '',
            `short_name` varchar(100) NOT NULL DEFAULT '',
            `category` enum('software','cloud','service','marketing','outsourcing','hardware','other') NOT NULL DEFAULT 'service',
            `level` enum('strategic','core','normal','backup') NOT NULL DEFAULT 'normal',
            `status` enum('active','paused','blacklist') NOT NULL DEFAULT 'active',
            `settlement_cycle` enum('advance','monthly','quarterly','on_delivery','other') NOT NULL DEFAULT 'monthly',
            `owner` varchar(50) NOT NULL DEFAULT '',
            `owner_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `contact_name` varchar(50) NOT NULL DEFAULT '',
            `contact_phone` varchar(20) NOT NULL DEFAULT '',
            `contact_email` varchar(100) NOT NULL DEFAULT '',
            `city` varchar(50) NOT NULL DEFAULT '',
            `bank_name` varchar(100) NOT NULL DEFAULT '',
            `bank_account` varchar(100) NOT NULL DEFAULT '',
            `tax_no` varchar(100) NOT NULL DEFAULT '',
            `notes` text,
            `record_created_at` datetime DEFAULT NULL,
            `record_updated_at` datetime DEFAULT NULL,
            `created_by_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `created_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `created_by_name` varchar(50) NOT NULL DEFAULT '',
            `updated_by_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `updated_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `updated_by_name` varchar(50) NOT NULL DEFAULT '',
            `createtime` bigint(16) DEFAULT NULL,
            `updatetime` bigint(16) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_business_supplier_legacy_id` (`legacy_id`),
            KEY `idx_business_supplier_status` (`status`),
            KEY `idx_business_supplier_owner_admin_id` (`owner_admin_id`),
            KEY `idx_business_supplier_supplier_name` (`supplier_name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='供应商档案'",

        "CREATE TABLE IF NOT EXISTS `{$prefix}business_expense_request` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `legacy_id` varchar(64) NOT NULL DEFAULT '',
            `request_no` varchar(50) NOT NULL DEFAULT '',
            `title` varchar(150) NOT NULL DEFAULT '',
            `expense_type` enum('procurement','travel','marketing','service','software','outsourcing','office','refund','other') NOT NULL DEFAULT 'procurement',
            `supplier_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `supplier_id` int(10) unsigned NOT NULL DEFAULT '0',
            `supplier_name` varchar(150) NOT NULL DEFAULT '',
            `customer_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `customer_id` int(10) unsigned NOT NULL DEFAULT '0',
            `customer_name` varchar(150) NOT NULL DEFAULT '',
            `contract_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `contract_id` int(10) unsigned NOT NULL DEFAULT '0',
            `contract_name` varchar(150) NOT NULL DEFAULT '',
            `request_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
            `requested_at` datetime DEFAULT NULL,
            `expected_pay_date` date DEFAULT NULL,
            `status` enum('draft','pending_approval','approved','processing','paid','rejected','cancelled') NOT NULL DEFAULT 'draft',
            `approval_status` enum('none','pending','approved','rejected','cancelled') NOT NULL DEFAULT 'none',
            `approval_updated_at` datetime DEFAULT NULL,
            `owner` varchar(50) NOT NULL DEFAULT '',
            `owner_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `payment_plan_id` int(10) unsigned NOT NULL DEFAULT '0',
            `payment_plan_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `payment_plan_title` varchar(150) NOT NULL DEFAULT '',
            `attachment_ids_json` text,
            `reason` text,
            `notes` text,
            `record_created_at` datetime DEFAULT NULL,
            `record_updated_at` datetime DEFAULT NULL,
            `created_by_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `created_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `created_by_name` varchar(50) NOT NULL DEFAULT '',
            `updated_by_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `updated_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `updated_by_name` varchar(50) NOT NULL DEFAULT '',
            `createtime` bigint(16) DEFAULT NULL,
            `updatetime` bigint(16) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_business_expense_request_legacy_id` (`legacy_id`),
            UNIQUE KEY `uk_business_expense_request_request_no` (`request_no`),
            KEY `idx_business_expense_request_supplier_id` (`supplier_id`),
            KEY `idx_business_expense_request_customer_id` (`customer_id`),
            KEY `idx_business_expense_request_contract_id` (`contract_id`),
            KEY `idx_business_expense_request_owner_admin_id` (`owner_admin_id`),
            KEY `idx_business_expense_request_status` (`status`),
            KEY `idx_business_expense_request_approval_status` (`approval_status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='费用申请'",
    ];
}

function seedBusinessData(PDO $pdo, string $prefix): void
{
    $staffMap = fetchStaffMap($pdo, $prefix);
    $projectMap = fetchMap($pdo, $prefix . 'project', 'name');
    $appProjectMap = fetchMap($pdo, $prefix . 'app_project', 'app_name');
    $actor = $staffMap['ops.gu'] ?? $staffMap['admin'] ?? ['admin_id' => 1, 'legacy_id' => 'staff_admin', 'name' => '陈总'];
    $financeActor = $staffMap['finance.li'] ?? $actor;

    $now = time();
    $customerTable = $prefix . 'business_customer';
    $contractTable = $prefix . 'business_contract';
    $supplierTable = $prefix . 'business_supplier';
    $receivablePlanTable = $prefix . 'business_receivable_plan';
    $customerFollowupTable = $prefix . 'business_customer_followup';
    $expenseRequestTable = $prefix . 'business_expense_request';
    $purchaseOrderTable = $prefix . 'business_purchase_order';
    $purchaseReconciliationTable = $prefix . 'business_purchase_reconciliation';
    $purchaseSettlementTable = $prefix . 'business_purchase_settlement';
    $purchaseInvoiceTable = $prefix . 'business_purchase_invoice';
    $paymentPlanTable = $prefix . 'business_payment_plan';
    $customerRows = [
        [
            'legacy_id' => 'customer_1001',
            'company_name' => '星环科技',
            'short_name' => '星环',
            'industry' => '企业服务',
            'customer_level' => 'a',
            'source' => 'existing',
            'stage' => 'delivery',
            'status' => 'active',
            'owner' => $actor['name'],
            'owner_admin_id' => $actor['admin_id'],
            'contact_name' => '林涛',
            'contact_phone' => '13800001111',
            'contact_email' => 'lin@xinghuan.example',
            'city' => '上海',
            'last_follow_up_at' => '2026-03-18 10:00:00',
            'notes' => '官网重构和年度运维的重点客户',
        ],
        [
            'legacy_id' => 'customer_1002',
            'company_name' => '北辰数据',
            'short_name' => '北辰',
            'industry' => 'SaaS',
            'customer_level' => 'a',
            'source' => 'direct',
            'stage' => 'contracted',
            'status' => 'active',
            'owner' => $financeActor['name'],
            'owner_admin_id' => $financeActor['admin_id'],
            'contact_name' => '赵静',
            'contact_phone' => '13900002222',
            'contact_email' => 'zhao@beichen.example',
            'city' => '杭州',
            'last_follow_up_at' => '2026-03-19 16:30:00',
            'notes' => '客服工单 SaaS 续费和二期增购在跟进',
        ],
        [
            'legacy_id' => 'customer_1003',
            'company_name' => '海右医药',
            'short_name' => '海右',
            'industry' => '医药',
            'customer_level' => 'b',
            'source' => 'referral',
            'stage' => 'proposal',
            'status' => 'active',
            'owner' => $actor['name'],
            'owner_admin_id' => $actor['admin_id'],
            'contact_name' => '周主任',
            'contact_phone' => '13700003333',
            'contact_email' => 'zhou@haiyou.example',
            'city' => '济南',
            'last_follow_up_at' => '2026-03-20 14:00:00',
            'notes' => '正在推进增长投放和数据分析服务合同',
        ],
    ];

    if (countTable($pdo, $customerTable) === 0) {
        foreach ($customerRows as $row) {
            insertBusinessRow($pdo, $customerTable, array_merge($row, buildAuditPayload($actor, $now)));
        }
    }

    $customerIdMap = fetchMap($pdo, $customerTable, 'company_name');

    $contractRows = [
        [
            'legacy_id' => 'contract_1001',
            'customer_legacy_id' => 'customer_1001',
            'customer_id' => $customerIdMap['星环科技'] ?? 0,
            'customer_name' => '星环科技',
            'contract_no' => 'HT-2026-001',
            'name' => '企业官网重构与年度运维服务',
            'category' => 'implementation',
            'amount' => 180000.00,
            'signed_at' => '2026-01-10',
            'start_date' => '2026-01-12',
            'end_date' => '2026-12-31',
            'status' => 'delivering',
            'owner' => $actor['name'],
            'owner_admin_id' => $actor['admin_id'],
            'project_legacy_id' => 'prj-1001',
            'project_id' => $projectMap['企业官网重构'] ?? 0,
            'app_project_legacy_id' => '',
            'app_project_id' => 0,
            'invoice_total' => 180000.00,
            'received_total' => 90000.00,
            'pending_total' => 90000.00,
            'attachment_ids_json' => '[]',
            'notes' => '包含官网重构、部署、运维和季度复盘服务',
        ],
        [
            'legacy_id' => 'contract_1002',
            'customer_legacy_id' => 'customer_1002',
            'customer_id' => $customerIdMap['北辰数据'] ?? 0,
            'customer_name' => '北辰数据',
            'contract_no' => 'HT-2026-002',
            'name' => '客服工单 SaaS 年度订阅',
            'category' => 'subscription',
            'amount' => 96000.00,
            'signed_at' => '2026-02-05',
            'start_date' => '2026-02-10',
            'end_date' => '2027-02-09',
            'status' => 'active',
            'owner' => $financeActor['name'],
            'owner_admin_id' => $financeActor['admin_id'],
            'project_legacy_id' => 'prj-1002',
            'project_id' => $projectMap['客服工单 SaaS'] ?? 0,
            'app_project_legacy_id' => 'ops-1002',
            'app_project_id' => $appProjectMap['工单助手'] ?? 0,
            'invoice_total' => 96000.00,
            'received_total' => 32000.00,
            'pending_total' => 64000.00,
            'attachment_ids_json' => '[]',
            'notes' => 'SaaS 年费合同，包含 50 个客服坐席和升级包',
        ],
        [
            'legacy_id' => 'contract_1003',
            'customer_legacy_id' => 'customer_1003',
            'customer_id' => $customerIdMap['海右医药'] ?? 0,
            'customer_name' => '海右医药',
            'contract_no' => 'HT-2026-003',
            'name' => '增长投放与数据分析服务',
            'category' => 'service',
            'amount' => 128000.00,
            'signed_at' => '2026-03-05',
            'start_date' => '2026-03-10',
            'end_date' => '2026-08-31',
            'status' => 'review',
            'owner' => $actor['name'],
            'owner_admin_id' => $actor['admin_id'],
            'project_legacy_id' => '',
            'project_id' => 0,
            'app_project_legacy_id' => 'ops-1001',
            'app_project_id' => $appProjectMap['云帆 CRM'] ?? 0,
            'invoice_total' => 50000.00,
            'received_total' => 0.00,
            'pending_total' => 128000.00,
            'attachment_ids_json' => '[]',
            'notes' => '合同正在法务复核，启动款未到账',
        ],
    ];

    if (countTable($pdo, $contractTable) === 0) {
        foreach ($contractRows as $row) {
            insertBusinessRow($pdo, $contractTable, array_merge($row, buildAuditPayload($actor, $now)));
        }
    }

    $contractMap = fetchMap($pdo, $contractTable, 'contract_no');

    if (countTable($pdo, $supplierTable) === 0) {
        $supplierRows = [
            [
                'legacy_id' => 'supplier_1001',
                'supplier_name' => '阿尔法云服务',
                'short_name' => '阿尔法云',
                'category' => 'cloud',
                'level' => 'core',
                'status' => 'active',
                'settlement_cycle' => 'monthly',
                'owner' => $financeActor['name'],
                'owner_admin_id' => $financeActor['admin_id'],
                'contact_name' => '徐莉',
                'contact_phone' => '13600001111',
                'contact_email' => 'service@alpha-cloud.example',
                'city' => '上海',
                'bank_name' => '招商银行上海分行',
                'bank_account' => '6225888888881001',
                'tax_no' => '91310000ALPHA1001',
                'notes' => '云主机、CDN 和对象存储的核心供应商',
            ],
            [
                'legacy_id' => 'supplier_1002',
                'supplier_name' => '青穹渠道伙伴',
                'short_name' => '青穹渠道',
                'category' => 'marketing',
                'level' => 'core',
                'status' => 'active',
                'settlement_cycle' => 'quarterly',
                'owner' => $actor['name'],
                'owner_admin_id' => $actor['admin_id'],
                'contact_name' => '顾扬',
                'contact_phone' => '13600002222',
                'contact_email' => 'partner@qingqiong.example',
                'city' => '杭州',
                'bank_name' => '杭州银行高新支行',
                'bank_account' => '6225888888881002',
                'tax_no' => '91330000QINGQIONG1002',
                'notes' => '投放返佣和渠道合作费用统一走这家供应商',
            ],
            [
                'legacy_id' => 'supplier_1003',
                'supplier_name' => '云杉数字服务',
                'short_name' => '云杉服务',
                'category' => 'service',
                'level' => 'normal',
                'status' => 'active',
                'settlement_cycle' => 'on_delivery',
                'owner' => $actor['name'],
                'owner_admin_id' => $actor['admin_id'],
                'contact_name' => '许越',
                'contact_phone' => '13600003333',
                'contact_email' => 'ops@yunsan.example',
                'city' => '南京',
                'bank_name' => '中国银行南京软件谷支行',
                'bank_account' => '6225888888881003',
                'tax_no' => '91320000YUNSAN1003',
                'notes' => '设计外包、数据清洗和运营支持服务供应商',
            ],
        ];

        foreach ($supplierRows as $row) {
            insertBusinessRow($pdo, $supplierTable, array_merge($row, buildAuditPayload($actor, $now)));
        }
    }

    $planRows = [
        [
            'legacy_id' => 'plan_1001',
            'contract_legacy_id' => 'contract_1001',
            'contract_id' => $contractMap['HT-2026-001'] ?? 0,
            'contract_name' => '企业官网重构与年度运维服务',
            'customer_legacy_id' => 'customer_1001',
            'customer_id' => $customerIdMap['星环科技'] ?? 0,
            'customer_name' => '星环科技',
            'title' => '首款',
            'due_date' => '2026-01-15',
            'amount' => 90000.00,
            'status' => 'received',
            'owner' => $financeActor['name'],
            'owner_admin_id' => $financeActor['admin_id'],
            'actual_received_at' => '2026-01-18 11:00:00',
            'notes' => '已完成到账并开票',
        ],
        [
            'legacy_id' => 'plan_1002',
            'contract_legacy_id' => 'contract_1001',
            'contract_id' => $contractMap['HT-2026-001'] ?? 0,
            'contract_name' => '企业官网重构与年度运维服务',
            'customer_legacy_id' => 'customer_1001',
            'customer_id' => $customerIdMap['星环科技'] ?? 0,
            'customer_name' => '星环科技',
            'title' => '验收尾款',
            'due_date' => '2026-04-15',
            'amount' => 90000.00,
            'status' => 'pending',
            'owner' => $financeActor['name'],
            'owner_admin_id' => $financeActor['admin_id'],
            'actual_received_at' => null,
            'notes' => '待项目验收完成后回款',
        ],
        [
            'legacy_id' => 'plan_1003',
            'contract_legacy_id' => 'contract_1002',
            'contract_id' => $contractMap['HT-2026-002'] ?? 0,
            'contract_name' => '客服工单 SaaS 年度订阅',
            'customer_legacy_id' => 'customer_1002',
            'customer_id' => $customerIdMap['北辰数据'] ?? 0,
            'customer_name' => '北辰数据',
            'title' => '首期订阅款',
            'due_date' => '2026-02-10',
            'amount' => 32000.00,
            'status' => 'received',
            'owner' => $financeActor['name'],
            'owner_admin_id' => $financeActor['admin_id'],
            'actual_received_at' => '2026-02-12 09:30:00',
            'notes' => '已完成到账',
        ],
        [
            'legacy_id' => 'plan_1004',
            'contract_legacy_id' => 'contract_1002',
            'contract_id' => $contractMap['HT-2026-002'] ?? 0,
            'contract_name' => '客服工单 SaaS 年度订阅',
            'customer_legacy_id' => 'customer_1002',
            'customer_id' => $customerIdMap['北辰数据'] ?? 0,
            'customer_name' => '北辰数据',
            'title' => '续费尾款',
            'due_date' => '2026-05-10',
            'amount' => 64000.00,
            'status' => 'processing',
            'owner' => $financeActor['name'],
            'owner_admin_id' => $financeActor['admin_id'],
            'actual_received_at' => null,
            'notes' => '财务已发送付款提醒，等待客户流程审批',
        ],
        [
            'legacy_id' => 'plan_1005',
            'contract_legacy_id' => 'contract_1003',
            'contract_id' => $contractMap['HT-2026-003'] ?? 0,
            'contract_name' => '增长投放与数据分析服务',
            'customer_legacy_id' => 'customer_1003',
            'customer_id' => $customerIdMap['海右医药'] ?? 0,
            'customer_name' => '海右医药',
            'title' => '启动款',
            'due_date' => '2026-03-25',
            'amount' => 50000.00,
            'status' => 'pending',
            'owner' => $actor['name'],
            'owner_admin_id' => $actor['admin_id'],
            'actual_received_at' => null,
            'notes' => '待法务审签完成后发起付款申请',
        ],
    ];

    if (countTable($pdo, $receivablePlanTable) === 0) {
        foreach ($planRows as $row) {
            insertBusinessRow($pdo, $receivablePlanTable, array_merge($row, buildAuditPayload($actor, $now)));
        }
    }

    if (countTable($pdo, $customerFollowupTable) === 0) {
        $followupRows = [
            [
                'legacy_id' => 'followup_1001',
                'customer_legacy_id' => 'customer_1001',
                'customer_id' => $customerIdMap['星环科技'] ?? 0,
                'customer_name' => '星环科技',
                'contract_legacy_id' => 'contract_1001',
                'contract_id' => $contractMap['HT-2026-001'] ?? 0,
                'contract_name' => '企业官网重构与年度运维服务',
                'title' => '官网重构阶段周会确认',
                'followup_type' => 'meeting',
                'follow_up_at' => '2026-03-18 10:00:00',
                'next_follow_up_at' => '2026-03-25 15:00:00',
                'status' => 'done',
                'owner' => $actor['name'],
                'owner_admin_id' => $actor['admin_id'],
                'contact_name' => '林涛',
                'result_summary' => '确认首页和产品页在 3 月底完成联调，尾款需要验收后发起。',
                'notes' => '项目团队和客户均已确认排期。',
            ],
            [
                'legacy_id' => 'followup_1002',
                'customer_legacy_id' => 'customer_1002',
                'customer_id' => $customerIdMap['北辰数据'] ?? 0,
                'customer_name' => '北辰数据',
                'contract_legacy_id' => 'contract_1002',
                'contract_id' => $contractMap['HT-2026-002'] ?? 0,
                'contract_name' => '客服工单 SaaS 年度订阅',
                'title' => '续费尾款回款推进',
                'followup_type' => 'payment',
                'follow_up_at' => '2026-03-19 16:30:00',
                'next_follow_up_at' => '2026-03-24 11:00:00',
                'status' => 'waiting',
                'owner' => $financeActor['name'],
                'owner_admin_id' => $financeActor['admin_id'],
                'contact_name' => '赵静',
                'result_summary' => '客户已提交内部付款流程，预计下周确认到账时间。',
                'notes' => '财务需在下周继续追单。',
            ],
            [
                'legacy_id' => 'followup_1003',
                'customer_legacy_id' => 'customer_1003',
                'customer_id' => $customerIdMap['海右医药'] ?? 0,
                'customer_name' => '海右医药',
                'contract_legacy_id' => 'contract_1003',
                'contract_id' => $contractMap['HT-2026-003'] ?? 0,
                'contract_name' => '增长投放与数据分析服务',
                'title' => '法务意见反馈',
                'followup_type' => 'proposal',
                'follow_up_at' => '2026-03-20 14:00:00',
                'next_follow_up_at' => '2026-03-23 10:30:00',
                'status' => 'planned',
                'owner' => $actor['name'],
                'owner_admin_id' => $actor['admin_id'],
                'contact_name' => '周主任',
                'result_summary' => '客户法务要求补充数据权限条款，方案版本待更新。',
                'notes' => '销售和法务需要共同确认修订版。',
            ],
        ];

        foreach ($followupRows as $row) {
            insertBusinessRow($pdo, $customerFollowupTable, array_merge($row, buildAuditPayload($actor, $now)));
        }
    }

    if (countTable($pdo, $paymentPlanTable) === 0) {
        $paymentRows = [
            [
                'legacy_id' => 'payment_plan_1001',
                'contract_legacy_id' => 'contract_1001',
                'contract_id' => $contractMap['HT-2026-001'] ?? 0,
                'contract_name' => '企业官网重构与年度运维服务',
                'customer_legacy_id' => 'customer_1001',
                'customer_id' => $customerIdMap['星环科技'] ?? 0,
                'customer_name' => '星环科技',
                'title' => '云主机与 CDN 季度付款',
                'payee_name' => '阿尔法云服务',
                'plan_type' => 'supplier',
                'due_date' => '2026-03-28',
                'amount' => 12000.00,
                'status' => 'pending',
                'owner' => $financeActor['name'],
                'owner_admin_id' => $financeActor['admin_id'],
                'actual_paid_at' => null,
                'notes' => '网站上线前需要完成本季度资源续费。',
            ],
            [
                'legacy_id' => 'payment_plan_1002',
                'contract_legacy_id' => 'contract_1002',
                'contract_id' => $contractMap['HT-2026-002'] ?? 0,
                'contract_name' => '客服工单 SaaS 年度订阅',
                'customer_legacy_id' => 'customer_1002',
                'customer_id' => $customerIdMap['北辰数据'] ?? 0,
                'customer_name' => '北辰数据',
                'title' => '实施顾问驻场服务费',
                'payee_name' => '深蓝实施团队',
                'plan_type' => 'implementation',
                'due_date' => '2026-04-05',
                'amount' => 18000.00,
                'status' => 'processing',
                'owner' => $financeActor['name'],
                'owner_admin_id' => $financeActor['admin_id'],
                'actual_paid_at' => null,
                'notes' => '待客户二期需求确认后付款。',
            ],
            [
                'legacy_id' => 'payment_plan_1003',
                'contract_legacy_id' => 'contract_1003',
                'contract_id' => $contractMap['HT-2026-003'] ?? 0,
                'contract_name' => '增长投放与数据分析服务',
                'customer_legacy_id' => 'customer_1003',
                'customer_id' => $customerIdMap['海右医药'] ?? 0,
                'customer_name' => '海右医药',
                'title' => '渠道返佣首期付款',
                'payee_name' => '青禾渠道伙伴',
                'plan_type' => 'commission',
                'due_date' => '2026-04-15',
                'amount' => 15000.00,
                'status' => 'pending',
                'owner' => $actor['name'],
                'owner_admin_id' => $actor['admin_id'],
                'actual_paid_at' => null,
                'notes' => '待合同正式生效后启动返佣申请。',
            ],
        ];

        foreach ($paymentRows as $row) {
            insertBusinessRow($pdo, $paymentPlanTable, array_merge($row, buildAuditPayload($actor, $now)));
        }
    }

    if (countTable($pdo, $purchaseOrderTable) === 0) {
        $supplierMap = fetchMap($pdo, $supplierTable, 'supplier_name');
        $paymentPlanMap = fetchMap($pdo, $paymentPlanTable, 'title');
        $purchaseRows = [
            [
                'legacy_id' => 'purchase_order_1001',
                'order_no' => 'PO-20260320-001',
                'title' => '云资源季度续费',
                'purchase_type' => 'cloud',
                'supplier_legacy_id' => 'supplier_1001',
                'supplier_id' => $supplierMap['阿尔法云服务'] ?? 0,
                'supplier_name' => '阿尔法云服务',
                'customer_legacy_id' => 'customer_1001',
                'customer_id' => $customerIdMap['星环科技'] ?? 0,
                'customer_name' => '星环科技',
                'contract_legacy_id' => 'contract_1001',
                'contract_id' => $contractMap['HT-2026-001'] ?? 0,
                'contract_name' => '企业官网重构与年度运维服务',
                'order_amount' => 12000.00,
                'ordered_at' => '2026-03-20 10:00:00',
                'expected_delivery_date' => '2026-03-28',
                'actual_delivery_at' => null,
                'status' => 'processing',
                'approval_status' => 'approved',
                'approval_updated_at' => '2026-03-20 15:00:00',
                'owner' => $financeActor['name'],
                'owner_admin_id' => $financeActor['admin_id'],
                'payment_plan_id' => $paymentPlanMap['云主机与 CDN 季度付款'] ?? 0,
                'payment_plan_legacy_id' => 'payment_plan_1001',
                'payment_plan_title' => '云主机与 CDN 季度付款',
                'reconciliation_id' => 0,
                'reconciliation_legacy_id' => '',
                'reconciliation_title' => '',
                'attachment_ids_json' => '[]',
                'purchase_content' => '续费官网部署所需的云主机、对象存储和 CDN 资源。',
                'notes' => '已生成付款计划，等待财务打款。',
            ],
            [
                'legacy_id' => 'purchase_order_1002',
                'order_no' => 'PO-20260321-002',
                'title' => '实施顾问驻场支持',
                'purchase_type' => 'service',
                'supplier_legacy_id' => 'supplier_1003',
                'supplier_id' => $supplierMap['云杉数字服务'] ?? 0,
                'supplier_name' => '云杉数字服务',
                'customer_legacy_id' => 'customer_1002',
                'customer_id' => $customerIdMap['北景数据'] ?? 0,
                'customer_name' => '北景数据',
                'contract_legacy_id' => 'contract_1002',
                'contract_id' => $contractMap['HT-2026-002'] ?? 0,
                'contract_name' => '客服工单 SaaS 年度订阅',
                'order_amount' => 8600.00,
                'ordered_at' => '2026-03-21 14:20:00',
                'expected_delivery_date' => '2026-03-29',
                'actual_delivery_at' => null,
                'status' => 'draft',
                'approval_status' => 'none',
                'approval_updated_at' => null,
                'owner' => $actor['name'],
                'owner_admin_id' => $actor['admin_id'],
                'payment_plan_id' => 0,
                'payment_plan_legacy_id' => '',
                'payment_plan_title' => '',
                'reconciliation_id' => 0,
                'reconciliation_legacy_id' => '',
                'reconciliation_title' => '',
                'attachment_ids_json' => '[]',
                'purchase_content' => '补充项目二期交付需要的设计支持、素材整理和运营协作。',
                'notes' => '待项目经理确认采购范围后提交审批。',
            ],
            [
                'legacy_id' => 'purchase_order_1003',
                'order_no' => 'PO-20260321-003',
                'title' => '增长投放素材制作',
                'purchase_type' => 'marketing',
                'supplier_legacy_id' => 'supplier_1002',
                'supplier_id' => $supplierMap['青穹渠道伙伴'] ?? 0,
                'supplier_name' => '青穹渠道伙伴',
                'customer_legacy_id' => 'customer_1003',
                'customer_id' => $customerIdMap['海右医药'] ?? 0,
                'customer_name' => '海右医药',
                'contract_legacy_id' => 'contract_1003',
                'contract_id' => $contractMap['HT-2026-003'] ?? 0,
                'contract_name' => '增长投放与数据分析服务',
                'order_amount' => 15000.00,
                'ordered_at' => '2026-03-21 09:30:00',
                'expected_delivery_date' => '2026-04-15',
                'actual_delivery_at' => null,
                'status' => 'approved',
                'approval_status' => 'approved',
                'approval_updated_at' => '2026-03-21 11:00:00',
                'owner' => $actor['name'],
                'owner_admin_id' => $actor['admin_id'],
                'payment_plan_id' => 0,
                'payment_plan_legacy_id' => '',
                'payment_plan_title' => '',
                'reconciliation_id' => 0,
                'reconciliation_legacy_id' => '',
                'reconciliation_title' => '',
                'attachment_ids_json' => '[]',
                'purchase_content' => '为增长投放准备首期素材、渠道对接和广告账户配置。',
                'notes' => '审批通过后可直接生成付款计划。',
            ],
        ];

        foreach ($purchaseRows as $row) {
            insertBusinessRow($pdo, $purchaseOrderTable, array_merge($row, buildAuditPayload($actor, $now)));
        }

        $linkedPurchaseStmt = $pdo->prepare("SELECT id FROM `{$purchaseOrderTable}` WHERE `legacy_id` = ? LIMIT 1");
        $linkedPurchaseStmt->execute(['purchase_order_1001']);
        $linkedPurchaseId = (int) $linkedPurchaseStmt->fetchColumn();
        if ($linkedPurchaseId > 0) {
            $updatePaymentStmt = $pdo->prepare("UPDATE `{$paymentPlanTable}` SET `purchase_order_id` = ?, `purchase_order_legacy_id` = ?, `purchase_order_title` = ? WHERE `legacy_id` = ?");
            $updatePaymentStmt->execute([$linkedPurchaseId, 'purchase_order_1001', '云资源季度续费', 'payment_plan_1001']);
        }
    }

    if (countTable($pdo, $purchaseReconciliationTable) === 0) {
        $purchaseLookupStmt = $pdo->prepare("SELECT id,supplier_id,customer_id,contract_id,title,order_amount,payment_plan_id,payment_plan_title FROM `{$purchaseOrderTable}` WHERE `legacy_id` = ? LIMIT 1");
        $purchaseLookupStmt->execute(['purchase_order_1001']);
        $seedPurchaseOrder = $purchaseLookupStmt->fetch();

        if ($seedPurchaseOrder) {
            $reconciliationRow = array_merge([
                'legacy_id' => 'purchase_reconciliation_1001',
                'reconcile_no' => 'PR-20260321-001',
                'title' => '云资源季度续费对账',
                'purchase_order_legacy_id' => 'purchase_order_1001',
                'purchase_order_id' => (int) $seedPurchaseOrder['id'],
                'purchase_order_title' => (string) $seedPurchaseOrder['title'],
                'payment_plan_legacy_id' => 'payment_plan_1001',
                'payment_plan_id' => (int) ($seedPurchaseOrder['payment_plan_id'] ?? 0),
                'payment_plan_title' => (string) ($seedPurchaseOrder['payment_plan_title'] ?? ''),
                'supplier_legacy_id' => 'supplier_1001',
                'supplier_id' => (int) ($seedPurchaseOrder['supplier_id'] ?? 0),
                'supplier_name' => '阿尔法云服务',
                'customer_legacy_id' => 'customer_1001',
                'customer_id' => (int) ($seedPurchaseOrder['customer_id'] ?? 0),
                'customer_name' => '星环科技',
                'contract_legacy_id' => 'contract_1001',
                'contract_id' => (int) ($seedPurchaseOrder['contract_id'] ?? 0),
                'contract_name' => '企业官网重构与年度运维服务',
                'order_amount' => round((float) ($seedPurchaseOrder['order_amount'] ?? 0), 2),
                'confirmed_amount' => round((float) ($seedPurchaseOrder['order_amount'] ?? 0), 2),
                'variance_amount' => 0.00,
                'reconciled_at' => '2026-03-21 16:00:00',
                'status' => 'confirmed',
                'owner' => $financeActor['name'],
                'owner_admin_id' => $financeActor['admin_id'],
                'attachment_ids_json' => '[]',
                'notes' => '已和供应商确认季度续费金额及资源明细。',
            ], buildAuditPayload($financeActor, $now));

            insertBusinessRow($pdo, $purchaseReconciliationTable, $reconciliationRow);
            $reconciliationId = (int) $pdo->lastInsertId();

            $pdo->prepare("
                UPDATE `{$purchaseOrderTable}`
                SET `reconciliation_id` = ?, `reconciliation_legacy_id` = ?, `reconciliation_title` = ?
                WHERE `id` = ?
            ")->execute([
                $reconciliationId,
                'purchase_reconciliation_1001',
                '云资源季度续费对账',
                (int) $seedPurchaseOrder['id'],
            ]);
        }
    }

    if (countTable($pdo, $purchaseSettlementTable) === 0) {
        $purchaseLookupStmt = $pdo->prepare("SELECT id,supplier_id,customer_id,contract_id,title,order_amount FROM `{$purchaseOrderTable}` WHERE `legacy_id` = ? LIMIT 1");
        $purchaseLookupStmt->execute(['purchase_order_1001']);
        $seedPurchaseOrder = $purchaseLookupStmt->fetch();

        $paymentLookupStmt = $pdo->prepare("SELECT id,title FROM `{$paymentPlanTable}` WHERE `legacy_id` = ? LIMIT 1");
        $paymentLookupStmt->execute(['payment_plan_1001']);
        $seedPaymentPlan = $paymentLookupStmt->fetch();

        if ($seedPurchaseOrder && $seedPaymentPlan) {
            $settlementRow = array_merge([
                'legacy_id' => 'purchase_settlement_1001',
                'settlement_no' => 'PS-20260321-001',
                'title' => '云资源季度续费结算',
                'purchase_order_legacy_id' => 'purchase_order_1001',
                'purchase_order_id' => (int) $seedPurchaseOrder['id'],
                'purchase_order_title' => (string) $seedPurchaseOrder['title'],
                'payment_plan_legacy_id' => 'payment_plan_1001',
                'payment_plan_id' => (int) $seedPaymentPlan['id'],
                'payment_plan_title' => (string) $seedPaymentPlan['title'],
                'supplier_legacy_id' => 'supplier_1001',
                'supplier_id' => (int) ($seedPurchaseOrder['supplier_id'] ?? 0),
                'supplier_name' => '阿尔法云服务',
                'customer_legacy_id' => 'customer_1001',
                'customer_id' => (int) ($seedPurchaseOrder['customer_id'] ?? 0),
                'customer_name' => '星环科技',
                'contract_legacy_id' => 'contract_1001',
                'contract_id' => (int) ($seedPurchaseOrder['contract_id'] ?? 0),
                'contract_name' => '企业官网重构与年度运维服务',
                'settlement_amount' => round((float) ($seedPurchaseOrder['order_amount'] ?? 0), 2),
                'paid_amount' => 0.00,
                'invoiced_amount' => 0.00,
                'balance_amount' => round((float) ($seedPurchaseOrder['order_amount'] ?? 0), 2),
                'invoice_status' => 'none',
                'invoice_no' => '',
                'invoiced_at' => null,
                'status' => 'reconciling',
                'owner' => $financeActor['name'],
                'owner_admin_id' => $financeActor['admin_id'],
                'settled_at' => null,
                'attachment_ids_json' => '[]',
                'notes' => '示例采购结算，用于展示采购到付款再到结算的完整链路',
            ], buildAuditPayload($financeActor, $now));

            insertBusinessRow($pdo, $purchaseSettlementTable, $settlementRow);
            $settlementId = (int) $pdo->lastInsertId();

            $updatePurchaseSettlementStmt = $pdo->prepare("
                UPDATE `{$purchaseOrderTable}`
                SET `settlement_id` = ?, `settlement_legacy_id` = ?, `settlement_title` = ?, `status` = 'processing'
                WHERE `id` = ?
            ");
            $updatePurchaseSettlementStmt->execute([
                $settlementId,
                'purchase_settlement_1001',
                '云资源季度续费结算',
                (int) $seedPurchaseOrder['id'],
            ]);
        }
    }

    if (countTable($pdo, $purchaseInvoiceTable) === 0) {
        $purchaseLookupStmt = $pdo->prepare("SELECT id,supplier_id,customer_id,contract_id,title,settlement_id,settlement_title FROM `{$purchaseOrderTable}` WHERE `legacy_id` = ? LIMIT 1");
        $purchaseLookupStmt->execute(['purchase_order_1001']);
        $seedPurchaseOrder = $purchaseLookupStmt->fetch();

        $settlementLookupStmt = $pdo->prepare("SELECT id,title,settlement_amount FROM `{$purchaseSettlementTable}` WHERE `legacy_id` = ? LIMIT 1");
        $settlementLookupStmt->execute(['purchase_settlement_1001']);
        $seedSettlement = $settlementLookupStmt->fetch();

        if ($seedPurchaseOrder && $seedSettlement) {
            $invoiceAmount = 6000.00;
            $taxAmount = 339.62;
            $invoiceRow = array_merge([
                'legacy_id' => 'purchase_invoice_1001',
                'invoice_no' => 'FP-20260321-001',
                'title' => '云资源季度续费发票',
                'purchase_order_legacy_id' => 'purchase_order_1001',
                'purchase_order_id' => (int) $seedPurchaseOrder['id'],
                'purchase_order_title' => (string) $seedPurchaseOrder['title'],
                'settlement_legacy_id' => 'purchase_settlement_1001',
                'settlement_id' => (int) $seedSettlement['id'],
                'settlement_title' => (string) $seedSettlement['title'],
                'supplier_legacy_id' => 'supplier_1001',
                'supplier_id' => (int) ($seedPurchaseOrder['supplier_id'] ?? 0),
                'supplier_name' => '阿尔法云服务',
                'customer_legacy_id' => 'customer_1001',
                'customer_id' => (int) ($seedPurchaseOrder['customer_id'] ?? 0),
                'customer_name' => '星环科技',
                'contract_legacy_id' => 'contract_1001',
                'contract_id' => (int) ($seedPurchaseOrder['contract_id'] ?? 0),
                'contract_name' => '企业官网重构与年度运维服务',
                'invoice_type' => 'electronic',
                'invoice_amount' => $invoiceAmount,
                'untaxed_amount' => round($invoiceAmount - $taxAmount, 2),
                'tax_amount' => $taxAmount,
                'invoiced_at' => '2026-03-21',
                'received_at' => '2026-03-21 18:20:00',
                'status' => 'received',
                'owner' => $financeActor['name'],
                'owner_admin_id' => $financeActor['admin_id'],
                'attachment_ids_json' => '[]',
                'notes' => '示例到票记录，剩余发票待供应商补齐。',
            ], buildAuditPayload($financeActor, $now));

            insertBusinessRow($pdo, $purchaseInvoiceTable, $invoiceRow);

            $pdo->prepare("
                UPDATE `{$purchaseSettlementTable}`
                SET `invoiced_amount` = ?, `invoice_status` = 'partial', `invoice_no` = ?, `invoiced_at` = ?
                WHERE `id` = ?
            ")->execute([
                $invoiceAmount,
                'FP-20260321-001',
                '2026-03-21',
                (int) $seedSettlement['id'],
            ]);
        }
    }

    if (countTable($pdo, $expenseRequestTable) === 0) {
        $supplierMap = fetchMap($pdo, $supplierTable, 'supplier_name');
        $paymentPlanMap = fetchMap($pdo, $paymentPlanTable, 'title');
        $expenseRows = [
            [
                'legacy_id' => 'expense_request_1001',
                'request_no' => 'FY-20260320-ALPHA',
                'title' => '云资源季度续费',
                'expense_type' => 'software',
                'supplier_legacy_id' => 'supplier_1001',
                'supplier_id' => $supplierMap['阿尔法云服务'] ?? 0,
                'supplier_name' => '阿尔法云服务',
                'customer_legacy_id' => 'customer_1001',
                'customer_id' => $customerIdMap['鏄熺幆绉戞妧'] ?? 0,
                'customer_name' => '鏄熺幆绉戞妧',
                'contract_legacy_id' => 'contract_1001',
                'contract_id' => $contractMap['HT-2026-001'] ?? 0,
                'contract_name' => '浼佷笟瀹樼綉閲嶆瀯涓庡勾搴﹁繍缁存湇鍔?',
                'request_amount' => 12000.00,
                'requested_at' => '2026-03-20 10:00:00',
                'expected_pay_date' => '2026-03-28',
                'status' => 'processing',
                'approval_status' => 'approved',
                'approval_updated_at' => '2026-03-20 15:00:00',
                'owner' => $financeActor['name'],
                'owner_admin_id' => $financeActor['admin_id'],
                'payment_plan_id' => $paymentPlanMap['浜戜富鏈轰笌 CDN 瀛ｅ害浠樻'] ?? 0,
                'payment_plan_legacy_id' => 'payment_plan_1001',
                'payment_plan_title' => '浜戜富鏈轰笌 CDN 瀛ｅ害浠樻',
                'attachment_ids_json' => '[]',
                'reason' => '网站资源季度续费，需要在续费日前完成付款',
                'notes' => '已由财务确认并生成付款计划',
            ],
            [
                'legacy_id' => 'expense_request_1002',
                'request_no' => 'FY-20260321-QQPD',
                'title' => '渠道返佣首期申请',
                'expense_type' => 'marketing',
                'supplier_legacy_id' => 'supplier_1002',
                'supplier_id' => $supplierMap['青穹渠道伙伴'] ?? 0,
                'supplier_name' => '青穹渠道伙伴',
                'customer_legacy_id' => 'customer_1003',
                'customer_id' => $customerIdMap['娴峰彸鍖昏嵂'] ?? 0,
                'customer_name' => '娴峰彸鍖昏嵂',
                'contract_legacy_id' => 'contract_1003',
                'contract_id' => $contractMap['HT-2026-003'] ?? 0,
                'contract_name' => '澧為暱鎶曟斁涓庢暟鎹垎鏋愭湇鍔?',
                'request_amount' => 15000.00,
                'requested_at' => '2026-03-21 09:30:00',
                'expected_pay_date' => '2026-04-15',
                'status' => 'approved',
                'approval_status' => 'approved',
                'approval_updated_at' => '2026-03-21 11:00:00',
                'owner' => $actor['name'],
                'owner_admin_id' => $actor['admin_id'],
                'payment_plan_id' => 0,
                'payment_plan_legacy_id' => '',
                'payment_plan_title' => '',
                'attachment_ids_json' => '[]',
                'reason' => '合同返佣条款已经确认，需在合同生效后支付首期返佣',
                'notes' => '待财务确认后生成付款计划',
            ],
            [
                'legacy_id' => 'expense_request_1003',
                'request_no' => 'FY-20260321-YSFW',
                'title' => '运营支持设计外包',
                'expense_type' => 'outsourcing',
                'supplier_legacy_id' => 'supplier_1003',
                'supplier_id' => $supplierMap['云杉数字服务'] ?? 0,
                'supplier_name' => '云杉数字服务',
                'customer_legacy_id' => 'customer_1002',
                'customer_id' => $customerIdMap['鍖楄景鏁版嵁'] ?? 0,
                'customer_name' => '鍖楄景鏁版嵁',
                'contract_legacy_id' => 'contract_1002',
                'contract_id' => $contractMap['HT-2026-002'] ?? 0,
                'contract_name' => '瀹㈡湇宸ュ崟 SaaS 骞村害璁㈤槄',
                'request_amount' => 8600.00,
                'requested_at' => '2026-03-21 14:20:00',
                'expected_pay_date' => '2026-03-29',
                'status' => 'draft',
                'approval_status' => 'none',
                'approval_updated_at' => null,
                'owner' => $actor['name'],
                'owner_admin_id' => $actor['admin_id'],
                'payment_plan_id' => 0,
                'payment_plan_legacy_id' => '',
                'payment_plan_title' => '',
                'attachment_ids_json' => '[]',
                'reason' => '项目二期交付前需要补一轮设计支持和素材整理',
                'notes' => '待项目经理确认范围后再发起审批',
            ],
        ];

        foreach ($expenseRows as $row) {
            insertBusinessRow($pdo, $expenseRequestTable, array_merge($row, buildAuditPayload($actor, $now)));
        }

        $linkedExpenseStmt = $pdo->prepare("SELECT id FROM `{$expenseRequestTable}` WHERE `legacy_id` = ? LIMIT 1");
        $linkedExpenseStmt->execute(['expense_request_1001']);
        $linkedExpenseId = (int) $linkedExpenseStmt->fetchColumn();
        if ($linkedExpenseId > 0) {
            $updatePaymentStmt = $pdo->prepare("UPDATE `{$paymentPlanTable}` SET `expense_request_id` = ?, `expense_request_legacy_id` = ?, `expense_request_title` = ? WHERE `legacy_id` = ?");
            $updatePaymentStmt->execute([$linkedExpenseId, 'expense_request_1001', '云资源季度续费', 'payment_plan_1001']);
        }
    }

    seedApprovalTemplates($pdo, $prefix, $staffMap, $now);
}

function seedApprovalTemplates(PDO $pdo, string $prefix, array $staffMap, int $now): void
{
    $templateTable = $prefix . 'business_approval_template';
    $stepTable = $prefix . 'business_approval_template_step';
    $leader = $staffMap['leader.he'] ?? $staffMap['admin'] ?? ['admin_id' => 1, 'legacy_id' => 'staff_admin', 'name' => '陈总'];
    $finance = $staffMap['finance.li'] ?? $leader;
    $ops = $staffMap['ops.gu'] ?? $leader;

    $templates = [
        [
            'legacy_id' => 'approval_template_contract_default',
            'name' => '合同双级审批',
            'object_type' => 'contract',
            'status' => 'active',
            'is_default' => 1,
            'min_amount' => 0,
            'max_amount' => 0,
            'step_count' => 2,
            'description' => '适用于标准合同审批，先业务负责人确认，再由管理层终审。',
            'steps' => [
                ['step_no' => 1, 'step_name' => '业务复核', 'actor' => $ops, 'notes' => '确认合同范围、报价和交付边界。'],
                ['step_no' => 2, 'step_name' => '管理终审', 'actor' => $leader, 'notes' => '确认风险、回款条款和合同生效安排。'],
            ],
        ],
        [
            'legacy_id' => 'approval_template_payment_default',
            'name' => '付款双级审批',
            'object_type' => 'payment_plan',
            'status' => 'active',
            'is_default' => 1,
            'min_amount' => 0,
            'max_amount' => 0,
            'step_count' => 2,
            'description' => '适用于供应商付款和成本付款，先财务复核，再由负责人确认。',
            'steps' => [
                ['step_no' => 1, 'step_name' => '财务复核', 'actor' => $finance, 'notes' => '确认付款金额、票据和计划日期。'],
                ['step_no' => 2, 'step_name' => '负责人确认', 'actor' => $leader, 'notes' => '确认付款必要性和预算占用。'],
            ],
        ],
        [
            'legacy_id' => 'approval_template_expense_default',
            'name' => '费用双级审批',
            'object_type' => 'expense_request',
            'status' => 'active',
            'is_default' => 1,
            'min_amount' => 0,
            'max_amount' => 0,
            'step_count' => 2,
            'description' => '适用于费用申请，先财务初审，再由负责人终审。',
            'steps' => [
                ['step_no' => 1, 'step_name' => '财务初审', 'actor' => $finance, 'notes' => '确认预算、税票和付款计划。'],
                ['step_no' => 2, 'step_name' => '负责人终审', 'actor' => $leader, 'notes' => '确认费用必要性和业务收益。'],
            ],
        ],
        [
            'legacy_id' => 'approval_template_purchase_default',
            'name' => '采购双级审批',
            'object_type' => 'purchase_order',
            'status' => 'active',
            'is_default' => 1,
            'min_amount' => 0,
            'max_amount' => 0,
            'step_count' => 2,
            'description' => '适用于采购单审批，先财务评估，再由管理层确认。',
            'steps' => [
                ['step_no' => 1, 'step_name' => '采购评估', 'actor' => $finance, 'notes' => '确认金额、账期和供应商结算条件。'],
                ['step_no' => 2, 'step_name' => '管理确认', 'actor' => $leader, 'notes' => '确认采购必要性和最终执行。'],
            ],
        ],
        [
            'legacy_id' => 'approval_template_payment_request_default',
            'name' => '付款申请双级审批',
            'object_type' => 'payment_request',
            'status' => 'active',
            'is_default' => 1,
            'min_amount' => 0,
            'max_amount' => 0,
            'step_count' => 2,
            'description' => '适用于采购结算后的付款申请，先财务复核，再由负责人确认付款。',
            'steps' => [
                ['step_no' => 1, 'step_name' => '财务复核', 'actor' => $finance, 'notes' => '确认付款金额、结算单、票据和供应商信息。'],
                ['step_no' => 2, 'step_name' => '负责人确认', 'actor' => $leader, 'notes' => '确认付款必要性和实际支付安排。'],
            ],
        ],
    ];

    foreach ($templates as $template) {
        $stmt = $pdo->prepare("SELECT id FROM `{$templateTable}` WHERE `legacy_id` = ? LIMIT 1");
        $stmt->execute([$template['legacy_id']]);
        $templateId = (int) $stmt->fetchColumn();

        if ($templateId <= 0) {
            $payload = array_merge([
                'legacy_id' => $template['legacy_id'],
                'name' => $template['name'],
                'object_type' => $template['object_type'],
                'status' => $template['status'],
                'is_default' => $template['is_default'],
                'min_amount' => $template['min_amount'],
                'max_amount' => $template['max_amount'],
                'step_count' => $template['step_count'],
                'description' => $template['description'],
            ], buildAuditPayload($leader, $now));
            insertBusinessRow($pdo, $templateTable, $payload);
            $templateId = (int) $pdo->lastInsertId();
        } else {
            $pdo->prepare("
                UPDATE `{$templateTable}`
                SET `name` = ?, `object_type` = ?, `status` = ?, `is_default` = ?, `min_amount` = ?, `max_amount` = ?, `step_count` = ?, `description` = ?, `record_updated_at` = ?, `updated_by_legacy_id` = ?, `updated_by_admin_id` = ?, `updated_by_name` = ?, `updatetime` = ?
                WHERE `id` = ?
            ")->execute([
                $template['name'],
                $template['object_type'],
                $template['status'],
                $template['is_default'],
                $template['min_amount'],
                $template['max_amount'],
                $template['step_count'],
                $template['description'],
                date('Y-m-d H:i:s'),
                $leader['legacy_id'],
                $leader['admin_id'],
                $leader['name'],
                $now,
                $templateId,
            ]);
        }

        foreach ($template['steps'] as $step) {
            $stepLegacyId = $template['legacy_id'] . '_step_' . $step['step_no'];
            $stepStmt = $pdo->prepare("SELECT id FROM `{$stepTable}` WHERE `legacy_id` = ? LIMIT 1");
            $stepStmt->execute([$stepLegacyId]);
            $stepId = (int) $stepStmt->fetchColumn();

            if ($stepId <= 0) {
                insertBusinessRow($pdo, $stepTable, array_merge([
                    'legacy_id' => $stepLegacyId,
                    'template_id' => $templateId,
                    'template_legacy_id' => $template['legacy_id'],
                    'template_name' => $template['name'],
                    'object_type' => $template['object_type'],
                    'step_no' => $step['step_no'],
                    'step_name' => $step['step_name'],
                    'approver_admin_id' => $step['actor']['admin_id'],
                    'approver_legacy_id' => $step['actor']['legacy_id'],
                    'approver_name' => $step['actor']['name'],
                    'status' => 'active',
                    'notes' => $step['notes'],
                ], buildAuditPayload($leader, $now)));
                continue;
            }

            $pdo->prepare("
                UPDATE `{$stepTable}`
                SET `template_id` = ?, `template_legacy_id` = ?, `template_name` = ?, `object_type` = ?, `step_no` = ?, `step_name` = ?, `approver_admin_id` = ?, `approver_legacy_id` = ?, `approver_name` = ?, `status` = 'active', `notes` = ?, `record_updated_at` = ?, `updated_by_legacy_id` = ?, `updated_by_admin_id` = ?, `updated_by_name` = ?, `updatetime` = ?
                WHERE `id` = ?
            ")->execute([
                $templateId,
                $template['legacy_id'],
                $template['name'],
                $template['object_type'],
                $step['step_no'],
                $step['step_name'],
                $step['actor']['admin_id'],
                $step['actor']['legacy_id'],
                $step['actor']['name'],
                $step['notes'],
                date('Y-m-d H:i:s'),
                $leader['legacy_id'],
                $leader['admin_id'],
                $leader['name'],
                $now,
                $stepId,
            ]);
        }
    }
}

function fetchStaffMap(PDO $pdo, string $prefix): array
{
    $map = [];
    $stmt = $pdo->query("SELECT account,admin_id,legacy_id,name FROM {$prefix}staff_profile ORDER BY id ASC");
    foreach ($stmt as $row) {
        $map[$row['account']] = [
            'admin_id' => (int) $row['admin_id'],
            'legacy_id' => $row['legacy_id'],
            'name' => $row['name'],
        ];
    }

    return $map;
}

function fetchMap(PDO $pdo, string $table, string $keyField): array
{
    $map = [];
    $stmt = $pdo->query("SELECT id,legacy_id,{$keyField} AS map_key FROM {$table} ORDER BY id ASC");
    foreach ($stmt as $row) {
        $map[$row['map_key']] = (int) $row['id'];
    }

    return $map;
}

function ensureBusinessApprovalColumns(PDO $pdo, string $prefix): void
{
    $approvalTable = $prefix . 'business_approval';
    $templateTable = $prefix . 'business_approval_template';
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `{$approvalTable}` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `legacy_id` varchar(64) NOT NULL DEFAULT '',
            `approval_no` varchar(50) NOT NULL DEFAULT '',
            `object_type` enum('contract','payment_plan','expense_request','purchase_order') NOT NULL DEFAULT 'contract',
            `object_id` int(10) unsigned NOT NULL DEFAULT '0',
            `object_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `object_title` varchar(150) NOT NULL DEFAULT '',
            `customer_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `customer_id` int(10) unsigned NOT NULL DEFAULT '0',
            `customer_name` varchar(150) NOT NULL DEFAULT '',
            `contract_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `contract_id` int(10) unsigned NOT NULL DEFAULT '0',
            `contract_name` varchar(150) NOT NULL DEFAULT '',
            `payment_plan_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `payment_plan_id` int(10) unsigned NOT NULL DEFAULT '0',
            `payment_plan_title` varchar(150) NOT NULL DEFAULT '',
            `expense_request_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `expense_request_id` int(10) unsigned NOT NULL DEFAULT '0',
            `expense_request_title` varchar(150) NOT NULL DEFAULT '',
            `purchase_order_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `purchase_order_id` int(10) unsigned NOT NULL DEFAULT '0',
            `purchase_order_title` varchar(150) NOT NULL DEFAULT '',
            `template_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `template_id` int(10) unsigned NOT NULL DEFAULT '0',
            `template_name` varchar(150) NOT NULL DEFAULT '',
            `current_step` int(10) unsigned NOT NULL DEFAULT '1',
            `total_steps` int(10) unsigned NOT NULL DEFAULT '1',
            `current_step_name` varchar(100) NOT NULL DEFAULT '',
            `step_snapshot_json` longtext,
            `decision_log_json` longtext,
            `status` enum('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
            `submit_reason` text,
            `decision_note` text,
            `applicant_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `applicant_name` varchar(50) NOT NULL DEFAULT '',
            `approver_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `approver_name` varchar(50) NOT NULL DEFAULT '',
            `applied_at` datetime DEFAULT NULL,
            `decided_at` datetime DEFAULT NULL,
            `record_created_at` datetime DEFAULT NULL,
            `record_updated_at` datetime DEFAULT NULL,
            `created_by_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `created_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `created_by_name` varchar(50) NOT NULL DEFAULT '',
            `updated_by_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `updated_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `updated_by_name` varchar(50) NOT NULL DEFAULT '',
            `createtime` bigint(16) DEFAULT NULL,
            `updatetime` bigint(16) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_business_approval_legacy_id` (`legacy_id`),
            UNIQUE KEY `uk_business_approval_approval_no` (`approval_no`),
            KEY `idx_business_approval_object` (`object_type`,`object_id`),
            KEY `idx_business_approval_status` (`status`),
            KEY `idx_business_approval_customer_id` (`customer_id`),
            KEY `idx_business_approval_contract_id` (`contract_id`),
            KEY `idx_business_approval_payment_plan_id` (`payment_plan_id`),
            KEY `idx_business_approval_expense_request_id` (`expense_request_id`),
            KEY `idx_business_approval_purchase_order_id` (`purchase_order_id`),
            KEY `idx_business_approval_template_id` (`template_id`),
            KEY `idx_business_approval_applicant_admin_id` (`applicant_admin_id`),
            KEY `idx_business_approval_approver_admin_id` (`approver_admin_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='审批中心'
    ");

    $pdo->exec("ALTER TABLE `{$approvalTable}` MODIFY COLUMN `object_type` enum('contract','payment_plan','expense_request','purchase_order','payment_request') NOT NULL DEFAULT 'contract'");
    $pdo->exec("ALTER TABLE `{$templateTable}` MODIFY COLUMN `object_type` enum('contract','payment_plan','expense_request','purchase_order','payment_request') NOT NULL DEFAULT 'contract'");
    $pdo->exec("ALTER TABLE `{$prefix}business_approval_template_step` MODIFY COLUMN `object_type` enum('contract','payment_plan','expense_request','purchase_order','payment_request') NOT NULL DEFAULT 'contract'");

    ensureTableColumn(
        $pdo,
        $prefix . 'business_contract',
        'approval_status',
        "ALTER TABLE `{$prefix}business_contract` ADD COLUMN `approval_status` enum('none','pending','approved','rejected','cancelled') NOT NULL DEFAULT 'none' AFTER `status`"
    );
    ensureTableColumn(
        $pdo,
        $prefix . 'business_contract',
        'approval_updated_at',
        "ALTER TABLE `{$prefix}business_contract` ADD COLUMN `approval_updated_at` datetime DEFAULT NULL AFTER `approval_status`"
    );
    ensureTableColumn(
        $pdo,
        $prefix . 'business_purchase_order',
        'approval_status',
        "ALTER TABLE `{$prefix}business_purchase_order` ADD COLUMN `approval_status` enum('none','pending','approved','rejected','cancelled') NOT NULL DEFAULT 'none' AFTER `status`"
    );
    ensureTableColumn(
        $pdo,
        $prefix . 'business_purchase_order',
        'approval_updated_at',
        "ALTER TABLE `{$prefix}business_purchase_order` ADD COLUMN `approval_updated_at` datetime DEFAULT NULL AFTER `approval_status`"
    );
    ensureTableColumn(
        $pdo,
        $prefix . 'business_purchase_order',
        'payment_plan_id',
        "ALTER TABLE `{$prefix}business_purchase_order` ADD COLUMN `payment_plan_id` int(10) unsigned NOT NULL DEFAULT '0' AFTER `owner_admin_id`"
    );
    ensureTableColumn(
        $pdo,
        $prefix . 'business_purchase_order',
        'payment_plan_legacy_id',
        "ALTER TABLE `{$prefix}business_purchase_order` ADD COLUMN `payment_plan_legacy_id` varchar(64) NOT NULL DEFAULT '' AFTER `payment_plan_id`"
    );
    ensureTableColumn(
        $pdo,
        $prefix . 'business_purchase_order',
        'payment_plan_title',
        "ALTER TABLE `{$prefix}business_purchase_order` ADD COLUMN `payment_plan_title` varchar(150) NOT NULL DEFAULT '' AFTER `payment_plan_legacy_id`"
    );
    ensureTableColumn(
        $pdo,
        $prefix . 'business_payment_plan',
        'approval_status',
        "ALTER TABLE `{$prefix}business_payment_plan` ADD COLUMN `approval_status` enum('none','pending','approved','rejected','cancelled') NOT NULL DEFAULT 'none' AFTER `status`"
    );
    ensureTableColumn(
        $pdo,
        $prefix . 'business_payment_plan',
        'approval_updated_at',
        "ALTER TABLE `{$prefix}business_payment_plan` ADD COLUMN `approval_updated_at` datetime DEFAULT NULL AFTER `approval_status`"
    );
    ensureTableColumn(
        $pdo,
        $prefix . 'business_payment_plan',
        'expense_request_legacy_id',
        "ALTER TABLE `{$prefix}business_payment_plan` ADD COLUMN `expense_request_legacy_id` varchar(64) NOT NULL DEFAULT '' AFTER `customer_name`"
    );
    ensureTableColumn(
        $pdo,
        $prefix . 'business_payment_plan',
        'expense_request_id',
        "ALTER TABLE `{$prefix}business_payment_plan` ADD COLUMN `expense_request_id` int(10) unsigned NOT NULL DEFAULT '0' AFTER `expense_request_legacy_id`"
    );
    ensureTableColumn(
        $pdo,
        $prefix . 'business_payment_plan',
        'expense_request_title',
        "ALTER TABLE `{$prefix}business_payment_plan` ADD COLUMN `expense_request_title` varchar(150) NOT NULL DEFAULT '' AFTER `expense_request_id`"
    );
    ensureTableColumn(
        $pdo,
        $prefix . 'business_payment_plan',
        'purchase_order_legacy_id',
        "ALTER TABLE `{$prefix}business_payment_plan` ADD COLUMN `purchase_order_legacy_id` varchar(64) NOT NULL DEFAULT '' AFTER `customer_name`"
    );
    ensureTableColumn(
        $pdo,
        $prefix . 'business_payment_plan',
        'purchase_order_id',
        "ALTER TABLE `{$prefix}business_payment_plan` ADD COLUMN `purchase_order_id` int(10) unsigned NOT NULL DEFAULT '0' AFTER `purchase_order_legacy_id`"
    );
    ensureTableColumn(
        $pdo,
        $prefix . 'business_payment_plan',
        'purchase_order_title',
        "ALTER TABLE `{$prefix}business_payment_plan` ADD COLUMN `purchase_order_title` varchar(150) NOT NULL DEFAULT '' AFTER `purchase_order_id`"
    );
    ensureTableColumn(
        $pdo,
        $approvalTable,
        'expense_request_legacy_id',
        "ALTER TABLE `{$approvalTable}` ADD COLUMN `expense_request_legacy_id` varchar(64) NOT NULL DEFAULT '' AFTER `payment_plan_title`"
    );
    ensureTableColumn(
        $pdo,
        $approvalTable,
        'expense_request_id',
        "ALTER TABLE `{$approvalTable}` ADD COLUMN `expense_request_id` int(10) unsigned NOT NULL DEFAULT '0' AFTER `expense_request_legacy_id`"
    );
    ensureTableColumn(
        $pdo,
        $approvalTable,
        'expense_request_title',
        "ALTER TABLE `{$approvalTable}` ADD COLUMN `expense_request_title` varchar(150) NOT NULL DEFAULT '' AFTER `expense_request_id`"
    );
    ensureTableColumn(
        $pdo,
        $approvalTable,
        'purchase_order_legacy_id',
        "ALTER TABLE `{$approvalTable}` ADD COLUMN `purchase_order_legacy_id` varchar(64) NOT NULL DEFAULT '' AFTER `expense_request_title`"
    );
    ensureTableColumn(
        $pdo,
        $approvalTable,
        'purchase_order_id',
        "ALTER TABLE `{$approvalTable}` ADD COLUMN `purchase_order_id` int(10) unsigned NOT NULL DEFAULT '0' AFTER `purchase_order_legacy_id`"
    );
    ensureTableColumn(
        $pdo,
        $approvalTable,
        'purchase_order_title',
        "ALTER TABLE `{$approvalTable}` ADD COLUMN `purchase_order_title` varchar(150) NOT NULL DEFAULT '' AFTER `purchase_order_id`"
    );
    ensureTableColumn(
        $pdo,
        $approvalTable,
        'payment_request_legacy_id',
        "ALTER TABLE `{$approvalTable}` ADD COLUMN `payment_request_legacy_id` varchar(64) NOT NULL DEFAULT '' AFTER `purchase_order_title`"
    );
    ensureTableColumn(
        $pdo,
        $approvalTable,
        'payment_request_id',
        "ALTER TABLE `{$approvalTable}` ADD COLUMN `payment_request_id` int(10) unsigned NOT NULL DEFAULT '0' AFTER `payment_request_legacy_id`"
    );
    ensureTableColumn(
        $pdo,
        $approvalTable,
        'payment_request_title',
        "ALTER TABLE `{$approvalTable}` ADD COLUMN `payment_request_title` varchar(150) NOT NULL DEFAULT '' AFTER `payment_request_id`"
    );
    ensureTableColumn(
        $pdo,
        $approvalTable,
        'template_legacy_id',
        "ALTER TABLE `{$approvalTable}` ADD COLUMN `template_legacy_id` varchar(64) NOT NULL DEFAULT '' AFTER `payment_request_title`"
    );
    ensureTableColumn(
        $pdo,
        $approvalTable,
        'template_id',
        "ALTER TABLE `{$approvalTable}` ADD COLUMN `template_id` int(10) unsigned NOT NULL DEFAULT '0' AFTER `template_legacy_id`"
    );
    ensureTableColumn(
        $pdo,
        $approvalTable,
        'template_name',
        "ALTER TABLE `{$approvalTable}` ADD COLUMN `template_name` varchar(150) NOT NULL DEFAULT '' AFTER `template_id`"
    );
    ensureTableColumn(
        $pdo,
        $approvalTable,
        'current_step',
        "ALTER TABLE `{$approvalTable}` ADD COLUMN `current_step` int(10) unsigned NOT NULL DEFAULT '1' AFTER `template_name`"
    );
    ensureTableColumn(
        $pdo,
        $approvalTable,
        'total_steps',
        "ALTER TABLE `{$approvalTable}` ADD COLUMN `total_steps` int(10) unsigned NOT NULL DEFAULT '1' AFTER `current_step`"
    );
    ensureTableColumn(
        $pdo,
        $approvalTable,
        'current_step_name',
        "ALTER TABLE `{$approvalTable}` ADD COLUMN `current_step_name` varchar(100) NOT NULL DEFAULT '' AFTER `total_steps`"
    );
    ensureTableColumn(
        $pdo,
        $approvalTable,
        'step_snapshot_json',
        "ALTER TABLE `{$approvalTable}` ADD COLUMN `step_snapshot_json` longtext AFTER `current_step_name`"
    );
    ensureTableColumn(
        $pdo,
        $approvalTable,
        'decision_log_json',
        "ALTER TABLE `{$approvalTable}` ADD COLUMN `decision_log_json` longtext AFTER `step_snapshot_json`"
    );
    ensureTableColumn(
        $pdo,
        $templateTable,
        'min_amount',
        "ALTER TABLE `{$templateTable}` ADD COLUMN `min_amount` decimal(12,2) NOT NULL DEFAULT '0.00' AFTER `is_default`"
    );
    ensureTableColumn(
        $pdo,
        $templateTable,
        'max_amount',
        "ALTER TABLE `{$templateTable}` ADD COLUMN `max_amount` decimal(12,2) NOT NULL DEFAULT '0.00' AFTER `min_amount`"
    );

    $pdo->exec("UPDATE `{$prefix}business_contract` SET `approval_status` = 'none' WHERE `approval_status` IS NULL OR `approval_status` = ''");
    $pdo->exec("UPDATE `{$prefix}business_payment_plan` SET `approval_status` = 'none' WHERE `approval_status` IS NULL OR `approval_status` = ''");
    $pdo->exec("UPDATE `{$prefix}business_expense_request` SET `approval_status` = 'none' WHERE `approval_status` IS NULL OR `approval_status` = ''");
    $pdo->exec("UPDATE `{$prefix}business_purchase_order` SET `approval_status` = 'none' WHERE `approval_status` IS NULL OR `approval_status` = ''");
    $pdo->exec("UPDATE `{$approvalTable}` SET `current_step` = 1 WHERE `current_step` IS NULL OR `current_step` <= 0");
    $pdo->exec("UPDATE `{$approvalTable}` SET `total_steps` = 1 WHERE `total_steps` IS NULL OR `total_steps` <= 0");
    $pdo->exec("UPDATE `{$approvalTable}` SET `current_step_name` = '人工审批' WHERE `current_step_name` IS NULL OR `current_step_name` = ''");
    $pdo->exec("UPDATE `{$approvalTable}` SET `decision_log_json` = '[]' WHERE `decision_log_json` IS NULL OR `decision_log_json` = ''");
}

function ensureBusinessPurchaseSettlementColumns(PDO $pdo, string $prefix): void
{
    ensureTableColumn(
        $pdo,
        $prefix . 'business_purchase_order',
        'settlement_id',
        "ALTER TABLE `{$prefix}business_purchase_order` ADD COLUMN `settlement_id` int(10) unsigned NOT NULL DEFAULT '0' AFTER `payment_plan_title`"
    );
    ensureTableColumn(
        $pdo,
        $prefix . 'business_purchase_order',
        'settlement_legacy_id',
        "ALTER TABLE `{$prefix}business_purchase_order` ADD COLUMN `settlement_legacy_id` varchar(64) NOT NULL DEFAULT '' AFTER `settlement_id`"
    );
    ensureTableColumn(
        $pdo,
        $prefix . 'business_purchase_order',
        'settlement_title',
        "ALTER TABLE `{$prefix}business_purchase_order` ADD COLUMN `settlement_title` varchar(150) NOT NULL DEFAULT '' AFTER `settlement_legacy_id`"
    );
}

function ensureBusinessPurchaseTraceColumns(PDO $pdo, string $prefix): void
{
    ensureTableColumn(
        $pdo,
        $prefix . 'business_purchase_order',
        'reconciliation_id',
        "ALTER TABLE `{$prefix}business_purchase_order` ADD COLUMN `reconciliation_id` int(10) unsigned NOT NULL DEFAULT '0' AFTER `payment_plan_title`"
    );
    ensureTableColumn(
        $pdo,
        $prefix . 'business_purchase_order',
        'reconciliation_legacy_id',
        "ALTER TABLE `{$prefix}business_purchase_order` ADD COLUMN `reconciliation_legacy_id` varchar(64) NOT NULL DEFAULT '' AFTER `reconciliation_id`"
    );
    ensureTableColumn(
        $pdo,
        $prefix . 'business_purchase_order',
        'reconciliation_title',
        "ALTER TABLE `{$prefix}business_purchase_order` ADD COLUMN `reconciliation_title` varchar(150) NOT NULL DEFAULT '' AFTER `reconciliation_legacy_id`"
    );
}

function ensureBusinessPaymentRequestColumns(PDO $pdo, string $prefix): void
{
    $table = $prefix . 'business_payment_request';

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `{$table}` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `legacy_id` varchar(64) NOT NULL DEFAULT '',
            `request_no` varchar(50) NOT NULL DEFAULT '',
            `title` varchar(150) NOT NULL DEFAULT '',
            `purchase_order_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `purchase_order_id` int(10) unsigned NOT NULL DEFAULT '0',
            `purchase_order_title` varchar(150) NOT NULL DEFAULT '',
            `settlement_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `settlement_id` int(10) unsigned NOT NULL DEFAULT '0',
            `settlement_title` varchar(150) NOT NULL DEFAULT '',
            `payment_plan_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `payment_plan_id` int(10) unsigned NOT NULL DEFAULT '0',
            `payment_plan_title` varchar(150) NOT NULL DEFAULT '',
            `supplier_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `supplier_id` int(10) unsigned NOT NULL DEFAULT '0',
            `supplier_name` varchar(150) NOT NULL DEFAULT '',
            `customer_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `customer_id` int(10) unsigned NOT NULL DEFAULT '0',
            `customer_name` varchar(150) NOT NULL DEFAULT '',
            `contract_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `contract_id` int(10) unsigned NOT NULL DEFAULT '0',
            `contract_name` varchar(150) NOT NULL DEFAULT '',
            `request_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
            `paid_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
            `requested_at` datetime DEFAULT NULL,
            `approved_at` datetime DEFAULT NULL,
            `paid_at` datetime DEFAULT NULL,
            `status` enum('draft','pending_approval','approved','paid','rejected','cancelled') NOT NULL DEFAULT 'draft',
            `approval_status` enum('none','pending','approved','rejected','cancelled') NOT NULL DEFAULT 'none',
            `approval_updated_at` datetime DEFAULT NULL,
            `owner` varchar(50) NOT NULL DEFAULT '',
            `owner_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `attachment_ids_json` text,
            `notes` text,
            `record_created_at` datetime DEFAULT NULL,
            `record_updated_at` datetime DEFAULT NULL,
            `created_by_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `created_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `created_by_name` varchar(50) NOT NULL DEFAULT '',
            `updated_by_legacy_id` varchar(64) NOT NULL DEFAULT '',
            `updated_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
            `updated_by_name` varchar(50) NOT NULL DEFAULT '',
            `createtime` bigint(16) DEFAULT NULL,
            `updatetime` bigint(16) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_business_payment_request_legacy_id` (`legacy_id`),
            UNIQUE KEY `uk_business_payment_request_request_no` (`request_no`),
            KEY `idx_business_payment_request_purchase_order_id` (`purchase_order_id`),
            KEY `idx_business_payment_request_settlement_id` (`settlement_id`),
            KEY `idx_business_payment_request_payment_plan_id` (`payment_plan_id`),
            KEY `idx_business_payment_request_supplier_id` (`supplier_id`),
            KEY `idx_business_payment_request_customer_id` (`customer_id`),
            KEY `idx_business_payment_request_contract_id` (`contract_id`),
            KEY `idx_business_payment_request_owner_admin_id` (`owner_admin_id`),
            KEY `idx_business_payment_request_status` (`status`),
            KEY `idx_business_payment_request_approval_status` (`approval_status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='付款申请'
    ");

    $pdo->exec("UPDATE `{$table}` SET `approval_status` = 'none' WHERE `approval_status` IS NULL OR `approval_status` = ''");
    $pdo->exec("UPDATE `{$table}` SET `attachment_ids_json` = '[]' WHERE `attachment_ids_json` IS NULL OR `attachment_ids_json` = ''");
}

function ensureTableColumn(PDO $pdo, string $table, string $column, string $sql): void
{
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
    $stmt->execute([$column]);
    if ($stmt->fetch()) {
        return;
    }

    $pdo->exec($sql);
}

function buildAuditPayload(array $actor, int $now): array
{
    return [
        'record_created_at' => date('Y-m-d H:i:s'),
        'record_updated_at' => date('Y-m-d H:i:s'),
        'created_by_legacy_id' => $actor['legacy_id'],
        'created_by_admin_id' => $actor['admin_id'],
        'created_by_name' => $actor['name'],
        'updated_by_legacy_id' => $actor['legacy_id'],
        'updated_by_admin_id' => $actor['admin_id'],
        'updated_by_name' => $actor['name'],
        'createtime' => $now,
        'updatetime' => $now,
    ];
}

function insertBusinessRow(PDO $pdo, string $table, array $row): void
{
    $fields = array_keys($row);
    $sql = "INSERT INTO {$table} (`" . implode('`,`', $fields) . "`) VALUES (:" . implode(',:', $fields) . ")";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($row);
}

function countTable(PDO $pdo, string $table): int
{
    return (int) $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
}
