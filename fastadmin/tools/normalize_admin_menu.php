<?php

$pdo = new PDO("mysql:host=127.0.0.1;port=3307;dbname=fastadmin_erp;charset=utf8mb4", "root", "root");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$updates = [
    'dashboard'           => '经营驾驶舱',
    'finance'             => '财务中心',
    'finance/transaction' => '资金流水',
    'finance/invoice'     => '应收应付',
    'project'             => '项目交付',
    'project/project'     => '项目台账',
    'project/task'        => '任务清单',
    'app'                 => '项目运营',
    'app/project'         => '项目台账',
    'app/issue'           => '问题记录',
    'app/issue_followup'  => '问题跟进',
    'app/tech_ticket'     => '研发联动',
    'app/release'         => '版本发布',
    'app/material'        => '内部资料',
    'app/milestone'       => '里程碑',
    'app/report'          => '项目汇报',
    'app/risk'            => '风险问题',
    'ai'                  => 'AI 助手',
    'ai/setting'          => '模型配置',
    'ai/conversation'     => 'AI 工作台',
    'general'             => '系统资料',
    'general/attachment'  => '附件中心',
    'staff'               => '人员与权限',
    'staff/profile'       => '员工档案',
    'staff/audit'         => '操作日志',
    'auth'                => '系统权限',
    'auth/admin'          => '后台账号',
    'auth/group'          => '权限组',
    'auth/rule'           => '规则节点',
    'auth/adminlog'       => '登录日志',
];

$stmt = $pdo->prepare('UPDATE fa_auth_rule SET title = ? WHERE name = ?');
$changed = [];
foreach ($updates as $name => $title) {
    $stmt->execute([$title, $name]);
    $changed[] = [$name, $title, $stmt->rowCount()];
}

foreach ($changed as $item) {
    echo $item[0] . ' => ' . $item[1] . ' (updated ' . $item[2] . ')' . PHP_EOL;
}
