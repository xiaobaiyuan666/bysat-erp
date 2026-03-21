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
        $contexts = [
            'finance' => $this->buildFinanceContext(),
            'project' => $this->buildProjectContext(),
            'app' => $this->buildAppContext(),
            'business' => $this->buildBusinessContext(),
        ];

        return [
            'setting' => $this->maskSetting($setting),
            'diagnostic' => $this->buildSettingDiagnostic($setting),
            'settings' => $this->getAvailableSettings(),
            'presets' => $this->getPresets(),
            'focuses' => $this->getFocuses(),
            'examples' => $this->getExamples(),
            'summary_cards' => $this->getSummaryCards($contexts),
            'messages' => $this->getConversationMessages(),
            'context_sections' => $this->getContextSectionLabels(),
            'workspace_actions' => $this->getWorkspaceActions(),
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

        $contexts = [
            'finance' => $this->buildFinanceContext(),
            'project' => $this->buildProjectContext(),
            'app' => $this->buildAppContext(),
            'business' => $this->buildBusinessContext(),
        ];
        $context = [
            'generated_at' => date('Y-m-d H:i:s'),
            'focus' => $focusConfig['key'],
            'summary_cards' => $this->getSummaryCards($contexts),
        ];
        foreach (['finance', 'project', 'app', 'business'] as $module) {
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
                    'title' => '首页已启用快速分析模式',
                    'message' => '为了避免首页长时间等待，本次已优先使用快模型和精简上下文完成分析。需要更完整的分析时，再进完整 AI 工作台。',
                    'action_label' => '打开完整 AI',
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
                        'title' => '已自动切到轻量分析模式',
                        'message' => '完整业务上下文响应偏慢，本次已改用精简摘要完成回答。若你更重视速度，建议把默认模型切到更快的版本。',
                        'action_label' => '去调整模型',
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
                            'action_label' => '去调整模型',
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
                        ? '首页为了避免长时间等待，已经跳过慢模型并直接给出系统兜底分析。需要更完整的分析时，再进完整 AI 工作台。'
                        : '当前网关没有在限定时间内返回结果，本次回答改用系统内置业务规则生成。你仍然可以继续打开财务、项目、APP 或客户跟进草稿页处理。',
                    'action_label' => '去调整模型',
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
            '你的回答只能基于系统提供的数据，不允许编造不存在的金额、日期、客户、项目、APP、版本、审批或单据信息。',
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
                'error' => '瑜版挸澧犳繅顐㈠晸閻ㄥ嫭妲搁梼鍧楀櫡娴?Coding Plan 閻?Anthropic 閸忕厧顔愰崷鏉挎絻閿涘奔绲鹃張顒傞兇缂佺喕铔嬮惃鍕Ц OpenAI 閸楀繗顔呴敍灞肩瑝閼崇晫娲块幒銉ゅ▏閻劏绻栨稉顏勬勾閸р偓閵?',
            ];
        }

        if ($this->isDashscopeCodingEndpoint((string) ($setting['base_url'] ?? ''))) {
            return [
                'ok' => false,
                'error' => '瑜版挸澧犻柊宥囩枂娴ｈ法鏁ら惃鍕Ц闂冨潡鍣锋禍?Coding Plan閵嗗倹鐗撮幑顔肩暭閺傚綊妾洪崚璁圭礉Coding Plan 娑撳秷鍏橀幒銉ュ煂鏉╂瑥顨?ERP 閸氬骸褰撮敍宀冾嚞閺€鍦暏闂冨潡鍣锋禍鎴犳閻愮厧鍚嬬€硅膩瀵繑鍨ㄩ崗璺虹暊闁氨鏁?OpenAI 閸忕厧顔愰幒銉ュ經閵?',
            ];
        }

        if (!function_exists('curl_init')) {
            return ['ok' => false, 'error' => '瑜版挸澧?PHP 閻滎垰顣ㄩ張顏勬儙閻?cURL 閹碘晛鐫嶉敍灞炬￥濞夋洝顕Ч鍌浤侀崹瀣复閸欙絻鈧?'];
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
            CURLOPT_TIMEOUT => isset($setting['request_timeout']) ? max(4, (int) $setting['request_timeout']) : 18,
            CURLOPT_CONNECTTIMEOUT => isset($setting['connect_timeout']) ? max(2, (int) $setting['connect_timeout']) : 8,
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
            $message = $decoded['error']['message'] ?? ('濡€崇€烽幒銉ュ經鏉╂柨娲?HTTP ' . $statusCode);
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
            return ['ok' => false, 'error' => '濡€崇€锋潻鏂挎礀閸愬懎顔愭稉铏光敄閵?'];
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
            $candidate['request_timeout'] = 6;
            $candidate['connect_timeout'] = 3;
            $candidate['max_tokens'] = 220;
            $candidates[] = $candidate;
        } else {
            $current = $setting;
            $current['request_timeout'] = 6;
            $current['connect_timeout'] = 3;
            $current['max_tokens'] = 220;
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
            $contexts = [
                'finance' => $this->buildFinanceContext(),
                'project' => $this->buildProjectContext(),
                'app' => $this->buildAppContext(),
                'business' => $this->buildBusinessContext(),
            ];
        }

        return [
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
            [
                'label' => 'APP 问题',
                'value' => (string) $contexts['app']['summary']['open_issue_count'],
                'hint' => '未关闭的问题记录',
            ],
        ];
    }
    protected function buildContext(string $focus): array
    {
        $contexts = [
            'finance' => $this->buildFinanceContext(),
            'project' => $this->buildProjectContext(),
            'app' => $this->buildAppContext(),
            'business' => $this->buildBusinessContext(),
        ];

        $context = [
            'generated_at' => date('Y-m-d H:i:s'),
            'focus' => $focus,
            'summary_cards' => $this->getSummaryCards($contexts),
        ];

        foreach (['finance', 'project', 'app', 'business'] as $module) {
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

    protected function getFocuses(): array
    {
        return [
            ['key' => 'overview', 'label' => '综合经营', 'description' => '把财务、项目、APP 运营和客户合同一起纳入分析。'],
            ['key' => 'finance', 'label' => '财务', 'description' => '重点看现金流、回款、付款、单据和智能记账。'],
            ['key' => 'project', 'label' => '项目交付', 'description' => '重点看项目进度、逾期任务、风险和负责人负荷。'],
            ['key' => 'app', 'label' => 'APP 运营', 'description' => '重点看问题记录、研发联动、版本发布和资料。'],
            ['key' => 'business', 'label' => '客户与合同', 'description' => '重点看客户跟进、合同、回款计划、付款计划和审批。'],
        ];
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
        return [
            ['key' => 'daily-brief', 'label' => '经营日报', 'focus' => 'overview', 'description' => '先看今天最该处理什么，适合老板和负责人直接点。', 'prompt' => '请基于当前数据输出一份经营日报，按“结论 / 关键依据 / 今天要做什么 / 本周要盯什么”回答。'],
            ['key' => 'cash-risk', 'label' => '现金流风险', 'focus' => 'finance', 'description' => '判断未来 30 天的资金压力和风险点。', 'prompt' => '请基于当前财务数据评估未来 30 天现金流风险，指出最关键的 3 个风险点，并给出今天和本周的动作建议。'],
            ['key' => 'collection-plan', 'label' => '回款建议', 'focus' => 'finance', 'description' => '梳理待回款优先级和催收动作。', 'prompt' => '请基于待回款、回款计划和逾期单据，列出最该优先推进的客户、金额、风险和下一步催款动作。'],
            ['key' => 'project-risk', 'label' => '项目复盘', 'focus' => 'project', 'description' => '找出最危险的项目和逾期任务。', 'prompt' => '请基于当前项目和任务数据，找出最需要优先处理的项目风险和逾期任务，并给出项目经理层面的动作建议。'],
            ['key' => 'app-review', 'label' => 'APP 问题分析', 'focus' => 'app', 'description' => '看问题、研发待办和版本发布风险。', 'prompt' => '请基于 APP 运营数据分析当前最值得关注的问题、研发待办和版本发布风险，并给出运营和技术的协同建议。'],
            ['key' => 'contract-risk', 'label' => '合同与回款', 'focus' => 'business', 'description' => '看合同推进、回款节奏和审批卡点。', 'prompt' => '请基于客户、合同、回款计划和审批数据，找出当前最需要推进的合同、回款和审批事项，并给出执行清单。'],
            ['key' => 'payment-review', 'label' => '付款安排', 'focus' => 'business', 'description' => '看本周要批、要付和会逾期的付款计划。', 'prompt' => '请基于付款计划、费用申请和审批中心数据，给出本周最应该处理的付款安排和审批建议。'],
        ];
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
        return [
            '今天最该先催哪几笔回款？',
            '审批中心里哪些单子今天必须先批？',
            '哪个交付项目最危险，负责人应该先做什么？',
            'APP 最近的问题主要集中在哪个模块？',
            '未来 30 天现金流有没有明显压力？',
            '本周最该处理的付款和费用申请有哪些？',
        ];
    }

    protected function getContextSectionLabels(): array
    {
        return [
            'finance' => '财务流水、应收应付、近期收支和逾期单据。',
            'project' => '项目台账、任务清单、逾期任务和负责人负荷。',
            'app' => 'APP 台账、问题记录、研发联动、版本发布和资料。',
            'business' => '客户档案、合同、回款计划、付款计划、费用申请和审批中心。',
        ];
    }

    protected function getWorkspaceActions(string $focus = 'overview'): array
    {
        $actions = [
            ['key' => 'finance-workbench', 'label' => '财务工作台', 'hint' => '看回款、付款、单据和智能记账。', 'icon' => 'fa fa-rmb', 'url' => $this->makeUrl('finance/workbench/index'), 'focuses' => ['overview', 'finance']],
            ['key' => 'project-workbench', 'label' => '项目工作台', 'hint' => '看交付风险、逾期任务和负责人负荷。', 'icon' => 'fa fa-tasks', 'url' => $this->makeUrl('project/workbench/index'), 'focuses' => ['overview', 'project']],
            ['key' => 'app-workbench', 'label' => 'APP 运营工作台', 'hint' => '看问题、研发联动、发版和资料。', 'icon' => 'fa fa-mobile', 'url' => $this->makeUrl('app/workbench/index'), 'focuses' => ['overview', 'app']],
            ['key' => 'contract-index', 'label' => '合同台账', 'hint' => '看合同推进、金额和状态。', 'icon' => 'fa fa-file-text-o', 'url' => $this->makeUrl('business/contract/index'), 'focuses' => ['overview', 'business']],
            ['key' => 'approval-center', 'label' => '审批中心', 'hint' => '看合同审批、付款审批和费用审批。', 'icon' => 'fa fa-check-square-o', 'url' => $this->makeUrl('business/approval/index'), 'focuses' => ['overview', 'business', 'finance']],
            ['key' => 'ai-setting', 'label' => 'AI 配置', 'hint' => '补模型配置、测试连接、切换默认模型。', 'icon' => 'fa fa-sliders', 'url' => $this->makeUrl('ai/setting/index'), 'focuses' => ['overview', 'finance', 'project', 'app', 'business']],
        ];

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
                $actions[] = ['kind' => 'prompt', 'label' => '拆成运营/研发动作', 'description' => '把结论拆成运营和研发两边的动作。', 'prompt' => '请把上面的 APP 结论拆成“运营动作 / 研发动作 / 发版注意事项”三部分。'];
                $actions[] = ['kind' => 'copy', 'label' => '复制为问题跟进草稿', 'description' => '生成可贴到问题记录或客服回告里的草稿。', 'content' => $this->buildDraftTemplate('问题跟进草稿', $answer, ['问题摘要', '处理建议', '下一步回告'])];
                $actions[] = ['kind' => 'link', 'label' => '打开问题跟进（带草稿）', 'description' => '先把 AI 结论带到问题跟进页，再选择对应问题保存。', 'url' => $this->makeUrl('app/issue_followup/add', [
                    'type' => 'follow_up',
                    'visibility' => 'internal',
                    'status' => 'processing',
                    'content' => $this->buildDraftTemplate('AI问题跟进', $answer, ['当前判断', '处理建议']),
                    'next_action' => $this->limitText('请按 AI 结论继续推进，并补充回告时间与责任人：' . $answer, 180),
                ]), 'icon' => 'fa fa-commenting-o'];
                $actions[] = ['kind' => 'link', 'label' => '打开 APP 运营工作台', 'description' => '回到问题、研发联动和发版页面继续处理。', 'url' => $this->makeUrl('app/workbench/index'), 'icon' => 'fa fa-mobile'];
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
                $lines[] = '当前 APP 运营最该先处理未关闭问题和待发布版本，避免问题积压。';
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
                $lines[] = '- 本周：跟进 APP 问题闭环和付款安排。';
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
    protected function makeUrl(string $route, array $params = []): string
    {
        return (string) url($route, $params);
    }

    protected function formatMoney($value): string
    {
        return '¥' . number_format((float) $value, 2);
    }
}
