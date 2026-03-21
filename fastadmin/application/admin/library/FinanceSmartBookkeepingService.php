<?php

namespace app\admin\library;

use think\Db;

class FinanceSmartBookkeepingService extends AiWorkspaceService
{
    public function getBootstrapData(int $settingId = 0): array
    {
        $setting = $settingId > 0 ? $this->getSettingById($settingId) : $this->getDefaultSetting();

        return [
            'setting' => $this->buildSettingSnapshot($setting),
            'examples' => $this->getExamples(),
            'projects' => $this->getProjects(),
            'record_types' => $this->getRecordTypeOptions(),
            'transaction_types' => $this->getTransactionTypeOptions(),
            'payment_methods' => $this->getPaymentMethodOptions(),
            'invoice_kinds' => $this->getInvoiceKindOptions(),
            'invoice_statuses' => $this->getInvoiceStatusOptions(),
            'categories' => $this->getCategoryOptions(),
        ];
    }

    public function parse(string $text, int $preferredProjectId = 0): array
    {
        $text = trim($text);
        if ($text === '') {
            return [
                'ok' => false,
                'error' => '请输入一句话记账描述，例如“今天给晨光办公付款 100 元，微信支付，办公用品”。',
            ];
        }

        $context = $this->buildParseContext($preferredProjectId);
        $modelResult = $this->parseWithModel($text, $context);
        if ($modelResult['ok']) {
            return $modelResult;
        }

        $ruleResult = $this->parseWithRules($text, $context, $preferredProjectId);
        if ($ruleResult['ok']) {
            $ruleResult['fallback_error'] = (string) ($modelResult['error'] ?? '');
            if (($modelResult['error'] ?? '') !== 'model_not_configured') {
                $ruleResult['message'] = '模型没有返回可直接入账的结构，已自动切换到规则解析。';
            }

            return $ruleResult;
        }

        return [
            'ok' => false,
            'error' => $ruleResult['error'] ?? ($modelResult['error'] ?? '智能记账解析失败'),
        ];
    }

    public function normalizeDraftPayload(array $payload): array
    {
        $recordType = trim((string) ($payload['record_type'] ?? 'transaction'));
        if (!in_array($recordType, ['transaction', 'invoice'], true)) {
            $recordType = 'transaction';
        }

        $draft = is_array($payload['draft'] ?? null) ? $payload['draft'] : [];
        $source = trim((string) ($payload['source'] ?? 'rule'));
        $sourceLabel = trim((string) ($payload['source_label'] ?? ($source === 'ai' ? '大模型解析' : '规则兜底')));
        $needsReview = !empty($payload['needs_review']);
        $message = trim((string) ($payload['message'] ?? ''));
        $fallbackError = trim((string) ($payload['fallback_error'] ?? ''));

        if ($recordType === 'invoice') {
            $normalizedDraft = $this->normalizeInvoiceDraft($draft);
        } else {
            $normalizedDraft = $this->normalizeTransactionDraft($draft);
        }

        if (!$normalizedDraft['ok']) {
            return $normalizedDraft;
        }

        $summary = $recordType === 'invoice'
            ? $this->buildInvoiceSummary($normalizedDraft['draft'])
            : $this->buildTransactionSummary($normalizedDraft['draft']);

        return [
            'ok' => true,
            'record_type' => $recordType,
            'draft' => $normalizedDraft['draft'],
            'source' => $source === 'ai' ? 'ai' : 'rule',
            'source_label' => $sourceLabel,
            'needs_review' => $needsReview,
            'message' => $message,
            'fallback_error' => $fallbackError,
            'summary' => $summary,
        ];
    }

    protected function parseWithModel(string $text, array $context): array
    {
        $setting = $this->getDefaultSetting();
        if (!$setting || !$this->isConfigured($setting)) {
            return [
                'ok' => false,
                'error' => 'model_not_configured',
            ];
        }

        $messages = $this->buildModelMessages($text, $context);
        $result = null;
        $workingSetting = $setting;

        foreach ($this->buildModelCandidates((string) ($setting['model'] ?? '')) as $candidateModel) {
            $trySetting = $setting;
            $trySetting['model'] = $candidateModel;
            $result = $this->requestStructuredModel($trySetting, $messages);
            if (!empty($result['ok'])) {
                $workingSetting = $trySetting;
                if ($candidateModel !== (string) ($setting['model'] ?? '')) {
                    $this->syncWorkingModelAlias((int) ($setting['id'] ?? 0), $candidateModel);
                }
                break;
            }
        }

        if (!$result || !$result['ok']) {
            return [
                'ok' => false,
                'error' => (string) (($result['error'] ?? '') ?: '模型调用失败'),
            ];
        }

        $json = $this->extractJson((string) ($result['content'] ?? ''));
        if (!$json) {
            return [
                'ok' => false,
                'error' => '模型返回内容不是有效 JSON',
            ];
        }

        $recordType = $this->normalizeRecordType((string) ($json['record_type'] ?? ''));
        if ($recordType === '') {
            $recordType = !empty($json['invoice']) ? 'invoice' : 'transaction';
        }

        $candidate = $recordType === 'invoice'
            ? (is_array($json['invoice'] ?? null) ? $json['invoice'] : $json)
            : (is_array($json['transaction'] ?? null) ? $json['transaction'] : $json);
        if (empty($candidate['notes'])) {
            $candidate['notes'] = $text;
        }

        $confidence = isset($json['confidence']) && is_numeric($json['confidence'])
            ? (float) $json['confidence']
            : 0.0;
        $needsReview = !empty($json['needs_review']) || $confidence < 0.78;

        $normalized = $recordType === 'invoice'
            ? $this->normalizeInvoiceDraft($candidate, $context['preferred_project_id'])
            : $this->normalizeTransactionDraft($candidate, $context['preferred_project_id']);

        if (!$normalized['ok']) {
            return [
                'ok' => false,
                'error' => $normalized['error'] ?? '模型解析结果不完整',
            ];
        }

        if ($recordType === 'invoice') {
            $draft = $normalized['draft'];
            if ($draft['title'] === '' || $draft['counterparty'] === '') {
                $needsReview = true;
            }
            $summary = $this->buildInvoiceSummary($draft);
        } else {
            $draft = $normalized['draft'];
            if ($draft['counterparty'] === '' || $draft['category'] === '') {
                $needsReview = true;
            }
            $summary = $this->buildTransactionSummary($draft);
        }

        return [
            'ok' => true,
            'record_type' => $recordType,
            'draft' => $draft,
            'source' => 'ai',
            'source_label' => '大模型解析',
            'needs_review' => $needsReview,
            'message' => $workingSetting['model'] === (string) ($setting['model'] ?? '')
                ? '已按大模型结果生成入账草稿，请确认后写入系统。'
                : ('已按大模型结果生成入账草稿，本次自动切换到了可用模型：' . $workingSetting['model']),
            'fallback_error' => '',
            'summary' => $summary,
        ];
    }

    protected function parseWithRules(string $text, array $context, int $preferredProjectId): array
    {
        $recordType = $this->looksLikeInvoiceIntent($text) ? 'invoice' : 'transaction';

        $normalized = $recordType === 'invoice'
            ? $this->buildInvoiceDraftFromRules($text, $context, $preferredProjectId)
            : $this->buildTransactionDraftFromRules($text, $context, $preferredProjectId);

        if (!$normalized['ok']) {
            return $normalized;
        }

        $summary = $recordType === 'invoice'
            ? $this->buildInvoiceSummary($normalized['draft'])
            : $this->buildTransactionSummary($normalized['draft']);

        return [
            'ok' => true,
            'record_type' => $recordType,
            'draft' => $normalized['draft'],
            'source' => 'rule',
            'source_label' => '规则兜底',
            'needs_review' => !empty($normalized['needs_review']),
            'message' => '已按规则生成草稿，建议你核对一下关键字段。',
            'summary' => $summary,
        ];
    }

    protected function buildModelMessages(string $text, array $context): array
    {
        $projectNames = array_map(function ($project) {
            return $project['name'];
        }, $context['projects']);

        return [
            [
                'role' => 'system',
                'content' => implode("\n", [
                    '你是企业财务智能记账助手，要把一句中文业务描述转成可直接写入系统的 JSON。',
                    '只输出 JSON，不要输出解释、Markdown 或代码块。',
                    '如果描述的是已经发生的收款/付款/支出，请输出 record_type=transaction。',
                    '如果描述的是还没发生、只是待收待付、应收应付、账期、回款计划、付款计划，请输出 record_type=invoice。',
                    'transaction 字段固定为：transaction_date,type,category,counterparty,amount,payment_method,project_name,notes。',
                    'invoice 字段固定为：kind,title,counterparty,amount,due_date,status,project_name,notes。',
                    'type 只能是 income 或 expense。',
                    'payment_method 只能是 bank、wechat、alipay、cash、other。',
                    'kind 只能是 receivable 或 payable。',
                    'status 只能是 pending、partial、paid、overdue、cancelled，新建草稿优先用 pending。',
                    'category 只能从以下列表选择：' . implode('、', $this->getCategoryOptions()),
                    'project_name 只能从以下项目名选择，无法确定就留空：' . ($projectNames ? implode('、', $projectNames) : '无'),
                    'amount 必须是数字，不带货币符号。',
                    '日期统一输出 YYYY-MM-DD；无法确定时按今天。',
                    '同时输出 confidence（0 到 1）和 needs_review（布尔值）。',
                ]),
            ],
            [
                'role' => 'user',
                'content' => implode("\n", array_filter([
                    '今天日期：' . date('Y-m-d'),
                    $context['preferred_project_name'] !== '' ? '用户手动指定项目：' . $context['preferred_project_name'] : '',
                    '用户原句：' . $text,
                    '示例一：今天给晨光办公付款 100 元，微信支付，办公用品。',
                    '示例二：下周需要给晓石测试支付 3000 元，项目测试费用。',
                    '示例三：客户星环科技月底前要回款 50000 元，官网项目尾款。',
                ])),
            ],
        ];
    }

    protected function requestStructuredModel(array $setting, array $messages): array
    {
        $endpoint = $this->buildEndpoint((string) ($setting['base_url'] ?? ''));
        if ($endpoint === '') {
            return ['ok' => false, 'error' => '模型接口地址未配置。'];
        }

        if (!function_exists('curl_init')) {
            return ['ok' => false, 'error' => '当前 PHP 环境未启用 cURL 扩展，无法请求模型接口。'];
        }

        $payload = [
            'model' => (string) ($setting['model'] ?? ''),
            'temperature' => (float) ($setting['temperature'] ?? 0.2),
            'max_tokens' => 300,
            'stream' => false,
            'messages' => $messages,
        ];

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        $apiKey = trim((string) ($setting['api_key'] ?? ''));
        if ($apiKey !== '') {
            $headers[] = 'Authorization: Bearer ' . $apiKey;
        }

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_TIMEOUT => 18,
            CURLOPT_CONNECTTIMEOUT => 5,
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
        $curlError = curl_error($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($raw === false) {
            return ['ok' => false, 'error' => $this->normalizeCurlError($curlError, $setting)];
        }

        $decoded = json_decode($raw, true);
        if ($statusCode < 200 || $statusCode >= 300) {
            $message = $decoded['error']['message'] ?? ('模型接口返回 HTTP ' . $statusCode);
            return ['ok' => false, 'error' => (string) $message];
        }

        $content = $decoded['choices'][0]['message']['content'] ?? '';
        if (is_array($content)) {
            $content = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (!is_string($content) || trim($content) === '') {
            return ['ok' => false, 'error' => '模型返回内容为空。'];
        }

        return ['ok' => true, 'content' => trim($content)];
    }

    protected function buildModelCandidates(string $model): array
    {
        $model = trim($model);
        if ($model === '') {
            return [];
        }

        $candidates = [$model];
        $normalized = $this->normalizeModelAlias($model);

        if ($normalized !== '' && !in_array($normalized, $candidates, true)) {
            $candidates[] = $normalized;
        }

        if (strpos($normalized, 'openai/') !== 0) {
            $prefixed = 'openai/' . ltrim($normalized !== '' ? $normalized : $model, '/');
            if (!in_array($prefixed, $candidates, true)) {
                $candidates[] = $prefixed;
            }
        }

        return $candidates;
    }

    protected function normalizeModelAlias(string $model): string
    {
        $model = trim($model);
        if ($model === '') {
            return '';
        }

        $provider = '';
        if (strpos($model, '/') !== false) {
            [$provider, $model] = explode('/', $model, 2);
            $provider = trim($provider);
            $model = trim($model);
        }

        if (preg_match('/^gpt(\\d+(?:\\.\\d+)?)$/i', $model, $matches) === 1) {
            $model = 'gpt-' . $matches[1];
        }

        if ($provider !== '') {
            return $provider . '/' . $model;
        }

        return $model;
    }

    protected function syncWorkingModelAlias(int $settingId, string $candidateModel): void
    {
        if ($settingId <= 0 || $candidateModel === '') {
            return;
        }

        Db::name('ai_setting')
            ->where('id', $settingId)
            ->update([
                'model' => $candidateModel,
                'updatetime' => time(),
            ]);
    }

    protected function buildParseContext(int $preferredProjectId = 0): array
    {
        $projects = $this->getProjects();
        $projectMap = [];
        $preferredProjectName = '';

        foreach ($projects as $project) {
            $projectMap[(int) $project['id']] = $project;
            if ((int) $project['id'] === $preferredProjectId) {
                $preferredProjectName = $project['name'];
            }
        }

        return [
            'projects' => $projects,
            'project_map' => $projectMap,
            'preferred_project_id' => $preferredProjectId,
            'preferred_project_name' => $preferredProjectName,
        ];
    }

    protected function buildSettingSnapshot(?array $setting): array
    {
        if (!$setting) {
            return [
                'configured' => false,
                'label' => '未配置模型',
                'mode' => '规则兜底',
                'hint' => '当前没有可用模型配置，解析时会直接按规则识别。',
                'diagnostic' => null,
            ];
        }

        $presented = $this->presentSetting($setting, true);
        $configured = !empty($presented['configured']);
        $diagnostic = $presented['diagnostic'] ?? null;

        return [
            'id' => (int) ($presented['id'] ?? 0),
            'configured' => $configured,
            'label' => trim(($presented['provider_name'] ?? '') . ' / ' . ($presented['model'] ?? ''), ' /'),
            'mode' => $configured ? '模型优先，规则兜底' : '规则兜底',
            'hint' => $configured
                ? '当前会优先调用大模型提取结构，再自动回退到规则解析。'
                : '当前没有可用模型配置，解析时会直接按规则识别。',
            'diagnostic' => $diagnostic,
        ];
    }

    protected function getExamples(): array
    {
        return [
            '今天给晨光办公付款 100 元，微信支付，办公用品。',
            '昨天收到星环科技回款 50000 元，银行转账，官网项目尾款。',
            '下周需要给晓石测试支付 3000 元，项目测试费用。',
            '客户云帆 CRM 月底前应回款 120000 元，实施项目尾款。',
        ];
    }

    protected function getProjects(): array
    {
        $rows = Db::name('project')
            ->field('id,name,client,status')
            ->order('status', 'asc')
            ->order('name', 'asc')
            ->select();

        $items = [];
        foreach ($rows as $row) {
            $label = (string) $row['name'];
            if (!empty($row['client'])) {
                $label .= ' / ' . $row['client'];
            }

            $items[] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'label' => $label,
            ];
        }

        return $items;
    }

    protected function getRecordTypeOptions(): array
    {
        return [
            'transaction' => '财务流水',
            'invoice' => '应收应付',
        ];
    }

    protected function getTransactionTypeOptions(): array
    {
        return [
            'income' => '收入',
            'expense' => '支出',
        ];
    }

    protected function getPaymentMethodOptions(): array
    {
        return [
            'bank' => '银行转账',
            'wechat' => '微信',
            'alipay' => '支付宝',
            'cash' => '现金',
            'other' => '其他',
        ];
    }

    protected function getInvoiceKindOptions(): array
    {
        return [
            'receivable' => '应收',
            'payable' => '应付',
        ];
    }

    protected function getInvoiceStatusOptions(): array
    {
        return [
            'pending' => '待处理',
            'partial' => '部分完成',
            'paid' => '已完成',
            'overdue' => '已逾期',
            'cancelled' => '已作废',
        ];
    }

    protected function getCategoryOptions(): array
    {
        return [
            '项目预付款',
            '项目回款',
            '订阅收入',
            '工资发放',
            '云资源',
            '市场投放',
            '办公支出',
            '外包测试',
            '差旅费用',
            '顾问服务',
            '其他收入',
            '其他支出',
        ];
    }

    protected function normalizeRecordType(string $recordType): string
    {
        return in_array($recordType, ['transaction', 'invoice'], true) ? $recordType : '';
    }

    protected function normalizeTransactionType(string $type): string
    {
        return in_array($type, ['income', 'expense'], true) ? $type : '';
    }

    protected function normalizePaymentMethod(string $paymentMethod): string
    {
        return array_key_exists($paymentMethod, $this->getPaymentMethodOptions()) ? $paymentMethod : 'other';
    }

    protected function normalizeInvoiceKind(string $kind): string
    {
        return in_array($kind, ['receivable', 'payable'], true) ? $kind : '';
    }

    protected function normalizeInvoiceStatus(string $status): string
    {
        return array_key_exists($status, $this->getInvoiceStatusOptions()) ? $status : 'pending';
    }

    protected function normalizeAmount($amount): float
    {
        if (is_string($amount)) {
            $amount = str_replace([',', '￥', '¥', '元'], '', $amount);
        }

        return is_numeric($amount) ? round((float) $amount, 2) : 0.0;
    }

    protected function normalizeDate(string $value, string $fallback = ''): string
    {
        $value = trim($value);
        if ($value === '') {
            return $fallback;
        }

        $timestamp = strtotime($value);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        if (preg_match('/^(\\d{1,2})月(\\d{1,2})日$/u', $value, $matches) === 1) {
            return sprintf('%04d-%02d-%02d', (int) date('Y'), (int) $matches[1], (int) $matches[2]);
        }

        return $fallback;
    }

    protected function extractJson(string $content): ?array
    {
        $content = trim($content);
        if ($content === '') {
            return null;
        }

        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\\{.*\\}/su', $content, $matches) !== 1) {
            return null;
        }

        $decoded = json_decode($matches[0], true);
        return is_array($decoded) ? $decoded : null;
    }

    protected function normalizeTransactionDraft(array $draft, int $preferredProjectId = 0): array
    {
        $type = $this->normalizeTransactionType((string) ($draft['type'] ?? ''));
        if ($type === '') {
            $type = $this->detectTransactionType((string) ($draft['notes'] ?? ''));
        }

        $amount = $this->normalizeAmount($draft['amount'] ?? 0);
        if ($amount <= 0) {
            return [
                'ok' => false,
                'error' => '没有识别出有效金额，请补充具体金额后再试。',
            ];
        }

        if ($type === '') {
            return [
                'ok' => false,
                'error' => '没有识别出是收入还是支出，请补充“收到”或“付款”等描述。',
            ];
        }

        $notes = trim((string) ($draft['notes'] ?? ''));
        $projectId = $preferredProjectId > 0 ? $preferredProjectId : $this->matchProjectIdByName((string) ($draft['project_name'] ?? ''));
        if ($projectId <= 0 && !empty($draft['project_id'])) {
            $projectId = (int) $draft['project_id'];
        }

        $category = trim((string) ($draft['category'] ?? ''));
        if (!in_array($category, $this->getCategoryOptions(), true)) {
            $category = $this->inferCategory($notes, $type);
        }
        if ($category === '') {
            $category = $type === 'income' ? '其他收入' : '其他支出';
        }

        $counterparty = trim((string) ($draft['counterparty'] ?? ''));
        if ($counterparty === '') {
            $counterparty = $this->detectCounterparty($notes, $type);
        }
        if ($counterparty === '') {
            $counterparty = $type === 'income' ? '待确认来款方' : '待补充往来方';
        }

        $transactionDate = $this->normalizeDate((string) ($draft['transaction_date'] ?? ($draft['date'] ?? '')), date('Y-m-d'));
        $paymentMethod = $this->normalizePaymentMethod((string) ($draft['payment_method'] ?? ''));

        return [
            'ok' => true,
            'draft' => [
                'transaction_date' => $transactionDate,
                'type' => $type,
                'category' => $category,
                'counterparty' => $counterparty,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'project_id' => $projectId,
                'notes' => $notes,
            ],
        ];
    }

    protected function normalizeInvoiceDraft(array $draft, int $preferredProjectId = 0): array
    {
        $kind = $this->normalizeInvoiceKind((string) ($draft['kind'] ?? ''));
        if ($kind === '') {
            $kind = $this->detectInvoiceKind((string) ($draft['notes'] ?? ''));
        }
        if ($kind === '') {
            $kind = 'receivable';
        }

        $amount = $this->normalizeAmount($draft['amount'] ?? 0);
        if ($amount <= 0) {
            return [
                'ok' => false,
                'error' => '没有识别出有效金额，请补充具体金额后再试。',
            ];
        }

        $notes = trim((string) ($draft['notes'] ?? ''));
        $projectId = $preferredProjectId > 0 ? $preferredProjectId : $this->matchProjectIdByName((string) ($draft['project_name'] ?? ''));
        if ($projectId <= 0 && !empty($draft['project_id'])) {
            $projectId = (int) $draft['project_id'];
        }

        $counterparty = trim((string) ($draft['counterparty'] ?? ''));
        if ($counterparty === '') {
            $transactionType = $kind === 'receivable' ? 'income' : 'expense';
            $counterparty = $this->detectCounterparty($notes, $transactionType);
        }
        if ($counterparty === '') {
            $counterparty = $kind === 'receivable' ? '待确认回款方' : '待确认付款方';
        }

        $dueDate = $this->normalizeDate((string) ($draft['due_date'] ?? ($draft['date'] ?? '')), $this->detectDate($notes) ?: date('Y-m-d'));
        $status = $this->normalizeInvoiceStatus((string) ($draft['status'] ?? 'pending'));
        $title = trim((string) ($draft['title'] ?? ''));
        if ($title === '') {
            $title = $counterparty . ($kind === 'receivable' ? '回款单' : '付款单');
        }

        return [
            'ok' => true,
            'draft' => [
                'kind' => $kind,
                'title' => $this->limitText($title, 150),
                'counterparty' => $counterparty,
                'amount' => $amount,
                'due_date' => $dueDate,
                'status' => $status,
                'project_id' => $projectId,
                'notes' => $notes,
            ],
        ];
    }

    protected function buildTransactionDraftFromRules(string $text, array $context, int $preferredProjectId): array
    {
        $type = $this->detectTransactionType($text);
        $amount = $this->detectAmount($text);
        if ($type === '' || $amount <= 0) {
            return [
                'ok' => false,
                'error' => '这句话里没有稳定识别出收支方向或金额，建议写清“收到/付款”和具体金额。',
            ];
        }

        $projectId = $preferredProjectId > 0 ? $preferredProjectId : $this->detectProjectIdFromText($context['projects'], $text);
        $category = $this->inferCategory($text, $type);
        $counterparty = $this->detectCounterparty($text, $type);
        $paymentMethod = $this->detectPaymentMethod($text);
        $transactionDate = $this->detectDate($text) ?: date('Y-m-d');
        $needsReview = false;

        if ($category === '') {
            $category = $type === 'income' ? '其他收入' : '其他支出';
            $needsReview = true;
        }
        if ($counterparty === '') {
            $counterparty = $type === 'income' ? '待确认来款方' : '待补充往来方';
            $needsReview = true;
        }
        if ($paymentMethod === '') {
            $paymentMethod = 'other';
            $needsReview = true;
        }

        return [
            'ok' => true,
            'needs_review' => $needsReview,
            'draft' => [
                'transaction_date' => $transactionDate,
                'type' => $type,
                'category' => $category,
                'counterparty' => $counterparty,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'project_id' => $projectId,
                'notes' => $text,
            ],
        ];
    }

    protected function buildInvoiceDraftFromRules(string $text, array $context, int $preferredProjectId): array
    {
        $kind = $this->detectInvoiceKind($text);
        $amount = $this->detectAmount($text);
        if ($amount <= 0) {
            return [
                'ok' => false,
                'error' => '这句话里没有识别出有效金额，建议补充待收或待付的具体金额。',
            ];
        }

        if ($kind === '') {
            $kind = 'receivable';
        }

        $projectId = $preferredProjectId > 0 ? $preferredProjectId : $this->detectProjectIdFromText($context['projects'], $text);
        $counterparty = $this->detectCounterparty($text, $kind === 'receivable' ? 'income' : 'expense');
        $dueDate = $this->detectDate($text) ?: $this->detectRelativeDueDate($text) ?: date('Y-m-d');
        $needsReview = false;

        if ($counterparty === '') {
            $counterparty = $kind === 'receivable' ? '待确认回款方' : '待确认付款方';
            $needsReview = true;
        }

        return [
            'ok' => true,
            'needs_review' => $needsReview,
            'draft' => [
                'kind' => $kind,
                'title' => $counterparty . ($kind === 'receivable' ? '回款单' : '付款单'),
                'counterparty' => $counterparty,
                'amount' => $amount,
                'due_date' => $dueDate,
                'status' => 'pending',
                'project_id' => $projectId,
                'notes' => $text,
            ],
        ];
    }

    protected function looksLikeInvoiceIntent(string $text): bool
    {
        if (preg_match('/(应收|应付|待收|待付|账期|催款|付款计划|回款计划|付款申请|未回款|未付款|月底前|下周|下月|计划付款|计划回款)/u', $text) === 1) {
            return true;
        }

        if (preg_match('/(今天|昨天|前天|收到|付款|支付|转账|打款|汇款)/u', $text) === 1) {
            return false;
        }

        return false;
    }

    protected function detectTransactionType(string $text): string
    {
        if (preg_match('/(收到|收款|回款|到账|入账|收入|进账)/u', $text) === 1) {
            return 'income';
        }

        if (preg_match('/(付款|支付|付了|支出|转给|打款给|汇给|报销|买了|采购|缴费|付费)/u', $text) === 1) {
            return 'expense';
        }

        return '';
    }

    protected function detectInvoiceKind(string $text): string
    {
        if (preg_match('/(应收|待收|催款|回款计划|未回款|月底前回款|客户回款)/u', $text) === 1) {
            return 'receivable';
        }

        if (preg_match('/(应付|待付|付款申请|付款计划|未付款|供应商款|月底前付款)/u', $text) === 1) {
            return 'payable';
        }

        return '';
    }

    protected function detectAmount(string $text): float
    {
        if (preg_match('/([0-9]+(?:,[0-9]{3})*(?:\\.[0-9]{1,2})?)\\s*(?:元|块钱|块|RMB|rmb|￥|¥)/u', $text, $matches) === 1) {
            return $this->normalizeAmount($matches[1]);
        }

        if (preg_match('/([0-9]+(?:,[0-9]{3})*(?:\\.[0-9]{1,2})?)/u', $text, $matches) === 1) {
            return $this->normalizeAmount($matches[1]);
        }

        return 0.0;
    }

    protected function detectPaymentMethod(string $text): string
    {
        $mapping = [
            'wechat' => '/(微信)/u',
            'alipay' => '/(支付宝)/u',
            'bank' => '/(银行|转账|公户|对公|打款|汇款)/u',
            'cash' => '/(现金)/u',
        ];

        foreach ($mapping as $method => $pattern) {
            if (preg_match($pattern, $text) === 1) {
                return $method;
            }
        }

        return '';
    }

    protected function detectCounterparty(string $text, string $type): string
    {
        $patterns = $type === 'income'
            ? [
                '/收到\\s*([^，。；、\\s]{1,30}?)(?:的)?(?:付款|回款|转账|打款|汇款|款项)?(?:人|[0-9]|$)/u',
                '/([^，。；、\\s]{1,30}?)(?:回款|打款|转账|付款)(?:了|给了|[0-9]|$)/u',
                '/来自\\s*([^，。；、\\s]{1,30}?)(?:的)?(?:回款|付款|转账|打款|汇款)?/u',
            ]
            : [
                '/给\\s*([^，。；、\\s]{1,30}?)(?:付款|支付|转账|打款|汇款|付了)/u',
                '/(?:向|付给|支付给|转给)\\s*([^，。；、\\s]{1,30}?)(?:付款|支付|转账|打款|汇款|[0-9]|$)/u',
                '/(?:采购|购买|报销)\\s*([^，。；、\\s]{1,30}?)(?:花了|支付|付款|[0-9]|$)/u',
            ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches) === 1) {
                return $this->cleanCounterparty((string) $matches[1]);
            }
        }

        return '';
    }

    protected function cleanCounterparty(string $counterparty): string
    {
        $counterparty = trim($counterparty);
        $counterparty = preg_replace('/^(人|给|向|付给|支付给)/u', '', $counterparty) ?: $counterparty;
        $counterparty = preg_replace('/(付款|支付|转账|打款|汇款|回款)$/u', '', $counterparty) ?: $counterparty;

        return trim($counterparty);
    }

    protected function inferCategory(string $text, string $type): string
    {
        $incomePatterns = [
            '订阅收入' => '/(订阅|续费|SaaS|saas)/u',
            '项目预付款' => '/(预付款|预收|首付款|定金)/u',
            '项目回款' => '/(回款|收款|到账|尾款|合同款|项目款)/u',
        ];

        $expensePatterns = [
            '工资发放' => '/(工资|薪资|社保|公积金|奖金)/u',
            '云资源' => '/(云服务器|服务器|带宽|短信|存储|cdn|CDN|域名|云资源)/u',
            '市场投放' => '/(投放|广告|推广|获客|投流)/u',
            '办公支出' => '/(办公|文具|耗材|打印|物业|房租|水电|办公用品)/u',
            '外包测试' => '/(外包|测试服务|回归测试|兼容性测试|验收测试|压测|第三方测试)/u',
            '差旅费用' => '/(差旅|机票|车票|打车|酒店|出差)/u',
            '顾问服务' => '/(顾问|咨询|服务费|法务|审计)/u',
        ];

        $patterns = $type === 'income' ? $incomePatterns : $expensePatterns;
        foreach ($patterns as $category => $pattern) {
            if (preg_match($pattern, $text) === 1) {
                return $category;
            }
        }

        return '';
    }

    protected function detectDate(string $text): string
    {
        if (preg_match('/(\\d{4})[年\\/-](\\d{1,2})[月\\/-](\\d{1,2})日?/u', $text, $matches) === 1) {
            return sprintf('%04d-%02d-%02d', (int) $matches[1], (int) $matches[2], (int) $matches[3]);
        }

        if (preg_match('/(\\d{1,2})月(\\d{1,2})日/u', $text, $matches) === 1) {
            return sprintf('%04d-%02d-%02d', (int) date('Y'), (int) $matches[1], (int) $matches[2]);
        }

        if (strpos($text, '今天') !== false) {
            return date('Y-m-d');
        }
        if (strpos($text, '昨天') !== false) {
            return date('Y-m-d', strtotime('-1 day'));
        }
        if (strpos($text, '前天') !== false) {
            return date('Y-m-d', strtotime('-2 day'));
        }
        if (strpos($text, '明天') !== false) {
            return date('Y-m-d', strtotime('+1 day'));
        }
        if (strpos($text, '后天') !== false) {
            return date('Y-m-d', strtotime('+2 day'));
        }

        return '';
    }

    protected function detectRelativeDueDate(string $text): string
    {
        if (preg_match('/(\\d{1,2})天后/u', $text, $matches) === 1) {
            return date('Y-m-d', strtotime('+' . (int) $matches[1] . ' day'));
        }

        if (strpos($text, '月底') !== false) {
            return date('Y-m-t');
        }
        if (strpos($text, '下周') !== false) {
            return date('Y-m-d', strtotime('+7 day'));
        }
        if (strpos($text, '下月') !== false) {
            return date('Y-m-d', strtotime('+1 month'));
        }

        return '';
    }

    protected function detectProjectIdFromText(array $projects, string $text): int
    {
        foreach ($projects as $project) {
            if ($project['name'] !== '' && $this->containsText($text, $project['name'])) {
                return (int) $project['id'];
            }
        }

        return 0;
    }

    protected function matchProjectIdByName(string $projectName): int
    {
        $projectName = trim($projectName);
        if ($projectName === '') {
            return 0;
        }

        foreach ($this->getProjects() as $project) {
            if ($project['name'] === $projectName || $this->containsText($projectName, $project['name'])) {
                return (int) $project['id'];
            }
        }

        return 0;
    }

    protected function buildTransactionSummary(array $draft): string
    {
        $typeText = $this->getTransactionTypeOptions()[$draft['type']] ?? $draft['type'];
        $paymentText = $this->getPaymentMethodOptions()[$draft['payment_method']] ?? $draft['payment_method'];

        return sprintf(
            '%s %.2f 元，往来对象 %s，分类 %s，支付方式 %s',
            $typeText,
            (float) $draft['amount'],
            $draft['counterparty'],
            $draft['category'],
            $paymentText
        );
    }

    protected function buildInvoiceSummary(array $draft): string
    {
        $kindText = $this->getInvoiceKindOptions()[$draft['kind']] ?? $draft['kind'];
        $statusText = $this->getInvoiceStatusOptions()[$draft['status']] ?? $draft['status'];

        return sprintf(
            '%s %.2f 元，往来对象 %s，到期 %s，状态 %s',
            $kindText,
            (float) $draft['amount'],
            $draft['counterparty'],
            $draft['due_date'],
            $statusText
        );
    }

    protected function limitText(string $text, int $limit = 280): string
    {
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            return mb_strlen($text) > $limit ? mb_substr($text, 0, $limit) : $text;
        }

        return strlen($text) > $limit ? substr($text, 0, $limit) : $text;
    }

    protected function containsText(string $text, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }

        if (function_exists('mb_stripos')) {
            return mb_stripos($text, $needle) !== false;
        }

        return stripos($text, $needle) !== false;
    }
}
