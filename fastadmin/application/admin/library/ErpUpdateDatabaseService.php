<?php

namespace app\admin\library;

use RuntimeException;
use think\Config;

class ErpUpdateDatabaseService
{
    protected $rootPath = '';
    protected $dataPath = '';
    protected $backupPath = '';

    public function __construct(string $rootPath = '')
    {
        $this->rootPath = $this->normalizePath($rootPath !== '' ? $rootPath : ROOT_PATH);
        $this->dataPath = $this->rootPath . '/data/updater';
        $this->backupPath = $this->dataPath . '/backups/database';
    }

    public function overview(array $manifest = []): array
    {
        $config = [
            'database' => '',
            'prefix' => 'fa_',
        ];
        $tableName = $this->resolveMigrationTable($manifest, $config);
        $backups = $this->listDatabaseBackups();
        $result = [
            'database_name' => '',
            'migration_table' => $tableName,
            'applied_count' => 0,
            'last_migration_id' => '',
            'last_applied_at' => '',
            'backups' => $backups,
            'last_backup' => $backups ? $backups[0] : null,
            'backup_mode' => $this->resolveBackupMode($manifest),
            'migration_strategy' => $this->resolveMigrationStrategy($manifest),
            'error' => '',
        ];

        try {
            $config = $this->loadDatabaseConfig();
            $tableName = $this->resolveMigrationTable($manifest, $config);
            $result['database_name'] = (string) ($config['database'] ?? '');
            $result['migration_table'] = $tableName;
            $pdo = $this->createDatabasePdo($config);
            $this->ensureMigrationTable($pdo, $tableName);
            $summary = $this->fetchMigrationSummary($pdo, $tableName);
            $result['applied_count'] = (int) $summary['applied_count'];
            $result['last_migration_id'] = (string) $summary['last_migration_id'];
            $result['last_applied_at'] = (string) $summary['last_applied_at'];
        } catch (\Throwable $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    public function buildEnvironmentChecks(array $manifest = []): array
    {
        $config = [
            'prefix' => 'fa_',
        ];
        $tableName = $this->resolveMigrationTable($manifest, $config);
        $dbConnectOk = false;
        $migrationTableOk = false;

        try {
            $config = $this->loadDatabaseConfig();
            $tableName = $this->resolveMigrationTable($manifest, $config);
            $pdo = $this->createDatabasePdo($config);
            $dbConnectOk = true;
            $this->ensureMigrationTable($pdo, $tableName);
            $migrationTableOk = true;
        } catch (\Throwable $e) {
            $dbConnectOk = false;
            $migrationTableOk = false;
        }

        return [
            [
                'label' => '数据库连接可用',
                'ok' => $dbConnectOk,
            ],
            [
                'label' => '数据库迁移账本可写',
                'ok' => $migrationTableOk,
            ],
            [
                'label' => '数据库备份目录可写',
                'ok' => $this->pathWritable($this->backupPath) || $this->pathWritable($this->dataPath) || $this->pathWritable($this->rootPath . '/data') || $this->pathWritable($this->rootPath),
            ],
        ];
    }

    public function createDatabaseBackupIfNeeded(array $manifest, string $label = ''): array
    {
        if (!$this->shouldCreateBackup($manifest)) {
            return [
                'created' => false,
                'reason' => 'not-required',
            ];
        }

        $config = $this->loadDatabaseConfig();
        $pdo = $this->createDatabasePdo($config);
        $this->ensureDirectory($this->backupPath);

        $safeLabel = $label !== '' ? preg_replace('/[^a-z0-9\-]+/i', '-', strtolower($label)) : 'update';
        $safeLabel = trim((string) $safeLabel, '-');
        if ($safeLabel === '') {
            $safeLabel = 'update';
        }

        $fileName = sprintf('db-%s-%s.sql', $safeLabel, date('Ymd-His'));
        $filePath = $this->backupPath . '/' . $fileName;
        $this->dumpDatabase($pdo, (string) $config['database'], $filePath);

        return [
            'created' => true,
            'name' => $fileName,
            'path' => $filePath,
            'size' => is_file($filePath) ? (int) filesize($filePath) : 0,
            'database' => (string) $config['database'],
            'time' => date('c'),
        ];
    }

    public function createDatabaseBackup(string $label = ''): array
    {
        return $this->createDatabaseBackupIfNeeded([
            'database' => [
                'backup_mode' => 'always',
            ],
        ], $label);
    }

    public function restoreDatabaseBackup(string $backupName): array
    {
        $backupName = trim($backupName);
        if ($backupName === '') {
            throw new RuntimeException('数据库回滚失败：缺少数据库备份文件。');
        }

        $filePath = $this->backupPath . '/' . $backupName;
        if (!is_file($filePath)) {
            throw new RuntimeException('数据库回滚失败：未找到数据库备份文件 ' . $backupName);
        }

        $config = $this->loadDatabaseConfig();
        $pdo = $this->createDatabasePdo($config);
        $this->importSqlFile($pdo, $filePath);

        return [
            'name' => $backupName,
            'path' => $filePath,
            'size' => (int) filesize($filePath),
            'time' => date('c', (int) filemtime($filePath)),
            'database' => (string) $config['database'],
        ];
    }

    public function applyManifestMigrations(array $manifest, string $packageRoot, string $sourceRef = ''): array
    {
        $migrations = $this->normalizeManifestMigrations($manifest);
        if (!$migrations) {
            return [
                'total' => 0,
                'applied_ids' => [],
                'skipped_ids' => [],
                'failed_id' => '',
                'strategy' => $this->resolveMigrationStrategy($manifest),
            ];
        }

        $config = $this->loadDatabaseConfig();
        $tableName = $this->resolveMigrationTable($manifest, $config);
        $pdo = $this->createDatabasePdo($config);
        $this->ensureMigrationTable($pdo, $tableName);
        $existing = $this->loadMigrationRecords($pdo, $tableName);
        $batchNo = date('YmdHis');
        $appliedIds = [];
        $skippedIds = [];

        foreach ($migrations as $migration) {
            $migrationId = $migration['id'];
            $checksum = $migration['checksum'];
            $file = $migration['file'];
            $description = $migration['description'];

            if (isset($existing[$migrationId]) && $existing[$migrationId]['status'] === 'applied') {
                $existingChecksum = (string) ($existing[$migrationId]['checksum'] ?? '');
                if ($checksum !== '' && $existingChecksum !== '' && strcasecmp($checksum, $existingChecksum) !== 0) {
                    throw new RuntimeException('数据库迁移已执行但内容发生变化，请使用新的 migration id 发布：' . $migrationId);
                }
                $skippedIds[] = $migrationId;
                continue;
            }

            $fullPath = $packageRoot . '/' . ltrim(str_replace('\\', '/', $file), '/');
            if (!is_file($fullPath)) {
                throw new RuntimeException('未找到数据库迁移文件：' . $file);
            }

            $sql = trim((string) file_get_contents($fullPath));
            if ($sql === '') {
                $skippedIds[] = $migrationId;
                continue;
            }

            $startedAt = date('Y-m-d H:i:s');
            $startedTs = time();
            $startTime = microtime(true);
            $this->upsertMigrationRecord($pdo, $tableName, [
                'migration_id' => $migrationId,
                'checksum' => $checksum,
                'status' => 'applying',
                'description' => $description,
                'source_ref' => $sourceRef,
                'batch_no' => $batchNo,
                'error_message' => '',
                'started_at' => $startedAt,
                'applied_at' => null,
                'execution_ms' => 0,
                'createtime' => $startedTs,
                'updatetime' => $startedTs,
            ]);

            try {
                $pdo->exec($sql);
                $duration = (int) round((microtime(true) - $startTime) * 1000);
                $appliedAt = date('Y-m-d H:i:s');
                $now = time();

                $this->upsertMigrationRecord($pdo, $tableName, [
                    'migration_id' => $migrationId,
                    'checksum' => $checksum,
                    'status' => 'applied',
                    'description' => $description,
                    'source_ref' => $sourceRef,
                    'batch_no' => $batchNo,
                    'error_message' => '',
                    'started_at' => $startedAt,
                    'applied_at' => $appliedAt,
                    'execution_ms' => $duration,
                    'createtime' => $startedTs,
                    'updatetime' => $now,
                ]);

                $appliedIds[] = $migrationId;
                $existing[$migrationId] = [
                    'status' => 'applied',
                    'checksum' => $checksum,
                ];
            } catch (\Throwable $e) {
                $duration = (int) round((microtime(true) - $startTime) * 1000);
                $now = time();

                $this->upsertMigrationRecord($pdo, $tableName, [
                    'migration_id' => $migrationId,
                    'checksum' => $checksum,
                    'status' => 'failed',
                    'description' => $description,
                    'source_ref' => $sourceRef,
                    'batch_no' => $batchNo,
                    'error_message' => $this->truncateMessage($e->getMessage()),
                    'started_at' => $startedAt,
                    'applied_at' => null,
                    'execution_ms' => $duration,
                    'createtime' => $startedTs,
                    'updatetime' => $now,
                ]);

                throw new RuntimeException('数据库迁移失败 [' . $migrationId . ']：' . $e->getMessage(), 0, $e);
            }
        }

        return [
            'total' => count($migrations),
            'applied_ids' => $appliedIds,
            'skipped_ids' => $skippedIds,
            'failed_id' => '',
            'strategy' => $this->resolveMigrationStrategy($manifest),
        ];
    }

    protected function normalizeManifestMigrations(array $manifest): array
    {
        $items = isset($manifest['migrations']) && is_array($manifest['migrations']) ? $manifest['migrations'] : [];
        $migrations = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $file = trim((string) ($item['file'] ?? ''));
            $id = trim((string) ($item['id'] ?? pathinfo($file, PATHINFO_FILENAME)));
            if ($file === '' || $id === '') {
                continue;
            }

            $migrations[] = [
                'id' => $id,
                'file' => $file,
                'checksum' => trim((string) ($item['checksum'] ?? '')),
                'description' => trim((string) ($item['description'] ?? $id)),
            ];
        }

        return $migrations;
    }

    protected function loadDatabaseConfig(): array
    {
        $database = (array) Config::get('database');
        $host = (string) ($database['hostname'] ?? '127.0.0.1');
        $port = (string) ($database['hostport'] ?? '3306');
        $name = (string) ($database['database'] ?? '');
        $user = (string) ($database['username'] ?? '');
        $password = (string) ($database['password'] ?? '');
        $prefix = (string) ($database['prefix'] ?? 'fa_');

        if ($name === '' || $user === '') {
            throw new RuntimeException('数据库配置不完整，无法执行在线升级。');
        }

        return [
            'hostname' => $host,
            'hostport' => $port !== '' ? $port : '3306',
            'database' => $name,
            'username' => $user,
            'password' => $password,
            'prefix' => $prefix !== '' ? $prefix : 'fa_',
        ];
    }

    protected function createDatabasePdo(array $config): \PDO
    {
        return new \PDO(
            sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $config['hostname'],
                $config['hostport'],
                $config['database']
            ),
            $config['username'],
            $config['password'],
            [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
            ]
        );
    }

    protected function ensureMigrationTable(\PDO $pdo, string $tableName): void
    {
        $pdo->exec(sprintf(
            "CREATE TABLE IF NOT EXISTS `%s` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `migration_id` varchar(120) NOT NULL DEFAULT '',
                `checksum` varchar(64) NOT NULL DEFAULT '',
                `status` enum('applying','applied','failed') NOT NULL DEFAULT 'applying',
                `description` varchar(255) NOT NULL DEFAULT '',
                `source_ref` varchar(120) NOT NULL DEFAULT '',
                `batch_no` varchar(40) NOT NULL DEFAULT '',
                `error_message` text,
                `started_at` datetime DEFAULT NULL,
                `applied_at` datetime DEFAULT NULL,
                `execution_ms` int(10) unsigned NOT NULL DEFAULT '0',
                `createtime` bigint(16) DEFAULT NULL,
                `updatetime` bigint(16) DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq_migration_id` (`migration_id`),
                KEY `idx_status_applied_at` (`status`,`applied_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ERP 在线升级数据库迁移记录';",
            $tableName
        ));
    }

    protected function fetchMigrationSummary(\PDO $pdo, string $tableName): array
    {
        $appliedCount = 0;
        $lastMigrationId = '';
        $lastAppliedAt = '';

        $countSql = sprintf("SELECT COUNT(*) AS aggregate FROM `%s` WHERE status = 'applied'", $tableName);
        $countRow = $pdo->query($countSql)->fetch(\PDO::FETCH_ASSOC);
        if (is_array($countRow)) {
            $appliedCount = (int) ($countRow['aggregate'] ?? 0);
        }

        $lastSql = sprintf(
            "SELECT migration_id, applied_at FROM `%s` WHERE status = 'applied' ORDER BY applied_at DESC, id DESC LIMIT 1",
            $tableName
        );
        $lastRow = $pdo->query($lastSql)->fetch(\PDO::FETCH_ASSOC);
        if (is_array($lastRow)) {
            $lastMigrationId = (string) ($lastRow['migration_id'] ?? '');
            $lastAppliedAt = (string) ($lastRow['applied_at'] ?? '');
        }

        return [
            'applied_count' => $appliedCount,
            'last_migration_id' => $lastMigrationId,
            'last_applied_at' => $lastAppliedAt,
        ];
    }

    protected function loadMigrationRecords(\PDO $pdo, string $tableName): array
    {
        $sql = sprintf("SELECT migration_id, checksum, status FROM `%s`", $tableName);
        $rows = $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $records = [];

        foreach ($rows as $row) {
            $migrationId = (string) ($row['migration_id'] ?? '');
            if ($migrationId === '') {
                continue;
            }

            $records[$migrationId] = [
                'checksum' => (string) ($row['checksum'] ?? ''),
                'status' => (string) ($row['status'] ?? ''),
            ];
        }

        return $records;
    }

    protected function upsertMigrationRecord(\PDO $pdo, string $tableName, array $record): void
    {
        $sql = sprintf(
            "INSERT INTO `%s`
                (`migration_id`, `checksum`, `status`, `description`, `source_ref`, `batch_no`, `error_message`, `started_at`, `applied_at`, `execution_ms`, `createtime`, `updatetime`)
             VALUES
                (:migration_id, :checksum, :status, :description, :source_ref, :batch_no, :error_message, :started_at, :applied_at, :execution_ms, :createtime, :updatetime)
             ON DUPLICATE KEY UPDATE
                `checksum` = VALUES(`checksum`),
                `status` = VALUES(`status`),
                `description` = VALUES(`description`),
                `source_ref` = VALUES(`source_ref`),
                `batch_no` = VALUES(`batch_no`),
                `error_message` = VALUES(`error_message`),
                `started_at` = VALUES(`started_at`),
                `applied_at` = VALUES(`applied_at`),
                `execution_ms` = VALUES(`execution_ms`),
                `updatetime` = VALUES(`updatetime`)",
            $tableName
        );

        $statement = $pdo->prepare($sql);
        $statement->execute([
            ':migration_id' => (string) $record['migration_id'],
            ':checksum' => (string) $record['checksum'],
            ':status' => (string) $record['status'],
            ':description' => (string) $record['description'],
            ':source_ref' => (string) $record['source_ref'],
            ':batch_no' => (string) $record['batch_no'],
            ':error_message' => (string) $record['error_message'],
            ':started_at' => $record['started_at'],
            ':applied_at' => $record['applied_at'],
            ':execution_ms' => (int) $record['execution_ms'],
            ':createtime' => (int) $record['createtime'],
            ':updatetime' => (int) $record['updatetime'],
        ]);
    }

    protected function shouldCreateBackup(array $manifest): bool
    {
        $mode = $this->resolveBackupMode($manifest);
        if ($mode === 'always') {
            return true;
        }

        if ($mode === 'never') {
            return false;
        }

        return !empty($manifest['migrations']) && is_array($manifest['migrations']);
    }

    protected function resolveBackupMode(array $manifest): string
    {
        $database = isset($manifest['database']) && is_array($manifest['database']) ? $manifest['database'] : [];
        $mode = strtolower(trim((string) ($database['backup_mode'] ?? 'when_migrations')));

        if (in_array($mode, ['always', 'never'], true)) {
            return $mode;
        }

        return 'when_migrations';
    }

    protected function resolveMigrationStrategy(array $manifest): string
    {
        $database = isset($manifest['database']) && is_array($manifest['database']) ? $manifest['database'] : [];
        $strategy = strtolower(trim((string) ($database['migration_strategy'] ?? 'pre_deploy')));

        return $strategy === 'post_deploy' ? 'post_deploy' : 'pre_deploy';
    }

    protected function resolveMigrationTable(array $manifest, array $config): string
    {
        $database = isset($manifest['database']) && is_array($manifest['database']) ? $manifest['database'] : [];
        $tableName = trim((string) ($database['migration_table'] ?? ''));
        if ($tableName !== '') {
            return $tableName;
        }

        return (string) $config['prefix'] . 'erp_update_migration';
    }

    protected function listDatabaseBackups(): array
    {
        if (!is_dir($this->backupPath)) {
            return [];
        }

        $items = [];
        foreach (glob($this->backupPath . '/*.sql') ?: [] as $file) {
            $items[] = [
                'name' => basename($file),
                'path' => $file,
                'size' => (int) filesize($file),
                'time' => date('c', (int) filemtime($file)),
            ];
        }

        usort($items, function (array $left, array $right): int {
            return strcmp((string) $right['time'], (string) $left['time']);
        });

        return array_slice($items, 0, 8);
    }

    protected function dumpDatabase(\PDO $pdo, string $database, string $file): void
    {
        $this->ensureDirectory(dirname($file));
        $handle = fopen($file, 'wb');
        if ($handle === false) {
            throw new RuntimeException('无法创建数据库备份文件：' . $file);
        }

        try {
            fwrite($handle, "SET NAMES utf8mb4;\n");
            fwrite($handle, "SET FOREIGN_KEY_CHECKS = 0;\n\n");

            $tables = $this->fetchTables($pdo, $database);
            foreach ($tables as $table) {
                fwrite($handle, sprintf("DROP TABLE IF EXISTS `%s`;\n", $table));

                $createRow = $pdo->query(sprintf("SHOW CREATE TABLE `%s`", $table))->fetch(\PDO::FETCH_ASSOC);
                $createSql = (string) ($createRow['Create Table'] ?? '');
                if ($createSql === '') {
                    throw new RuntimeException('无法获取建表语句：' . $table);
                }

                fwrite($handle, $createSql . ";\n\n");
                $this->writeTableRows($pdo, $table, $handle);
                fwrite($handle, "\n");
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS = 1;\n");
        } finally {
            fclose($handle);
        }
    }

    protected function importSqlFile(\PDO $pdo, string $file): void
    {
        $handle = fopen($file, 'rb');
        if ($handle === false) {
            throw new RuntimeException('无法读取数据库备份文件：' . $file);
        }

        $buffer = '';
        $inSingle = false;
        $inDouble = false;
        $escape = false;

        try {
            while (($line = fgets($handle)) !== false) {
                if ($buffer === '' && preg_match('/^\s*(--|#)/', $line)) {
                    continue;
                }

                $length = strlen($line);
                for ($index = 0; $index < $length; $index++) {
                    $char = $line[$index];
                    $buffer .= $char;

                    if ($escape) {
                        $escape = false;
                        continue;
                    }

                    if (($inSingle || $inDouble) && $char === '\\') {
                        $escape = true;
                        continue;
                    }

                    if ($char === "'" && !$inDouble) {
                        $inSingle = !$inSingle;
                        continue;
                    }

                    if ($char === '"' && !$inSingle) {
                        $inDouble = !$inDouble;
                        continue;
                    }

                    if ($char === ';' && !$inSingle && !$inDouble) {
                        $statement = trim($buffer);
                        $buffer = '';
                        if ($statement !== '') {
                            $pdo->exec($statement);
                        }
                    }
                }
            }

            $statement = trim($buffer);
            if ($statement !== '') {
                $pdo->exec($statement);
            }
        } finally {
            fclose($handle);
        }
    }

    protected function fetchTables(\PDO $pdo, string $database): array
    {
        $statement = $pdo->prepare(
            'SELECT table_name FROM information_schema.tables WHERE table_schema = :schema AND table_type = :type ORDER BY table_name'
        );
        $statement->execute([
            ':schema' => $database,
            ':type' => 'BASE TABLE',
        ]);

        return $statement->fetchAll(\PDO::FETCH_COLUMN) ?: [];
    }

    protected function writeTableRows(\PDO $pdo, string $table, $handle): void
    {
        $statement = $pdo->query(sprintf("SELECT * FROM `%s`", $table), \PDO::FETCH_ASSOC);
        if (!$statement) {
            return;
        }

        $columns = [];
        $batch = [];
        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            if (!$columns) {
                $columns = array_keys($row);
            }

            $batch[] = $this->buildInsertValueSql($pdo, $columns, $row);
            if (count($batch) >= 100) {
                $this->flushInsertBatch($table, $columns, $batch, $handle);
                $batch = [];
            }
        }

        if ($batch) {
            $this->flushInsertBatch($table, $columns, $batch, $handle);
        }
    }

    protected function flushInsertBatch(string $table, array $columns, array $batch, $handle): void
    {
        if (!$columns || !$batch) {
            return;
        }

        $columnSql = implode(', ', array_map(function (string $column): string {
            return sprintf('`%s`', $column);
        }, $columns));

        $sql = sprintf("INSERT INTO `%s` (%s) VALUES\n%s;\n", $table, $columnSql, implode(",\n", $batch));
        fwrite($handle, $sql);
    }

    protected function buildInsertValueSql(\PDO $pdo, array $columns, array $row): string
    {
        $values = [];
        foreach ($columns as $column) {
            $value = $row[$column];
            if ($value === null) {
                $values[] = 'NULL';
                continue;
            }

            if (is_bool($value)) {
                $values[] = $value ? '1' : '0';
                continue;
            }

            if (is_int($value) || is_float($value)) {
                $values[] = (string) $value;
                continue;
            }

            $values[] = $pdo->quote((string) $value);
        }

        return '(' . implode(', ', $values) . ')';
    }

    protected function truncateMessage(string $message): string
    {
        return mb_substr($message, 0, 1000, 'UTF-8');
    }

    protected function ensureDirectory(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        if (!mkdir($path, 0755, true) && !is_dir($path)) {
            throw new RuntimeException('无法创建目录：' . $path);
        }
    }

    protected function pathWritable(string $path): bool
    {
        if (is_dir($path) || is_file($path)) {
            return is_writable($path);
        }

        return is_writable(dirname($path));
    }

    protected function normalizePath(string $path): string
    {
        return rtrim(str_replace('\\', '/', $path), '/');
    }
}
