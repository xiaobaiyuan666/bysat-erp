<?php

namespace app\admin\library;

use think\Db;

class AiWorkspaceService
{
    public function presentSetting(array $row, bool $maskApiKey = true): array
    {
        $setting = $this->decorateSetting($row, $maskApiKey);
        $setting['endpoint'] = $this->buildEndpoint((string) ($setting['base_url'] ?? ''));
        $setting['diagnostic'] = $this->buildSettingDiagnostic($setting);

        return $setting;
    }

    public function getBootstrapData(int $settingId = 0): array
    {
        $setting = $settingId > 0 ? $this->getSettingById($settingId) : $this->getDefaultSetting();
        $contexts = $this->getContextMap();

        return [
            'setting' => $this->maskSetting($setting),
            'diagnostic' => $this->buildSettingDiagnostic($setting),
            'presets' => $this->getPresets(),
            'focuses' => $this->getFocuses(),
            'examples' => $this->getExamples(),
            'summary_cards' => $this->getSummaryCards($contexts),
            'messages' => $this->getConversationMessages(),
            'pending_task' => $this->getLatestPendingTask(),
            'context_sections' => $this->getContextSectionLabels(),
            'workspace_actions' => $this->getWorkspaceActions(),
        ];
    }

    public function submitTask(string $prompt, string $focus = 'overview', string $presetKey = '', int $settingId = 0, array $options = []): array
    {
        $prompt = trim($prompt);
        $preset = $this->getPresetByKey($presetKey);
        $focusConfig = $this->getFocusConfig($focus);
        $quickMode = !empty($options['quick_mode']);

        if ($prompt === '' && !$preset) {
            return [
                'ok' => false,
                'error' => '请输入问题，或者先点一个常用分析。',
            ];
        }

        $setting = $settingId > 0 ? $this->getSettingById($settingId) : $this->getDefaultSetting();
        if (!$setting || !$this->isConfigured($setting)) {
            return [
                'ok' => false,
                'error' => '当前还没有可用的模型配置，请先到 AI 配置里补齐 Base URL、API Key 和模型名称。',
                'diagnostic' => $this->buildSettingDiagnostic($setting),
                'workspace_actions' => $this->getWorkspaceActions($focusConfig['key']),
                'suggestions' => [],
            ];
        }

        $adminId = $this->getCurrentAdminId();
        if ($adminId <= 0) {
            return [
                'ok' => false,
                'error' => '登录状态已失效，请刷新页面后重试。',
            ];
        }

        $this->ensureTaskTable();

        $taskId = (int) Db::name('ai_task')->insertGetId([
            'admin_id' => $adminId,
            'prompt' => $prompt,
            'focus' => $focusConfig['key'],
            'preset_key' => $preset ? (string) $preset['key'] : '',
            'setting_id' => (int) ($setting['id'] ?? 0),
            'quick_mode' => $quickMode ? 1 : 0,
            'status' => 'queued',
            'result_json' => '',
            'error_message' => '',
            'started_at' => null,
            'finished_at' => null,
            'createtime' => time(),
            'updatetime' => time(),
        ]);

        $task = $this->getTaskRow($taskId);
        if (!$task) {
            return [
                'ok' => false,
                'error' => '任务创建失败，请稍后重试。',
            ];
        }

        return [
            'ok' => true,
            'task' => $this->presentTask($task),
            'setting' => $this->maskSetting($setting),
            'focus' => $focusConfig,
        ];
    }

    public function runTask(int $taskId): array
    {
        $this->ensureTaskTable();

        $task = $this->getTaskRow($taskId);
        if (!$task) {
            return [
                'ok' => false,
                'error' => '任务不存在或已被删除。',
            ];
        }

        if ((int) ($task['admin_id'] ?? 0) !== $this->getCurrentAdminId()) {
            return [
                'ok' => false,
                'error' => '你无权执行这条 AI 任务。',
            ];
        }

        $status = (string) ($task['status'] ?? 'queued');
        if (in_array($status, ['done', 'failed', 'processing'], true)) {
            return [
                'ok' => $status !== 'failed',
                'task' => $this->presentTask($task),
                'result' => $this->decodeTaskResult((string) ($task['result_json'] ?? '')),
                'error' => (string) ($task['error_message'] ?? ''),
            ];
        }

        $affected = Db::name('ai_task')
            ->where('id', $taskId)
            ->where('status', 'queued')
            ->update([
                'status' => 'processing',
                'started_at' => date('Y-m-d H:i:s'),
                'error_message' => '',
                'updatetime' => time(),
            ]);

        if (!$affected) {
            $latest = $this->getTaskRow($taskId);
            return [
                'ok' => true,
                'task' => $latest ? $this->presentTask($latest) : null,
                'result' => $latest ? $this->decodeTaskResult((string) ($latest['result_json'] ?? '')) : null,
            ];
        }

        @ignore_user_abort(true);
        @set_time_limit(0);

        $result = $this->ask(
            (string) ($task['prompt'] ?? ''),
            (string) ($task['focus'] ?? 'overview'),
            (string) ($task['preset_key'] ?? ''),
            (int) ($task['setting_id'] ?? 0),
            [
                'quick_mode' => !empty($task['quick_mode']),
            ]
        );

        if (!empty($result['ok'])) {
            Db::name('ai_task')
                ->where('id', $taskId)
                ->update([
                    'status' => 'done',
                    'result_json' => json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'error_message' => '',
                    'finished_at' => date('Y-m-d H:i:s'),
                    'updatetime' => time(),
                ]);
        } else {
            Db::name('ai_task')
                ->where('id', $taskId)
                ->update([
                    'status' => 'failed',
                    'result_json' => json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'error_message' => (string) ($result['error'] ?? 'AI 后台分析失败，请稍后重试。'),
                    'finished_at' => date('Y-m-d H:i:s'),
                    'updatetime' => time(),
                ]);
        }

        $latest = $this->getTaskRow($taskId);
        return [
            'ok' => !empty($result['ok']),
            'task' => $latest ? $this->presentTask($latest) : null,
            'result' => $result,
            'error' => (string) ($result['error'] ?? ''),
        ];
    }

    public function getTaskStatus(int $taskId): array
    {
        $this->ensureTaskTable();

        $task = $this->getTaskRow($taskId);
        if (!$task || (int) ($task['admin_id'] ?? 0) !== $this->getCurrentAdminId()) {
            return [
                'ok' => false,
                'error' => '任务不存在或你无权查看。',
            ];
        }

        return [
            'ok' => true,
            'task' => $this->presentTask($task),
            'result' => $this->decodeTaskResult((string) ($task['result_json'] ?? '')),
        ];
    }

    public function getSettingById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = Db::name('ai_setting')->where('id', $id)->find();
        return $row ? $this->applyDefaultFlag($this->presentSetting($row, false)) : null;
    }

    public function getDefaultSetting(): ?array
    {
        $rows = Db::name('ai_setting')->order('id', 'desc')->select();
        if (!$rows) {
            return null;
        }

        $decorated = [];
        foreach ($rows as $row) {
            $decorated[] = $this->presentSetting($row, false);
        }

        foreach ($decorated as $row) {
            if (!empty($row['is_default'])) {
                return $row;
            }
        }

        $decorated[0]['is_default'] = true;
        return $decorated[0];
    }

    public function getAvailableSettings(): array
    {
        $rows = Db::name('ai_setting')->order('id', 'desc')->select();
        if (!$rows) {
            return [];
        }

        $defaultId = 0;
        $items = [];
        foreach ($rows as $row) {
            $decorated = $this->presentSetting($row, true);
            if (!empty($decorated['is_default']) && $defaultId === 0) {
                $defaultId = (int) $decorated['id'];
            }
            $items[] = [
                'id' => (int) $decorated['id'],
                'provider_name' => (string) $decorated['provider_name'],
                'model' => (string) $decorated['model'],
                'base_url' => (string) $decorated['base_url'],
                'configured' => (bool) $decorated['configured'],
                'is_default' => (bool) $decorated['is_default'],
                'skip_ssl_verify' => (bool) $decorated['skip_ssl_verify'],
                'label' => trim((string) $decorated['provider_name'] . ' / ' . (string) $decorated['model'], ' /'),
                'diagnostic' => $this->buildSettingDiagnostic($decorated),
            ];
        }

        if ($defaultId === 0 && !empty($items[0]['id'])) {
            $defaultId = (int) $items[0]['id'];
        }

        foreach ($items as &$item) {
            $item['is_default'] = (int) $item['id'] === $defaultId;
        }
        unset($item);

        return $items;
    }

    public function markDefaultSetting(int $id): void
    {
        $rows = Db::name('ai_setting')->field('id,workspace_json')->select();
        if (!$rows) {
            return;
        }

        foreach ($rows as $row) {
            $meta = $this->decodeWorkspaceMeta((string) ($row['workspace_json'] ?? ''));
            $meta['is_default'] = (int) $row['id'] === $id;

            Db::name('ai_setting')
                ->where('id', (int) $row['id'])
                ->update([
                    'workspace_json' => json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'updatetime' => time(),
                ]);
        }
    }

    public function testSetting(array $setting): array
    {
        if (!$this->isConfigured($setting)) {
            return [
                'ok' => false,
                'error' => '当前配置还不完整，请先补齐 Base URL、API Key 和模型名称。',
            ];
        }

        return $this->requestModel($setting, [
            ['role' => 'system', 'content' => '你是连接测试助手，请只回复“连接正常”。'],
            ['role' => 'user', 'content' => '请只回复连接正常'],
        ]);
    }
    public function ask(string $prompt, string $focus = 'overview', string $presetKey = '', int $settingId = 0, array $options = []): array
    {
        $prompt = trim($prompt);
        $preset = $this->getPresetByKey($presetKey);
        $focusConfig = $this->getFocusConfig($focus);
        $quickMode = !empty($options['quick_mode']);

        if ($prompt === '' && !$preset) {
            return [
                'ok' => false,
                'error' => '请输入问题，或者先点一个常用分析。',
            ];
        }

        $setting = $settingId > 0 ? $this->getSettingById($settingId) : $this->getDefaultSetting();
        if (!$setting || !$this->isConfigured($setting)) {
            return [
                'ok' => false,
                'error' => '当前还没有可用的模型配置，请先到“AI 配置”里补齐 Base URL、API Key 和模型名称。',
                'diagnostic' => $this->buildSettingDiagnostic($setting),
                'workspace_actions' => $this->getWorkspaceActions($focusConfig['key']),
                'suggestions' => [],
            ];
        }

        $finalPrompt = $prompt;
        if ($preset) {
            $finalPrompt = $preset['prompt'];
            if ($prompt !== '') {
                $finalPrompt .= "\n\n用户补充：\n" . $prompt;
            }
            if ($focus === 'overview' && !empty($preset['focus'])) {
                $focusConfig = $this->getFocusConfig($preset['focus']);
            }
        }

        $contexts = $this->getContextMap();
        $context = [
            'generated_at' => date('Y-m-d H:i:s'),
            'focus' => $focusConfig['key'],
            'summary_cards' => $this->getSummaryCards($contexts),
        ];
        foreach (array_keys($contexts) as $module) {
            if ($focusConfig['key'] === 'overview' || $focusConfig['key'] === $module) {
                $context[$module] = $contexts[$module];
            }
        }
        $context = $this->compactContextForModel($context);

        $this->appendConversation('user', $finalPrompt);

        $messages = [
            ['role' => 'system', 'content' => $this->buildSystemPrompt($setting, $focusConfig['label'])],
            ['role' => 'system', 'content' => $this->buildContextPrompt($context)],
        ];

        foreach ($this->getConversationMessages(2) as $message) {
            $messages[] = [
                'role' => $message['role'],
                'content' => $message['content'],
            ];
        }

        $activeSetting = $setting;
        $diagnostic = $this->buildSettingDiagnostic($setting);
        $result = ['ok' => false, 'error' => ''];

        $retryMessages = [
            ['role' => 'system', 'content' => $this->buildSystemPrompt($setting, $focusConfig['label'])],
            ['role' => 'system', 'content' => $this->buildLeanContextPrompt($context)],
            ['role' => 'user', 'content' => $finalPrompt],
        ];

        if ($quickMode) {
            foreach ($this->buildQuickAttemptSettings($setting) as $candidateSetting) {
                $retry = $this->requestModel($candidateSetting, $retryMessages);
                if (!$retry['ok']) {
                    continue;
                }

                $result = $retry;
                $activeSetting = $candidateSetting;
                $diagnostic = [
                    'type' => 'warning',
                    'title' => '已启用快速模式',
                    'message' => '本次优先使用更快的模型和精简上下文，先给你可执行结果。如果需要更完整的分析，可以切到深度模式再问一次。',
                    'action_label' => '继续对话',
                    'action_url' => $this->makeUrl('ai/conversation/index', ['focus' => $focusConfig['key']]),
                ];
                break;
            }
        } else {
            $result = $this->requestModel($setting, $messages);
            if (!$result['ok'] && $this->shouldTryFallbackModel($result['error'] ?? '')) {
                $retry = $this->requestModel($setting, $retryMessages);
                if ($retry['ok']) {
                    $result = $retry;
                    $diagnostic = [
                        'type' => 'warning',
                        'title' => '已切到精简分析',
                        'message' => '完整上下文响应偏慢，本次已改用精简摘要完成回答。如果你更在意速度，建议把默认模型切到更快的版本。',
                        'action_label' => '调整模型',
                        'action_url' => $this->makeUrl('ai/setting/edit', ['ids' => (int) ($setting['id'] ?? 0)]),
                    ];
                } else {
                    foreach ($this->buildFallbackModels((string) ($setting['model'] ?? '')) as $fallbackModel) {
                        $fallbackSetting = $setting;
                        $fallbackSetting['model'] = $fallbackModel;
                        $retry = $this->requestModel($fallbackSetting, $retryMessages);
                        if (!$retry['ok']) {
                            continue;
                        }

                        $result = $retry;
                        $activeSetting = $fallbackSetting;
                        $diagnostic = [
                            'type' => 'warning',
                            'title' => '主模型响应偏慢，已自动切换',
                            'message' => '当前配置的 ' . (string) ($setting['model'] ?? '主模型') . ' 没有在限定时间内返回结果，本次已临时改用 ' . $fallbackModel . ' 完成回答。建议去 AI 配置里把默认模型切到更快的版本。',
                            'action_label' => '调整模型',
                            'action_url' => $this->makeUrl('ai/setting/edit', ['ids' => (int) ($setting['id'] ?? 0)]),
                        ];
                        break;
                    }
                }
            }
        }

        if (!$result['ok']) {
            $fallbackAnswer = $this->buildLocalFallbackAnswer($focusConfig['key'], $contexts, $finalPrompt);
            $this->appendConversation('assistant', $fallbackAnswer);

            return [
                'ok' => true,
                'answer' => $fallbackAnswer,
                'messages' => $this->getConversationMessages(),
                'setting' => $this->maskSetting($activeSetting),
                'diagnostic' => [
                    'type' => 'warning',
                    'title' => $quickMode ? '快速模式超时，已切换系统兜底分析' : '模型超时，已切换系统兜底分析',
                    'message' => $quickMode
                        ? '本次为了避免长时间等待，已经跳过慢模型并直接给出系统兜底分析。需要更完整的分析时，可以切到深度模式再问一次。'
                        : '当前网关没有在限定时间内返回结果，本次回答改用系统内置业务规则生成。你仍然可以继续打开财务、项目、项目运营或客户跟进草稿页处理。',
                    'action_label' => '调整模型',
                    'action_url' => $this->makeUrl('ai/setting/edit', ['ids' => (int) ($setting['id'] ?? 0)]),
                ],
                'summary_cards' => $this->getSummaryCards($contexts),
                'workspace_actions' => $this->getWorkspaceActions($focusConfig['key']),
                'suggestions' => $this->buildSuggestions($focusConfig['key'], $fallbackAnswer),
            ];
        }

        $this->appendConversation('assistant', $result['content']);

        return [
            'ok' => true,
            'answer' => $result['content'],
            'messages' => $this->getConversationMessages(),
            'setting' => $this->maskSetting($activeSetting),
            'diagnostic' => $diagnostic,
            'focus' => $focusConfig,
            'summary_cards' => $this->getSummaryCards($contexts),
            'workspace_actions' => $this->getWorkspaceActions($focusConfig['key']),
            'suggestions' => $this->buildSuggestions($focusConfig['key'], $result['content']),
        ];
    }
    public function clearConversation(): void
    {
        Db::name('ai_conversation')->where('id', '>', 0)->delete();
    }

    protected function getLatestPendingTask(): ?array
    {
        $this->ensureTaskTable();

        $adminId = $this->getCurrentAdminId();
        if ($adminId <= 0) {
            return null;
        }

        $task = Db::name('ai_task')
            ->where('admin_id', $adminId)
            ->where('status', 'in', ['queued', 'processing'])
            ->order('id', 'desc')
            ->find();

        return $task ? $this->presentTask($task) : null;
    }

    public function getConversationMessages(int $limit = 18): array
    {
        $rows = Db::name('ai_conversation')
            ->field('id,role,content,message_at')
            ->order('id', 'desc')
            ->limit($limit)
            ->select();

        $rows = array_reverse($rows ?: []);
        $messages = [];
        foreach ($rows as $row) {
            $content = trim((string) ($row['content'] ?? ''));
            if ($content === '' || ($row['role'] === 'assistant' && $content === '1')) {
                continue;
            }

            $messages[] = [
                'id' => (int) $row['id'],
                'role' => (string) $row['role'],
                'content' => $content,
                'message_at' => (string) ($row['message_at'] ?? ''),
            ];
        }

        return $messages;
    }

    protected function appendConversation(string $role, string $content): void
    {
        Db::name('ai_conversation')->insert([
            'role' => $role,
            'content' => trim($content),
            'message_at' => date('Y-m-d H:i:s'),
            'createtime' => time(),
            'updatetime' => time(),
        ]);
    }

    protected function getCurrentAdminId(): int
    {
        return (int) Auth::instance()->id;
    }

    protected function getTaskRow(int $taskId): ?array
    {
        if ($taskId <= 0) {
            return null;
        }

        $this->ensureTaskTable();

        $row = Db::name('ai_task')->where('id', $taskId)->find();
        return $row ?: null;
    }

    protected function presentTask(array $row): array
    {
        $status = (string) ($row['status'] ?? 'queued');
        $result = $this->decodeTaskResult((string) ($row['result_json'] ?? ''));
        $durationSeconds = $this->resolveTaskDurationSeconds($row);

        return [
            'id' => (int) ($row['id'] ?? 0),
            'status' => $status,
            'status_text' => $this->getTaskStatusText($status),
            'status_message' => $this->getTaskStatusMessage($status, !empty($row['quick_mode']), (string) ($row['error_message'] ?? '')),
            'prompt' => (string) ($row['prompt'] ?? ''),
            'focus' => (string) ($row['focus'] ?? 'overview'),
            'preset_key' => (string) ($row['preset_key'] ?? ''),
            'setting_id' => (int) ($row['setting_id'] ?? 0),
            'quick_mode' => !empty($row['quick_mode']),
            'started_at' => (string) ($row['started_at'] ?? ''),
            'finished_at' => (string) ($row['finished_at'] ?? ''),
            'created_at' => !empty($row['createtime']) ? date('Y-m-d H:i:s', (int) $row['createtime']) : '',
            'duration_seconds' => $durationSeconds,
            'duration_label' => $this->formatTaskDurationLabel($durationSeconds),
            'error_message' => (string) ($row['error_message'] ?? ''),
            'result' => $result,
        ];
    }

    protected function resolveTaskDurationSeconds(array $row): float
    {
        $createdAt = !empty($row['createtime']) ? (int) $row['createtime'] : 0;
        if ($createdAt <= 0) {
            return 0.0;
        }

        $finishedAt = !empty($row['finished_at']) ? strtotime((string) $row['finished_at']) : 0;
        $startedAt = !empty($row['started_at']) ? strtotime((string) $row['started_at']) : 0;

        $endAt = $finishedAt > 0 ? $finishedAt : time();
        $beginAt = $startedAt > 0 ? $startedAt : $createdAt;

        if ($endAt < $beginAt) {
            $beginAt = $createdAt;
        }

        return max(0, round($endAt - $beginAt, 1));
    }

    protected function formatTaskDurationLabel(float $seconds): string
    {
        if ($seconds <= 0) {
            return '';
        }

        if ($seconds < 60) {
            return rtrim(rtrim(number_format($seconds, $seconds < 10 ? 1 : 0, '.', ''), '0'), '.') . ' 秒';
        }

        $minutes = floor($seconds / 60);
        $remain = (int) round($seconds - ($minutes * 60));
        if ($remain <= 0) {
            return $minutes . ' 分钟';
        }

        return $minutes . ' 分 ' . $remain . ' 秒';
    }

    protected function decodeTaskResult(string $payload): ?array
    {
        $payload = trim($payload);
        if ($payload === '') {
            return null;
        }

        $decoded = json_decode($payload, true);
        return is_array($decoded) ? $decoded : null;
    }

    protected function getTaskStatusText(string $status): string
    {
        $map = [
            'queued' => '排队中',
            'processing' => '分析中',
            'done' => '已完成',
            'failed' => '失败',
        ];

        return $map[$status] ?? '未知';
    }

    protected function getTaskStatusMessage(string $status, bool $quickMode, string $errorMessage = ''): string
    {
        switch ($status) {
            case 'queued':
                return $quickMode
                    ? '任务已提交，正在排队做快速分析。你可以继续浏览其他页面。'
                    : '任务已提交，正在排队做深度分析。你可以继续浏览其他页面。';
            case 'processing':
                return $quickMode
                    ? 'AI 正在后台做快速分析，结果会自动回到当前页面。'
                    : 'AI 正在后台做深度分析，结果会自动回到当前页面。';
            case 'done':
                return '后台分析已完成。';
            case 'failed':
                return $errorMessage !== '' ? $errorMessage : '后台分析失败，请稍后重试。';
            default:
                return '任务状态未知，请刷新后重试。';
        }
    }

    protected function ensureTaskTable(): void
    {
        static $ensured = false;
        if ($ensured) {
            return;
        }

        $table = config('database.prefix') . 'ai_task';
        try {
            $exists = Db::query("SHOW TABLES LIKE '{$table}'");
            if ($exists) {
                $ensured = true;
                return;
            }
        } catch (\Throwable $e) {
        }

        Db::execute(
            "CREATE TABLE IF NOT EXISTS `{$table}` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '管理员',
                `prompt` text COMMENT '原始问题',
                `focus` varchar(30) NOT NULL DEFAULT 'overview' COMMENT '分析范围',
                `preset_key` varchar(60) NOT NULL DEFAULT '' COMMENT '预设',
                `setting_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '模型配置',
                `quick_mode` tinyint(1) NOT NULL DEFAULT '1' COMMENT '快速模式',
                `status` enum('queued','processing','done','failed') NOT NULL DEFAULT 'queued' COMMENT '任务状态',
                `result_json` longtext COMMENT '结果 JSON',
                `error_message` varchar(500) NOT NULL DEFAULT '' COMMENT '错误信息',
                `started_at` datetime DEFAULT NULL COMMENT '开始时间',
                `finished_at` datetime DEFAULT NULL COMMENT '完成时间',
                `createtime` bigint(16) DEFAULT NULL,
                `updatetime` bigint(16) DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_ai_task_admin_status` (`admin_id`,`status`),
                KEY `idx_ai_task_createtime` (`createtime`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI 后台任务'"
        );

        $ensured = true;
    }

    protected function decorateSetting(array $row, bool $maskApiKey = true): array
    {
        $meta = $this->decodeWorkspaceMeta((string) ($row['workspace_json'] ?? ''));
        $row['workspace_meta'] = $meta;
        $row['is_default'] = !empty($meta['is_default']);
        $row['skip_ssl_verify'] = !empty($meta['skip_ssl_verify']);
        $row['configured'] = $this->isConfigured($row);
        $row['temperature'] = isset($row['temperature']) ? (float) $row['temperature'] : 0.2;
        $row['updated_at_text'] = !empty($row['updatetime']) ? date('Y-m-d H:i:s', (int) $row['updatetime']) : '';
        if ($maskApiKey) {
            $row['api_key'] = $this->maskApiKey((string) ($row['api_key'] ?? ''));
        }

        return $row;
    }

    protected function maskSetting(?array $setting): ?array
    {
        if (!$setting) {
            return null;
        }

        $setting = $this->applyDefaultFlag($setting);
        $isDefault = !empty($setting['is_default']);
        $presented = $this->presentSetting($setting, true);
        if ($isDefault) {
            $presented['is_default'] = true;
        }

        return $presented;
    }

    protected function applyDefaultFlag(?array $setting): ?array
    {
        if (!$setting) {
            return null;
        }

        $defaultId = $this->resolveDefaultSettingId();
        if ($defaultId > 0 && (int) ($setting['id'] ?? 0) === $defaultId) {
            $setting['is_default'] = true;
        }

        return $setting;
    }

    protected function resolveDefaultSettingId(): int
    {
        static $defaultId = null;
        if ($defaultId !== null) {
            return $defaultId;
        }

        $rows = Db::name('ai_setting')->field('id,workspace_json')->order('id', 'desc')->select();
        if (!$rows) {
            $defaultId = 0;
            return $defaultId;
        }

        foreach ($rows as $row) {
            $meta = $this->decodeWorkspaceMeta((string) ($row['workspace_json'] ?? ''));
            if (!empty($meta['is_default'])) {
                $defaultId = (int) $row['id'];
                return $defaultId;
            }
        }

        $defaultId = (int) ($rows[0]['id'] ?? 0);
        return $defaultId;
    }

    protected function maskApiKey(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (strlen($value) <= 8) {
            return '******';
        }

        return substr($value, 0, 4) . '******' . substr($value, -4);
    }

    protected function decodeWorkspaceMeta(string $raw): array
    {
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $keys = array_keys($decoded);
        $isAssoc = $keys !== range(0, count($decoded) - 1);
        return $isAssoc ? $decoded : ['legacy_flags' => $decoded];
    }

    protected function isConfigured(array $setting): bool
    {
        return trim((string) ($setting['base_url'] ?? '')) !== ''
            && trim((string) ($setting['api_key'] ?? '')) !== ''
            && trim((string) ($setting['model'] ?? '')) !== '';
    }

    protected function buildEndpoint(string $baseUrl): string
    {
        $baseUrl = rtrim(trim($baseUrl), '/');
        if ($baseUrl === '') {
            return '';
        }
        if (substr($baseUrl, -17) === '/chat/completions') {
            return $baseUrl;
        }
        if (substr($baseUrl, -3) === '/v1') {
            return $baseUrl . '/chat/completions';
        }
        if (strpos($baseUrl, '/v1/') !== false) {
            return $baseUrl;
        }

        return $baseUrl . '/v1/chat/completions';
    }

    protected function buildSystemPrompt(array $setting, string $focusLabel): string
    {
        $defaultPrompt = implode("\n", [
            '你是软件公司的经营与执行助手，服务对象包括老板、财务负责人、项目经理、运营负责人和销售负责人。',
            '你的回答只能基于系统提供的数据，不允许编造不存在的金额、日期、客户、项目、项目运营事项、版本、审批或单据信息。',
            '默认使用简体中文，表达直接务实，优先给出今天就能执行的建议。',
            '回答时优先按下面结构输出：',
            '1. 先给结论',
            '2. 再给关键依据',
            '3. 再给建议动作（今天 / 本周 / 本月）',
            '4. 如果数据不足，请明确说明还缺什么',
            '当前分析视角：' . $focusLabel . '。',
        ]);

        $customPrompt = trim((string) ($setting['system_prompt'] ?? ''));
        if ($customPrompt !== '') {
            $defaultPrompt .= "\n\n附加要求：\n" . $customPrompt;
        }

        return $defaultPrompt;
    }
    protected function requestModel(array $setting, array $messages): array
    {
        @set_time_limit(60);
        $endpoint = $this->buildEndpoint((string) ($setting['base_url'] ?? ''));
        if ($endpoint === '') {
            return ['ok' => false, 'error' => '濡€崇€烽幒銉ュ經閸︽澘娼冮張顏堝帳缂冾喓鈧?'];
        }

        if ($this->isAnthropicCodingEndpoint((string) ($setting['base_url'] ?? ''))) {
            return [
                'ok' => false,
                'error' => '当前地址是 Anthropic Coding Plan 协议，不是 OpenAI 兼容协议。ERP 只能接 OpenAI 兼容的 chat/completions 接口，请改成通用兼容地址。',
            ];
        }

        if ($this->isDashscopeCodingEndpoint((string) ($setting['base_url'] ?? ''))) {
            return [
                'ok' => false,
                'error' => '当前地址是阿里云 Coding Plan 专用接口，不能直接给 ERP 业务系统使用。请改用百炼兼容模式或其他 OpenAI 兼容网关。',
            ];
        }

        if (!function_exists('curl_init')) {
            return ['ok' => false, 'error' => '当前 PHP 环境没有启用 cURL 扩展，模型请求无法发出。请先在服务器里启用 cURL。'];
        }

        $payload = [
            'model' => (string) $setting['model'],
            'temperature' => (float) ($setting['temperature'] ?? 0.2),
            'max_tokens' => isset($setting['max_tokens']) ? max(120, (int) $setting['max_tokens']) : 500,
            'messages' => $messages,
        ];

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];
        if (trim((string) ($setting['api_key'] ?? '')) !== '') {
            $headers[] = 'Authorization: Bearer ' . trim((string) $setting['api_key']);
        }

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_TIMEOUT => isset($setting['request_timeout']) ? max(8, (int) $setting['request_timeout']) : 45,
            CURLOPT_CONNECTTIMEOUT => isset($setting['connect_timeout']) ? max(3, (int) $setting['connect_timeout']) : 10,
            CURLOPT_NOSIGNAL => true,
        ]);

        if (!empty($setting['skip_ssl_verify'])) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        } else {
            $caBundle = $this->resolveCaBundlePath();
            if ($caBundle !== '') {
                curl_setopt($ch, CURLOPT_CAINFO, $caBundle);
            }
        }

        $raw = curl_exec($ch);
        $curlError = trim((string) curl_error($ch));
        $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($raw === false) {
            return ['ok' => false, 'error' => $this->normalizeCurlError($curlError, $setting)];
        }

        $decoded = json_decode($raw, true);
        if ($statusCode < 200 || $statusCode >= 300) {
            $message = $decoded['error']['message'] ?? ('模型接口返回异常 HTTP ' . $statusCode);
            return [
                'ok' => false,
                'error' => $this->normalizeHttpError((string) $message, $statusCode, $endpoint),
            ];
        }

        $content = $decoded['choices'][0]['message']['content'] ?? '';
        if (is_array($content)) {
            $content = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (!is_string($content) || trim($content) === '') {
            return ['ok' => false, 'error' => '模型已返回响应，但没有解析出有效内容。'];
        }

        return ['ok' => true, 'content' => trim($content)];
    }

    protected function buildSettingDiagnostic(?array $setting): ?array
    {
        if (!$setting) {
            return [
                'type' => 'warning',
                'title' => '还没有可用模型',
                'message' => '请先新增一条 OpenAI 兼容模型配置，再回来使用 AI 工作台。',
                'action_label' => '去新增模型',
                'action_url' => $this->makeUrl('ai/setting/add'),
            ];
        }

        if (!$this->isConfigured($setting)) {
            return [
                'type' => 'warning',
                'title' => '模型配置还不完整',
                'message' => '当前记录还缺 Base URL、API Key 或模型名称，请先补齐。',
                'action_label' => '去补配置',
                'action_url' => $this->makeUrl('ai/setting/edit', ['ids' => (int) ($setting['id'] ?? 0)]),
            ];
        }

        $baseUrl = trim((string) ($setting['base_url'] ?? ''));
        if ($baseUrl !== '' && $this->isAnthropicCodingEndpoint($baseUrl)) {
            return [
                'type' => 'danger',
                'title' => '当前地址和协议不匹配',
                'message' => '你填写的是 Coding Plan 的 Anthropic 兼容地址，但这套 ERP 当前走的是 OpenAI 协议，请改成通用 OpenAI 兼容接口。',
                'action_label' => '去修改配置',
                'action_url' => $this->makeUrl('ai/setting/edit', ['ids' => (int) ($setting['id'] ?? 0)]),
            ];
        }

        if ($baseUrl !== '' && $this->isDashscopeCodingEndpoint($baseUrl)) {
            return [
                'type' => 'danger',
                'title' => 'Coding Plan 不能接入当前系统',
                'message' => '阿里云官方限制 Coding Plan 仅用于编程工具，不能用于自定义业务系统后端。请改用阿里云百炼兼容模式或其他通用接口。',
                'action_label' => '去修改配置',
                'action_url' => $this->makeUrl('ai/setting/edit', ['ids' => (int) ($setting['id'] ?? 0)]),
            ];
        }

        $recommendedFastModel = $this->getPreferredFastModelCandidate((string) ($setting['model'] ?? ''));
        if ($recommendedFastModel !== '' && strcasecmp($recommendedFastModel, (string) ($setting['model'] ?? '')) !== 0) {
            return [
                'type' => 'info',
                'title' => '当前模型更偏完整推理',
                'message' => '这条配置可以用，但如果你更在意 AI 工作台响应速度，建议先切到“' . $recommendedFastModel . '”。列表页已经支持一键切换推荐模型。',
                'action_label' => '去 AI 配置',
                'action_url' => $this->makeUrl('ai/setting/index'),
            ];
        }

        if (!empty($setting['skip_ssl_verify'])) {
            return [
                'type' => 'info',
                'title' => '已开启跳过 SSL 校验',
                'message' => '当前配置会跳过证书校验，适合测试环境临时排障。正式环境建议关闭，并补齐 CA 证书链。',
            ];
        }

        return [
            'type' => 'success',
            'title' => '模型配置看起来正常',
            'message' => '可以直接在这里发问，或者去 AI 配置里点“测试连接”做连通性确认。',
        ];
    }

    protected function normalizeCurlError(string $curlError, array $setting): string
    {
        if ($curlError === '') {
            return '模型请求失败。';
        }

        if (stripos($curlError, 'unable to get local issuer certificate') !== false) {
            if (!empty($setting['skip_ssl_verify'])) {
                return '模型请求失败：当前机器的 SSL 证书链不完整，而且即使跳过校验仍然无法连接，请检查接口地址和网络环境。';
            }

            return '模型请求失败：当前机器的 PHP cURL 缺少 CA 证书链。系统会优先尝试使用内置证书包，如仍失败，请检查网络代理、公司根证书或本机证书链。';
        }

        if (stripos($curlError, 'timed out') !== false) {
            return '模型请求超时：目标网关没有在限定时间内返回结果，请先测试连接，或者换一个响应更快的模型。';
        }

        return '模型请求失败：' . $curlError;
    }

    protected function shouldTryFallbackModel(string $error): bool
    {
        return stripos($error, '超时') !== false || stripos($error, 'timed out') !== false;
    }

    protected function buildFallbackModels(string $model): array
    {
        $model = trim($model);
        if ($model === '') {
            return [];
        }

        $candidates = [];
        if (stripos($model, 'gpt-5.4') !== false) {
            $candidates[] = str_ireplace('gpt-5.4', 'gpt-5.2', $model);
            $candidates[] = str_ireplace('gpt-5.4', 'gpt-5.1', $model);
        } elseif (stripos($model, 'gpt5.4') !== false) {
            $candidates[] = str_ireplace('gpt5.4', 'gpt5.2', $model);
            $candidates[] = str_ireplace('gpt5.4', 'gpt5.1', $model);
        } elseif (stripos($model, 'gpt-5.2') !== false) {
            $candidates[] = str_ireplace('gpt-5.2', 'gpt-4.1-mini', $model);
            $candidates[] = str_ireplace('gpt-5.2', 'gpt-4o-mini', $model);
        } elseif (stripos($model, 'gpt5.2') !== false) {
            $candidates[] = str_ireplace('gpt5.2', 'gpt-4.1-mini', $model);
            $candidates[] = str_ireplace('gpt5.2', 'gpt-4o-mini', $model);
        }

        foreach ([
            'google/gemini-3-flash:free',
            'google/gemini-3-pro',
            'zai/glm-4.7:free',
            'deepseek-chat',
        ] as $candidate) {
            $candidates[] = $candidate;
        }

        $filtered = [];
        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate === '' || strcasecmp($candidate, $model) === 0 || in_array($candidate, $filtered, true)) {
                continue;
            }
            $filtered[] = $candidate;
        }

        return $filtered;
    }

    protected function getPreferredFastModelCandidate(string $model): string
    {
        $candidates = $this->buildFallbackModels($model);
        return $candidates ? (string) $candidates[0] : '';
    }

    protected function buildQuickAttemptSettings(array $setting): array
    {
        $candidates = [];
        $fastCandidates = $this->buildQuickModeModels((string) ($setting['model'] ?? ''));

        if ($fastCandidates) {
            $candidate = $setting;
            $candidate['model'] = $fastCandidates[0];
            $candidate['request_timeout'] = 15;
            $candidate['connect_timeout'] = 5;
            $candidate['max_tokens'] = 260;
            $candidates[] = $candidate;
        } else {
            $current = $setting;
            $current['request_timeout'] = 15;
            $current['connect_timeout'] = 5;
            $current['max_tokens'] = 260;
            $candidates[] = $current;
        }

        return $candidates;
    }

    protected function buildQuickModeModels(string $model): array
    {
        $models = [];

        foreach ([
            'google/gemini-3-flash:free',
            'zai/glm-4.7:free',
            'deepseek-chat',
        ] as $candidate) {
            if (!in_array($candidate, $models, true)) {
                $models[] = $candidate;
            }
        }

        foreach ($this->buildFallbackModels($model) as $candidate) {
            if (!in_array($candidate, $models, true)) {
                $models[] = $candidate;
            }
        }

        return $models;
    }


    protected function normalizeHttpError(string $message, int $statusCode, string $endpoint): string
    {
        if ($statusCode === 401 && stripos($endpoint, 'dashscope.aliyuncs.com') !== false) {
            return '模型接口认证失败：当前 API Key 不适用于这个阿里云地址，请检查是否使用了百炼兼容模式专用 Key。';
        }
        if ($statusCode === 404) {
            return '模型接口返回 404：当前网关地址不对，或者该地址并不提供 OpenAI 兼容的 chat/completions 接口。';
        }

        return $message;
    }
    protected function isDashscopeCodingEndpoint(string $baseUrl): bool
    {
        return stripos($baseUrl, 'coding.dashscope.aliyuncs.com') !== false;
    }

    protected function isAnthropicCodingEndpoint(string $baseUrl): bool
    {
        return stripos($baseUrl, '/apps/anthropic') !== false;
    }

    protected function resolveCaBundlePath(): string
    {
        $path = dirname(dirname(dirname(dirname(__DIR__)))) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'certs' . DIRECTORY_SEPARATOR . 'cacert.pem';
        return is_file($path) ? $path : '';
    }

    protected function getSummaryCards(array $contexts = []): array
    {
        if (!$contexts) {
            $contexts = $this->getContextMap();
        }

        $cards = [
            [
                'label' => '本月收入',
                'value' => $this->formatMoney($contexts['finance']['summary']['month_income']),
                'hint' => '本月财务流水收入合计',
            ],
            [
                'label' => '待回款',
                'value' => $this->formatMoney($contexts['finance']['summary']['open_receivable_amount']),
                'hint' => '未完成应收金额',
            ],
            [
                'label' => '待付款',
                'value' => $this->formatMoney($contexts['business']['summary']['open_payment_plan_amount']),
                'hint' => '付款计划里还未完成的金额',
            ],
            [
                'label' => '待审批',
                'value' => (string) $contexts['business']['summary']['pending_approval_count'],
                'hint' => '审批中心待处理记录数',
            ],
            [
                'label' => '交付中项目',
                'value' => (string) $contexts['project']['summary']['active_project_count'],
                'hint' => '执行中和交付中的项目',
            ],
        ];

        if (isset($contexts['app'])) {
            $cards[] = [
                'label' => '运营问题',
                'value' => (string) $contexts['app']['summary']['open_issue_count'],
                'hint' => '未关闭的问题记录',
            ];
        }

        return $cards;
    }
    protected function buildContext(string $focus): array
    {
        $contexts = $this->getContextMap();

        $context = [
            'generated_at' => date('Y-m-d H:i:s'),
            'focus' => $focus,
            'summary_cards' => $this->getSummaryCards($contexts),
        ];

        foreach (array_keys($contexts) as $module) {
            if ($focus === 'overview' || $focus === $module) {
                $context[$module] = $contexts[$module];
            }
        }

        return $context;
    }

    protected function compactContextForModel(array $context): array
    {
        if (isset($context['summary_cards']) && is_array($context['summary_cards'])) {
            $context['summary_cards'] = array_slice($context['summary_cards'], 0, 4);
        }

        foreach (['finance', 'project', 'app', 'business'] as $module) {
            if (empty($context[$module]) || !is_array($context[$module])) {
                continue;
            }

            foreach ($context[$module] as $key => $value) {
                if (!is_array($value) || !$this->isSequentialArray($value)) {
                    continue;
                }

                $context[$module][$key] = array_slice($value, 0, 3);
            }
        }

        return $context;
    }

    protected function buildContextPrompt(array $context): string
    {
        $lines = [
            '以下是当前业务工作台摘要，请严格基于这些信息回答，不要编造不存在的数据。',
        ];

        if (!empty($context['summary_cards']) && is_array($context['summary_cards'])) {
            $lines[] = '关键看板：';
            foreach ($context['summary_cards'] as $card) {
                $lines[] = '- ' . ($card['label'] ?? '指标') . '：' . ($card['value'] ?? '-') . '；说明：' . ($card['hint'] ?? '');
            }
        }

        foreach (['finance', 'project', 'app', 'business'] as $module) {
            if (empty($context[$module]) || !is_array($context[$module])) {
                continue;
            }

            $lines[] = strtoupper($module) . '：';
            foreach ($context[$module] as $section => $value) {
                if (is_array($value) && $this->isSequentialArray($value)) {
                    $lines[] = '- ' . $section . '：';
                    foreach (array_slice($value, 0, 3) as $index => $row) {
                        $lines[] = '  ' . ($index + 1) . '. ' . $this->flattenContextRow($row);
                    }
                    continue;
                }

                if (is_array($value)) {
                    $lines[] = '- ' . $section . '：' . $this->flattenContextRow($value);
                    continue;
                }

                $lines[] = '- ' . $section . '：' . (string) $value;
            }
        }

        return implode("\n", $lines);
    }

    protected function buildLeanContextPrompt(array $context): string
    {
        $lines = [
            '以下是本次分析的精简业务摘要，请基于这些信息回答。',
        ];

        if (!empty($context['summary_cards']) && is_array($context['summary_cards'])) {
            foreach ($context['summary_cards'] as $card) {
                $lines[] = '- ' . ($card['label'] ?? '指标') . '：' . ($card['value'] ?? '-');
            }
        }

        foreach (['finance', 'project', 'app', 'business'] as $module) {
            if (empty($context[$module]['summary']) || !is_array($context[$module]['summary'])) {
                continue;
            }

            $lines[] = strtoupper($module) . ' 概览：' . $this->flattenContextRow($context[$module]['summary']);
        }

        return implode("\n", $lines);
    }

    protected function isSequentialArray(array $value): bool
    {
        return array_keys($value) === range(0, count($value) - 1);
    }

    protected function flattenContextRow(array $row): string
    {
        $parts = [];
        foreach ($row as $key => $value) {
            if (is_array($value)) {
                continue;
            }

            $value = trim((string) $value);
            if ($value === '' || $value === '0' || $value === '0.00') {
                continue;
            }

            $parts[] = $key . '=' . $value;
            if (count($parts) >= 6) {
                break;
            }
        }

        return $parts ? implode('；', $parts) : '无补充明细';
    }
    protected function buildFinanceContext(): array
    {
        $today = date('Y-m-d');
        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');

        $openReceivableAmount = (float) Db::name('finance_invoice')
            ->where('kind', 'receivable')
            ->where('status', '<>', 'paid')
            ->sum('amount');

        $openPayableAmount = (float) Db::name('finance_invoice')
            ->where('kind', 'payable')
            ->where('status', '<>', 'paid')
            ->sum('amount');

        return [
            'summary' => [
                'month_income' => (float) Db::name('finance_transaction')->where('type', 'income')->where('transaction_date', '>=', $monthStart)->where('transaction_date', '<=', $monthEnd)->sum('amount'),
                'month_expense' => (float) Db::name('finance_transaction')->where('type', 'expense')->where('transaction_date', '>=', $monthStart)->where('transaction_date', '<=', $monthEnd)->sum('amount'),
                'open_receivable_amount' => $openReceivableAmount,
                'open_payable_amount' => $openPayableAmount,
                'overdue_receivable_amount' => (float) Db::name('finance_invoice')->where('kind', 'receivable')->where('status', 'not in', ['paid', 'cancelled'])->where('due_date', '<', $today)->sum('amount'),
                'overdue_payable_amount' => (float) Db::name('finance_invoice')->where('kind', 'payable')->where('status', 'not in', ['paid', 'cancelled'])->where('due_date', '<', $today)->sum('amount'),
            ],
            'recent_transactions' => Db::name('finance_transaction')->field('transaction_date,type,category,counterparty,amount,payment_method,notes')->order('transaction_date', 'desc')->order('id', 'desc')->limit(6)->select(),
            'open_invoices' => Db::name('finance_invoice')->field('kind,title,counterparty,amount,due_date,status,notes')->where('status', 'not in', ['paid', 'cancelled'])->order('due_date', 'asc')->limit(6)->select(),
        ];
    }

    protected function buildProjectContext(): array
    {
        $today = date('Y-m-d');

        return [
            'summary' => [
                'active_project_count' => (int) Db::name('project')->where('status', 'in', ['delivery', 'running', 'active'])->count(),
                'overdue_task_count' => (int) Db::name('project_task')->where('status', 'not in', ['done', 'closed'])->where('due_date', '<', $today)->count(),
                'blocked_task_count' => (int) Db::name('project_task')->where('status', 'blocked')->count(),
            ],
            'projects' => Db::name('project')->field('name,client,owner,status,priority,budget,start_date,due_date,description')->order('updatetime', 'desc')->limit(6)->select(),
            'tasks' => Db::name('project_task')->field('title,assignee,status,priority,due_date,estimate_hours,actual_hours,notes')->order('due_date', 'asc')->order('id', 'desc')->limit(8)->select(),
            'owner_loads' => Db::name('project_task')->field('assignee, count(*) as total_count, sum(case when status in (\'doing\',\'review\',\'blocked\',\'overdue\') then 1 else 0 end) as open_count, sum(case when status = \'overdue\' or (status <> \'done\' and due_date < \'' . $today . '\') then 1 else 0 end) as overdue_count')->where('assignee', '<>', '')->group('assignee')->order('open_count', 'desc')->order('overdue_count', 'desc')->limit(6)->select(),
        ];
    }

    protected function buildAppContext(): array
    {
        return [
            'summary' => [
                'open_issue_count' => (int) Db::name('app_issue')->where('status', 'not in', ['closed', 'resolved'])->count(),
                'open_tech_ticket_count' => (int) Db::name('app_tech_ticket')->where('status', 'not in', ['closed', 'done', 'released'])->count(),
                'pending_release_count' => (int) Db::name('app_release')->where('status', 'in', ['planned', 'ready', 'testing'])->count(),
            ],
            'projects' => Db::name('app_project')->field('name,app_name,app_version,lifecycle_stage,manager,status,priority,core_metric,target')->order('updatetime', 'desc')->limit(6)->select(),
            'issues' => Db::name('app_issue')->field('ticket_no,title,source,category,status,priority,assignee,opened_at,last_follow_up_at,next_action')->order('opened_at', 'desc')->order('id', 'desc')->limit(6)->select(),
            'tech_tickets' => Db::name('app_tech_ticket')->field('title,type,status,priority,app_module,app_version,owner,due_date,impact,estimate_hours,actual_hours')->order('due_date', 'asc')->order('id', 'desc')->limit(6)->select(),
            'releases' => Db::name('app_release')->field('version,title,status,owner,release_date,channel,verification_summary,customer_sync_status,release_result')->order('release_date', 'desc')->order('id', 'desc')->limit(6)->select(),
            'materials' => Db::name('app_material')->field('title,category,version_tag,applicable_versions,archive_status,expires_on,updated_on')->order('id', 'desc')->limit(6)->select(),
        ];
    }

    protected function buildBusinessContext(): array
    {
        return [
            'summary' => [
                'active_customer_count' => (int) Db::name('business_customer')->where('status', 'active')->count(),
                'active_contract_amount' => (float) Db::name('business_contract')->where('status', 'in', ['review', 'active', 'delivering'])->sum('amount'),
                'open_receivable_plan_amount' => (float) Db::name('business_receivable_plan')->where('status', 'in', ['pending', 'processing', 'overdue'])->sum('amount'),
                'open_payment_plan_amount' => (float) Db::name('business_payment_plan')->where('status', 'in', ['pending', 'processing', 'overdue'])->sum('amount'),
                'pending_approval_count' => (int) Db::name('business_approval')->where('status', 'pending')->count(),
                'pending_expense_amount' => (float) Db::name('business_expense_request')->where('status', 'in', ['draft', 'pending_approval', 'approved', 'processing'])->sum('request_amount'),
            ],
            'customers' => Db::name('business_customer')->field('company_name,industry,stage,status,owner,contact_name,last_follow_up_at')->order('updatetime', 'desc')->limit(6)->select(),
            'contracts' => Db::name('business_contract')->field('contract_no,name,customer_name,amount,status,approval_status,owner,start_date,end_date')->order('updatetime', 'desc')->limit(6)->select(),
            'receivable_plans' => Db::name('business_receivable_plan')->field('title,customer_name,contract_name,amount,due_date,status,owner')->where('status', 'in', ['pending', 'processing', 'overdue'])->order('due_date', 'asc')->limit(6)->select(),
            'payment_plans' => Db::name('business_payment_plan')->field('title,payee_name,customer_name,contract_name,amount,due_date,status,approval_status,owner')->where('status', 'in', ['pending', 'processing', 'overdue'])->order('due_date', 'asc')->limit(6)->select(),
            'approvals' => Db::name('business_approval')->field('approval_no,object_type,object_title,status,applicant_name,approver_name,applied_at')->where('status', 'pending')->order('applied_at', 'asc')->limit(6)->select(),
            'expense_requests' => Db::name('business_expense_request')->field('request_no,title,expense_type,supplier_name,request_amount,status,approval_status,expected_pay_date,owner')->where('status', 'in', ['draft', 'pending_approval', 'approved', 'processing'])->order('updatetime', 'desc')->limit(6)->select(),
        ];
    }

    protected function getContextMap(): array
    {
        $contexts = [
            'finance' => $this->buildFinanceContext(),
            'project' => $this->buildProjectContext(),
            'business' => $this->buildBusinessContext(),
        ];

        if ($this->canAccess('app/workbench/index')) {
            $contexts['app'] = $this->buildAppContext();
        }

        return $contexts;
    }

    protected function getFocuses(): array
    {
        $items = [
            ['key' => 'overview', 'label' => '综合经营', 'description' => '把当前可用模块一起纳入分析，给出今天最先处理的动作。'],
            ['key' => 'finance', 'label' => '财务', 'description' => '重点看现金流、回款、付款、单据和智能记账。'],
            ['key' => 'project', 'label' => '项目交付', 'description' => '重点看项目进度、逾期任务、风险和负责人负荷。'],
            ['key' => 'business', 'label' => '客户与合同', 'description' => '重点看客户跟进、合同、回款计划、付款计划和审批。'],
        ];

        if ($this->canAccess('app/workbench/index')) {
            $items[] = ['key' => 'app', 'label' => '项目运营', 'description' => '重点看问题记录、研发联动、版本发布和资料。'];
        }

        return $items;
    }
    protected function getFocusConfig(string $focus): array
    {
        foreach ($this->getFocuses() as $item) {
            if ($item['key'] === $focus) {
                return $item;
            }
        }

        return $this->getFocuses()[0];
    }

    protected function getPresets(): array
    {
        $items = [
            ['key' => 'today-priority', 'label' => '今日优先级', 'focus' => 'overview', 'description' => '直接告诉我今天先处理什么。', 'prompt' => '请基于当前经营数据，按“先做什么 / 为什么 / 不做会有什么影响”给出今天最该优先处理的 5 件事。'],
            ['key' => 'cash-risk', 'label' => '财务待办', 'focus' => 'finance', 'description' => '把回款、付款和风险拆成今天可执行的动作。', 'prompt' => '请基于当前财务数据，输出“今天要做 / 本周要盯 / 风险提醒”三段财务待办。'],
            ['key' => 'project-risk', 'label' => '项目风险', 'focus' => 'project', 'description' => '找出最危险的项目和逾期任务。', 'prompt' => '请基于当前项目和任务数据，找出最需要优先处理的项目风险和逾期任务，并给出负责人动作建议。'],
            ['key' => 'contract-risk', 'label' => '合同回款', 'focus' => 'business', 'description' => '看合同推进、回款和审批卡点。', 'prompt' => '请基于客户、合同、回款计划和审批数据，列出当前最需要推进的合同、回款和审批事项，并给出执行顺序。'],
        ];

        if ($this->canAccess('app/workbench/index')) {
            $items[] = ['key' => 'app-review', 'label' => '项目运营推进', 'focus' => 'app', 'description' => '看问题、研发待办和发版优先级。', 'prompt' => '请基于项目运营数据，按“先处理的问题 / 要联动的研发 / 要确认的发版事项”给出执行建议。'];
        }

        return $items;
    }

    protected function getPresetByKey(string $key): ?array
    {
        foreach ($this->getPresets() as $preset) {
            if ($preset['key'] === $key) {
                return $preset;
            }
        }

        return null;
    }

    protected function getExamples(): array
    {
        $items = [
            '今天我最应该先处理哪 5 件事？',
            '哪些回款今天必须先催？',
            '哪个项目风险最高，负责人先做什么？',
            '审批中心里今天必须先批哪些单子？',
        ];

        if ($this->canAccess('app/workbench/index')) {
            $items[] = '项目运营当前最急的问题和发版是什么？';
        }

        return $items;
    }

    protected function getContextSectionLabels(): array
    {
        $items = [
            'finance' => '财务流水、应收应付、近期收支和逾期单据。',
            'project' => '项目台账、任务清单、逾期任务和负责人负荷。',
            'business' => '客户档案、合同、回款计划、付款计划、费用申请和审批中心。',
        ];

        if ($this->canAccess('app/workbench/index')) {
            $items['app'] = '项目台账、问题记录、研发联动、版本发布和资料。';
        }

        return $items;
    }

    protected function getWorkspaceActions(string $focus = 'overview'): array
    {
        $actions = [
            ['key' => 'finance-workbench', 'label' => '财务工作台', 'hint' => '看回款、付款、单据和智能记账。', 'icon' => 'fa fa-rmb', 'url' => $this->makeUrl('finance/workbench/index'), 'focuses' => ['overview', 'finance']],
            ['key' => 'project-workbench', 'label' => '项目工作台', 'hint' => '看交付风险、逾期任务和负责人负荷。', 'icon' => 'fa fa-tasks', 'url' => $this->makeUrl('project/workbench/index'), 'focuses' => ['overview', 'project']],
            ['key' => 'approval-center', 'label' => '审批中心', 'hint' => '看合同审批、付款审批和费用审批。', 'icon' => 'fa fa-check-square-o', 'url' => $this->makeUrl('business/approval/index'), 'focuses' => ['overview', 'business', 'finance']],
            ['key' => 'contract-index', 'label' => '合同台账', 'hint' => '看合同推进、金额和状态。', 'icon' => 'fa fa-file-text-o', 'url' => $this->makeUrl('business/contract/index'), 'focuses' => ['overview', 'business']],
            ['key' => 'ai-setting', 'label' => 'AI 配置', 'hint' => '补模型配置、测试连接、切换默认模型。', 'icon' => 'fa fa-sliders', 'url' => $this->makeUrl('ai/setting/index'), 'focuses' => ['overview', 'finance', 'project', 'business', 'app']],
        ];

        if ($this->canAccess('app/workbench/index')) {
            $actions[] = ['key' => 'app-workbench', 'label' => '项目运营工作台', 'hint' => '看问题、研发联动、发版和资料。', 'icon' => 'fa fa-mobile', 'url' => $this->makeUrl('app/workbench/index'), 'focuses' => ['overview', 'app']];
        }

        $filtered = [];
        foreach ($actions as $action) {
            if ($focus === 'overview' || in_array($focus, $action['focuses'], true)) {
                $filtered[] = $action;
            }
        }

        return $filtered;
    }

    protected function buildSuggestions(string $focus, string $answer): array
    {
        $answer = trim($answer);
        $actions = [
            ['kind' => 'copy', 'label' => '复制回答', 'description' => '直接复制这次 AI 结论。', 'content' => $answer],
        ];

        switch ($focus) {
            case 'finance':
                $actions[] = ['kind' => 'prompt', 'label' => '改成今日待办', 'description' => '把结论拆成今天就能执行的清单。', 'prompt' => '请把上面的财务结论改成“今天要做 / 本周要盯 / 需要谁配合”三段待办清单。'];
                $actions[] = ['kind' => 'copy', 'label' => '复制为回款备注', 'description' => '生成一份可贴到财务跟进里的备注草稿。', 'content' => $this->buildDraftTemplate('回款跟进备注', $answer, ['关键风险', '今日动作', '本周动作'])];
                $actions[] = ['kind' => 'link', 'label' => '打开财务工作台', 'description' => '回到台账继续处理回款、付款和记账。', 'url' => $this->makeUrl('finance/workbench/index'), 'icon' => 'fa fa-rmb'];
                break;
            case 'project':
                $actions[] = ['kind' => 'prompt', 'label' => '改成项目经理待办', 'description' => '输出项目经理本周执行清单。', 'prompt' => '请把上面的项目分析改成“今天处理 / 本周协调 / 需要升级汇报”的项目经理待办清单。'];
                $actions[] = ['kind' => 'copy', 'label' => '复制为项目备注', 'description' => '生成可贴到项目复盘里的备注草稿。', 'content' => $this->buildDraftTemplate('项目风险备注', $answer, ['风险摘要', '负责人动作', '需要协调'])];
                $actions[] = ['kind' => 'link', 'label' => '打开任务新增（带草稿）', 'description' => '把 AI 结论先带到任务新增页，再补项目和执行人即可。', 'url' => $this->makeUrl('project/task/add', [
                    'title' => $this->buildDraftTitle('AI整理任务', $answer),
                    'notes' => $this->buildDraftTemplate('AI任务拆解', $answer, ['任务目标', '执行步骤', '验收标准']),
                    'status' => 'todo',
                    'priority' => 'medium',
                    'due_date' => date('Y-m-d', strtotime('+2 day')),
                ]), 'icon' => 'fa fa-plus-square-o'];
                $actions[] = ['kind' => 'link', 'label' => '打开项目工作台', 'description' => '去项目台账继续处理任务和风险。', 'url' => $this->makeUrl('project/workbench/index'), 'icon' => 'fa fa-tasks'];
                break;
            case 'app':
                $actions[] = ['kind' => 'prompt', 'label' => '拆成运营/研发动作', 'description' => '把结论拆成运营和研发两边的动作。', 'prompt' => '请把上面的项目运营结论拆成“运营动作 / 研发动作 / 发版注意事项”三部分。'];
                $actions[] = ['kind' => 'copy', 'label' => '复制为问题跟进草稿', 'description' => '生成可贴到问题记录或客服回告里的草稿。', 'content' => $this->buildDraftTemplate('问题跟进草稿', $answer, ['问题摘要', '处理建议', '下一步回告'])];
                $actions[] = ['kind' => 'link', 'label' => '打开问题跟进（带草稿）', 'description' => '先把 AI 结论带到问题跟进页，再选择对应问题保存。', 'url' => $this->makeUrl('app/issue_followup/add', [
                    'type' => 'follow_up',
                    'visibility' => 'internal',
                    'status' => 'processing',
                    'content' => $this->buildDraftTemplate('AI问题跟进', $answer, ['当前判断', '处理建议']),
                    'next_action' => $this->limitText('请按 AI 结论继续推进，并补充回告时间与责任人：' . $answer, 180),
                ]), 'icon' => 'fa fa-commenting-o'];
                $actions[] = ['kind' => 'link', 'label' => '打开项目运营工作台', 'description' => '回到问题、研发联动和发版页面继续处理。', 'url' => $this->makeUrl('app/workbench/index'), 'icon' => 'fa fa-mobile'];
                break;
            case 'business':
                $actions[] = ['kind' => 'prompt', 'label' => '改成合同执行清单', 'description' => '输出合同、回款、付款和审批的执行顺序。', 'prompt' => '请把上面的分析改成“今天处理 / 本周推进 / 需要审批”的合同执行清单。'];
                $actions[] = ['kind' => 'copy', 'label' => '复制为客户跟进草稿', 'description' => '生成可贴到客户跟进记录里的草稿。', 'content' => $this->buildDraftTemplate('客户跟进草稿', $answer, ['本次结论', '下一步动作', '下次跟进时间'])];
                $actions[] = ['kind' => 'link', 'label' => '打开客户跟进（带草稿）', 'description' => '先把 AI 草稿带到客户跟进页，再选择客户和合同保存。', 'url' => $this->makeUrl('business/customer_followup/add', [
                    'title' => $this->buildDraftTitle('AI客户跟进', $answer),
                    'followup_type' => 'meeting',
                    'status' => 'done',
                    'follow_up_at' => date('Y-m-d H:i:s'),
                    'next_follow_up_at' => date('Y-m-d H:i:s', strtotime('+3 day')),
                    'result_summary' => $this->limitText($answer, 240),
                    'notes' => $this->buildDraftTemplate('AI客户跟进草稿', $answer, ['本次结论', '下一步动作', '下次跟进时间']),
                ]), 'icon' => 'fa fa-handshake-o'];
                $actions[] = ['kind' => 'link', 'label' => '打开审批中心', 'description' => '去审批中心继续处理合同、付款和费用审批。', 'url' => $this->makeUrl('business/approval/index'), 'icon' => 'fa fa-check-square-o'];
                break;
            default:
                $actions[] = ['kind' => 'prompt', 'label' => '改成执行清单', 'description' => '把综合结论拆成今天、本周、本月三段动作。', 'prompt' => '请把上面的综合结论改成“今天 / 本周 / 本月”三段执行清单，并说明优先级。'];
                $actions[] = ['kind' => 'link', 'label' => '打开审批中心', 'description' => '先回系统处理审批和卡点事项。', 'url' => $this->makeUrl('business/approval/index'), 'icon' => 'fa fa-check-square-o'];
                $actions[] = ['kind' => 'link', 'label' => '打开财务工作台', 'description' => '先去看回款、付款和现金流。', 'url' => $this->makeUrl('finance/workbench/index'), 'icon' => 'fa fa-rmb'];
                break;
        }

        return $actions;
    }

    protected function buildLocalFallbackAnswer(string $focus, array $contexts, string $prompt): string
    {
        $lines = [
            '结论：',
        ];

        switch ($focus) {
            case 'finance':
                $summary = $contexts['finance']['summary'];
                $lines[] = '当前财务最该先盯回款、付款和逾期单据，先保证现金流稳定。';
                $lines[] = '';
                $lines[] = '关键依据：';
                $lines[] = '- 本月收入：' . $this->formatMoney($summary['month_income']);
                $lines[] = '- 本月支出：' . $this->formatMoney($summary['month_expense']);
                $lines[] = '- 待回款：' . $this->formatMoney($summary['open_receivable_amount']);
                $lines[] = '- 待付款：' . $this->formatMoney($summary['open_payable_amount']);
                $lines[] = '- 逾期应收：' . $this->formatMoney($summary['overdue_receivable_amount']);
                $lines[] = '- 逾期应付：' . $this->formatMoney($summary['overdue_payable_amount']);
                $lines[] = '';
                $lines[] = '建议动作：';
                $lines[] = '- 今天：优先处理逾期回款和金额最大的未收款单据。';
                $lines[] = '- 本周：把待付款按到期日排好顺序，避免新的逾期。';
                $lines[] = '- 本月：结合智能记账和附件补齐，减少账实不一致。';
                break;
            case 'project':
                $summary = $contexts['project']['summary'];
                $lines[] = '当前项目侧最该先处理逾期任务和阻塞项，避免交付风险继续扩大。';
                $lines[] = '';
                $lines[] = '关键依据：';
                $lines[] = '- 交付中项目：' . (int) $summary['active_project_count'];
                $lines[] = '- 逾期任务：' . (int) $summary['overdue_task_count'];
                $lines[] = '- 阻塞任务：' . (int) $summary['blocked_task_count'];
                $lines[] = '';
                $lines[] = '建议动作：';
                $lines[] = '- 今天：先清掉最紧急的逾期任务，并明确负责人。';
                $lines[] = '- 本周：对高风险项目补一轮节点复盘和资源协调。';
                $lines[] = '- 本月：把重复延期的任务沉淀成项目复盘事项。';
                break;
            case 'app':
                $summary = $contexts['app']['summary'];
                $lines[] = '当前项目运营最该先处理未关闭问题和待发布版本，避免问题积压。';
                $lines[] = '';
                $lines[] = '关键依据：';
                $lines[] = '- 未关闭问题：' . (int) $summary['open_issue_count'];
                $lines[] = '- 未完成研发待办：' . (int) $summary['open_tech_ticket_count'];
                $lines[] = '- 待发布版本：' . (int) $summary['pending_release_count'];
                $lines[] = '';
                $lines[] = '建议动作：';
                $lines[] = '- 今天：先把问题单和研发待办对应起来。';
                $lines[] = '- 本周：逐个确认待发布版本的验证结论和回告安排。';
                $lines[] = '- 本月：把高频问题整理成资料或标准回告模板。';
                break;
            case 'business':
                $summary = $contexts['business']['summary'];
                $lines[] = '当前客户与合同最该先推进回款、付款和审批卡点，避免合同执行节奏被拖慢。';
                $lines[] = '';
                $lines[] = '关键依据：';
                $lines[] = '- 活跃客户数：' . (int) $summary['active_customer_count'];
                $lines[] = '- 生效中合同金额：' . $this->formatMoney($summary['active_contract_amount']);
                $lines[] = '- 待回款计划金额：' . $this->formatMoney($summary['open_receivable_plan_amount']);
                $lines[] = '- 待付款计划金额：' . $this->formatMoney($summary['open_payment_plan_amount']);
                $lines[] = '- 待审批单数：' . (int) $summary['pending_approval_count'];
                $lines[] = '- 待处理费用申请金额：' . $this->formatMoney($summary['pending_expense_amount']);
                $lines[] = '';
                $lines[] = '建议动作：';
                $lines[] = '- 今天：优先清理待审批和最临近到期的回款/付款事项。';
                $lines[] = '- 本周：按合同和客户维度补一轮跟进记录，避免推进断档。';
                $lines[] = '- 本月：把高频费用和付款事项沉淀到审批和付款模板里。';
                break;
            default:
                $lines[] = '当前最该先盯财务、审批和交付风险三条线，先处理最影响现金流和交付节奏的事项。';
                $lines[] = '';
                $lines[] = '关键依据：';
                foreach ($this->getSummaryCards($contexts) as $card) {
                    $lines[] = '- ' . $card['label'] . '：' . $card['value'];
                }
                $lines[] = '';
                $lines[] = '建议动作：';
                $lines[] = '- 今天：先处理待审批、待回款和高风险项目。';
                $lines[] = '- 本周：跟进项目运营问题闭环和付款安排。';
                $lines[] = '- 本月：补齐台账、附件和跟进记录。';
                break;
        }

        if (trim($prompt) !== '') {
            $lines[] = '';
            $lines[] = '补充说明：';
            $lines[] = '- 本次已按“' . trim($prompt) . '”这个问题给出系统兜底分析。';
        }

        return implode("\n", $lines);
    }

    protected function buildDraftTemplate(string $title, string $answer, array $sections): string
    {
        $lines = ['【' . $title . '】', trim($answer), ''];
        foreach ($sections as $section) {
            $lines[] = '【' . $section . '】';
            $lines[] = '';
        }

        return trim(implode("\n", $lines));
    }
    protected function buildDraftTitle(string $prefix, string $answer): string
    {
        $summary = preg_replace('/\s+/u', ' ', trim($answer));
        $summary = $this->limitText($summary, 22);
        if ($summary === '') {
            return $prefix . ' / ' . date('m-d H:i');
        }

        return $prefix . ' / ' . $summary;
    }

    protected function limitText(string $text, int $limit = 280): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text));
        if ($text === '') {
            return '';
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            return mb_strlen($text) > $limit ? mb_substr($text, 0, $limit - 1) . '…' : $text;
        }

        return strlen($text) > $limit ? substr($text, 0, $limit - 3) . '...' : $text;
    }

    protected function canAccess(string $rule): bool
    {
        if (strpos($rule, 'app/') === 0 && !$this->isModuleEnabled('app')) {
            return false;
        }

        try {
            return (bool) Auth::instance()->check($rule);
        } catch (\Throwable $e) {
            return true;
        }
    }

    protected function isModuleEnabled(string $moduleKey): bool
    {
        static $cache = [];
        if (array_key_exists($moduleKey, $cache)) {
            return $cache[$moduleKey];
        }

        try {
            $service = new ErpModuleService();
            $service->ensureStorage();
            $cache[$moduleKey] = $service->isEnabled($moduleKey);
        } catch (\Throwable $e) {
            $cache[$moduleKey] = true;
        }

        return $cache[$moduleKey];
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

    protected function makeUrl(string $route, array $params = []): string
    {
        return (string) url($route, $params);
    }

    protected function formatMoney($value): string
    {
        return '¥' . number_format((float) $value, 2);
    }
}
