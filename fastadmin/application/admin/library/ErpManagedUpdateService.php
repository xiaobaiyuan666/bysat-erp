<?php

namespace app\admin\library;

use RuntimeException;

class ErpManagedUpdateService extends ErpUpdateService
{
    public function overview(): array
    {
        $overview = parent::overview();
        if (!empty($overview['config']) && is_array($overview['config'])) {
            $overview['config'] = $this->presentConfig($this->loadConfig());
        }
        if (!empty($overview['warnings']) || isset($overview['warnings'])) {
            $overview['warnings'] = $this->buildWarnings($this->loadConfig());
        }

        return $overview;
    }

    public function checkForUpdates(): array
    {
        $data = parent::checkForUpdates();
        $data['warnings'] = $this->buildWarnings($this->loadConfig());

        return $data;
    }

    public function performUpdate(): array
    {
        ignore_user_abort(true);
        set_time_limit(0);

        $environment = $this->buildEnvironmentChecks();
        foreach ($environment as $item) {
            if (empty($item['ok'])) {
                throw new RuntimeException('更新环境检查未通过：' . (string) $item['label']);
            }
        }

        $config = $this->loadConfig();
        $remote = $this->fetchRemotePackageInfo($config);
        $lockHandle = $this->acquireLock();
        $tempDir = $this->tempPath . '/' . date('Ymd-His');
        $backup = null;
        $databaseBackup = [
            'created' => false,
            'reason' => 'not-started',
        ];

        try {
            $this->ensureDirectory($tempDir);
            $archiveFile = $tempDir . '/package.zip';
            $extractPath = $tempDir . '/extract';

            $this->downloadFile($remote['download_url'], $archiveFile, $config);
            $this->extractArchive($archiveFile, $extractPath);

            $packageRoot = $this->detectPackageRoot($extractPath, (string) $config['package_subdir']);
            $packageManifest = $this->loadManifest($packageRoot . '/deploy/update-manifest.json');

            $backup = $this->createBackup($remote);
            $databaseBackup = $this->databaseService->createDatabaseBackupIfNeeded(
                $packageManifest,
                (string) ($remote['ref_short'] ?? ($remote['ref'] ?? 'update'))
            );
            $migrationResult = $this->databaseService->applyManifestMigrations(
                $packageManifest,
                $packageRoot,
                (string) ($remote['ref'] ?? '')
            );
            $copyResult = $this->overlayPackage($packageRoot);
            $cleanupResult = $this->applyCleanupFromManifest($packageManifest);
            $this->clearRuntime();

            $state = [
                'current_ref' => (string) ($remote['ref'] ?? ''),
                'current_version' => (string) ($packageManifest['version'] ?? ($remote['version'] ?? '')),
                'current_label' => (string) ($remote['label'] ?? ''),
                'current_mode' => (string) $config['source_mode'],
                'last_update_at' => date('c'),
                'last_backup' => $backup['name'],
                'last_backup_size' => $backup['size'],
                'last_database_backup' => (string) ($databaseBackup['name'] ?? ''),
                'last_database_backup_time' => (string) ($databaseBackup['time'] ?? ''),
                'last_copy_result' => $copyResult,
                'last_cleanup_result' => $cleanupResult,
                'last_migrations' => $migrationResult['applied_ids'],
            ];

            $this->writeJson($this->statePath, $state);
            $history = $this->loadHistory();
            array_unshift($history, [
                'status' => '成功',
                'type' => 'update',
                'mode' => (string) $config['source_mode'],
                'label' => (string) ($remote['label'] ?? ''),
                'ref' => (string) ($remote['ref'] ?? ''),
                'backup' => $backup['name'],
                'database_backup' => (string) ($databaseBackup['name'] ?? ''),
                'time' => date('c'),
                'files' => $copyResult['files'],
                'directories' => $copyResult['directories'],
                'cleanup' => $cleanupResult['removed_files'],
                'migrations' => $migrationResult['applied_ids'],
            ]);
            $this->writeJson($this->historyPath, array_slice($history, 0, 30));

            return [
                'remote' => $remote,
                'backup' => $backup,
                'database_backup' => $databaseBackup,
                'copy_result' => $copyResult,
                'cleanup_result' => $cleanupResult,
                'migration_result' => $migrationResult,
                'admin_entry' => $this->detectAdminEntry(),
                'local' => $this->buildLocalInfo($packageManifest, $state),
            ];
        } catch (\Throwable $e) {
            $history = $this->loadHistory();
            array_unshift($history, [
                'status' => '失败',
                'type' => 'update',
                'mode' => (string) ($config['source_mode'] ?? ''),
                'label' => (string) ($remote['label'] ?? ''),
                'ref' => (string) ($remote['ref'] ?? ''),
                'backup' => $backup['name'] ?? '',
                'database_backup' => $databaseBackup['name'] ?? '',
                'time' => date('c'),
                'message' => $e->getMessage(),
            ]);
            $this->writeJson($this->historyPath, array_slice($history, 0, 30));
            throw $e;
        } finally {
            $this->releaseLock($lockHandle);
            $this->removeDirectory($tempDir);
        }
    }

    public function performRollback(int $historyIndex = 0): array
    {
        ignore_user_abort(true);
        set_time_limit(0);

        $history = $this->loadHistory();
        $target = $history[$historyIndex] ?? null;
        if (!$target || !is_array($target)) {
            throw new RuntimeException('未找到可回滚的更新记录。');
        }

        $codeBackupName = trim((string) ($target['backup'] ?? ''));
        if ($codeBackupName === '') {
            throw new RuntimeException('该记录没有可用的代码备份，无法回滚。');
        }

        $lockHandle = $this->acquireLock();
        $tempDir = $this->tempPath . '/rollback-' . date('Ymd-His');
        $safetyCodeBackup = null;
        $safetyDatabaseBackup = null;

        try {
            $this->ensureDirectory($tempDir);
            $safetyCodeBackup = $this->createBackup(['mode' => 'rollback-safety']);
            $safetyDatabaseBackup = $this->databaseService->createDatabaseBackup('rollback-safety');

            $restoredDatabase = null;
            $databaseBackupName = trim((string) ($target['database_backup'] ?? ''));
            if ($databaseBackupName !== '') {
                $restoredDatabase = $this->databaseService->restoreDatabaseBackup($databaseBackupName);
            }

            $copyResult = $this->restoreCodeBackup($codeBackupName, $tempDir);
            $this->clearRuntime();

            $state = $this->loadState();
            $state['current_ref'] = (string) ($target['ref'] ?? ($state['current_ref'] ?? ''));
            $state['current_label'] = (string) ($target['label'] ?? ($state['current_label'] ?? ''));
            $state['last_rollback_at'] = date('c');
            $state['last_rollback_target_backup'] = $codeBackupName;
            $state['last_rollback_target_database_backup'] = $databaseBackupName;
            $state['last_rollback_safety_backup'] = (string) ($safetyCodeBackup['name'] ?? '');
            $state['last_rollback_safety_database_backup'] = (string) ($safetyDatabaseBackup['name'] ?? '');
            $this->writeJson($this->statePath, $state);

            $newHistory = $this->loadHistory();
            array_unshift($newHistory, [
                'status' => '回滚成功',
                'type' => 'rollback',
                'label' => (string) ($target['label'] ?? ''),
                'ref' => (string) ($target['ref'] ?? ''),
                'backup' => $codeBackupName,
                'database_backup' => $databaseBackupName,
                'safety_backup' => (string) ($safetyCodeBackup['name'] ?? ''),
                'safety_database_backup' => (string) ($safetyDatabaseBackup['name'] ?? ''),
                'time' => date('c'),
                'files' => $copyResult['files'],
                'directories' => $copyResult['directories'],
            ]);
            $this->writeJson($this->historyPath, array_slice($newHistory, 0, 30));

            return [
                'rollback_target' => $target,
                'backup' => $safetyCodeBackup,
                'database_backup' => $safetyDatabaseBackup,
                'restored_database' => $restoredDatabase,
                'copy_result' => $copyResult,
                'admin_entry' => $this->detectAdminEntry(),
            ];
        } catch (\Throwable $e) {
            $newHistory = $this->loadHistory();
            array_unshift($newHistory, [
                'status' => '回滚失败',
                'type' => 'rollback',
                'label' => (string) ($target['label'] ?? ''),
                'ref' => (string) ($target['ref'] ?? ''),
                'backup' => $codeBackupName,
                'database_backup' => (string) ($target['database_backup'] ?? ''),
                'safety_backup' => $safetyCodeBackup['name'] ?? '',
                'safety_database_backup' => $safetyDatabaseBackup['name'] ?? '',
                'time' => date('c'),
                'message' => $e->getMessage(),
            ]);
            $this->writeJson($this->historyPath, array_slice($newHistory, 0, 30));
            throw $e;
        } finally {
            $this->releaseLock($lockHandle);
            $this->removeDirectory($tempDir);
        }
    }

    protected function buildEnvironmentChecks(): array
    {
        return array_merge([
            ['label' => 'PHP 已启用 curl 扩展', 'ok' => extension_loaded('curl')],
            ['label' => 'PHP 已启用 zip 扩展', 'ok' => class_exists('ZipArchive')],
            ['label' => '项目根目录可写', 'ok' => is_writable($this->rootPath)],
            ['label' => '更新数据目录可写', 'ok' => $this->pathWritable($this->dataPath) || $this->pathWritable($this->rootPath . '/data') || $this->pathWritable($this->rootPath)],
            ['label' => '更新清单文件存在', 'ok' => is_file($this->manifestPath)],
        ], $this->databaseService->buildEnvironmentChecks($this->loadManifest($this->manifestPath)));
    }

    protected function presentConfig(array $config): array
    {
        return [
            'source_mode' => (string) $config['source_mode'],
            'owner' => (string) $config['owner'],
            'repo' => (string) $config['repo'],
            'branch' => (string) $config['branch'],
            'release_tag' => (string) $config['release_tag'],
            'release_asset_pattern' => (string) $config['release_asset_pattern'],
            'package_subdir' => (string) $config['package_subdir'],
            'skip_ssl_verify' => !empty($config['skip_ssl_verify']),
            'has_token' => trim((string) $config['github_token']) !== '',
            'token_hint' => trim((string) $config['github_token']) !== ''
                ? 'Token 已保存，留空即可保留当前配置。'
                : '公开仓库通常不需要填写 Token。',
        ];
    }

    protected function buildWarnings(array $config): array
    {
        $warnings = [];

        if ((string) $config['source_mode'] === self::SOURCE_BRANCH) {
            $warnings[] = '分支模式会直接拉取 GitHub 仓库压缩包。若 composer 依赖发生变化，建议使用 Release 发布包。';
        } else {
            $warnings[] = 'Release 模式既可以发布完整包，也可以发布增量 patch 包，适合正式环境稳定升级。';
        }

        if (!extension_loaded('curl')) {
            $warnings[] = '请先启用 curl 扩展，再执行在线更新。';
        }

        if (!class_exists('ZipArchive')) {
            $warnings[] = '请先启用 zip 扩展，再执行在线更新。';
        }

        if (!empty($config['skip_ssl_verify'])) {
            $warnings[] = '当前已关闭 GitHub 请求的 SSL 校验，仅建议在服务器缺少 CA 证书时临时使用。';
        }

        $warnings[] = '正式环境升级前建议先做一次宝塔整站快照。系统会自动备份代码；如版本包含数据库迁移，也会自动导出 SQL 备份。';

        return $warnings;
    }

    protected function fetchRemotePackageInfo(array $config): array
    {
        $remote = parent::fetchRemotePackageInfo($config);
        $remote['label'] = ((string) $config['source_mode'] === self::SOURCE_RELEASE) ? 'GitHub 发布包' : 'GitHub 分支';
        if (trim((string) ($remote['message'] ?? '')) === '') {
            $remote['message'] = '远端版本未提供额外说明。';
        }

        return $remote;
    }

    protected function looksLikeAppRoot(string $path): bool
    {
        if (parent::looksLikeAppRoot($path)) {
            return true;
        }

        $manifestPath = $path . '/deploy/update-manifest.json';
        if (!is_file($manifestPath)) {
            return false;
        }

        $manifest = $this->loadManifest($manifestPath);
        $package = isset($manifest['package']) && is_array($manifest['package']) ? $manifest['package'] : [];

        return strtolower((string) ($package['package_type'] ?? '')) === 'patch';
    }

    protected function restoreCodeBackup(string $backupName, string $tempDir): array
    {
        $backupFile = $this->backupPath . '/' . $backupName;
        if (!is_file($backupFile)) {
            throw new RuntimeException('代码回滚失败：未找到备份文件 ' . $backupName);
        }

        $extractPath = $tempDir . '/restore';
        $this->extractArchive($backupFile, $extractPath);

        $directories = 0;
        $files = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($extractPath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $sourcePath = $this->normalizePath($item->getPathname());
            $relativePath = ltrim(substr($sourcePath, strlen($extractPath)), '/');
            if ($relativePath === '' || $this->shouldPreservePath($relativePath)) {
                continue;
            }

            $targetPath = $this->rootPath . '/' . $relativePath;
            if ($item->isDir()) {
                $this->ensureDirectory($targetPath);
                $directories++;
                continue;
            }

            $this->ensureDirectory(dirname($targetPath));
            if (!copy($sourcePath, $targetPath)) {
                throw new RuntimeException('代码回滚失败：无法恢复文件 ' . $relativePath);
            }
            $files++;
        }

        return [
            'directories' => $directories,
            'files' => $files,
        ];
    }

    protected function applyCleanupFromManifest(array $manifest): array
    {
        $cleanup = isset($manifest['cleanup']) && is_array($manifest['cleanup']) ? $manifest['cleanup'] : [];
        $removeFiles = isset($cleanup['remove_files']) && is_array($cleanup['remove_files']) ? $cleanup['remove_files'] : [];
        $removed = 0;

        foreach ($removeFiles as $relativePath) {
            $relativePath = trim(str_replace('\\', '/', (string) $relativePath), '/');
            if ($relativePath === '' || $this->shouldPreservePath($relativePath)) {
                continue;
            }

            $targetPath = $this->rootPath . '/' . $relativePath;
            if (!file_exists($targetPath)) {
                continue;
            }

            if (is_dir($targetPath)) {
                $this->removeDirectory($targetPath);
            } else {
                @unlink($targetPath);
            }
            $removed++;
        }

        return [
            'removed_files' => $removed,
            'planned_remove_files' => count($removeFiles),
        ];
    }
}
