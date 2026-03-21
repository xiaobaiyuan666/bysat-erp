<?php

namespace app\admin\library;

class AiProviderDiscovery
{
    public function discover(string $baseUrl, string $apiKey, string $currentModel = '', array $options = []): array
    {
        $baseUrl = trim($baseUrl);
        $apiKey = trim($apiKey);
        $currentModel = trim($currentModel);

        if ($baseUrl === '' || $apiKey === '') {
            return [
                'ok' => false,
                'error' => '请先填写 Base URL 和 API Key。',
            ];
        }

        $normalizedBaseUrl = $this->normalizeBaseUrl($baseUrl);
        $modelsUrl = $this->buildModelsUrl($normalizedBaseUrl);
        $modelsResponse = $this->requestJson($modelsUrl, $apiKey, 'GET', $options);
        if (!$modelsResponse['ok']) {
            return $modelsResponse;
        }

        $payload = $modelsResponse['data'];
        $models = [];
        foreach (($payload['data'] ?? []) as $item) {
            $id = trim((string) ($item['id'] ?? ''));
            if ($id === '') {
                continue;
            }

            $models[] = [
                'id' => $id,
                'owned_by' => (string) ($item['owned_by'] ?? ''),
                'supported_endpoint_types' => (array) ($item['supported_endpoint_types'] ?? []),
            ];
        }

        $providerName = $this->guessProviderName($normalizedBaseUrl);
        $protocol = 'openai-compatible';
        $notes = [];
        $includeRootProbe = array_key_exists('include_root_probe', $options)
            ? (bool) $options['include_root_probe']
            : true;

        if ($includeRootProbe) {
            $rootProbe = $this->requestText($this->baseOrigin($normalizedBaseUrl), $apiKey, $options);
            if ($rootProbe['ok'] && stripos($rootProbe['body'], 'new-api') !== false) {
                $providerName = '通用兼容网关';
                $notes[] = '检测到目标网关首页带有 New API 标识。';
            }
        }

        $modelIds = array_values(array_map(function ($item) {
            return $item['id'];
        }, $models));

        if ($currentModel !== '' && !in_array($currentModel, $modelIds, true)) {
            $notes[] = '当前填写的模型不在接口返回列表里，请优先从已检测到的模型中选择。';
        }

        $recommendation = $this->buildRecommendation($currentModel, $modelIds);

        return [
            'ok' => true,
            'protocol' => $protocol,
            'provider_name' => $providerName,
            'normalized_base_url' => $normalizedBaseUrl,
            'models_url' => $modelsUrl,
            'models' => $models,
            'model_ids' => $modelIds,
            'current_model_found' => $currentModel === '' ? null : in_array($currentModel, $modelIds, true),
            'notes' => $notes,
            'recommended_model' => $recommendation['recommended_model'],
            'faster_model_candidates' => $recommendation['faster_model_candidates'],
            'recommendation_reason' => $recommendation['recommendation_reason'],
        ];
    }

    public function probeChatCompletion(string $baseUrl, string $apiKey, string $model, array $options = []): array
    {
        $baseUrl = trim($baseUrl);
        $apiKey = trim($apiKey);
        $model = trim($model);

        if ($baseUrl === '' || $apiKey === '' || $model === '') {
            return [
                'ok' => false,
                'error' => '缺少 Base URL、API Key 或模型名称。',
            ];
        }

        $normalizedBaseUrl = $this->normalizeBaseUrl($baseUrl);
        $chatUrl = $this->buildChatUrl($normalizedBaseUrl);
        $payload = [
            'model' => $model,
            'temperature' => 0,
            'max_tokens' => 8,
            'stream' => false,
            'messages' => [
                ['role' => 'user', 'content' => '请只回复：连接正常'],
            ],
        ];

        $result = $this->requestRaw(
            $chatUrl,
            $apiKey,
            'POST',
            $options,
            ['Content-Type: application/json'],
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        if (!$result['ok']) {
            return $result;
        }

        $decoded = json_decode($result['body'], true);
        $content = $decoded['choices'][0]['message']['content'] ?? '';
        if (is_array($content)) {
            $content = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (!is_string($content) || trim($content) === '') {
            return [
                'ok' => false,
                'error' => '模型返回内容为空。',
            ];
        }

        return [
            'ok' => true,
            'content' => trim($content),
            'elapsed' => $result['elapsed'] ?? null,
        ];
    }

    protected function normalizeBaseUrl(string $baseUrl): string
    {
        $baseUrl = rtrim(trim($baseUrl), '/');

        foreach (['/v1/chat/completions', '/chat/completions', '/v1/models', '/models'] as $suffix) {
            if (substr($baseUrl, -strlen($suffix)) === $suffix) {
                return substr($baseUrl, 0, -strlen($suffix)) ?: $baseUrl;
            }
        }

        return $baseUrl;
    }

    protected function buildModelsUrl(string $baseUrl): string
    {
        if (substr($baseUrl, -3) === '/v1' || preg_match('#/v\d+$#', $baseUrl)) {
            return $baseUrl . '/models';
        }

        return $baseUrl . '/v1/models';
    }

    protected function buildChatUrl(string $baseUrl): string
    {
        if (substr($baseUrl, -3) === '/v1' || preg_match('#/v\d+$#', $baseUrl)) {
            return $baseUrl . '/chat/completions';
        }

        return $baseUrl . '/v1/chat/completions';
    }

    protected function baseOrigin(string $baseUrl): string
    {
        $parts = parse_url($baseUrl);
        if (!$parts || empty($parts['scheme']) || empty($parts['host'])) {
            return $baseUrl;
        }

        $origin = $parts['scheme'] . '://' . $parts['host'];
        if (!empty($parts['port'])) {
            $origin .= ':' . $parts['port'];
        }

        return $origin;
    }

    protected function guessProviderName(string $baseUrl): string
    {
        $host = strtolower((string) parse_url($baseUrl, PHP_URL_HOST));
        if ($host === '') {
            return 'OpenAI Compatible';
        }
        if (strpos($host, 'dashscope.aliyuncs.com') !== false) {
            return '阿里云百炼';
        }
        if (strpos($host, 'deepseek.com') !== false) {
            return 'DeepSeek';
        }
        if (strpos($host, 'openai.com') !== false) {
            return 'OpenAI';
        }
        if (strpos($host, 'anthropic.com') !== false) {
            return 'Anthropic Compatible';
        }

        return 'OpenAI Compatible';
    }

    protected function buildRecommendation(string $currentModel, array $modelIds): array
    {
        $resolvedCurrent = $this->resolveModelCandidate($currentModel, $modelIds);
        $candidates = [];

        foreach ($this->buildCurrentFamilyCandidates($currentModel) as $candidate) {
            $resolved = $this->resolveModelCandidate($candidate, $modelIds);
            if ($resolved !== '' && !$this->containsModel($resolved, $candidates)) {
                $candidates[] = $resolved;
            }
        }

        foreach ([
            'openai/gpt-5.2',
            'gpt-5.2',
            'openai/gpt-4.1-mini',
            'gpt-4.1-mini',
            'openai/gpt-4o-mini',
            'gpt-4o-mini',
            'google/gemini-3-flash:free',
            'google/gemini-3-flash',
            'deepseek-chat',
            'qwen-turbo',
            'qwen-plus',
        ] as $candidate) {
            $resolved = $this->resolveModelCandidate($candidate, $modelIds);
            if ($resolved !== '' && !$this->containsModel($resolved, $candidates)) {
                $candidates[] = $resolved;
            }
        }

        foreach ($modelIds as $id) {
            if (!preg_match('/(mini|flash|turbo|chat|free)/i', (string) $id)) {
                continue;
            }

            $resolved = $this->resolveModelCandidate((string) $id, $modelIds);
            if ($resolved !== '' && !$this->containsModel($resolved, $candidates)) {
                $candidates[] = $resolved;
            }
        }

        if (!$candidates && $resolvedCurrent !== '') {
            $candidates[] = $resolvedCurrent;
        }

        $recommended = '';
        foreach ($candidates as $candidate) {
            if ($resolvedCurrent !== '' && $this->sameModelFamily($candidate, $resolvedCurrent)) {
                continue;
            }
            $recommended = $candidate;
            break;
        }

        if ($recommended === '' && $resolvedCurrent !== '') {
            $recommended = $resolvedCurrent;
        }

        $reason = '';
        if ($recommended !== '') {
            if ($resolvedCurrent !== '' && !$this->sameModelFamily($recommended, $resolvedCurrent)) {
                $reason = '当前模型更偏完整推理，推荐先切到响应更快的“' . $recommended . '”用于日常工作台分析。';
            } else {
                $reason = '当前可用模型里，这条配置可以直接先用。';
            }
        }

        return [
            'recommended_model' => $recommended,
            'faster_model_candidates' => array_slice($candidates, 0, 5),
            'recommendation_reason' => $reason,
        ];
    }

    protected function buildCurrentFamilyCandidates(string $currentModel): array
    {
        $currentModel = trim($currentModel);
        if ($currentModel === '') {
            return [];
        }

        $variants = [];
        $replacements = [
            ['gpt-5.4', 'gpt-5.2'],
            ['gpt-5.4', 'gpt-4.1-mini'],
            ['gpt-5.4', 'gpt-4o-mini'],
            ['gpt5.4', 'gpt5.2'],
            ['gpt5.4', 'gpt-4.1-mini'],
            ['gpt5.4', 'gpt-4o-mini'],
        ];

        foreach ($replacements as $pair) {
            [$from, $to] = $pair;
            if (stripos($currentModel, $from) === false) {
                continue;
            }

            $variants[] = str_ireplace($from, $to, $currentModel);
        }

        return $variants;
    }

    protected function resolveModelCandidate(string $candidate, array $modelIds): string
    {
        $candidate = trim($candidate);
        if ($candidate === '') {
            return '';
        }

        foreach ($modelIds as $id) {
            if (strcasecmp((string) $id, $candidate) === 0) {
                return (string) $id;
            }
        }

        return '';
    }

    protected function containsModel(string $candidate, array $modelIds): bool
    {
        foreach ($modelIds as $id) {
            if ($this->sameModelFamily((string) $id, $candidate)) {
                return true;
            }
        }

        return false;
    }

    protected function sameModelFamily(string $left, string $right): bool
    {
        return $this->normalizeModelKey($left) === $this->normalizeModelKey($right);
    }

    protected function normalizeModelKey(string $model): string
    {
        $model = strtolower(trim($model));
        if (strpos($model, 'openai/') === 0) {
            $model = substr($model, 7);
        }

        return $model;
    }

    protected function requestJson(string $url, string $apiKey, string $method = 'GET', array $options = []): array
    {
        $result = $this->requestRaw($url, $apiKey, $method, $options);
        if (!$result['ok']) {
            return $result;
        }

        $decoded = json_decode($result['body'], true);
        if (!is_array($decoded)) {
            return [
                'ok' => false,
                'error' => '模型接口返回的不是 JSON，无法识别协议。',
            ];
        }

        return [
            'ok' => true,
            'data' => $decoded,
            'status' => $result['status'],
        ];
    }

    protected function requestText(string $url, string $apiKey, array $options = []): array
    {
        return $this->requestRaw($url, $apiKey, 'GET', $options);
    }

    protected function requestRaw(
        string $url,
        string $apiKey,
        string $method,
        array $options = [],
        array $extraHeaders = [],
        ?string $body = null
    ): array {
        if (!function_exists('curl_init')) {
            return [
                'ok' => false,
                'error' => '当前 PHP 环境未启用 cURL 扩展，无法检测模型接口。',
            ];
        }

        $headers = array_merge([
            'Accept: application/json',
            'Authorization: Bearer ' . $apiKey,
        ], $extraHeaders);

        $timeout = isset($options['timeout']) ? max(3, (int) $options['timeout']) : 15;
        $connectTimeout = isset($options['connect_timeout']) ? max(2, (int) $options['connect_timeout']) : 6;
        $skipSslVerify = !empty($options['skip_ssl_verify']);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => $connectTimeout,
            CURLOPT_NOSIGNAL => true,
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        if (stripos($url, 'https://') === 0) {
            if ($skipSslVerify) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            } else {
                $caBundle = $this->resolveCaBundlePath();
                if ($caBundle !== '') {
                    curl_setopt($ch, CURLOPT_CAINFO, $caBundle);
                }
            }
        }

        $startedAt = microtime(true);
        $responseBody = curl_exec($ch);
        $error = trim((string) curl_error($ch));
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $elapsed = (int) round((microtime(true) - $startedAt) * 1000);
        curl_close($ch);

        if ($responseBody === false) {
            if (stripos($error, 'timed out') !== false) {
                return [
                    'ok' => false,
                    'error' => '接口探测超时，目标网关没有在限定时间内返回结果。',
                ];
            }

            return [
                'ok' => false,
                'error' => $error !== '' ? ('接口探测失败：' . $error) : '接口探测失败。',
            ];
        }

        if ($status < 200 || $status >= 300) {
            $bodyText = trim(substr((string) $responseBody, 0, 240));
            return [
                'ok' => false,
                'error' => '接口探测失败：HTTP ' . $status . ($bodyText !== '' ? '，' . $bodyText : ''),
            ];
        }

        return [
            'ok' => true,
            'status' => $status,
            'body' => (string) $responseBody,
            'elapsed' => $elapsed,
        ];
    }

    protected function resolveCaBundlePath(): string
    {
        $path = dirname(dirname(dirname(dirname(__DIR__)))) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'certs' . DIRECTORY_SEPARATOR . 'cacert.pem';
        return is_file($path) ? $path : '';
    }
}
