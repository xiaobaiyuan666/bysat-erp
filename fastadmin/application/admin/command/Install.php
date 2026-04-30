<?php

namespace app\admin\command;

use fast\Random;
use PDO;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\Db;
use think\Exception;
use think\Lang;
use think\Request;
use think\Response;
use think\View;

class Install extends Command
{

    /**
     * 最低PHP版本
     * @var string
     */
    protected $minPhpVersion = '7.4.0';

    protected $model = null;
    /**
     * @var \think\View 视图类实例
     */
    protected $view;

    /**
     * @var \think\Request Request 实例
     */
    protected $request;

    protected function configure()
    {
        $config = Config::get('database');
        $this
            ->setName('install')
            ->addOption('hostname', 'a', Option::VALUE_OPTIONAL, 'mysql hostname', $config['hostname'])
            ->addOption('hostport', 'o', Option::VALUE_OPTIONAL, 'mysql hostport', $config['hostport'])
            ->addOption('database', 'd', Option::VALUE_OPTIONAL, 'mysql database', $config['database'])
            ->addOption('prefix', 'r', Option::VALUE_OPTIONAL, 'table prefix', $config['prefix'])
            ->addOption('username', 'u', Option::VALUE_OPTIONAL, 'mysql username', $config['username'])
            ->addOption('password', 'p', Option::VALUE_OPTIONAL, 'mysql password', $config['password'])
            ->addOption('mode', 'm', Option::VALUE_OPTIONAL, 'install mode', 'clean')
            ->addOption('force', 'f', Option::VALUE_OPTIONAL, 'force override', false)
            ->setDescription('New installation of FastAdmin');
    }

    /**
     * 命令行安装
     */
    protected function execute(Input $input, Output $output)
    {
        define('INSTALL_PATH', APP_PATH . 'admin' . DS . 'command' . DS . 'Install' . DS);
        // 覆盖安装
        $force = $input->getOption('force');
        $hostname = $input->getOption('hostname');
        $hostport = $input->getOption('hostport');
        $database = $input->getOption('database');
        $prefix = $input->getOption('prefix');
        $username = $input->getOption('username');
        $password = $input->getOption('password');
        $installMode = $input->getOption('mode') ?: 'clean';

        $installLockFile = INSTALL_PATH . "install.lock";
        if (is_file($installLockFile) && !$force) {
            throw new Exception("\nFastAdmin already installed!\nIf you need to reinstall again, use the parameter --force=true ");
        }

        $adminUsername = 'admin';
        $adminPassword = Random::alnum(10);
        $adminEmail = 'admin@admin.com';
        $siteName = (string)(Config::get('erp.brand.system_name') ?: __('My Website'));

        $adminName = $this->installation($hostname, $hostport, $database, $username, $password, $prefix, $adminUsername, $adminPassword, $adminEmail, $siteName, $installMode);
        if ($adminName) {
            $output->highlight("Admin url:https://www.example.com/{$adminName}");
        }

        $output->highlight("Admin username:{$adminUsername}");
        $output->highlight("Admin password:{$adminPassword}");

        \think\Cache::rm('__menu__');

        $output->info("Install Successed!");
    }

    /**
     * PC端安装
     */
    public function index()
    {
        $this->view = View::instance(array_merge(Config::get('template'), ['tpl_cache' => false]));
        $this->request = Request::instance();
        $databaseConfig = Config::get('database');

        define('INSTALL_PATH', APP_PATH . 'admin' . DS . 'command' . DS . 'Install' . DS);

        $lang = $this->request->langset();
        $lang = preg_match("/^([a-zA-Z\-_]{2,10})\$/i", $lang) ? $lang : 'zh-cn';

        if (!$lang || in_array($lang, ['zh-cn', 'zh-hans-cn'])) {
            Lang::load(INSTALL_PATH . 'zh-cn.php');
        }

        $installLockFile = INSTALL_PATH . "install.lock";

        if (is_file($installLockFile)) {
            echo __('The system has been installed. If you need to reinstall, please remove %s first', 'install.lock');
            exit;
        }
        $output = function ($code, $msg, $url = null, $data = null) {
            return Response::create(['code' => $code, 'msg' => $msg, 'url' => $url, 'data' => $data], 'json');
        };

        if ($this->request->isPost()) {
            $mysqlHostname = $this->request->post('mysqlHostname', '127.0.0.1');
            $mysqlHostport = $this->request->post('mysqlHostport', '3306');
            $hostArr = explode(':', $mysqlHostname);
            if (count($hostArr) > 1) {
                $mysqlHostname = $hostArr[0];
                $mysqlHostport = $hostArr[1];
            }
            $mysqlUsername = $this->request->post('mysqlUsername', 'root');
            $mysqlPassword = $this->request->post('mysqlPassword', '');
            $mysqlDatabase = $this->request->post('mysqlDatabase', '');
            $mysqlPrefix = $this->normalizeTablePrefix((string)$this->request->post('mysqlPrefix', 'fa_'));
            $adminUsername = $this->request->post('adminUsername', 'admin');
            $adminPassword = $this->request->post('adminPassword', '');
            $adminPasswordConfirmation = $this->request->post('adminPasswordConfirmation', '');
            $adminEmail = $this->request->post('adminEmail', 'admin@example.com');
            $siteName = $this->request->post('siteName', (string)(Config::get('erp.brand.system_name') ?: __('My Website')));
            $installMode = $this->normalizeInstallMode((string)$this->request->post('installMode', 'clean'));

            if ($adminPassword !== $adminPasswordConfirmation) {
                return $output(0, __('The two passwords you entered did not match'));
            }

            $adminName = '';
            try {
                $adminName = $this->installation($mysqlHostname, $mysqlHostport, $mysqlDatabase, $mysqlUsername, $mysqlPassword, $mysqlPrefix, $adminUsername, $adminPassword, $adminEmail, $siteName, $installMode);
            } catch (\PDOException $e) {
                return $output(0, $this->friendlyInstallError($e->getMessage()));
            } catch (\Exception $e) {
                return $output(0, $this->friendlyInstallError($e->getMessage()));
            }
            return $output(1, __('Install Successed'), null, [
                'adminName' => $adminName,
                'adminUsername' => $adminUsername,
                'installMode' => $installMode,
                'siteName' => $siteName,
                'tablePrefix' => $mysqlPrefix,
                'firstUseUrl' => './docs/首次使用说明.html',
                'deployGuideUrl' => './docs/正式部署与升级说明.html',
            ]);
        }
        $errInfo = '';
        try {
            $this->checkenv();
        } catch (\Exception $e) {
            $errInfo = $e->getMessage();
        }
        return $this->view->fetch(INSTALL_PATH . "install.html", [
            'errInfo' => $errInfo,
            'defaultSiteName' => (string)(Config::get('erp.brand.system_name') ?: __('My Website')),
            'defaultInstallMode' => 'clean',
            'hasDemoInstall' => is_file(ROOT_PATH . 'database' . DS . 'install_demo.sql'),
            'defaultMysqlHostname' => (string)($databaseConfig['hostname'] ?? '127.0.0.1'),
            'defaultMysqlDatabase' => (string)($databaseConfig['database'] ?? ''),
            'defaultMysqlUsername' => (string)($databaseConfig['username'] ?? 'root'),
            'defaultMysqlPassword' => (string)($databaseConfig['password'] ?? ''),
            'defaultMysqlPrefix' => $this->normalizeTablePrefix((string)($databaseConfig['prefix'] ?? 'fa_')),
            'defaultMysqlHostport' => (string)($databaseConfig['hostport'] ?? '3306'),
            'firstUseUrl' => './docs/首次使用说明.html',
            'deployGuideUrl' => './docs/正式部署与升级说明.html',
        ]);
    }

    /**
     * 执行安装
     */
    protected function installation($mysqlHostname, $mysqlHostport, $mysqlDatabase, $mysqlUsername, $mysqlPassword, $mysqlPrefix, $adminUsername, $adminPassword, $adminEmail = null, $siteName = null, $installMode = 'clean')
    {
        $this->checkenv();

        if ($mysqlDatabase == '') {
            throw new Exception(__('Please input correct database'));
        }
        if (!preg_match("/^\w{3,12}$/", $adminUsername)) {
            throw new Exception(__('Please input correct username'));
        }
        if (!preg_match("/^[\S]{6,16}$/", $adminPassword)) {
            throw new Exception(__('Please input correct password'));
        }
        $weakPasswordArr = ['123456', '12345678', '123456789', '654321', '111111', '000000', 'password', 'qwerty', 'abc123', '1qaz2wsx'];
        if (in_array($adminPassword, $weakPasswordArr)) {
            throw new Exception(__('Password is too weak'));
        }
        if ($siteName == '' || preg_match("/fast" . "admin/i", $siteName)) {
            throw new Exception(__('Please input correct website'));
        }

        $sqlFile = $this->getInstallSqlFile($installMode);
        $sql = file_get_contents($sqlFile);

        $sql = str_replace("`fa_", "`{$mysqlPrefix}", $sql);
        $sql = $this->normalizeInstallSql($sql);

        $instance = $this->createInstallConnection($mysqlHostname, $mysqlHostport, $mysqlDatabase, $mysqlUsername, $mysqlPassword, $mysqlPrefix);
        $instance->getPdo()->exec($sql);
        // 后台入口文件
        $adminFile = ROOT_PATH . 'public' . DS . 'admin.php';

        // 数据库配置文件
        $envSampleFile = ROOT_PATH . '.env.sample';
        $envFile = ROOT_PATH . '.env';
        if (!file_exists($envFile)) {
            if (!copy($envSampleFile, $envFile)) {
                throw new Exception(__('Failed to copy %s to %s', '.env.sample', '.env'));
            }
        }

        $envText = @file_get_contents($envFile);

        $callback = function ($matches) use ($mysqlHostname, $mysqlHostport, $mysqlUsername, $mysqlPassword, $mysqlDatabase, $mysqlPrefix) {
            $field = "mysql" . ucfirst($matches[1]);
            $replace = $$field;
            return "{$matches[1]} = {$replace}";
        };
        $envText = preg_replace_callback("/(hostname|database|username|password|hostport|prefix)\s*=\s*(.*)/", $callback, $envText);

        // 检测能否成功写入数据库配置
        $result = @file_put_contents($envFile, $envText);
        if (!$result) {
            throw new Exception(__('The current permissions are insufficient to write the file %s', '.env'));
        }

        // 设置新的Token随机密钥key
        $oldTokenKey = Config::get('token.key');
        $newTokenKey = \fast\Random::alnum(32);
        $coreConfigFile = CONF_PATH . 'config.php';
        $coreConfigText = @file_get_contents($coreConfigFile);
        $coreConfigText = preg_replace("/'key'(\s+)=>(\s+)'{$oldTokenKey}'/", "'key'\$1=>\$2'{$newTokenKey}'", $coreConfigText);

        $result = @file_put_contents($coreConfigFile, $coreConfigText);
        if (!$result) {
            throw new Exception(__('The current permissions are insufficient to write the file %s', 'application/config.php'));
        }

        $avatar = '/assets/img/avatar.png';
        // 变更默认管理员密码
        $adminPassword = $adminPassword ?: Random::alnum(8);
        $adminEmail = $adminEmail ?: "admin@example.com";
        $newSalt = substr(md5(uniqid(true)), 0, 6);
        $newPassword = md5(md5($adminPassword) . $newSalt);
        $data = ['username' => $adminUsername, 'email' => $adminEmail, 'avatar' => $avatar, 'password' => $newPassword, 'salt' => $newSalt];
        $instance->name('admin')->where('username', 'admin')->update($data);

        // 变更前台默认用户的密码,随机生成
        $newSalt = substr(md5(uniqid(true)), 0, 6);
        $newPassword = md5(md5(Random::alnum(8)) . $newSalt);
        $instance->name('user')->where('username', 'admin')->update(['avatar' => $avatar, 'password' => $newPassword, 'salt' => $newSalt]);

        // 修改后台入口
        $adminName = $this->detectAdminEntry();
        if (is_file($adminFile)) {
            $adminName = Random::alpha(10) . '.php';
            rename($adminFile, ROOT_PATH . 'public' . DS . $adminName);
        }

        //修改站点名称
        if ($siteName != Config::get('site.name')) {
            $instance->name('config')->where('name', 'name')->update(['value' => $siteName]);
            $siteConfigFile = CONF_PATH . 'extra' . DS . 'site.php';
            $siteConfig = include $siteConfigFile;
            $configList = $instance->name("config")->select();
            foreach ($configList as $k => $value) {
                if (in_array($value['type'], ['selects', 'checkbox', 'images', 'files'])) {
                    $value['value'] = is_array($value['value']) ? $value['value'] : explode(',', $value['value']);
                }
                if ($value['type'] == 'array') {
                    $value['value'] = (array)json_decode($value['value'], true);
                }
                $siteConfig[$value['name']] = $value['value'];
            }
            $siteConfig['name'] = $siteName;
            file_put_contents($siteConfigFile, '<?php' . "\n\nreturn " . var_export_short($siteConfig) . ";\n");
        }

        $installLockFile = INSTALL_PATH . "install.lock";
        //检测能否成功写入lock文件
        $result = @file_put_contents($installLockFile, 1);
        if (!$result) {
            throw new Exception(__('The current permissions are insufficient to write the file %s', 'application/admin/command/Install/install.lock'));
        }

        try {
            //删除安装脚本
            // Keep install.php in place; install.lock is sufficient to block reinstallation
        } catch (\Exception $e) {

        }

        return $adminName;
    }

    /**
     * 检测环境
     */
    protected function checkenv()
    {
        // 检测目录是否存在
        $checkDirs = [
            'thinkphp',
            'vendor',
            'public' . DS . 'assets' . DS . 'libs'
        ];

        $envSampleFile = ROOT_PATH . '.env.sample';
        $envFile = ROOT_PATH . '.env';
        $installLockDir = dirname(INSTALL_PATH . 'install.lock');

        if (version_compare(PHP_VERSION, $this->minPhpVersion, '<')) {
            throw new Exception(__("The current PHP %s is too low, please use PHP %s or higher", PHP_VERSION, $this->minPhpVersion));
        }
        if (!extension_loaded("PDO") || !extension_loaded("pdo_mysql")) {
            throw new Exception(__("PDO is not currently installed and cannot be installed"));
        }
        if (!is_file($envSampleFile)) {
            throw new Exception(__('Please go to the official website to download the full package or resource package and try to install'));
        }
        if ((is_file($envFile) && !is_really_writable($envFile)) || (!is_file($envFile) && !is_really_writable(ROOT_PATH))) {
            throw new Exception(__('The current permissions are insufficient to write the file %s', '.env'));
        }
        if (!is_really_writable($installLockDir)) {
            throw new Exception(__('The current permissions are insufficient to write the file %s', 'application/admin/command/Install/install.lock'));
        }
        foreach ($checkDirs as $k => $v) {
            if (!is_dir(ROOT_PATH . $v)) {
                throw new Exception(__('Please go to the official website to download the full package or resource package and try to install'));
            }
        }
        return true;
    }

    protected function normalizeInstallMode(string $installMode): string
    {
        return $installMode === 'demo' ? 'demo' : 'clean';
    }

    protected function getInstallSqlFile(string $installMode): string
    {
        $installMode = $this->normalizeInstallMode($installMode);
        $candidate = ROOT_PATH . 'database' . DS . ($installMode === 'demo' ? 'install_demo.sql' : 'install_clean.sql');

        if (is_file($candidate)) {
            return $candidate;
        }

        return INSTALL_PATH . 'fastadmin.sql';
    }

    protected function normalizeInstallSql(string $sql): string
    {
        return str_replace(
            "enum('addtabs','blank','dialog','ajax') DEFAULT NULL",
            "enum('','addtabs','blank','dialog','ajax') DEFAULT ''",
            $sql
        );
    }

    protected function normalizeTablePrefix(string $prefix): string
    {
        $prefix = trim($prefix);
        $prefix = preg_replace('/[^A-Za-z0-9_]/', '', $prefix);

        return $prefix !== '' ? $prefix : 'fa_';
    }

    protected function friendlyInstallError(string $message): string
    {
        if (stripos($message, 'Access denied for user') !== false) {
            return '数据库账号或密码错误，或者当前账号没有目标数据库权限。请先在宝塔确认数据库已创建、账号已授权，再重新安装。';
        }
        if (stripos($message, 'Unknown database') !== false) {
            return '目标数据库不存在。请先在宝塔创建数据库，并把账号授权给这个数据库。';
        }
        if (stripos($message, 'Data truncated for column') !== false && stripos($message, 'menutype') !== false) {
            return '安装数据与当前 MySQL 严格模式冲突。请重新上传最新安装包后再安装。';
        }
        if (stripos($message, 'Duplicate entry') !== false || stripos($message, 'already exists') !== false) {
            return '目标数据库里已经有旧数据或旧表了。请换一个空数据库安装，或先清空原库。';
        }

        return $message;
    }

    protected function createInstallConnection(string $mysqlHostname, string $mysqlHostport, string $mysqlDatabase, string $mysqlUsername, string $mysqlPassword, string $mysqlPrefix)
    {
        $config = Config::get('database');
        $connectionConfig = [
            'type'     => "{$config['type']}",
            'hostname' => "{$mysqlHostname}",
            'hostport' => "{$mysqlHostport}",
            'database' => "{$mysqlDatabase}",
            'username' => "{$mysqlUsername}",
            'password' => "{$mysqlPassword}",
            'prefix'   => "{$mysqlPrefix}",
        ];

        try {
            $instance = Db::connect($connectionConfig);
            $instance->execute("SELECT 1");
            return $instance;
        } catch (\Throwable $connectException) {
            try {
                $pdo = new PDO("{$config['type']}:host={$mysqlHostname}" . ($mysqlHostport ? ";port={$mysqlHostport}" : '') . ';charset=utf8mb4', $mysqlUsername, $mysqlPassword);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (\Throwable $serverException) {
                throw new Exception('无法连接目标数据库。并不要求 root，但当前数据库账号必须对目标库有权限。如果使用子账号，请先在宝塔创建数据库并授权，再重新安装。原始错误：' . $connectException->getMessage());
            }

            try {
                $pdo->query("CREATE DATABASE IF NOT EXISTS `{$mysqlDatabase}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
            } catch (\Throwable $createException) {
                throw new Exception('当前数据库账号没有建库权限。并不要求 root，请先在宝塔创建数据库并授权给当前用户，再重新运行安装器。原始错误：' . $createException->getMessage());
            }

            $instance = Db::connect($connectionConfig);
            $instance->execute("SELECT 1");

            return $instance;
        }
    }

    protected function detectAdminEntry(): string
    {
        $publicPath = ROOT_PATH . 'public' . DS;
        $excluded = ['index.php', 'install.php', 'router.php'];
        $files = glob($publicPath . '*.php') ?: [];
        sort($files);

        foreach ($files as $file) {
            $name = basename(str_replace('\\', '/', $file));
            if (!in_array($name, $excluded, true)) {
                return $name;
            }
        }

        return '';
    }
}
