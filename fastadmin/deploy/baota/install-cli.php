<?php

declare(strict_types=1);

use ErpDeploy\Baota\Installer;

$rootPath = dirname(__DIR__, 2);
require $rootPath . DIRECTORY_SEPARATOR . 'deploy' . DIRECTORY_SEPARATOR . 'baota' . DIRECTORY_SEPARATOR . 'Installer.php';

$options = getopt('', [
    'site-name::',
    'db-host::',
    'db-port::',
    'db-name::',
    'db-user::',
    'db-password::',
    'db-prefix::',
    'admin-username::',
    'admin-password::',
    'admin-email::',
    'install-mode::',
    'reset-tables::',
    'force-lock::',
]);

$payload = [
    'root_path' => $rootPath,
    'site_name' => $options['site-name'] ?? 'ERP AI 管理系统',
    'db_host' => $options['db-host'] ?? '127.0.0.1',
    'db_port' => $options['db-port'] ?? '3306',
    'db_name' => $options['db-name'] ?? 'bysat_erp',
    'db_user' => $options['db-user'] ?? 'root',
    'db_password' => $options['db-password'] ?? '',
    'db_prefix' => $options['db-prefix'] ?? 'fa_',
    'admin_username' => $options['admin-username'] ?? 'admin',
    'admin_password' => $options['admin-password'] ?? 'Admin@123',
    'admin_email' => $options['admin-email'] ?? 'admin@example.com',
    'install_mode' => $options['install-mode'] ?? 'clean',
    'reset_tables' => filter_var($options['reset-tables'] ?? '1', FILTER_VALIDATE_BOOL),
    'force_lock' => filter_var($options['force-lock'] ?? '0', FILTER_VALIDATE_BOOL),
];

try {
    $result = Installer::install($payload);
    fwrite(STDOUT, json_encode([
        'ok' => true,
        'result' => $result,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL);
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, json_encode([
        'ok' => false,
        'error' => $exception->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL);
    exit(1);
}
