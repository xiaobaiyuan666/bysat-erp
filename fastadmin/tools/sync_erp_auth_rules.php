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

$now = time();

$menuNodes = [
    ['name' => 'dashboard', 'parent' => null, 'title' => 'AI 指挥台', 'icon' => 'fa fa-dashboard', 'remark' => '企业 ERP AI 智能管理首页。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 120],
    ['name' => 'ai', 'parent' => null, 'title' => 'AI 中枢', 'icon' => 'fa fa-comments-o', 'remark' => 'AI 工作台和模型配置。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 115],
    ['name' => 'ai/conversation', 'parent' => 'ai', 'title' => 'AI 工作台', 'icon' => 'fa fa-comments-o', 'remark' => '结合财务、项目、客户和项目运营数据做分析。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 20],
    ['name' => 'ai/setting', 'parent' => 'ai', 'title' => 'AI 配置', 'icon' => 'fa fa-sliders', 'remark' => '维护模型配置和联通测试。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 10],

    ['name' => 'finance', 'parent' => null, 'title' => '财务中心', 'icon' => 'fa fa-rmb', 'remark' => '流水、应收应付和财务工作台。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 110],
    ['name' => 'finance/workbench', 'parent' => 'finance', 'title' => '财务工作台', 'icon' => 'fa fa-dashboard', 'remark' => '优先处理回款、付款和附件补传。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 40],
    ['name' => 'finance/transaction', 'parent' => 'finance', 'title' => '资金流水', 'icon' => 'fa fa-exchange', 'remark' => '记录收入、支出和资金往来。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 30],
    ['name' => 'finance/invoice', 'parent' => 'finance', 'title' => '应收应付', 'icon' => 'fa fa-file-text-o', 'remark' => '统一跟进回款、付款和逾期单据。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 20],

    ['name' => 'business', 'parent' => null, 'title' => '客户与合同', 'icon' => 'fa fa-handshake-o', 'remark' => '客户、采购、合同与审批主线。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 105],
    ['name' => 'business/workbench', 'parent' => 'business', 'title' => '业务工作台', 'icon' => 'fa fa-dashboard', 'remark' => '集中处理客户、采购和付款审批。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 28],
    ['name' => 'business/customer', 'parent' => 'business', 'title' => '客户档案', 'icon' => 'fa fa-address-book-o', 'remark' => '维护客户资料和负责人。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 30],
    ['name' => 'business/customer_followup', 'parent' => 'business', 'title' => '客户跟进', 'icon' => 'fa fa-commenting-o', 'remark' => '记录沟通结果、下次跟进和回款推进。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 25],
    ['name' => 'business/supplier', 'parent' => 'business', 'title' => '供应商档案', 'icon' => 'fa fa-truck', 'remark' => '维护供应商资料和结算信息。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 24],
    ['name' => 'business/purchase_order', 'parent' => 'business', 'title' => '采购单', 'icon' => 'fa fa-shopping-cart', 'remark' => '管理采购、外包和付款联动。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 23],
    ['name' => 'business/purchase_reconciliation', 'parent' => 'business', 'title' => '采购对账', 'icon' => 'fa fa-random', 'remark' => '核对采购金额和供应商对账结果。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 22],
    ['name' => 'business/purchase_settlement', 'parent' => 'business', 'title' => '采购结算', 'icon' => 'fa fa-balance-scale', 'remark' => '跟进采购对账、结算和票据状态。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 21],
    ['name' => 'business/purchase_invoice', 'parent' => 'business', 'title' => '采购发票', 'icon' => 'fa fa-file-text', 'remark' => '登记到票、验票和附件。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 20],
    ['name' => 'business/contract', 'parent' => 'business', 'title' => '合同台账', 'icon' => 'fa fa-file-text-o', 'remark' => '管理合同金额、状态和关联项目。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 19],
    ['name' => 'business/approval', 'parent' => 'business', 'title' => '审批中心', 'icon' => 'fa fa-check-square-o', 'remark' => '统一处理合同、费用、采购和付款审批。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 18],
    ['name' => 'business/approval_template', 'parent' => 'business', 'title' => '审批模板', 'icon' => 'fa fa-sitemap', 'remark' => '配置多级审批模板和审批节点。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 17],
    ['name' => 'business/receivable_plan', 'parent' => 'business', 'title' => '回款计划', 'icon' => 'fa fa-calendar-check-o', 'remark' => '跟进合同回款节点和到账情况。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 12],
    ['name' => 'business/expense_request', 'parent' => 'business', 'title' => '费用申请', 'icon' => 'fa fa-money', 'remark' => '提交采购、投放、外包等费用申请。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 8],
    ['name' => 'business/payment_request', 'parent' => 'business', 'title' => '付款申请', 'icon' => 'fa fa-credit-card-alt', 'remark' => '处理采购结算后的付款申请。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 7],
    ['name' => 'business/payment_plan', 'parent' => 'business', 'title' => '付款计划', 'icon' => 'fa fa-credit-card', 'remark' => '统一跟踪合同成本、供应商和费用付款。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 5],

    ['name' => 'project', 'parent' => null, 'title' => '项目交付', 'icon' => 'fa fa-briefcase', 'remark' => '项目台账和任务清单。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 100],
    ['name' => 'project/workbench', 'parent' => 'project', 'title' => '项目工作台', 'icon' => 'fa fa-dashboard', 'remark' => '优先处理逾期、阻塞和待验收任务。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 40],
    ['name' => 'project/project', 'parent' => 'project', 'title' => '项目台账', 'icon' => 'fa fa-folder-open', 'remark' => '查看项目状态、负责人和交付进度。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 30],
    ['name' => 'project/task', 'parent' => 'project', 'title' => '任务清单', 'icon' => 'fa fa-tasks', 'remark' => '查看逾期、进行中和阻塞任务。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 20],

    ['name' => 'app', 'parent' => null, 'title' => '项目运营', 'icon' => 'fa fa-mobile', 'remark' => '问题、研发联动、发版和资料中心。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 90],
    ['name' => 'app/workbench', 'parent' => 'app', 'title' => '项目运营工作台', 'icon' => 'fa fa-dashboard', 'remark' => '问题记录、研发联动、版本发布统一入口。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 80],
    ['name' => 'app/project', 'parent' => 'app', 'title' => '项目台账', 'icon' => 'fa fa-table', 'remark' => '查看项目生命周期、版本和负责人。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 70],
    ['name' => 'app/issue', 'parent' => 'app', 'title' => '问题记录', 'icon' => 'fa fa-bug', 'remark' => '客服反馈、Bug 和产品意见统一收口。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 60],
    ['name' => 'app/issue_followup', 'parent' => 'app', 'title' => '问题跟进', 'icon' => 'fa fa-commenting-o', 'remark' => '记录问题处理过程和客户回告。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 55],
    ['name' => 'app/tech_ticket', 'parent' => 'app', 'title' => '研发联动', 'icon' => 'fa fa-code-fork', 'remark' => 'Bug、升级和优化需求统一流转。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 50],
    ['name' => 'app/release', 'parent' => 'app', 'title' => '版本发布', 'icon' => 'fa fa-rocket', 'remark' => '测试、发布、回滚和客户回告。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 40],
    ['name' => 'app/material', 'parent' => 'app', 'title' => '内部资料', 'icon' => 'fa fa-folder-open-o', 'remark' => '资料下载、适用版本和归档状态。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 35],
    ['name' => 'app/milestone', 'parent' => 'app', 'title' => '里程碑', 'icon' => 'fa fa-flag-checkered', 'remark' => '维护关键节点和交付节奏。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 30],
    ['name' => 'app/report', 'parent' => 'app', 'title' => '项目汇报', 'icon' => 'fa fa-line-chart', 'remark' => '记录项目推进、阶段总结和下步动作。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 20],
    ['name' => 'app/risk', 'parent' => 'app', 'title' => '风险问题', 'icon' => 'fa fa-exclamation-triangle', 'remark' => '统一跟踪运营风险和异常。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 10],

    ['name' => 'staff', 'parent' => null, 'title' => '人员与权限', 'icon' => 'fa fa-users', 'remark' => '员工档案、操作日志和权限管理。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 70],
    ['name' => 'staff/profile', 'parent' => 'staff', 'title' => '员工档案', 'icon' => 'fa fa-id-card-o', 'remark' => '维护员工账号、岗位、部门和权限组。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 20],
    ['name' => 'staff/audit', 'parent' => 'staff', 'title' => '操作日志', 'icon' => 'fa fa-history', 'remark' => '查看谁新增、修改和删除了什么。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 10],

    ['name' => 'general', 'parent' => null, 'title' => '系统设置', 'icon' => 'fa fa-cogs', 'remark' => '系统展示、模块开关、在线更新和附件管理。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 60],
    ['name' => 'general/config', 'parent' => 'general', 'title' => '基础设置', 'icon' => 'fa fa-cog', 'remark' => '维护系统名称、Logo、官网入口和登录页展示。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 30],
    ['name' => 'general/module', 'parent' => 'general', 'title' => '模块中心', 'icon' => 'fa fa-puzzle-piece', 'remark' => '控制项目运营等业务开关。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 20],
    ['name' => 'general/upgrade', 'parent' => 'general', 'title' => '在线更新', 'icon' => 'fa fa-cloud-download', 'remark' => '检查 GitHub 更新、自动备份并执行在线更新。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 15],
    ['name' => 'general/attachment', 'parent' => 'general', 'title' => '附件中心', 'icon' => 'fa fa-paperclip', 'remark' => '查看票据、图片、附件和上传记录。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 10],

    ['name' => 'auth', 'parent' => null, 'title' => '系统权限', 'icon' => 'fa fa-lock', 'remark' => '后台账号、权限组和规则节点。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 50],
    ['name' => 'auth/admin', 'parent' => 'auth', 'title' => '后台账号', 'icon' => 'fa fa-user-secret', 'remark' => '维护后台登录账号和所属权限组。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 40],
    ['name' => 'auth/adminlog', 'parent' => 'auth', 'title' => '登录日志', 'icon' => 'fa fa-sign-in', 'remark' => '查看后台登录和访问轨迹。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 30],
    ['name' => 'auth/group', 'parent' => 'auth', 'title' => '权限组', 'icon' => 'fa fa-object-group', 'remark' => '按岗位维护模块权限。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 20],
    ['name' => 'auth/rule', 'parent' => 'auth', 'title' => '规则节点', 'icon' => 'fa fa-list-alt', 'remark' => '查看系统菜单和权限节点。', 'ismenu' => 1, 'type' => 'menu', 'weigh' => 10],
];

$resourceSets = [
    ['name' => 'dashboard', 'parent' => 'dashboard', 'actions' => viewActions('AI 指挥台')],
    ['name' => 'ai/conversation', 'parent' => 'ai/conversation', 'actions' => aiConversationActions()],
    ['name' => 'ai/setting', 'parent' => 'ai/setting', 'actions' => aiSettingActions()],
    ['name' => 'finance/workbench', 'parent' => 'finance/workbench', 'actions' => financeWorkbenchActions()],
    ['name' => 'finance/transaction', 'parent' => 'finance/transaction', 'actions' => financeTransactionActions()],
    ['name' => 'finance/invoice', 'parent' => 'finance/invoice', 'actions' => financeInvoiceActions()],
    ['name' => 'business/workbench', 'parent' => 'business/workbench', 'actions' => viewActions('业务工作台')],
    ['name' => 'business/customer', 'parent' => 'business/customer', 'actions' => crudActions('客户档案')],
    ['name' => 'business/customer_followup', 'parent' => 'business/customer_followup', 'actions' => crudActions('客户跟进')],
    ['name' => 'business/supplier', 'parent' => 'business/supplier', 'actions' => crudActions('供应商档案')],
    ['name' => 'business/purchase_order', 'parent' => 'business/purchase_order', 'actions' => purchaseOrderActions()],
    ['name' => 'business/purchase_reconciliation', 'parent' => 'business/purchase_reconciliation', 'actions' => crudActions('采购对账')],
    ['name' => 'business/purchase_settlement', 'parent' => 'business/purchase_settlement', 'actions' => crudActions('采购结算')],
    ['name' => 'business/purchase_invoice', 'parent' => 'business/purchase_invoice', 'actions' => crudActions('采购发票')],
    ['name' => 'business/contract', 'parent' => 'business/contract', 'actions' => crudActions('合同台账')],
    ['name' => 'business/approval', 'parent' => 'business/approval', 'actions' => approvalActions()],
    ['name' => 'business/approval_template', 'parent' => 'business/approval_template', 'actions' => crudActions('审批模板')],
    ['name' => 'business/approval_template_step', 'parent' => 'business/approval_template', 'actions' => crudActions('审批模板节点')],
    ['name' => 'business/receivable_plan', 'parent' => 'business/receivable_plan', 'actions' => crudActions('回款计划')],
    ['name' => 'business/expense_request', 'parent' => 'business/expense_request', 'actions' => expenseRequestActions()],
    ['name' => 'business/payment_request', 'parent' => 'business/payment_request', 'actions' => paymentRequestActions()],
    ['name' => 'business/payment_plan', 'parent' => 'business/payment_plan', 'actions' => crudActions('付款计划')],
    ['name' => 'project/workbench', 'parent' => 'project/workbench', 'actions' => viewActions('项目工作台')],
    ['name' => 'project/project', 'parent' => 'project/project', 'actions' => crudActions('项目台账')],
    ['name' => 'project/task', 'parent' => 'project/task', 'actions' => crudActions('任务清单')],
    ['name' => 'app/workbench', 'parent' => 'app/workbench', 'actions' => viewActions('项目运营工作台')],
    ['name' => 'app/project', 'parent' => 'app/project', 'actions' => crudActions('项目台账')],
    ['name' => 'app/issue', 'parent' => 'app/issue', 'actions' => crudActions('问题记录')],
    ['name' => 'app/issue_followup', 'parent' => 'app/issue_followup', 'actions' => crudActions('问题跟进')],
    ['name' => 'app/tech_ticket', 'parent' => 'app/tech_ticket', 'actions' => crudActions('研发联动')],
    ['name' => 'app/release', 'parent' => 'app/release', 'actions' => crudActions('版本发布')],
    ['name' => 'app/material', 'parent' => 'app/material', 'actions' => crudActions('内部资料')],
    ['name' => 'app/milestone', 'parent' => 'app/milestone', 'actions' => crudActions('里程碑')],
    ['name' => 'app/report', 'parent' => 'app/report', 'actions' => crudActions('项目汇报')],
    ['name' => 'app/risk', 'parent' => 'app/risk', 'actions' => crudActions('风险问题')],
    ['name' => 'staff/profile', 'parent' => 'staff/profile', 'actions' => crudActions('员工档案')],
    ['name' => 'staff/audit', 'parent' => 'staff/audit', 'actions' => auditActions()],
    ['name' => 'general/config', 'parent' => 'general/config', 'actions' => viewActions('基础设置')],
    ['name' => 'general/module', 'parent' => 'general/module', 'actions' => moduleActions()],
    ['name' => 'general/upgrade', 'parent' => 'general/upgrade', 'actions' => upgradeActions()],
    ['name' => 'general/attachment', 'parent' => 'general/attachment', 'actions' => attachmentActions()],
    ['name' => 'auth/admin', 'parent' => 'auth/admin', 'actions' => adminActions()],
    ['name' => 'auth/adminlog', 'parent' => 'auth/adminlog', 'actions' => adminLogActions()],
    ['name' => 'auth/group', 'parent' => 'auth/group', 'actions' => groupActions()],
    ['name' => 'auth/rule', 'parent' => 'auth/rule', 'actions' => ruleActions()],
];

$hiddenMenuNames = [
    'addon',
    'category',
    'general/profile',
    'app/issue_followup',
    'app/milestone',
    'app/report',
    'app/risk',
    'user',
    'user/user',
    'user/group',
    'user/rule',
];

$pdo->beginTransaction();

try {
    foreach ($menuNodes as $node) {
        upsertRule($pdo, $prefix, $node, $now);
    }

    foreach ($resourceSets as $resource) {
        foreach ($resource['actions'] as $action => $meta) {
            upsertRule($pdo, $prefix, [
                'parent' => $resource['parent'],
                'name' => $resource['name'] . '/' . $action,
                'title' => $meta['title'],
                'remark' => $meta['remark'],
                'icon' => '',
                'ismenu' => 0,
                'type' => 'file',
                'weigh' => 0,
            ], $now);
        }
    }

    foreach ($hiddenMenuNames as $name) {
        $stmt = $pdo->prepare("UPDATE {$prefix}auth_rule SET ismenu = 0, updatetime = ? WHERE name = ?");
        $stmt->execute([$now, $name]);
    }

    syncGroupRules($pdo, $prefix, $now);
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}

clearCacheDirectories($rootPath . DIRECTORY_SEPARATOR . 'runtime');

$ruleCount = (int) $pdo->query("SELECT COUNT(*) FROM {$prefix}auth_rule")->fetchColumn();
echo 'ERP 权限菜单同步完成，当前规则数：' . $ruleCount . PHP_EOL;

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

function viewActions(string $label): array
{
    return [
        'index' => ['title' => '查看', 'remark' => '查看' . $label],
    ];
}

function crudActions(string $label): array
{
    return [
        'index' => ['title' => '查看', 'remark' => '查看' . $label],
        'add' => ['title' => '新增', 'remark' => '新增' . $label],
        'edit' => ['title' => '编辑', 'remark' => '编辑' . $label],
        'del' => ['title' => '删除', 'remark' => '删除' . $label],
        'multi' => ['title' => '批量操作', 'remark' => '批量处理' . $label],
        'selectpage' => ['title' => '选择数据', 'remark' => $label . '下拉选择'],
    ];
}

function purchaseOrderActions(): array
{
    $actions = crudActions('采购单');
    $actions['createpaymentplan'] = ['title' => '生成付款计划', 'remark' => '根据采购单生成付款计划'];

    return $actions;
}

function expenseRequestActions(): array
{
    $actions = crudActions('费用申请');
    $actions['createpaymentplan'] = ['title' => '生成付款计划', 'remark' => '根据费用申请生成付款计划'];

    return $actions;
}

function paymentRequestActions(): array
{
    $actions = crudActions('付款申请');
    $actions['markpaid'] = ['title' => '标记已付款', 'remark' => '将审批通过的付款申请标记为已付款'];

    return $actions;
}

function approvalActions(): array
{
    $actions = crudActions('审批中心');
    $actions['approve'] = ['title' => '审批通过', 'remark' => '通过审批'];
    $actions['reject'] = ['title' => '驳回审批', 'remark' => '驳回审批'];
    $actions['cancel'] = ['title' => '撤销审批', 'remark' => '撤销审批'];

    return $actions;
}

function financeWorkbenchActions(): array
{
    $actions = viewActions('财务工作台');
    $actions['smartbookbootstrap'] = ['title' => '智能记账初始化', 'remark' => '获取智能记账初始化数据'];
    $actions['smartbook'] = ['title' => '智能记账解析', 'remark' => '解析一句话记账内容'];
    $actions['smartbooksave'] = ['title' => '智能记账保存', 'remark' => '将智能记账草稿写入系统'];
    $actions['reportprint'] = ['title' => '打印报表', 'remark' => '打印财务汇总报表'];
    $actions['reportexport'] = ['title' => '导出报表', 'remark' => '导出财务统计 CSV'];

    return $actions;
}

function financeTransactionActions(): array
{
    $actions = crudActions('资金流水');
    $actions['printview'] = ['title' => '打印预览', 'remark' => '资金流水打印预览'];

    return $actions;
}

function financeInvoiceActions(): array
{
    $actions = crudActions('应收应付');
    $actions['printview'] = ['title' => '打印预览', 'remark' => '应收应付账单打印预览'];

    return $actions;
}

function aiConversationActions(): array
{
    $actions = viewActions('AI 工作台');
    $actions['bootstrap'] = ['title' => '工作台初始化', 'remark' => '获取 AI 工作台初始化数据'];
    $actions['ask'] = ['title' => '发送提问', 'remark' => '向 AI 发起提问'];
    $actions['submit'] = ['title' => '提交后台任务', 'remark' => '提交 AI 后台分析任务'];
    $actions['run'] = ['title' => '执行后台任务', 'remark' => '执行 AI 后台分析任务'];
    $actions['status'] = ['title' => '查询任务状态', 'remark' => '查询 AI 后台任务状态'];
    $actions['clear'] = ['title' => '清空会话', 'remark' => '清空 AI 会话记录'];

    return $actions;
}

function aiSettingActions(): array
{
    $actions = crudActions('AI 配置');
    $actions['setdefault'] = ['title' => '设为默认', 'remark' => '将模型设为默认'];
    $actions['ping'] = ['title' => '测试连接', 'remark' => '测试模型连通性'];
    $actions['applyrecommended'] = ['title' => '应用推荐模型', 'remark' => '切换到推荐模型'];
    $actions['discover'] = ['title' => '探测模型', 'remark' => '识别协议并加载模型列表'];

    return $actions;
}

function attachmentActions(): array
{
    return [
        'index' => ['title' => '查看', 'remark' => '查看附件中心'],
        'select' => ['title' => '选择', 'remark' => '选择附件'],
        'add' => ['title' => '上传', 'remark' => '上传附件'],
        'del' => ['title' => '删除', 'remark' => '删除附件'],
        'classify' => ['title' => '分类', 'remark' => '附件分类'],
    ];
}

function moduleActions(): array
{
    return [
        'index' => ['title' => '查看', 'remark' => '查看模块中心'],
        'save' => ['title' => '保存开关', 'remark' => '保存模块开关配置'],
    ];
}

function upgradeActions(): array
{
    return [
        'index' => ['title' => '查看', 'remark' => '查看在线更新中心'],
        'overview' => ['title' => '总览', 'remark' => '获取在线更新总览数据'],
        'saveconfig' => ['title' => '保存配置', 'remark' => '保存 GitHub 更新源配置'],
        'checkupdate' => ['title' => '检查更新', 'remark' => '检查 GitHub 是否有新版本'],
        'startupdate' => ['title' => '执行更新', 'remark' => '创建备份后应用在线更新'],
        'rollback' => ['title' => '回滚', 'remark' => '从更新备份回滚系统文件和数据库'],
    ];
}

function auditActions(): array
{
    return [
        'index' => ['title' => '查看', 'remark' => '查看操作日志'],
        'add' => ['title' => '新增', 'remark' => '新增日志记录'],
        'edit' => ['title' => '编辑', 'remark' => '编辑日志记录'],
        'del' => ['title' => '删除', 'remark' => '删除日志记录'],
        'multi' => ['title' => '批量操作', 'remark' => '批量处理日志记录'],
    ];
}

function adminActions(): array
{
    return [
        'index' => ['title' => '查看', 'remark' => '查看后台账号'],
        'add' => ['title' => '新增', 'remark' => '新增后台账号'],
        'edit' => ['title' => '编辑', 'remark' => '编辑后台账号'],
        'del' => ['title' => '删除', 'remark' => '删除后台账号'],
        'multi' => ['title' => '批量操作', 'remark' => '批量处理后台账号'],
    ];
}

function adminLogActions(): array
{
    return [
        'index' => ['title' => '查看', 'remark' => '查看登录日志'],
        'detail' => ['title' => '详情', 'remark' => '查看登录日志详情'],
        'add' => ['title' => '新增', 'remark' => '新增登录日志'],
        'edit' => ['title' => '编辑', 'remark' => '编辑登录日志'],
        'del' => ['title' => '删除', 'remark' => '删除登录日志'],
        'multi' => ['title' => '批量操作', 'remark' => '批量处理登录日志'],
    ];
}

function groupActions(): array
{
    return [
        'index' => ['title' => '查看', 'remark' => '查看权限组'],
        'add' => ['title' => '新增', 'remark' => '新增权限组'],
        'edit' => ['title' => '编辑', 'remark' => '编辑权限组'],
        'del' => ['title' => '删除', 'remark' => '删除权限组'],
        'multi' => ['title' => '批量操作', 'remark' => '批量处理权限组'],
    ];
}

function ruleActions(): array
{
    return [
        'index' => ['title' => '查看', 'remark' => '查看规则节点'],
        'add' => ['title' => '新增', 'remark' => '新增规则节点'],
        'edit' => ['title' => '编辑', 'remark' => '编辑规则节点'],
        'del' => ['title' => '删除', 'remark' => '删除规则节点'],
        'dragsort' => ['title' => '拖拽排序', 'remark' => '调整规则节点排序'],
    ];
}

function upsertRule(PDO $pdo, string $prefix, array $node, int $now): void
{
    $parentId = 0;
    if (!empty($node['parent'])) {
        $parentId = findRuleId($pdo, $prefix, (string) $node['parent']);
    }

    $existing = findRule($pdo, $prefix, (string) $node['name']);
    $payload = [
        'pid' => $parentId,
        'name' => (string) $node['name'],
        'title' => (string) ($node['title'] ?? ''),
        'icon' => (string) ($node['icon'] ?? ''),
        'condition' => '',
        'remark' => (string) ($node['remark'] ?? ''),
        'ismenu' => (int) ($node['ismenu'] ?? 0),
        'type' => (string) ($node['type'] ?? 'file'),
        'status' => 'normal',
        'weigh' => (int) ($node['weigh'] ?? 0),
        'updatetime' => $now,
    ];

    if ($existing) {
        $sql = "UPDATE {$prefix}auth_rule SET `pid`=:pid,`title`=:title,`icon`=:icon,`condition`=:condition,`remark`=:remark,`ismenu`=:ismenu,`type`=:type,`status`=:status,`weigh`=:weigh,`updatetime`=:updatetime WHERE `id`=:id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'pid' => $payload['pid'],
            'title' => $payload['title'],
            'icon' => $payload['icon'],
            'condition' => $payload['condition'],
            'remark' => $payload['remark'],
            'ismenu' => $payload['ismenu'],
            'type' => $payload['type'],
            'status' => $payload['status'],
            'weigh' => $payload['weigh'],
            'updatetime' => $payload['updatetime'],
            'id' => (int) $existing['id'],
        ]);

        return;
    }

    $sql = "INSERT INTO {$prefix}auth_rule (`pid`,`name`,`title`,`icon`,`condition`,`remark`,`ismenu`,`type`,`status`,`weigh`,`createtime`,`updatetime`) VALUES (:pid,:name,:title,:icon,:condition,:remark,:ismenu,:type,:status,:weigh,:createtime,:updatetime)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'pid' => $payload['pid'],
        'name' => $payload['name'],
        'title' => $payload['title'],
        'icon' => $payload['icon'],
        'condition' => $payload['condition'],
        'remark' => $payload['remark'],
        'ismenu' => $payload['ismenu'],
        'type' => $payload['type'],
        'status' => $payload['status'],
        'weigh' => $payload['weigh'],
        'createtime' => $now,
        'updatetime' => $payload['updatetime'],
    ]);
}

function findRule(PDO $pdo, string $prefix, string $name): ?array
{
    $stmt = $pdo->prepare("SELECT id,name FROM {$prefix}auth_rule WHERE name = ? LIMIT 1");
    $stmt->execute([$name]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function findRuleId(PDO $pdo, string $prefix, string $name): int
{
    $row = findRule($pdo, $prefix, $name);

    return $row ? (int) $row['id'] : 0;
}

function syncGroupRules(PDO $pdo, string $prefix, int $now): void
{
    $nameToId = [];
    foreach ($pdo->query("SELECT id,name FROM {$prefix}auth_rule") as $row) {
        $nameToId[(string) $row['name']] = (int) $row['id'];
    }

    $groupPatterns = [
        'ERP 财务组' => ['dashboard', 'dashboard/index', 'ai', 'ai/conversation/*', 'finance', 'finance/*', 'business', 'business/*', 'general', 'general/attachment/*'],
        'ERP 项目组' => ['dashboard', 'dashboard/index', 'ai', 'ai/conversation/*', 'project', 'project/*', 'business', 'business/*', 'general', 'general/attachment/*'],
        'ERP 运营组' => ['dashboard', 'dashboard/index', 'ai', 'ai/conversation/*', 'business', 'business/*', 'app', 'app/*', 'general', 'general/attachment/*'],
        'ERP 客服组' => ['dashboard', 'dashboard/index', 'ai', 'ai/conversation/*', 'app', 'app/workbench*', 'app/project*', 'app/issue*', 'app/issue_followup*', 'app/material*', 'app/release', 'app/release/index', 'app/release/edit', 'general', 'general/attachment/*'],
        'ERP 技术组' => ['dashboard', 'dashboard/index', 'ai', 'ai/conversation/*', 'app', 'app/workbench*', 'app/issue*', 'app/issue_followup*', 'app/tech_ticket*', 'app/release*', 'project', 'project/workbench*', 'project/task*'],
        'ERP 公司组' => ['dashboard', 'dashboard/index', 'ai', 'ai/*', 'finance', 'finance/*', 'business', 'business/*', 'project', 'project/*', 'app', 'app/*', 'staff', 'staff/*', 'general', 'general/*', 'auth', 'auth/*'],
        'ERP 只读组' => [
            'dashboard', 'dashboard/index',
            'ai', 'ai/conversation', 'ai/conversation/index',
            'finance', 'finance/workbench', 'finance/workbench/index', 'finance/transaction', 'finance/transaction/index', 'finance/invoice', 'finance/invoice/index',
            'business', 'business/workbench', 'business/workbench/index', 'business/customer', 'business/customer/index', 'business/customer_followup', 'business/customer_followup/index', 'business/supplier', 'business/supplier/index', 'business/purchase_order', 'business/purchase_order/index', 'business/purchase_reconciliation', 'business/purchase_reconciliation/index', 'business/purchase_settlement', 'business/purchase_settlement/index', 'business/purchase_invoice', 'business/purchase_invoice/index', 'business/contract', 'business/contract/index', 'business/approval', 'business/approval/index', 'business/receivable_plan', 'business/receivable_plan/index', 'business/expense_request', 'business/expense_request/index', 'business/payment_request', 'business/payment_request/index', 'business/payment_plan', 'business/payment_plan/index',
            'project', 'project/workbench', 'project/workbench/index', 'project/project', 'project/project/index', 'project/task', 'project/task/index',
            'app', 'app/workbench', 'app/workbench/index', 'app/project', 'app/project/index', 'app/issue', 'app/issue/index', 'app/issue_followup', 'app/issue_followup/index', 'app/tech_ticket', 'app/tech_ticket/index', 'app/release', 'app/release/index', 'app/material', 'app/material/index', 'app/milestone', 'app/milestone/index', 'app/report', 'app/report/index', 'app/risk', 'app/risk/index',
            'general', 'general/config', 'general/config/index', 'general/attachment', 'general/attachment/index',
        ],
    ];

    foreach ($groupPatterns as $groupName => $patterns) {
        ensureAuthGroup($pdo, $prefix, $groupName, $now);
        $ids = resolveRuleIds($patterns, $nameToId);
        if (empty($ids)) {
            continue;
        }

        $stmt = $pdo->prepare("UPDATE {$prefix}auth_group SET rules = ?, updatetime = ?, status = 'normal' WHERE name = ?");
        $stmt->execute([implode(',', $ids), $now, $groupName]);
    }
}

function ensureAuthGroup(PDO $pdo, string $prefix, string $groupName, int $now): void
{
    $stmt = $pdo->prepare("SELECT id FROM {$prefix}auth_group WHERE name = ? LIMIT 1");
    $stmt->execute([$groupName]);
    $row = $stmt->fetch();
    if ($row) {
        return;
    }

    $insert = $pdo->prepare("INSERT INTO {$prefix}auth_group (`pid`,`name`,`rules`,`createtime`,`updatetime`,`status`) VALUES (0, ?, '', ?, ?, 'normal')");
    $insert->execute([$groupName, $now, $now]);
}

function resolveRuleIds(array $patterns, array $nameToId): array
{
    $ids = [];
    foreach ($patterns as $pattern) {
        if (strpos($pattern, '*') === false) {
            if (isset($nameToId[$pattern])) {
                $ids[] = $nameToId[$pattern];
            }
            continue;
        }

        $regex = '/^' . str_replace('\*', '.*', preg_quote($pattern, '/')) . '$/';
        foreach ($nameToId as $name => $id) {
            if (preg_match($regex, $name)) {
                $ids[] = $id;
            }
        }
    }

    $ids = array_values(array_unique(array_map('intval', $ids)));
    sort($ids);

    return $ids;
}

function clearCacheDirectories(string $runtimePath): void
{
    $targets = [
        $runtimePath . DIRECTORY_SEPARATOR . 'cache',
        $runtimePath . DIRECTORY_SEPARATOR . 'temp',
    ];

    foreach ($targets as $path) {
        if (!is_dir($path)) {
            continue;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }
    }
}
