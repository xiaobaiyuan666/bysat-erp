<?php

declare(strict_types=1);

$rootPath = dirname(__DIR__);
$env = parseSimpleEnv($rootPath . DIRECTORY_SEPARATOR . '.env');

$host = $env['database.hostname'] ?? $env['hostname'] ?? '127.0.0.1';
$port = $env['database.hostport'] ?? $env['hostport'] ?? '3306';
$database = $env['database.database'] ?? $env['database'] ?? 'fastadmin';
$username = $env['database.username'] ?? $env['username'] ?? 'root';
$password = $env['database.password'] ?? $env['password'] ?? '';
$prefix = $env['database.prefix'] ?? $env['prefix'] ?? 'fa_';

$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database),
    $username,
    $password,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

$configs = [
    ['name', 'basic', '系统名称', '登录页、后台标题和打印页使用的系统名称', 'ERP AI 管理系统', 'required'],
    ['beian', 'basic', '备案号', '如不需要展示可留空', '', ''],
    ['agreement', 'basic', '协议说明', '如不需要展示可留空', '', ''],
    ['login_subtitle', 'basic', '登录页副标题', '显示在登录页系统名称下方，可留空', '综合型中小企业业务管理系统', ''],
    ['admin_logo_mini', 'basic', '后台折叠 Logo', '左侧菜单收起时显示，建议 2-4 个字', 'ERP', ''],
    ['admin_logo_text', 'basic', '后台完整 Logo', '左上角完整系统名称', 'ERP AI 管理系统', ''],
    ['site_home_url', 'basic', '官网地址', '顶部官网入口；留空则不显示', '', ''],
    ['site_home_label', 'basic', '官网入口名称', '顶部官网入口文字', '官网', ''],
    ['copyright', 'basic', '版权说明', '登录页、打印页和页脚展示；留空则不显示', '', ''],
];

$statement = $pdo->prepare("INSERT INTO `{$prefix}config` (`name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`)
    VALUES (:name, :group, :title, :tip, 'string', '', :value, '', :rule, '', '')
    ON DUPLICATE KEY UPDATE
        `group` = VALUES(`group`),
        `title` = VALUES(`title`),
        `tip` = VALUES(`tip`),
        `type` = VALUES(`type`),
        `rule` = VALUES(`rule`),
        `value` = CASE
            WHEN `value` = '' OR `value` LIKE '%?%' OR `value` LIKE '%江苏白猿%' OR `value` LIKE '%白猿%' OR `value` LIKE '%猿创%' OR `value` LIKE '%100% AI%' OR `value` = '我的网站' OR `value` LIKE '%bysat.com%' OR `value` LIKE '%著作权%'
            THEN VALUES(`value`)
            ELSE `value`
        END");

foreach ($configs as $config) {
    $statement->execute([
        ':name' => $config[0],
        ':group' => $config[1],
        ':title' => $config[2],
        ':tip' => $config[3],
        ':value' => $config[4],
        ':rule' => $config[5],
    ]);
}

writeSiteConfig($rootPath, $configs);
clearCacheDirectories($rootPath . DIRECTORY_SEPARATOR . 'runtime');

echo "系统展示配置同步完成\n";

function parseSimpleEnv(string $path): array
{
    $result = [];
    $section = '';
    if (!is_file($path)) {
        return $result;
    }

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
        $result[$section ? $section . '.' . $key : $key] = trim($value, "\"'");
    }

    return $result;
}

function writeSiteConfig(string $rootPath, array $configs): void
{
    $siteFile = $rootPath . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'extra' . DIRECTORY_SEPARATOR . 'site.php';
    $siteConfig = is_file($siteFile) ? include $siteFile : [];
    if (!is_array($siteConfig)) {
        $siteConfig = [];
    }

    foreach ($configs as $config) {
        $siteConfig[$config[0]] = $config[4];
    }

    file_put_contents($siteFile, "<?php\n\nreturn " . var_export($siteConfig, true) . ";\n");
}

function clearCacheDirectories(string $runtimePath): void
{
    foreach (['cache', 'temp'] as $name) {
        $path = $runtimePath . DIRECTORY_SEPARATOR . $name;
        if (!is_dir($path)) {
            continue;
        }
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
    }
}
