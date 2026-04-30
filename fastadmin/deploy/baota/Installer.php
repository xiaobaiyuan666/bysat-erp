<?php

declare(strict_types=1);

namespace ErpDeploy\Baota;

use PDO;
use PDOException;
use RuntimeException;
use Throwable;

final class Installer
{
    public const MODE_CLEAN = 'clean';
    public const MODE_DEMO = 'demo';

    /**
     * @return array<string, mixed>
     */
    public static function defaults(string $rootPath): array
    {
        return [
            'root_path' => self::normalizePath($rootPath),
            'site_name' => 'ERP AI 管理系统',
            'db_host' => '127.0.0.1',
            'db_port' => '3306',
            'db_name' => 'bysat_erp',
            'db_user' => 'root',
            'db_password' => '',
            'db_prefix' => 'fa_',
            'admin_username' => 'admin',
            'admin_password' => 'Admin@123',
            'admin_email' => 'admin@example.com',
            'install_mode' => self::MODE_CLEAN,
            'reset_tables' => true,
            'force_lock' => false,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function environmentReport(string $rootPath): array
    {
        $rootPath = self::normalizePath($rootPath);

        return [
            self::buildCheck('PHP 版本', version_compare(PHP_VERSION, '7.4.0', '>='), '当前：' . PHP_VERSION . '，要求 >= 7.4'),
            self::buildCheck('PDO MySQL 扩展', extension_loaded('pdo') && extension_loaded('pdo_mysql'), '请启用 pdo 和 pdo_mysql'),
            self::buildCheck('Curl 扩展', extension_loaded('curl'), '建议启用，供 AI 请求和在线更新使用'),
            self::buildCheck('项目目录', is_dir($rootPath), $rootPath),
            self::buildCheck('vendor 目录', is_dir($rootPath . DIRECTORY_SEPARATOR . 'vendor'), '正式部署包已内置 vendor'),
            self::buildCheck('public 目录', is_dir($rootPath . DIRECTORY_SEPARATOR . 'public'), '宝塔站点根目录必须指向 public'),
            self::buildCheck('纯净安装 SQL', is_file(self::sqlFilePath($rootPath, self::MODE_CLEAN)), '如果缺失，请重新打包部署包'),
            self::buildCheck('演示安装 SQL', is_file(self::sqlFilePath($rootPath, self::MODE_DEMO)), '如果缺失，请重新打包部署包'),
            self::buildCheck('.env 可写', self::pathWritable($rootPath . DIRECTORY_SEPARATOR . '.env') || self::pathWritable($rootPath), '安装器会把数据库配置写入 .env'),
            self::buildCheck('install.lock 可写', self::pathWritable(dirname(self::installLockPath($rootPath))), self::installLockPath($rootPath)),
        ];
    }

    public static function isInstalled(string $rootPath): bool
    {
        return is_file(self::installLockPath($rootPath));
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public static function install(array $input): array
    {
        $options = array_merge(self::defaults((string) ($input['root_path'] ?? dirname(__DIR__, 2))), $input);
        $rootPath = self::normalizePath((string) $options['root_path']);

        if (self::isInstalled($rootPath) && !self::toBool($options['force_lock'])) {
            throw new RuntimeException('系统已安装。如需重装，请删除 install.lock 或进入重装模式。');
        }

        self::assertEnvironment($rootPath);

        $siteName = self::requireString($options['site_name'], '站点名称不能为空');
        $dbHost = self::requireString($options['db_host'], '数据库地址不能为空');
        $dbPort = self::normalizePort($options['db_port']);
        $dbName = self::requireDatabaseName((string) $options['db_name']);
        $dbUser = self::requireString($options['db_user'], '数据库账号不能为空');
        $dbPassword = (string) ($options['db_password'] ?? '');
        $dbPrefix = self::normalizePrefix((string) ($options['db_prefix'] ?? 'fa_'));
        $adminUsername = self::normalizeAdminUsername((string) ($options['admin_username'] ?? 'admin'));
        $adminPassword = self::normalizeAdminPassword((string) ($options['admin_password'] ?? ''));
        $adminEmail = self::normalizeEmail((string) ($options['admin_email'] ?? 'admin@example.com'));
        $installMode = self::normalizeInstallMode((string) ($options['install_mode'] ?? self::MODE_DEMO));
        $resetTables = self::toBool($options['reset_tables']);

        $databaseMeta = self::prepareDatabase($dbHost, $dbPort, $dbName, $dbUser, $dbPassword);
        $pdo = $databaseMeta['pdo'];

        if ($resetTables) {
            self::dropPrefixedTables($pdo, $dbPrefix);
        }

        self::importSqlFile($pdo, self::sqlFilePath($rootPath, $installMode), $dbPrefix);
        self::ensureWritableDirectories($rootPath);
        self::writeEnv($rootPath, $dbHost, $dbPort, $dbName, $dbUser, $dbPassword, $dbPrefix);
        self::refreshTokenKey($rootPath);
        self::updateAdminAccount($pdo, $dbPrefix, $adminUsername, $adminPassword, $adminEmail);
        self::ensureAiSetting($pdo, $dbPrefix);
        self::updateSiteName($pdo, $rootPath, $dbPrefix, $siteName);
        self::touchInstallLock($rootPath);
        self::clearRuntime($rootPath . DIRECTORY_SEPARATOR . 'runtime');

        return [
            'site_name' => $siteName,
            'install_mode' => $installMode,
            'admin_entry' => self::adminEntry($rootPath),
            'admin_url' => './' . self::adminEntry($rootPath),
            'admin_username' => $adminUsername,
            'admin_password' => $adminPassword,
            'db_name' => $dbName,
            'db_prefix' => $dbPrefix,
            'database_created' => $databaseMeta['created'],
            'demo_accounts' => $installMode === self::MODE_DEMO ? self::demoAccounts() : [],
        ];
    }

    public static function installLockPath(string $rootPath): string
    {
        return self::normalizePath($rootPath) . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'command' . DIRECTORY_SEPARATOR . 'Install' . DIRECTORY_SEPARATOR . 'install.lock';
    }

    public static function adminEntry(string $rootPath): string
    {
        $publicPath = self::normalizePath($rootPath) . DIRECTORY_SEPARATOR . 'public';
        if (!is_dir($publicPath)) {
            return 'admin.php';
        }

        $excluded = ['index.php', 'install.php', 'router.php'];
        $files = glob($publicPath . DIRECTORY_SEPARATOR . '*.php') ?: [];
        sort($files);

        foreach ($files as $file) {
            $name = basename(str_replace('\\', '/', $file));
            $name = ltrim($name, '\\/');
            if (!in_array($name, $excluded, true)) {
                return $name;
            }
        }

        return 'admin.php';
    }

    /**
     * @return array<int, array<string, string>>
     */
    public static function demoAccounts(): array
    {
        return [
            ['username' => 'admin', 'password' => 'Admin@123', 'label' => '超级管理员'],
            ['username' => 'finance.li', 'password' => 'Start@123', 'label' => '财务'],
            ['username' => 'pm.zhang', 'password' => 'Start@123', 'label' => '项目经理'],
            ['username' => 'ops.gu', 'password' => 'Start@123', 'label' => '运营'],
            ['username' => 'service.liu', 'password' => 'Start@123', 'label' => '客服'],
            ['username' => 'tech.zhou', 'password' => 'Start@123', 'label' => '技术'],
        ];
    }

    private static function sqlFilePath(string $rootPath, string $installMode): string
    {
        $file = $installMode === self::MODE_CLEAN ? 'install_clean.sql' : 'install_demo.sql';

        return self::normalizePath($rootPath) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . $file;
    }

    private static function assertEnvironment(string $rootPath): void
    {
        foreach (self::environmentReport($rootPath) as $check) {
            if (!$check['ok']) {
                throw new RuntimeException((string) $check['label'] . ' 检查未通过：' . (string) $check['detail']);
            }
        }
    }

    /**
     * @return array{pdo: PDO, created: bool}
     */
    private static function prepareDatabase(string $host, string $port, string $database, string $username, string $password): array
    {
        try {
            return [
                'pdo' => self::connectDatabase($host, $port, $database, $username, $password),
                'created' => false,
            ];
        } catch (RuntimeException $connectException) {
            try {
                $serverPdo = self::connectServer($host, $port, $username, $password);
            } catch (RuntimeException $serverException) {
                throw new RuntimeException(
                    '无法连接目标数据库。并不要求 root，但当前数据库账号必须对目标库有权限。如果使用子账号，请先在宝塔创建数据库并授权，再重新安装。原始错误：' . $connectException->getMessage(),
                    0,
                    $serverException
                );
            }

            try {
                self::ensureDatabase($serverPdo, $database);
            } catch (RuntimeException $createException) {
                throw new RuntimeException(
                    '当前数据库账号没有建库权限。并不要求 root，请先在宝塔创建数据库并授权给当前用户，再重新运行安装器。原始错误：' . $createException->getMessage(),
                    0,
                    $createException
                );
            }

            return [
                'pdo' => self::connectDatabase($host, $port, $database, $username, $password),
                'created' => true,
            ];
        }
    }

    private static function connectServer(string $host, string $port, string $username, string $password): PDO
    {
        return self::newPdo(sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $host, $port), $username, $password);
    }

    private static function connectDatabase(string $host, string $port, string $database, string $username, string $password): PDO
    {
        return self::newPdo(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database), $username, $password);
    }

    private static function newPdo(string $dsn, string $username, string $password): PDO
    {
        try {
            return new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException('数据库连接失败：' . $exception->getMessage(), 0, $exception);
        }
    }

    private static function ensureDatabase(PDO $pdo, string $database): void
    {
        try {
            $pdo->exec(sprintf(
                'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
                str_replace('`', '``', $database)
            ));
        } catch (Throwable $exception) {
            throw new RuntimeException('数据库创建失败：' . $exception->getMessage(), 0, $exception);
        }
    }

    private static function dropPrefixedTables(PDO $pdo, string $prefix): void
    {
        $like = str_replace(['\\', '_', '%'], ['\\\\', '\\_', '\\%'], $prefix) . '%';
        $statement = $pdo->query("SHOW TABLES LIKE '{$like}'");
        if ($statement === false) {
            return;
        }

        $tables = $statement->fetchAll(PDO::FETCH_COLUMN);
        if (empty($tables)) {
            return;
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($tables as $table) {
            $pdo->exec(sprintf('DROP TABLE IF EXISTS `%s`', str_replace('`', '``', (string) $table)));
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    private static function importSqlFile(PDO $pdo, string $sqlFile, string $prefix): void
    {
        if (!is_file($sqlFile)) {
            throw new RuntimeException('未找到安装 SQL：' . $sqlFile);
        }

        $sql = (string) file_get_contents($sqlFile);
        if ($sql === '') {
            throw new RuntimeException('安装 SQL 为空：' . $sqlFile);
        }

        if ($prefix !== 'fa_') {
            $sql = str_replace('`fa_', '`' . $prefix, $sql);
        }

        try {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
            $pdo->exec($sql);
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        } catch (Throwable $exception) {
            throw new RuntimeException('SQL 导入失败：' . $exception->getMessage(), 0, $exception);
        }
    }

    private static function writeEnv(
        string $rootPath,
        string $dbHost,
        string $dbPort,
        string $dbName,
        string $dbUser,
        string $dbPassword,
        string $dbPrefix
    ): void {
        $envContent = "[app]\n";
        $envContent .= "debug = false\n";
        $envContent .= "trace = false\n\n";
        $envContent .= "[database]\n";
        $envContent .= "hostname = {$dbHost}\n";
        $envContent .= "database = {$dbName}\n";
        $envContent .= "username = {$dbUser}\n";
        $envContent .= "password = {$dbPassword}\n";
        $envContent .= "hostport = {$dbPort}\n";
        $envContent .= "prefix = {$dbPrefix}\n";

        $envFile = $rootPath . DIRECTORY_SEPARATOR . '.env';
        if (@file_put_contents($envFile, $envContent) === false) {
            throw new RuntimeException('写入 .env 失败，请检查目录权限。');
        }
    }

    private static function refreshTokenKey(string $rootPath): void
    {
        $configFile = $rootPath . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'config.php';
        if (!is_file($configFile)) {
            return;
        }

        $content = (string) file_get_contents($configFile);
        $newTokenKey = self::randomString(32);
        $updated = preg_replace("/('key'\\s*=>\\s*')[^']*(')/", '$1' . $newTokenKey . '$2', $content, 1);
        if ($updated !== null && $updated !== $content) {
            @file_put_contents($configFile, $updated);
        }
    }

    private static function ensureWritableDirectories(string $rootPath): void
    {
        $directories = [
            $rootPath . DIRECTORY_SEPARATOR . 'runtime',
            $rootPath . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'cache',
            $rootPath . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'log',
            $rootPath . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'temp',
            $rootPath . DIRECTORY_SEPARATOR . 'storage',
            $rootPath . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads',
            $rootPath . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'downloads',
        ];

        foreach ($directories as $directory) {
            if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
                throw new RuntimeException('创建目录失败：' . $directory);
            }
            @chmod($directory, 0775);
        }
    }

    private static function updateAdminAccount(PDO $pdo, string $prefix, string $username, string $password, string $email): void
    {
        $salt = substr(md5(uniqid('admin', true)), 0, 6);
        $passwordHash = md5(md5($password) . $salt);

        $statement = $pdo->prepare(
            "UPDATE {$prefix}admin
            SET username = :username,
                nickname = :nickname,
                email = :email,
                avatar = :avatar,
                password = :password,
                salt = :salt,
                status = 'normal',
                token = '',
                loginfailure = 0,
                loginip = ''
            WHERE id = 1"
        );
        $statement->execute([
            ':username' => $username,
            ':nickname' => '管理员',
            ':email' => $email,
            ':avatar' => '/assets/img/avatar.png',
            ':password' => $passwordHash,
            ':salt' => $salt,
        ]);

        $profileStatement = $pdo->prepare(
            "UPDATE {$prefix}staff_profile
            SET account = :account,
                name = :name,
                role_key = 'admin',
                status = 'active',
                updatetime = :updatetime
            WHERE admin_id = 1"
        );
        $profileStatement->execute([
            ':account' => $username,
            ':name' => '管理员',
            ':updatetime' => time(),
        ]);
    }

    private static function ensureAiSetting(PDO $pdo, string $prefix): void
    {
        $count = (int) $pdo->query("SELECT COUNT(*) FROM {$prefix}ai_setting")->fetchColumn();
        if ($count > 0) {
            return;
        }

        $statement = $pdo->prepare(
            "INSERT INTO {$prefix}ai_setting
            (provider_name, base_url, api_key, model, temperature, system_prompt, workspace_json, createtime, updatetime)
            VALUES (:provider_name, :base_url, :api_key, :model, :temperature, :system_prompt, :workspace_json, :createtime, :updatetime)"
        );
        $statement->execute([
            ':provider_name' => 'OpenAI Compatible',
            ':base_url' => '',
            ':api_key' => '',
            ':model' => '',
            ':temperature' => '0.20',
            ':system_prompt' => '',
            ':workspace_json' => '[]',
            ':createtime' => time(),
            ':updatetime' => time(),
        ]);
    }

    private static function updateSiteName(PDO $pdo, string $rootPath, string $prefix, string $siteName): void
    {
        $statement = $pdo->prepare("UPDATE {$prefix}config SET value = :value WHERE name = 'name'");
        $statement->execute([':value' => $siteName]);

        $brandConfigs = [
            ['login_subtitle', 'basic', '登录页副标题', '显示在登录页系统名称下方，可留空', '综合型中小企业业务管理系统'],
            ['admin_logo_mini', 'basic', '后台折叠 Logo', '左侧菜单收起时显示，建议 2-4 个字', 'ERP'],
            ['admin_logo_text', 'basic', '后台完整 Logo', '左上角完整系统名称', $siteName],
            ['site_home_url', 'basic', '官网地址', '顶部官网入口；留空则不显示', ''],
            ['site_home_label', 'basic', '官网入口名称', '顶部官网入口文字', '官网'],
            ['copyright', 'basic', '版权说明', '登录页、打印页和页脚展示；留空则不显示', ''],
        ];
        $upsert = $pdo->prepare("INSERT INTO {$prefix}config (`name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`)
            VALUES (:name, :group, :title, :tip, 'string', '', :value, '', '', '', '')
            ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `tip` = VALUES(`tip`)");
        foreach ($brandConfigs as $item) {
            $upsert->execute([
                ':name' => $item[0],
                ':group' => $item[1],
                ':title' => $item[2],
                ':tip' => $item[3],
                ':value' => $item[4],
            ]);
        }
        $statement = $pdo->prepare("UPDATE {$prefix}config SET value = :value WHERE name = 'admin_logo_text' AND (value = '' OR value = 'ERP AI 管理系统')");
        $statement->execute([':value' => $siteName]);

        $siteConfigFile = $rootPath . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'extra' . DIRECTORY_SEPARATOR . 'site.php';
        $siteConfig = [];
        if (is_file($siteConfigFile)) {
            $loaded = include $siteConfigFile;
            if (is_array($loaded)) {
                $siteConfig = $loaded;
            }
        }

        $siteConfig['name'] = $siteName;
        foreach ($brandConfigs as $item) {
            if ($item[0] === 'admin_logo_text' && in_array((string)($siteConfig[$item[0]] ?? ''), ['', 'ERP AI 管理系统'], true)) {
                $siteConfig[$item[0]] = $siteName;
                continue;
            }
            if (!array_key_exists($item[0], $siteConfig)) {
                $siteConfig[$item[0]] = $item[4];
            }
        }

        $content = "<?php\n\nreturn " . var_export($siteConfig, true) . ";\n";
        @file_put_contents($siteConfigFile, $content);
    }

    private static function touchInstallLock(string $rootPath): void
    {
        if (@file_put_contents(self::installLockPath($rootPath), '1') === false) {
            throw new RuntimeException('写入 install.lock 失败，请检查目录权限。');
        }
    }

    private static function clearRuntime(string $runtimePath): void
    {
        if (!is_dir($runtimePath)) {
            return;
        }

        $items = scandir($runtimePath);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            self::deletePath($runtimePath . DIRECTORY_SEPARATOR . $item);
        }
    }

    private static function deletePath(string $path): void
    {
        if (is_file($path) || is_link($path)) {
            @unlink($path);
            return;
        }

        if (!is_dir($path)) {
            return;
        }

        $children = scandir($path);
        if ($children === false) {
            return;
        }

        foreach ($children as $child) {
            if ($child === '.' || $child === '..') {
                continue;
            }
            self::deletePath($path . DIRECTORY_SEPARATOR . $child);
        }

        @rmdir($path);
    }

    /**
     * @return array<string, mixed>
     */
    private static function buildCheck(string $label, bool $ok, string $detail): array
    {
        return [
            'label' => $label,
            'ok' => $ok,
            'detail' => $detail,
        ];
    }

    private static function normalizePath(string $path): string
    {
        return rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
    }

    private static function pathWritable(string $path): bool
    {
        if (file_exists($path)) {
            return is_writable($path);
        }

        return is_writable(dirname($path));
    }

    /**
     * @param mixed $value
     */
    private static function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array((string) $value, ['1', 'true', 'on', 'yes'], true);
    }

    /**
     * @param mixed $value
     */
    private static function requireString($value, string $message): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            throw new RuntimeException($message);
        }

        return $value;
    }

    /**
     * @param mixed $value
     */
    private static function normalizePort($value): string
    {
        $value = trim((string) $value);
        if ($value === '' || !ctype_digit($value)) {
            throw new RuntimeException('数据库端口格式不正确');
        }

        return $value;
    }

    private static function requireDatabaseName(string $database): string
    {
        $database = trim($database);
        if ($database === '' || !preg_match('/^[A-Za-z0-9_]+$/', $database)) {
            throw new RuntimeException('数据库名称只能包含字母、数字和下划线');
        }

        return $database;
    }

    private static function normalizePrefix(string $prefix): string
    {
        $prefix = trim($prefix);
        if ($prefix === '') {
            $prefix = 'fa_';
        }
        if (!preg_match('/^[A-Za-z0-9_]+$/', $prefix)) {
            throw new RuntimeException('数据表前缀只能包含字母、数字和下划线');
        }
        if (substr($prefix, -1) !== '_') {
            $prefix .= '_';
        }

        return $prefix;
    }

    private static function normalizeAdminUsername(string $username): string
    {
        $username = trim($username);
        if (!preg_match('/^[A-Za-z0-9_.]{3,30}$/', $username)) {
            throw new RuntimeException('管理员账号只能是 3-30 位字母、数字、下划线或点号');
        }

        return $username;
    }

    private static function normalizeAdminPassword(string $password): string
    {
        if (!preg_match('/^[\\S]{6,32}$/', $password)) {
            throw new RuntimeException('管理员密码必须为 6-32 位且不能包含空格');
        }

        return $password;
    }

    private static function normalizeEmail(string $email): string
    {
        $email = trim($email);
        if ($email === '') {
            return 'admin@example.com';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('管理员邮箱格式不正确');
        }

        return $email;
    }

    private static function normalizeInstallMode(string $installMode): string
    {
        return $installMode === self::MODE_CLEAN ? self::MODE_CLEAN : self::MODE_DEMO;
    }

    private static function randomString(int $length): string
    {
        $pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $maxIndex = strlen($pool) - 1;
        $value = '';

        for ($index = 0; $index < $length; $index++) {
            $value .= $pool[random_int(0, $maxIndex)];
        }

        return $value;
    }
}
