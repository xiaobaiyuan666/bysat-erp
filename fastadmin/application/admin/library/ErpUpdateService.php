<?php

namespace app\admin\library;

use RuntimeException;
use think\Config;

class ErpUpdateService
{
    public const SOURCE_BRANCH = 'branch';
    public const SOURCE_RELEASE = 'release';

    protected $rootPath = '';
    protected $dataPath = '';
    protected $configPath = '';
    protected $statePath = '';
    protected $historyPath = '';
    protected $backupPath = '';
    protected $tempPath = '';
    protected $manifestPath = '';
    protected $databaseService = null;

    public function __construct(string $rootPath = '')
    {
        $this->rootPath = $this->normalizePath($rootPath !== '' ? $rootPath : ROOT_PATH);
        $this->dataPath = $this->rootPath . '/data/updater';
        $this->configPath = $this->dataPath . '/config.json';
        $this->statePath = $this->dataPath . '/state.json';
        $this->historyPath = $this->dataPath . '/history.json';
        $this->backupPath = $this->dataPath . '/backups';
        $this->tempPath = $this->dataPath . '/tmp';
        $this->manifestPath = $this->rootPath . '/deploy/update-manifest.json';
        $this->databaseService = new ErpUpdateDatabaseService($this->rootPath);
    }

    public function overview(): array
    {
        $manifest = $this->loadManifest($this->manifestPath);
        $config = $this->loadConfig();
        $state = $this->loadState();

        return [
            'local' => $this->buildLocalInfo($manifest, $state),
            'config' => $this->presentConfig($config),
            'environment' => $this->buildEnvironmentChecks(),
            'database' => $this->databaseService->overview($manifest),
            'backups' => $this->listBackups(),
            'history' => $this->loadHistory(),
            'warnings' => $this->buildWarnings($config),
        ];
    }

    public function saveConfig(array $input): array
    {
        $current = $this->loadConfig();
        $config = $current;

        $config['source_mode'] = $this->normalizeSourceMode((string) ($input['source_mode'] ?? $current['source_mode']));
        $config['owner'] = $this->normalizeSimpleValue((string) ($input['owner'] ?? $current['owner']), '必须填写 GitHub 仓库所有者');
        $config['repo'] = $this->normalizeSimpleValue((string) ($input['repo'] ?? $current['repo']), '必须填写 GitHub 仓库名称');
        $config['branch'] = $this->normalizeSimpleValue((string) ($input['branch'] ?? $current['branch']), '必须填写 GitHub 分支');
        $config['release_tag'] = trim((string) ($input['release_tag'] ?? $current['release_tag']));
        $config['release_asset_pattern'] = trim((string) ($input['release_asset_pattern'] ?? $current['release_asset_pattern']));
        $config['package_subdir'] = trim((string) ($input['package_subdir'] ?? $current['package_subdir']));
        $config['skip_ssl_verify'] = !empty($input['skip_ssl_verify']);

        $token = trim((string) ($input['github_token'] ?? ''));
        if ($token !== '') {
            $config['github_token'] = $token;
        }

        $this->ensureDataDirectories();
        $this->writeJson($this->configPath, $config);

        return $this->presentConfig($config);
    }

    public function checkForUpdates(): array
    {
        $manifest = $this->loadManifest($this->manifestPath);
        $state = $this->loadState();
        $config = $this->loadConfig();
        $local = $this->buildLocalInfo($manifest, $state);
        $remote = $this->fetchRemotePackageInfo($config);

        $currentRef = (string) ($local['current_ref'] ?? '');
        $remoteRef = (string) ($remote['ref'] ?? '');

        return [
            'local' => $local,
            'remote' => $remote,
            'update_available' => $remoteRef !== '' && strcasecmp($currentRef, $remoteRef) !== 0,
            'warnings' => $this->buildWarnings($config),
        ];
    }

    public function performUpdate(): array
    {
        ignore_user_abort(true);
        set_time_limit(0);

        $environment = $this->buildEnvironmentChecks();
        foreach ($environment as $item) {
            if (empty($item['ok'])) {
                throw new RuntimeException('更新环境检查未通过：' . $item['label']);
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
            $migrationResult = $this->applyMigrations($packageManifest, $packageRoot, (string) ($remote['ref'] ?? ''));
            $copyResult = $this->overlayPackage($packageRoot);
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
                'last_migrations' => $migrationResult['applied_ids'],
            ];

            $this->writeJson($this->statePath, $state);
            $history = $this->loadHistory();
            array_unshift($history, [
                'status' => '成功',
                'mode' => (string) $config['source_mode'],
                'label' => (string) ($remote['label'] ?? ''),
                'ref' => (string) ($remote['ref'] ?? ''),
                'backup' => $backup['name'],
                'database_backup' => (string) ($databaseBackup['name'] ?? ''),
                'time' => date('c'),
                'files' => $copyResult['files'],
                'directories' => $copyResult['directories'],
                'migrations' => $migrationResult['applied_ids'],
            ]);
            $this->writeJson($this->historyPath, array_slice($history, 0, 20));

            return [
                'remote' => $remote,
                'backup' => $backup,
                'database_backup' => $databaseBackup,
                'copy_result' => $copyResult,
                'migration_result' => $migrationResult,
                'admin_entry' => $this->detectAdminEntry(),
                'local' => $this->buildLocalInfo($packageManifest, $state),
            ];
        } catch (\Throwable $e) {
            $history = $this->loadHistory();
            array_unshift($history, [
                'status' => '失败',
                'mode' => (string) ($config['source_mode'] ?? ''),
                'label' => (string) ($remote['label'] ?? ''),
                'ref' => (string) ($remote['ref'] ?? ''),
                'backup' => $backup['name'] ?? '',
                'database_backup' => $databaseBackup['name'] ?? '',
                'time' => date('c'),
                'message' => $e->getMessage(),
            ]);
            $this->writeJson($this->historyPath, array_slice($history, 0, 20));
            throw $e;
        } finally {
            $this->releaseLock($lockHandle);
            $this->removeDirectory($tempDir);
        }
    }

    protected function buildLocalInfo(array $manifest, array $state): array
    {
        $currentRef = (string) ($state['current_ref'] ?? ($manifest['source']['commit'] ?? ''));
        $currentVersion = (string) ($state['current_version'] ?? ($manifest['version'] ?? ''));
        $currentLabel = (string) ($state['current_label'] ?? ($manifest['label'] ?? ''));

        return [
            'version' => $currentVersion !== '' ? $currentVersion : '未识别',
            'label' => $currentLabel !== '' ? $currentLabel : '当前安装版本',
            'current_ref' => $currentRef,
            'current_ref_short' => $currentRef !== '' ? substr($currentRef, 0, 8) : '-',
            'built_at' => (string) ($manifest['built_at'] ?? ''),
            'last_update_at' => (string) ($state['last_update_at'] ?? ''),
            'last_backup' => (string) ($state['last_backup'] ?? ''),
            'default_repo' => [
                'owner' => (string) ($manifest['source']['owner'] ?? ''),
                'repo' => (string) ($manifest['source']['repo'] ?? ''),
                'branch' => (string) ($manifest['source']['branch'] ?? 'master'),
            ],
        ];
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
                ? 'Token 已保存，留空即可保持当前配置。'
                : '公开仓库通常不需要填写 Token。',
        ];
    }

    protected function buildEnvironmentChecks(): array
    {
        return [
            ['label' => 'PHP 已启用 curl 扩展', 'ok' => extension_loaded('curl')],
            ['label' => 'PHP 已启用 zip 扩展', 'ok' => class_exists('ZipArchive')],
            ['label' => '项目根目录可写', 'ok' => is_writable($this->rootPath)],
            ['label' => '更新数据目录可写', 'ok' => $this->pathWritable($this->dataPath) || $this->pathWritable($this->rootPath . '/data') || $this->pathWritable($this->rootPath)],
            ['label' => '更新清单文件存在', 'ok' => is_file($this->manifestPath)],
        ];
    }

    protected function buildWarnings(array $config): array
    {
        $warnings = [];

        if ((string) $config['source_mode'] === self::SOURCE_BRANCH) {
            $warnings[] = '分支模式会直接拉取 GitHub 源码压缩包。当前会保留已有 vendor 文件，如果 composer 依赖发生变化，建议改用发布包模式。';
        }

        if (!extension_loaded('curl')) {
            $warnings[] = '请先启用 curl 扩展，再执行在线更新。';
        }

        if (!class_exists('ZipArchive')) {
            $warnings[] = '请先启用 zip 扩展，再执行在线更新。';
        }

        if (!empty($config['skip_ssl_verify'])) {
            $warnings[] = '当前已关闭 GitHub 请求的 SSL 校验。只有服务器缺少 CA 证书时才建议这样做。';
        }

        return $warnings;
    }

    protected function fetchRemotePackageInfo(array $config): array
    {
        if ((string) $config['source_mode'] === self::SOURCE_RELEASE) {
            return $this->fetchReleaseInfo($config);
        }

        return $this->fetchBranchInfo($config);
    }

    protected function fetchBranchInfo(array $config): array
    {
        $url = sprintf(
            'https://api.github.com/repos/%s/%s/commits/%s',
            rawurlencode((string) $config['owner']),
            rawurlencode((string) $config['repo']),
            rawurlencode((string) $config['branch'])
        );

        $data = $this->requestJson($url, $config);
        $commit = (array) ($data['commit'] ?? []);
        $author = (array) ($commit['author'] ?? []);

        return [
            'mode' => self::SOURCE_BRANCH,
            'label' => 'GitHub 分支',
            'version' => (string) ($data['sha'] ?? ''),
            'ref' => (string) ($data['sha'] ?? ''),
            'ref_short' => isset($data['sha']) ? substr((string) $data['sha'], 0, 8) : '',
            'published_at' => (string) ($author['date'] ?? ''),
            'message' => trim((string) ($commit['message'] ?? '')),
            'download_url' => sprintf(
                'https://api.github.com/repos/%s/%s/zipball/%s',
                rawurlencode((string) $config['owner']),
                rawurlencode((string) $config['repo']),
                rawurlencode((string) $config['branch'])
            ),
            'html_url' => sprintf(
                'https://github.com/%s/%s/commits/%s',
                rawurlencode((string) $config['owner']),
                rawurlencode((string) $config['repo']),
                rawurlencode((string) $config['branch'])
            ),
        ];
    }

    protected function fetchReleaseInfo(array $config): array
    {
        $tag = trim((string) ($config['release_tag'] ?? ''));
        $url = $tag === '' || strtolower($tag) === 'latest'
            ? sprintf(
                'https://api.github.com/repos/%s/%s/releases/latest',
                rawurlencode((string) $config['owner']),
                rawurlencode((string) $config['repo'])
            )
            : sprintf(
                'https://api.github.com/repos/%s/%s/releases/tags/%s',
                rawurlencode((string) $config['owner']),
                rawurlencode((string) $config['repo']),
                rawurlencode($tag)
            );

        $data = $this->requestJson($url, $config);
        $asset = $this->findReleaseAsset((array) ($data['assets'] ?? []), (string) $config['release_asset_pattern']);
        if (!$asset) {
            throw new RuntimeException('没有找到匹配的发布包，请先把完整部署压缩包上传到 GitHub Release。');
        }

        return [
            'mode' => self::SOURCE_RELEASE,
            'label' => 'GitHub 发布包',
            'version' => (string) ($data['tag_name'] ?? ''),
            'ref' => (string) ($data['target_commitish'] ?? ($data['tag_name'] ?? '')),
            'ref_short' => isset($data['target_commitish']) ? substr((string) $data['target_commitish'], 0, 8) : '',
            'published_at' => (string) ($data['published_at'] ?? ''),
            'message' => trim((string) ($data['body'] ?? '')),
            'download_url' => (string) ($asset['browser_download_url'] ?? ''),
            'html_url' => (string) ($data['html_url'] ?? ''),
            'asset_name' => (string) ($asset['name'] ?? ''),
        ];
    }

    protected function findReleaseAsset(array $assets, string $pattern): array
    {
        if ($pattern !== '') {
            foreach ($assets as $asset) {
                $name = (string) ($asset['name'] ?? '');
                if ($name !== '' && fnmatch($pattern, $name)) {
                    return (array) $asset;
                }
            }
        }

        foreach ($assets as $asset) {
            $name = strtolower((string) ($asset['name'] ?? ''));
            if (substr($name, -4) === '.zip') {
                return (array) $asset;
            }
        }

        return [];
    }

    protected function downloadFile(string $url, string $targetFile, array $config): void
    {
        $headers = $this->buildGithubHeaders($config);
        $this->ensureDirectory(dirname($targetFile));

        $handle = fopen($targetFile, 'wb');
        if ($handle === false) {
            throw new RuntimeException('无法创建更新包文件。');
        }

        $curl = curl_init($url);
        if ($curl === false) {
            fclose($handle);
            throw new RuntimeException('无法初始化下载请求。');
        }

        curl_setopt_array($curl, [
            CURLOPT_FILE => $handle,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_CONNECTTIMEOUT => 25,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => !$this->shouldSkipSslVerify($config),
            CURLOPT_SSL_VERIFYHOST => $this->shouldSkipSslVerify($config) ? 0 : 2,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_USERAGENT => 'bysat-erp-updater',
        ]);

        $result = curl_exec($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        $this->closeCurlHandle($curl);
        fclose($handle);

        if ($result === false || $statusCode >= 400) {
            @unlink($targetFile);
            if ($error !== '' && stripos($error, 'SSL certificate problem') !== false && !$this->shouldSkipSslVerify($config)) {
                throw new RuntimeException('更新包下载失败：SSL 校验未通过。请安装 CA 证书，或在更新中心勾选“跳过 SSL 校验”。');
            }
            throw new RuntimeException('更新包下载失败：' . ($error !== '' ? $error : 'HTTP ' . $statusCode));
        }
    }

    protected function requestJson(string $url, array $config): array
    {
        $headers = $this->buildGithubHeaders($config);
        $curl = curl_init($url);
        if ($curl === false) {
            throw new RuntimeException('无法初始化 GitHub 请求。');
        }

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => !$this->shouldSkipSslVerify($config),
            CURLOPT_SSL_VERIFYHOST => $this->shouldSkipSslVerify($config) ? 0 : 2,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_USERAGENT => 'bysat-erp-updater',
        ]);

        $body = curl_exec($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        $this->closeCurlHandle($curl);

        if ($body === false || $statusCode >= 400) {
            if ($error !== '' && stripos($error, 'SSL certificate problem') !== false && !$this->shouldSkipSslVerify($config)) {
                throw new RuntimeException('GitHub 请求失败：SSL 校验未通过。请安装 CA 证书，或在更新中心勾选“跳过 SSL 校验”。');
            }
            throw new RuntimeException('GitHub 请求失败：' . ($error !== '' ? $error : 'HTTP ' . $statusCode));
        }

        $data = json_decode((string) $body, true);
        if (!is_array($data)) {
            throw new RuntimeException('GitHub 返回的数据不是有效的 JSON。');
        }

        if (!empty($data['message']) && isset($data['documentation_url'])) {
            throw new RuntimeException('GitHub 请求失败：' . (string) $data['message']);
        }

        return $data;
    }

    protected function buildGithubHeaders(array $config): array
    {
        $headers = [
            'Accept: application/vnd.github+json',
            'X-GitHub-Api-Version: 2022-11-28',
        ];

        $token = trim((string) ($config['github_token'] ?? ''));
        if ($token !== '') {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        return $headers;
    }

    protected function closeCurlHandle($curl): void
    {
        if ($curl === null || $curl === false) {
            return;
        }

        // curl_close() is deprecated as of PHP 8.5 because CurlHandle cleanup is automatic.
        if (PHP_VERSION_ID < 80500) {
            curl_close($curl);
        }
    }

    protected function createBackup(array $remote): array
    {
        $this->ensureDataDirectories();

        $name = sprintf(
            'backup-%s-%s.zip',
            preg_replace('/[^a-z0-9\-]+/i', '-', strtolower((string) ($remote['mode'] ?? 'update'))),
            date('Ymd-His')
        );
        $file = $this->backupPath . '/' . $name;
        $zip = new \ZipArchive();

        if ($zip->open($file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('无法创建更新备份包。');
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->rootPath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $fullPath = $this->normalizePath($item->getPathname());
            $relativePath = ltrim(substr($fullPath, strlen($this->rootPath)), '/');
            if ($relativePath === '' || $this->shouldSkipBackupPath($relativePath)) {
                continue;
            }

            if ($item->isDir()) {
                $zip->addEmptyDir($relativePath);
                continue;
            }

            $zip->addFile($fullPath, $relativePath);
        }

        $zip->close();

        return [
            'name' => $name,
            'path' => $file,
            'size' => is_file($file) ? (int) filesize($file) : 0,
        ];
    }

    protected function overlayPackage(string $packageRoot): array
    {
        $directories = 0;
        $files = 0;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($packageRoot, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $sourcePath = $this->normalizePath($item->getPathname());
            $relativePath = ltrim(substr($sourcePath, strlen($packageRoot)), '/');
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
                throw new RuntimeException('复制更新文件失败：' . $relativePath);
            }
            $files++;
        }

        return [
            'directories' => $directories,
            'files' => $files,
        ];
    }

    protected function applyMigrations(array $manifest): array
    {
        $migrations = isset($manifest['migrations']) && is_array($manifest['migrations']) ? $manifest['migrations'] : [];
        $appliedIds = [];

        if (!$migrations) {
            return ['applied_ids' => []];
        }

        $pdo = $this->createDatabasePdo();
        foreach ($migrations as $migration) {
            if (!is_array($migration)) {
                continue;
            }

            $file = trim((string) ($migration['file'] ?? ''));
            $id = trim((string) ($migration['id'] ?? basename($file)));
            if ($file === '' || $id === '') {
                continue;
            }

            $fullPath = $this->rootPath . '/' . ltrim(str_replace('\\', '/', $file), '/');
            if (!is_file($fullPath)) {
                continue;
            }

            $sql = trim((string) file_get_contents($fullPath));
            if ($sql === '') {
                continue;
            }

            $pdo->exec($sql);
            $appliedIds[] = $id;
        }

        return ['applied_ids' => $appliedIds];
    }

    protected function createDatabasePdo(): \PDO
    {
        $database = (array) Config::get('database');
        $host = (string) ($database['hostname'] ?? '127.0.0.1');
        $port = (string) ($database['hostport'] ?? '3306');
        $name = (string) ($database['database'] ?? '');
        $user = (string) ($database['username'] ?? '');
        $password = (string) ($database['password'] ?? '');

        if ($name === '' || $user === '') {
            throw new RuntimeException('数据库配置不完整，无法执行更新迁移。');
        }

        return new \PDO(
            sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name),
            $user,
            $password,
            [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
            ]
        );
    }

    protected function detectPackageRoot(string $extractRoot, string $preferredSubdir): string
    {
        $preferredSubdir = trim($preferredSubdir, '/');
        if ($preferredSubdir !== '') {
            $candidate = $this->findPackageRootByTail($extractRoot, $preferredSubdir);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($extractRoot, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if (!$item->isDir()) {
                continue;
            }

            $candidate = $this->normalizePath($item->getPathname());
            if ($this->looksLikeAppRoot($candidate)) {
                return $candidate;
            }
        }

        throw new RuntimeException('无法识别下载包中的程序根目录。');
    }

    protected function findPackageRootByTail(string $extractRoot, string $tail): string
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($extractRoot, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if (!$item->isDir()) {
                continue;
            }

            $candidate = $this->normalizePath($item->getPathname());
            if (substr($candidate, -strlen('/' . $tail)) === '/' . $tail && $this->looksLikeAppRoot($candidate)) {
                return $candidate;
            }
        }

        return '';
    }

    protected function looksLikeAppRoot(string $path): bool
    {
        return is_dir($path . '/application/admin') && is_file($path . '/public/index.php');
    }

    protected function extractArchive(string $archiveFile, string $extractPath): void
    {
        $this->ensureDirectory($extractPath);
        $zip = new \ZipArchive();
        if ($zip->open($archiveFile) !== true) {
            throw new RuntimeException('无法打开下载得到的更新压缩包。');
        }

        if (!$zip->extractTo($extractPath)) {
            $zip->close();
            throw new RuntimeException('无法解压下载得到的更新压缩包。');
        }
        $zip->close();
    }

    protected function clearRuntime(): void
    {
        $runtimePath = $this->rootPath . '/runtime';
        if (!is_dir($runtimePath)) {
            return;
        }

        $iterator = new \FilesystemIterator($runtimePath, \FilesystemIterator::SKIP_DOTS);
        foreach ($iterator as $item) {
            $path = $this->normalizePath($item->getPathname());
            $relativePath = ltrim(substr($path, strlen($this->rootPath)), '/');
            if ($this->shouldPreservePath($relativePath)) {
                continue;
            }

            if ($item->isDir()) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }
    }

    protected function shouldPreservePath(string $relativePath): bool
    {
        $relativePath = trim(str_replace('\\', '/', $relativePath), '/');
        foreach ($this->preservePaths() as $path) {
            if ($relativePath === $path || strpos($relativePath, $path . '/') === 0) {
                return true;
            }
        }

        return false;
    }

    protected function shouldSkipBackupPath(string $relativePath): bool
    {
        $relativePath = trim(str_replace('\\', '/', $relativePath), '/');
        $skip = [
            'runtime',
            'data/updater/backups',
            'data/updater/tmp',
        ];

        foreach ($skip as $path) {
            if ($relativePath === $path || strpos($relativePath, $path . '/') === 0) {
                return true;
            }
        }

        return false;
    }

    protected function preservePaths(): array
    {
        return [
            '.env',
            'runtime',
            'public/uploads',
            'data/updater',
            'application/admin/command/Install/install.lock',
        ];
    }

    protected function ensureDataDirectories(): void
    {
        $this->ensureDirectory($this->dataPath);
        $this->ensureDirectory($this->backupPath);
        $this->ensureDirectory($this->tempPath);
    }

    protected function listBackups(): array
    {
        if (!is_dir($this->backupPath)) {
            return [];
        }

        $items = [];
        foreach (glob($this->backupPath . '/*.zip') ?: [] as $file) {
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

    protected function loadConfig(): array
    {
        $manifest = $this->loadManifest($this->manifestPath);
        $source = isset($manifest['source']) && is_array($manifest['source']) ? $manifest['source'] : [];
        $package = isset($manifest['package']) && is_array($manifest['package']) ? $manifest['package'] : [];
        $config = [
            'source_mode' => $this->normalizeSourceMode((string) ($package['preferred_source_mode'] ?? self::SOURCE_BRANCH)),
            'owner' => (string) ($source['owner'] ?? ''),
            'repo' => (string) ($source['repo'] ?? ''),
            'branch' => (string) ($source['branch'] ?? 'master'),
            'release_tag' => 'latest',
            'release_asset_pattern' => (string) ($package['release_asset_pattern'] ?? '*.zip'),
            'package_subdir' => (string) ($package['subdir'] ?? 'fastadmin'),
            'skip_ssl_verify' => false,
            'github_token' => '',
        ];

        if (is_file($this->configPath)) {
            $saved = json_decode((string) file_get_contents($this->configPath), true);
            if (is_array($saved)) {
                $config = array_merge($config, $saved);
            }
        }

        return $config;
    }

    protected function loadState(): array
    {
        if (!is_file($this->statePath)) {
            return [];
        }

        $data = json_decode((string) file_get_contents($this->statePath), true);

        return is_array($data) ? $data : [];
    }

    protected function loadHistory(): array
    {
        if (!is_file($this->historyPath)) {
            return [];
        }

        $data = json_decode((string) file_get_contents($this->historyPath), true);

        return is_array($data) ? $data : [];
    }

    protected function loadManifest(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }

        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) ? $data : [];
    }

    protected function detectAdminEntry(): string
    {
        $publicPath = $this->rootPath . '/public';
        if (!is_dir($publicPath)) {
            return 'admin.php';
        }

        $excluded = ['index.php', 'install.php', 'router.php'];
        foreach (glob($publicPath . '/*.php') ?: [] as $file) {
            $name = basename(str_replace('\\', '/', (string) $file));
            if (!in_array($name, $excluded, true)) {
                return $name;
            }
        }

        return 'admin.php';
    }

    protected function acquireLock()
    {
        $this->ensureDataDirectories();
        $file = $this->dataPath . '/update.lock';
        $handle = fopen($file, 'c+');
        if ($handle === false) {
            throw new RuntimeException('无法创建更新锁文件。');
        }

        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            throw new RuntimeException('已有其他更新任务正在执行，请稍后再试。');
        }

        return $handle;
    }

    protected function releaseLock($handle): void
    {
        if (!is_resource($handle)) {
            return;
        }

        flock($handle, LOCK_UN);
        fclose($handle);
    }

    protected function writeJson(string $path, array $data): void
    {
        $this->ensureDirectory(dirname($path));
        $result = file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        if ($result === false) {
            throw new RuntimeException('无法写入更新元数据：' . basename($path));
        }
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

    protected function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }

        @rmdir($path);
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

    protected function normalizeSourceMode(string $value): string
    {
        return $value === self::SOURCE_RELEASE ? self::SOURCE_RELEASE : self::SOURCE_BRANCH;
    }

    protected function normalizeSimpleValue(string $value, string $message): string
    {
        $value = trim($value);
        if ($value === '') {
            throw new RuntimeException($message);
        }

        return $value;
    }

    protected function shouldSkipSslVerify(array $config): bool
    {
        return !empty($config['skip_ssl_verify']);
    }
}
