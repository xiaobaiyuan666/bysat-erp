<?php

declare(strict_types=1);

function ai_settings(array $data): array
{
    return $data['ai']['settings'] ?? default_data()['ai']['settings'];
}

function ai_conversation(array $data): array
{
    return $data['ai']['conversation'] ?? [];
}

function ai_is_configured(array $settings): bool
{
    return trim((string) ($settings['base_url'] ?? '')) !== '' && trim((string) ($settings['model'] ?? '')) !== '';
}

function ai_endpoint(string $baseUrl): string
{
    $baseUrl = rtrim(trim($baseUrl), '/');

    if ($baseUrl === '') {
        return '';
    }

    if (str_ends_with($baseUrl, '/chat/completions')) {
        return $baseUrl;
    }

    if (str_ends_with($baseUrl, '/v1')) {
        return $baseUrl . '/chat/completions';
    }

    if (str_contains($baseUrl, '/v1/')) {
        return $baseUrl;
    }

    return $baseUrl . '/v1/chat/completions';
}

function ai_default_system_prompt(): string
{
    return implode("\n", [
        '你是这套企业管理系统里的经营分析顾问，服务对象是中国中小企业老板、财务负责人和项目负责人。',
        '你必须只基于系统提供的数据回答，不允许编造系统中不存在的金额、日期、项目或客户信息。',
        '默认使用简体中文，表达务实直接，优先给出能马上执行的动作。',
        '回答时尽量按下面结构输出：',
        '一、结论摘要',
        '二、关键数据依据',
        '三、风险与异常',
        '四、建议动作（分今天、本周、本月）',
        '五、需要补充的数据',
        '如果用户的问题偏财务，优先关注现金流、回款、逾期、成本占比和利润。',
        '如果用户的问题偏项目，优先关注交付风险、预算消耗、逾期任务和责任人负荷。',
        '引用数据时尽量写出具体金额、日期、项目名和责任人。',
    ]);
}

function ai_prompt_presets(): array
{
    return [
        [
            'key' => 'daily-ops',
            'label' => '经营日报',
            'description' => '汇总经营结论、异常和今日动作。',
            'prompt' => '请基于当前系统数据生成一份经营日报，按“一、结论摘要 / 二、关键数据依据 / 三、风险与异常 / 四、今日行动项 / 五、本周跟进项”输出，重点关注现金流、回款、项目交付和任务逾期。',
        ],
        [
            'key' => 'cash-risk',
            'label' => '现金流风险',
            'description' => '评估未来 30 天现金流与付款压力。',
            'prompt' => '请结合最近流水、待回款、待付款、逾期单据和项目状态，评估未来 30 天现金流风险，并按高/中/低给出判断。输出时列出最关键的 3 个风险点和对应处理建议。',
        ],
        [
            'key' => 'collections',
            'label' => '回款建议',
            'description' => '梳理应收节点和催收优先级。',
            'prompt' => '请基于当前应收账款数据，给出一份回款推进建议。要求列出逾期单据、即将到期单据、建议先跟进的客户顺序，以及每笔应收的沟通动作。',
        ],
        [
            'key' => 'project-review',
            'label' => '项目复盘',
            'description' => '定位预算、进度和交付风险。',
            'prompt' => '请结合项目预算、实际成本、进度、逾期任务和责任人负荷，做一份项目复盘。输出最需要关注的项目、问题成因和下一步安排。',
        ],
        [
            'key' => 'cost-control',
            'label' => '成本优化',
            'description' => '识别高支出项和可控空间。',
            'prompt' => '请结合近期支出分类、项目成本和未付账款，分析有哪些成本项可以优化。要求按“立即可控 / 本月可控 / 需管理层决策”分类给出建议。',
        ],
    ];
}

function ai_build_context(array $data): array
{
    $dashboard = dashboard_metrics($data);
    $projectRows = project_summaries($data);
    $taskRows = task_rows($data);
    $invoiceRows = invoice_rows($data);
    $transactionRows = recent_transactions($data['transactions'], 30);

    return [
        'generated_at' => date('Y-m-d H:i:s'),
        'company' => $data['meta']['company'] ?? '',
        'currency' => $data['meta']['currency'] ?? 'CNY',
        'dashboard' => $dashboard,
        'alerts' => business_alerts($data),
        'cashflow' => [
            'monthly' => monthly_cashflow($data['transactions'], 6),
            'income_breakdown' => category_breakdown($data['transactions'], 'income', 6),
            'expense_breakdown' => category_breakdown($data['transactions'], 'expense', 6),
        ],
        'receivables_payables' => [
            'summary' => invoice_status_summary($invoiceRows),
            'due_soon' => array_slice(due_invoice_rows($invoiceRows, 15), 0, 12),
            'open_invoices' => array_slice(array_values(array_filter(
                $invoiceRows,
                static fn(array $row): bool => (string) $row['status'] !== 'paid'
            )), 0, 20),
        ],
        'projects' => [
            'health' => project_health_rows($projectRows, 8),
            'all' => array_slice($projectRows, 0, 12),
        ],
        'tasks' => [
            'status' => task_status_summary($taskRows),
            'assignee_load' => assignee_load_rows($taskRows, 8),
            'urgent' => array_slice(array_values(array_filter(
                $taskRows,
                static fn(array $row): bool => (bool) $row['overdue'] || (string) $row['status'] !== 'done'
            )), 0, 12),
        ],
        'recent_transactions' => $transactionRows,
    ];
}

function ai_trim_conversation(array $conversation, int $maxMessages = 12): array
{
    $conversation = array_values(array_filter($conversation, static function (array $message): bool {
        return in_array($message['role'] ?? '', ['user', 'assistant'], true)
            && trim((string) ($message['content'] ?? '')) !== '';
    }));

    return array_slice($conversation, -$maxMessages);
}

function ai_append_message(array &$data, string $role, string $content): void
{
    $data['ai']['conversation'][] = [
        'role' => $role,
        'content' => $content,
        'created_at' => date('Y-m-d H:i:s'),
    ];

    $data['ai']['conversation'] = ai_trim_conversation($data['ai']['conversation'], 16);
}

function ai_request(array $settings, array $messages): array
{
    $endpoint = ai_endpoint((string) ($settings['base_url'] ?? ''));

    if ($endpoint === '') {
        return [
            'ok' => false,
            'error' => '模型接口地址未配置。',
        ];
    }

    if (!function_exists('curl_init')) {
        return [
            'ok' => false,
            'error' => '当前 PHP 环境未启用 cURL 扩展，无法请求模型接口。',
        ];
    }

    $payload = [
        'model' => (string) ($settings['model'] ?? ''),
        'temperature' => (float) ($settings['temperature'] ?? 0.2),
        'messages' => $messages,
    ];

    $headers = [
        'Content-Type: application/json',
    ];

    if (trim((string) ($settings['api_key'] ?? '')) !== '') {
        $headers[] = 'Authorization: Bearer ' . trim((string) $settings['api_key']);
    }

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        CURLOPT_TIMEOUT => 120,
        CURLOPT_CONNECTTIMEOUT => 15,
    ]);

    $raw = curl_exec($ch);
    $curlError = curl_error($ch);
    $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($raw === false) {
        return [
            'ok' => false,
            'error' => '模型请求失败：' . $curlError,
        ];
    }

    $decoded = json_decode($raw, true);

    if ($statusCode < 200 || $statusCode >= 300) {
        $message = $decoded['error']['message'] ?? ('模型接口返回 HTTP ' . $statusCode);

        return [
            'ok' => false,
            'error' => (string) $message,
        ];
    }

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
    ];
}

function ai_ask(array $data, string $question): array
{
    $settings = ai_settings($data);

    if (!ai_is_configured($settings)) {
        return [
            'ok' => false,
            'error' => '请先在模型设置中填写 Base URL 和模型名称。',
        ];
    }

    $customPrompt = trim((string) ($settings['system_prompt'] ?? ''));
    $systemPrompt = ai_default_system_prompt();

    if ($customPrompt !== '' && $customPrompt !== $systemPrompt) {
        $systemPrompt .= "\n\n附加要求：\n" . $customPrompt;
    }

    $context = ai_build_context($data);
    $messages = [
        [
            'role' => 'system',
            'content' => $systemPrompt,
        ],
        [
            'role' => 'system',
            'content' => "以下是当前业务数据快照，请严格基于这些数据回答：\n" .
                json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ],
    ];

    $conversation = ai_trim_conversation(ai_conversation($data), 10);
    foreach ($conversation as $message) {
        $messages[] = [
            'role' => (string) $message['role'],
            'content' => (string) $message['content'],
        ];
    }

    $lastMessage = $conversation === [] ? null : $conversation[count($conversation) - 1];
    if (
        $lastMessage === null
        || (string) ($lastMessage['role'] ?? '') !== 'user'
        || trim((string) ($lastMessage['content'] ?? '')) !== trim($question)
    ) {
        $messages[] = [
            'role' => 'user',
            'content' => $question,
        ];
    }

    return ai_request($settings, $messages);
}
