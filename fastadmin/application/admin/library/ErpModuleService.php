<?php

namespace app\admin\library;

use think\Db;
use think\Exception;

class ErpModuleService
{
    protected $table = 'erp_module';

    public function getBrand(): array
    {
        $config = $this->getErpConfig();

        return (array) ($config['brand'] ?? []);
    }

    public function getCoreCapabilities(): array
    {
        $config = $this->getErpConfig();

        return array_values(array_filter(array_map('strval', (array) ($config['core_capabilities'] ?? []))));
    }

    public function getCategoryDefinitions(): array
    {
        $config = $this->getErpConfig();
        $categories = (array) ($config['module_categories'] ?? []);
        $result = [];

        foreach ($categories as $key => $category) {
            $category = (array) $category;
            $categoryKey = (string) ($category['key'] ?? $key);
            $result[$categoryKey] = [
                'key' => $categoryKey,
                'title' => (string) ($category['title'] ?? $categoryKey),
                'description' => (string) ($category['description'] ?? ''),
                'sort_no' => (int) ($category['sort_no'] ?? 0),
            ];
        }

        if (!isset($result['other'])) {
            $result['other'] = [
                'key' => 'other',
                'title' => '其他插件',
                'description' => '',
                'sort_no' => 999,
            ];
        }

        uasort($result, function (array $left, array $right): int {
            if ($left['sort_no'] === $right['sort_no']) {
                return strcmp($left['title'], $right['title']);
            }

            return $left['sort_no'] <=> $right['sort_no'];
        });

        return $result;
    }

    public function getDefinitions(): array
    {
        $config = $this->getErpConfig();
        $modules = (array) ($config['modules'] ?? []);
        $categories = $this->getCategoryDefinitions();
        $result = [];
        foreach ($modules as $key => $module) {
            $module = (array) $module;
            $module['key'] = (string) ($module['key'] ?? $key);
            $module['title'] = (string) ($module['title'] ?? $module['key']);
            $module['short_title'] = (string) ($module['short_title'] ?? $module['title']);
            $module['type'] = (string) ($module['type'] ?? 'plugin');
            $module['icon'] = (string) ($module['icon'] ?? 'fa fa-puzzle-piece');
            $module['description'] = (string) ($module['description'] ?? '');
            $module['default_enabled'] = (int) ($module['default_enabled'] ?? 1);
            $module['locked'] = (int) ($module['locked'] ?? 0);
            $module['route'] = (string) ($module['route'] ?? '');
            $module['sort_no'] = (int) ($module['sort_no'] ?? 0);
            $module['rule_prefixes'] = array_values(array_filter(array_map('strval', (array) ($module['rule_prefixes'] ?? [$module['key']]))));
            $module['category_key'] = (string) ($module['category_key'] ?? 'other');
            $category = $categories[$module['category_key']] ?? [
                'key' => $module['category_key'],
                'title' => (string) ($module['category_title'] ?? '其他插件'),
                'description' => (string) ($module['category_desc'] ?? ''),
                'sort_no' => 999,
            ];
            $module['category_title'] = (string) ($module['category_title'] ?? $category['title']);
            $module['category_desc'] = (string) ($module['category_desc'] ?? $category['description']);
            $result[$module['key']] = $module;
        }

        return $result;
    }

    public function getSwitchableModules(): array
    {
        return array_values($this->getModules());
    }

    public function getGroupedModules(): array
    {
        $groups = [];
        foreach ($this->getCategoryDefinitions() as $categoryKey => $category) {
            $groups[$categoryKey] = $category + [
                'modules' => [],
                'enabled_count' => 0,
                'module_count' => 0,
            ];
        }

        foreach ($this->getModules() as $module) {
            $categoryKey = (string) ($module['category_key'] ?? 'other');
            if (!isset($groups[$categoryKey])) {
                $groups[$categoryKey] = [
                    'key' => $categoryKey,
                    'title' => (string) ($module['category_title'] ?? '其他插件'),
                    'description' => (string) ($module['category_desc'] ?? ''),
                    'sort_no' => 999,
                    'modules' => [],
                    'enabled_count' => 0,
                    'module_count' => 0,
                ];
            }

            $groups[$categoryKey]['modules'][] = $module;
            $groups[$categoryKey]['module_count']++;
            if ((int) ($module['is_enabled'] ?? 0) === 1) {
                $groups[$categoryKey]['enabled_count']++;
            }
        }

        return array_values(array_filter($groups, function (array $group): bool {
            return !empty($group['modules']);
        }));
    }

    public function getModules(): array
    {
        $definitions = $this->getDefinitions();
        $rows = $this->getStoredRows();

        foreach ($definitions as $key => &$definition) {
            $row = $rows[$key] ?? [];
            $definition['id'] = (int) ($row['id'] ?? 0);
            $definition['is_enabled'] = array_key_exists('is_enabled', $row)
                ? (int) $row['is_enabled']
                : (int) $definition['default_enabled'];
            $definition['sort_no'] = array_key_exists('sort_no', $row)
                ? (int) $row['sort_no']
                : (int) ($definition['sort_no'] ?? 0);
            $definition['updated_at'] = (string) ($row['updated_at'] ?? '');
        }
        unset($definition);

        uasort($definitions, function (array $left, array $right): int {
            $leftSort = (int) ($left['sort_no'] ?? 0);
            $rightSort = (int) ($right['sort_no'] ?? 0);
            if ($leftSort === $rightSort) {
                return strcmp((string) ($left['title'] ?? ''), (string) ($right['title'] ?? ''));
            }

            return $leftSort <=> $rightSort;
        });

        return $definitions;
    }

    public function getEnabledMap(): array
    {
        $map = [];
        foreach ($this->getModules() as $module) {
            $map[(string) $module['key']] = (int) ($module['is_enabled'] ?? 0) === 1;
        }

        return $map;
    }

    public function isEnabled(string $moduleKey): bool
    {
        $modules = $this->getModules();
        if (!isset($modules[$moduleKey])) {
            return true;
        }

        return (int) ($modules[$moduleKey]['is_enabled'] ?? 0) === 1;
    }

    public function getModule(string $moduleKey): ?array
    {
        $modules = $this->getModules();

        return $modules[$moduleKey] ?? null;
    }

    public function saveSwitches(array $payload): array
    {
        $this->ensureStorage();
        $definitions = $this->getDefinitions();
        $actor = $this->getActor();
        $now = time();

        Db::startTrans();
        try {
            foreach ($definitions as $key => $definition) {
                if ((int) ($definition['locked'] ?? 0) === 1) {
                    continue;
                }

                $enabled = !empty($payload[$key]) ? 1 : 0;
                $existing = Db::name($this->table)->where('module_key', $key)->find();
                $row = [
                    'module_key' => $key,
                    'module_name' => (string) $definition['title'],
                    'module_type' => (string) $definition['type'],
                    'icon' => (string) $definition['icon'],
                    'description' => (string) $definition['description'],
                    'entry_route' => (string) $definition['route'],
                    'is_enabled' => $enabled,
                    'is_locked' => (int) ($definition['locked'] ?? 0),
                    'sort_no' => (int) ($definition['sort_no'] ?? 0),
                    'updated_by_admin_id' => (int) $actor['admin_id'],
                    'updated_by_name' => (string) $actor['name'],
                    'updated_at' => date('Y-m-d H:i:s'),
                    'updatetime' => $now,
                ];

                if ($existing) {
                    Db::name($this->table)->where('id', (int) $existing['id'])->update($row);
                } else {
                    $row['legacy_id'] = 'module_' . $key;
                    $row['created_by_admin_id'] = (int) $actor['admin_id'];
                    $row['created_by_name'] = (string) $actor['name'];
                    $row['createtime'] = $now;
                    Db::name($this->table)->insert($row);
                }
            }

            $this->syncRuleVisibility();
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }

        return $this->getModules();
    }

    public function syncRuleVisibility(): void
    {
        if (!$this->tableExists('auth_rule')) {
            return;
        }

        $modules = $this->getModules();
        foreach ($modules as $module) {
            $enabled = (int) ($module['is_enabled'] ?? 0) === 1;
            foreach ((array) ($module['rule_prefixes'] ?? []) as $prefix) {
                $query = Db::name('auth_rule')->where('name', 'like', $prefix . '%');
                $query->update([
                    'status' => $enabled ? 'normal' : 'hidden',
                    'updatetime' => time(),
                ]);
            }
        }
    }

    public function guard(string $moduleKey): void
    {
        if ($this->isEnabled($moduleKey)) {
            return;
        }

        $module = $this->getModule($moduleKey);
        $title = (string) ($module['title'] ?? $moduleKey);
        throw new Exception($title . '已关闭，请在 系统资料 -> 模块中心 开启后再使用。');
    }

    public function ensureStorage(): void
    {
        if ($this->tableExists($this->table)) {
            $this->ensureDefaultRows();
            return;
        }

        $prefix = (string) config('database.prefix');
        $table = $prefix . $this->table;
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `{$table}` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_id` varchar(64) NOT NULL DEFAULT '',
  `module_key` varchar(50) NOT NULL DEFAULT '',
  `module_name` varchar(100) NOT NULL DEFAULT '',
  `module_type` varchar(20) NOT NULL DEFAULT 'plugin',
  `icon` varchar(50) NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL DEFAULT '',
  `entry_route` varchar(100) NOT NULL DEFAULT '',
  `is_enabled` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `is_locked` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `sort_no` int(10) unsigned NOT NULL DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `created_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `created_by_name` varchar(50) NOT NULL DEFAULT '',
  `updated_by_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `updated_by_name` varchar(50) NOT NULL DEFAULT '',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_module_key` (`module_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
        Db::execute($sql);
        $this->ensureDefaultRows();
    }

    protected function ensureDefaultRows(): void
    {
        $definitions = $this->getDefinitions();
        if (empty($definitions)) {
            return;
        }

        $actor = $this->getActor();
        $now = time();
        foreach ($definitions as $key => $definition) {
            $existing = Db::name($this->table)->where('module_key', $key)->find();
            if ($existing) {
                continue;
            }

            Db::name($this->table)->insert([
                'legacy_id' => 'module_' . $key,
                'module_key' => $key,
                'module_name' => (string) $definition['title'],
                'module_type' => (string) $definition['type'],
                'icon' => (string) $definition['icon'],
                'description' => (string) $definition['description'],
                'entry_route' => (string) $definition['route'],
                'is_enabled' => (int) ($definition['default_enabled'] ?? 1),
                'is_locked' => (int) ($definition['locked'] ?? 0),
                'sort_no' => (int) ($definition['sort_no'] ?? 0),
                'updated_at' => date('Y-m-d H:i:s'),
                'created_by_admin_id' => (int) $actor['admin_id'],
                'created_by_name' => (string) $actor['name'],
                'updated_by_admin_id' => (int) $actor['admin_id'],
                'updated_by_name' => (string) $actor['name'],
                'createtime' => $now,
                'updatetime' => $now,
            ]);
        }
    }

    protected function getStoredRows(): array
    {
        if (!$this->tableExists($this->table)) {
            return [];
        }

        $this->ensureDefaultRows();
        $rows = Db::name($this->table)->select();
        $map = [];
        foreach ($rows as $row) {
            $map[(string) ($row['module_key'] ?? '')] = $row;
        }

        return $map;
    }

    protected function getActor(): array
    {
        $admin = session('admin');
        $adminId = (int) ($admin['id'] ?? 0);
        $name = (string) ($admin['nickname'] ?? $admin['username'] ?? '系统管理员');

        return [
            'admin_id' => $adminId,
            'name' => $name,
        ];
    }

    protected function tableExists(string $table): bool
    {
        static $cache = [];
        if (array_key_exists($table, $cache)) {
            return $cache[$table];
        }

        $fullTable = config('database.prefix') . $table;
        $cache[$table] = !empty(Db::query("SHOW TABLES LIKE '{$fullTable}'"));

        return $cache[$table];
    }

    protected function getErpConfig(): array
    {
        static $config = null;
        if ($config !== null) {
            return $config;
        }

        $config = (array) config('erp');
        if (!empty($config)) {
            return $config;
        }

        $path = APP_PATH . 'extra' . DIRECTORY_SEPARATOR . 'erp.php';
        if (is_file($path)) {
            $loaded = include $path;
            $config = is_array($loaded) ? $loaded : [];
        } else {
            $config = [];
        }

        return $config;
    }
}
