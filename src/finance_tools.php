<?php

declare(strict_types=1);

function attachment_upload_root(string $bucket = 'finance'): string
{
    return root_path('public/uploads/' . trim($bucket, '/'));
}

function ensure_attachment_upload_dir(string $bucket = 'finance'): string
{
    $subdir = date('Y/m');
    $directory = attachment_upload_root($bucket) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $subdir);

    if (!is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    return $directory;
}

function normalize_uploaded_files(array $fileField): array
{
    if (!isset($fileField['name'])) {
        return [];
    }

    if (!is_array($fileField['name'])) {
        return [$fileField];
    }

    $files = [];
    foreach ($fileField['name'] as $index => $name) {
        $files[] = [
            'name' => $name,
            'type' => $fileField['type'][$index] ?? '',
            'tmp_name' => $fileField['tmp_name'][$index] ?? '',
            'error' => $fileField['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $fileField['size'][$index] ?? 0,
        ];
    }

    return $files;
}

function uploaded_files_present(array $fileField): bool
{
    foreach (normalize_uploaded_files($fileField) as $file) {
        if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            return true;
        }
    }

    return false;
}

function detect_uploaded_image_meta(string $tmpName): ?array
{
    $mimeMap = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string) ($finfo->file($tmpName) ?: '');

        if (isset($mimeMap[$mime])) {
            return [
                'mime' => $mime,
                'extension' => $mimeMap[$mime],
            ];
        }
    }

    if (function_exists('getimagesize')) {
        $imageInfo = @getimagesize($tmpName);
        $mime = (string) ($imageInfo['mime'] ?? '');

        if (isset($mimeMap[$mime])) {
            return [
                'mime' => $mime,
                'extension' => $mimeMap[$mime],
            ];
        }
    }

    return null;
}

function detect_uploaded_file_meta(string $tmpName, array $allowedMimeMap, string $originalName = ''): ?array
{
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string) ($finfo->file($tmpName) ?: '');

        if (isset($allowedMimeMap[$mime])) {
            return [
                'mime' => $mime,
                'extension' => (string) $allowedMimeMap[$mime],
            ];
        }
    }

    if (function_exists('mime_content_type')) {
        $mime = (string) (mime_content_type($tmpName) ?: '');

        if (isset($allowedMimeMap[$mime])) {
            return [
                'mime' => $mime,
                'extension' => (string) $allowedMimeMap[$mime],
            ];
        }
    }

    if (function_exists('getimagesize')) {
        $imageInfo = @getimagesize($tmpName);
        $mime = (string) ($imageInfo['mime'] ?? '');

        if (isset($allowedMimeMap[$mime])) {
            return [
                'mime' => $mime,
                'extension' => (string) $allowedMimeMap[$mime],
            ];
        }
    }

    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if ($extension !== '' && in_array($extension, $allowedMimeMap, true)) {
        $matchedMime = array_search($extension, $allowedMimeMap, true);

        return [
            'mime' => is_string($matchedMime) ? $matchedMime : 'application/octet-stream',
            'extension' => $extension,
        ];
    }

    return null;
}

function store_uploaded_files(array $fileField, array $options = []): array
{
    $files = normalize_uploaded_files($fileField);
    $bucket = trim((string) ($options['bucket'] ?? 'finance'), '/');
    $maxSize = (int) ($options['max_size'] ?? (8 * 1024 * 1024));
    $allowedMimeMap = is_array($options['allowed_mime_map'] ?? null)
        ? $options['allowed_mime_map']
        : [];
    $uploadFailedMessage = (string) ($options['upload_failed_message'] ?? '文件上传失败，请重新选择后再试。');
    $invalidFileMessage = (string) ($options['invalid_file_message'] ?? '上传文件无效，请重新上传。');
    $tooLargeMessage = (string) ($options['too_large_message'] ?? '上传文件过大，请压缩后再试。');
    $invalidTypeMessage = (string) ($options['invalid_type_message'] ?? '当前文件类型不支持上传。');
    $saveFailedMessage = (string) ($options['save_failed_message'] ?? '文件保存失败，请检查目录权限。');

    if ($files === []) {
        return [
            'ok' => true,
            'attachments' => [],
        ];
    }

    $prepared = [];
    $attachments = [];

    foreach ($files as $file) {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if ($error !== UPLOAD_ERR_OK) {
            return [
                'ok' => false,
                'error' => $uploadFailedMessage,
            ];
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            return [
                'ok' => false,
                'error' => $invalidFileMessage,
            ];
        }

        if ((int) ($file['size'] ?? 0) > $maxSize) {
            return [
                'ok' => false,
                'error' => $tooLargeMessage,
            ];
        }

        $fileMeta = detect_uploaded_file_meta($tmpName, $allowedMimeMap, (string) ($file['name'] ?? ''));
        if ($fileMeta === null) {
            return [
                'ok' => false,
                'error' => $invalidTypeMessage,
            ];
        }

        $prepared[] = [
            'file' => $file,
            'tmp_name' => $tmpName,
            'mime' => (string) $fileMeta['mime'],
            'extension' => (string) $fileMeta['extension'],
        ];
    }

    $directory = ensure_attachment_upload_dir($bucket === '' ? 'finance' : $bucket);
    foreach ($prepared as $item) {
        $filename = date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $item['extension'];
        $target = $directory . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($item['tmp_name'], $target)) {
            foreach ($attachments as $attachment) {
                delete_attachment_file($attachment);
            }

            return [
                'ok' => false,
                'error' => $saveFailedMessage,
            ];
        }

        $relativePath = 'uploads/' . ($bucket === '' ? 'finance' : $bucket) . '/' . date('Y/m') . '/' . $filename;
        $attachments[] = [
            'id' => next_id('att'),
            'name' => (string) (($item['file']['name'] ?? $filename)),
            'path' => $relativePath,
            'mime' => $item['mime'],
            'size' => (int) (($item['file']['size'] ?? 0)),
            'uploaded_at' => date('Y-m-d H:i:s'),
        ];
    }

    return [
        'ok' => true,
        'attachments' => $attachments,
    ];
}

function store_uploaded_image_attachments(array $fileField, string $bucket = 'finance'): array
{
    return store_uploaded_files($fileField, [
        'bucket' => $bucket,
        'max_size' => 8 * 1024 * 1024,
        'allowed_mime_map' => [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ],
        'upload_failed_message' => '附件上传失败，请重新选择图片后再试。',
        'invalid_file_message' => '附件文件无效，请重新上传。',
        'too_large_message' => '单张附件不能超过 8MB。',
        'invalid_type_message' => '附件只支持 JPG、PNG、WEBP、GIF 图片。',
        'save_failed_message' => '附件保存失败，请检查目录权限。',
    ]);
}

function record_attachments(array $record): array
{
    $attachments = $record['attachments'] ?? [];

    return is_array($attachments) ? array_values($attachments) : [];
}

function attachment_public_path(array $attachment): string
{
    return ltrim((string) ($attachment['path'] ?? ''), '/');
}

function delete_attachment_file(array $attachment): void
{
    $path = attachment_public_path($attachment);
    if ($path === '') {
        return;
    }

    $fullPath = root_path('public/' . $path);
    if (is_file($fullPath)) {
        @unlink($fullPath);
    }
}

function filter_remaining_attachments(array $attachments, array $removeIds): array
{
    $removeIds = array_values(array_filter(array_map('strval', $removeIds), static fn(string $id): bool => $id !== ''));
    if ($removeIds === []) {
        return $attachments;
    }

    $remaining = [];

    foreach ($attachments as $attachment) {
        $attachmentId = (string) ($attachment['id'] ?? '');

        if ($attachmentId !== '' && in_array($attachmentId, $removeIds, true)) {
            delete_attachment_file($attachment);
            continue;
        }

        $remaining[] = $attachment;
    }

    return $remaining;
}

function purge_attachments(array $attachments): void
{
    foreach ($attachments as $attachment) {
        delete_attachment_file($attachment);
    }
}

function detect_project_id_from_text(array $projects, string $text): string
{
    foreach ($projects as $project) {
        $name = (string) ($project['name'] ?? '');
        if ($name !== '' && text_contains_ci($text, $name)) {
            return (string) ($project['id'] ?? '');
        }
    }

    return '';
}

function smart_bookkeeping_parse(array $data, string $text): array
{
    $aiResult = smart_bookkeeping_parse_with_llm($data, $text);
    if ($aiResult['ok']) {
        return $aiResult;
    }

    $ruleResult = smart_bookkeeping_parse_with_rules($data, $text);

    if ($ruleResult['ok'] && ($aiResult['error'] ?? '') !== 'model_not_configured') {
        $ruleResult['fallback_from'] = 'ai';
        $ruleResult['fallback_error'] = (string) ($aiResult['error'] ?? '');
    }

    return $ruleResult;
}

function smart_bookkeeping_parse_with_ai(array $data, string $text): array
{
    $settings = ai_settings($data);

    if (!ai_is_configured($settings)) {
        return [
            'ok' => false,
            'error' => 'model_not_configured',
        ];
    }

    $projectNames = array_map(static fn(array $project): string => (string) ($project['name'] ?? ''), $data['projects']);
    $messages = [
        [
            'role' => 'system',
            'content' => implode("\n", [
                '你是财务流水解析器。',
                '把用户的一句中文记账文本解析成 JSON。',
                '只允许输出 JSON，不要输出解释、Markdown 或代码块。',
                '字段固定为：date,type,counterparty,amount,category,payment_method,project_name,notes,confidence。',
                'date 必须是 YYYY-MM-DD。',
                'type 只能是 income 或 expense。',
                'payment_method 只能是 bank、alipay、wechat、cash、other。',
                'amount 必须是数字。',
                '识别不出来时请填空字符串，confidence 输出 0 到 1 的数字。',
            ]),
        ],
        [
            'role' => 'user',
            'content' => "可识别项目名称：" . implode('、', array_filter($projectNames)) . "\n用户原文：" . $text,
        ],
    ];

    $result = ai_request($settings, $messages);
    if (!$result['ok']) {
        return $result;
    }

    $json = smart_bookkeeping_extract_json((string) $result['content']);
    if ($json === null) {
        return [
            'ok' => false,
            'error' => 'ai_parse_invalid',
        ];
    }

    $parsed = [
        'date' => normalize_bookkeeping_date((string) ($json['date'] ?? '')),
        'type' => normalize_bookkeeping_type((string) ($json['type'] ?? '')),
        'counterparty' => trim((string) ($json['counterparty'] ?? '')),
        'amount' => (float) ($json['amount'] ?? 0),
        'category' => trim((string) ($json['category'] ?? '')),
        'payment_method' => normalize_payment_method((string) ($json['payment_method'] ?? '')),
        'project_id' => match_project_id_by_name($data['projects'], (string) ($json['project_name'] ?? '')),
        'notes' => trim((string) ($json['notes'] ?? $text)),
    ];

    if ($parsed['date'] === '') {
        $parsed['date'] = date('Y-m-d');
    }

    if ($parsed['type'] === '' || $parsed['amount'] <= 0) {
        return [
            'ok' => false,
            'error' => 'ai_parse_incomplete',
        ];
    }

    if ($parsed['counterparty'] === '') {
        $parsed['counterparty'] = $parsed['type'] === 'income' ? '待确认来款方' : '待补充往来方';
    }

    if ($parsed['category'] === '') {
        $parsed['category'] = $parsed['type'] === 'income' ? '其他收入' : '其他支出';
    }

    return [
        'ok' => true,
        'source' => 'ai',
        'needs_review' => trim((string) ($json['counterparty'] ?? '')) === '' || trim((string) ($json['category'] ?? '')) === '',
        'parsed' => $parsed,
    ];
}

function smart_bookkeeping_parse_with_llm(array $data, string $text): array
{
    $settings = ai_settings($data);

    if (!ai_is_configured($settings)) {
        return [
            'ok' => false,
            'error' => 'model_not_configured',
        ];
    }

    $result = ai_request($settings, smart_bookkeeping_ai_messages($data, $text));
    if (!$result['ok']) {
        return $result;
    }

    $json = smart_bookkeeping_extract_json((string) $result['content']);
    if ($json === null) {
        return [
            'ok' => false,
            'error' => 'ai_parse_invalid',
        ];
    }

    $allowedCategories = transaction_category_suggestions();
    $category = trim((string) ($json['category'] ?? ''));
    if ($category !== '' && !in_array($category, $allowedCategories, true)) {
        $category = '';
    }

    $confidence = (float) ($json['confidence'] ?? 0);
    $parsed = [
        'date' => normalize_bookkeeping_date((string) ($json['date'] ?? '')),
        'type' => normalize_bookkeeping_type((string) ($json['type'] ?? '')),
        'counterparty' => trim((string) ($json['counterparty'] ?? '')),
        'amount' => (float) ($json['amount'] ?? 0),
        'category' => $category,
        'payment_method' => normalize_payment_method((string) ($json['payment_method'] ?? '')),
        'project_id' => match_project_id_by_name($data['projects'], (string) ($json['project_name'] ?? '')),
        'notes' => trim((string) ($json['notes'] ?? $text)),
    ];

    if ($parsed['date'] === '') {
        $parsed['date'] = date('Y-m-d');
    }

    if ($parsed['type'] === '' || $parsed['amount'] <= 0) {
        return [
            'ok' => false,
            'error' => 'ai_parse_incomplete',
        ];
    }

    if ($parsed['counterparty'] === '') {
        $parsed['counterparty'] = $parsed['type'] === 'income' ? '待确认来款方' : '待补充往来方';
    }

    if ($parsed['category'] === '') {
        $parsed['category'] = $parsed['type'] === 'income' ? '其他收入' : '其他支出';
    }

    return [
        'ok' => true,
        'source' => 'ai',
        'needs_review' => $confidence < 0.75
            || trim((string) ($json['counterparty'] ?? '')) === ''
            || trim((string) ($json['category'] ?? '')) === '',
        'parsed' => $parsed,
    ];
}

function smart_bookkeeping_ai_messages(array $data, string $text): array
{
    $projectNames = array_values(array_filter(array_map(
        static fn(array $project): string => (string) ($project['name'] ?? ''),
        $data['projects']
    )));

    $categoryOptions = implode('、', transaction_category_suggestions());
    $projectOptions = $projectNames === [] ? '无' : implode('、', $projectNames);

    return [
        [
            'role' => 'system',
            'content' => implode("\n", [
                '你是企业财务记账解析器，要把一句中文记账描述转换成结构化 JSON。',
                '只输出 JSON，不要输出解释、Markdown、代码块或多余文字。',
                '固定字段：date,type,counterparty,amount,category,payment_method,project_name,notes,confidence。',
                'date 必须是 YYYY-MM-DD，无法确定时按今天处理。',
                'type 只能是 income 或 expense。',
                'payment_method 只能是 bank、alipay、wechat、cash、other。',
                'category 必须优先从以下分类里选一个，无法确定再留空：' . $categoryOptions,
                'project_name 只能从以下项目名中选择，无法确定留空：' . $projectOptions,
                'amount 必须是数字，不能带货币符号。',
                'notes 保留用户原句并可补一句简短说明。',
                'confidence 输出 0 到 1 之间的小数。',
                '不要编造系统里没有的项目名、客户名和金额。',
            ]),
        ],
        [
            'role' => 'user',
            'content' => implode("\n", [
                '今天日期：' . date('Y-m-d'),
                '用户原句：' . $text,
                '示例一：今天给晨光办公付款100元，微信支付，买办公用品。',
                '示例二：昨天收到星环科技回款50000元，银行转账，官网项目尾款。',
            ]),
        ],
    ];
}

function smart_bookkeeping_parse_with_rules(array $data, string $text): array
{
    $text = trim($text);
    if ($text === '') {
        return [
            'ok' => false,
            'error' => '请先输入要记账的内容。',
        ];
    }

    $type = detect_bookkeeping_type($text);
    $amount = detect_bookkeeping_amount($text);

    if ($type === '' || $amount <= 0) {
        return [
            'ok' => false,
            'error' => '这句话里没有稳定识别出收支方向或金额，建议写清“收款/付款”和具体金额。',
        ];
    }

    $counterparty = detect_bookkeeping_counterparty($text, $type);
    $category = infer_bookkeeping_category($text, $type);
    $paymentMethod = detect_bookkeeping_payment_method($text);
    $date = detect_bookkeeping_date($text);
    $projectId = detect_project_id_from_text($data['projects'], $text);
    $needsReview = false;

    if ($counterparty === '') {
        $counterparty = $type === 'income' ? '待确认来款方' : '待补充往来方';
        $needsReview = true;
    }

    if ($category === '') {
        $category = $type === 'income' ? '其他收入' : '其他支出';
        $needsReview = true;
    }

    if ($paymentMethod === '') {
        $paymentMethod = 'other';
        $needsReview = true;
    }

    if ($date === '') {
        $date = date('Y-m-d');
    }

    return [
        'ok' => true,
        'source' => 'rule',
        'needs_review' => $needsReview,
        'parsed' => [
            'date' => $date,
            'type' => $type,
            'counterparty' => $counterparty,
            'amount' => $amount,
            'category' => $category,
            'payment_method' => $paymentMethod,
            'project_id' => $projectId,
            'notes' => $text,
        ],
    ];
}

function smart_bookkeeping_extract_json(string $content): ?array
{
    $content = trim($content);
    if ($content === '') {
        return null;
    }

    $decoded = json_decode($content, true);
    if (is_array($decoded)) {
        return $decoded;
    }

    if (preg_match('/\{.*\}/su', $content, $matches) !== 1) {
        return null;
    }

    $decoded = json_decode($matches[0], true);

    return is_array($decoded) ? $decoded : null;
}

function normalize_bookkeeping_type(string $type): string
{
    return in_array($type, ['income', 'expense'], true) ? $type : '';
}

function normalize_payment_method(string $paymentMethod): string
{
    return array_key_exists($paymentMethod, payment_method_options()) ? $paymentMethod : 'other';
}

function normalize_bookkeeping_date(string $date): string
{
    $timestamp = strtotime($date);

    return $timestamp === false ? '' : date('Y-m-d', $timestamp);
}

function match_project_id_by_name(array $projects, string $projectName): string
{
    $projectName = trim($projectName);

    if ($projectName === '') {
        return '';
    }

    foreach ($projects as $project) {
        if ((string) ($project['name'] ?? '') === $projectName) {
            return (string) ($project['id'] ?? '');
        }
    }

    return detect_project_id_from_text($projects, $projectName);
}

function detect_bookkeeping_type(string $text): string
{
    if (preg_match('/(收到|收款|回款|到账|入账|收入|进账)/u', $text) === 1) {
        return 'income';
    }

    if (preg_match('/(付款|支付|付了|支出|转给|打款给|汇给|报销|买了|采购|缴费|付费)/u', $text) === 1) {
        return 'expense';
    }

    return '';
}

function detect_bookkeeping_amount(string $text): float
{
    if (preg_match('/([0-9]+(?:\.[0-9]{1,2})?)\s*(?:元|块钱|块|￥|¥|rmb|RMB)/u', $text, $matches) === 1) {
        return (float) $matches[1];
    }

    if (preg_match('/([0-9]+(?:\.[0-9]{1,2})?)/u', $text, $matches) === 1) {
        return (float) $matches[1];
    }

    return 0.0;
}

function detect_bookkeeping_payment_method(string $text): string
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

function detect_bookkeeping_counterparty(string $text, string $type): string
{
    $patterns = $type === 'income'
        ? [
            '/收到\s*([^，。,.；;：:\s]{1,30}?)(?:的)?(?:付款|回款|转账|打款|汇款|款项)?(?:了)?[0-9]/u',
            '/([^，。,.；;：:\s]{1,30}?)(?:回款|打款|转账|付款)(?:了)?[0-9]/u',
            '/来自\s*([^，。,.；;：:\s]{1,30}?)(?:的)?(?:回款|付款|转账|打款|汇款)/u',
        ]
        : [
            '/给\s*([^，。,.；;：:\s]{1,30}?)(?:付款|支付|转账|打款|汇款|付了)/u',
            '/(?:向|付给|支付给|转给)\s*([^，。,.；;：:\s]{1,30}?)(?:付款|支付|转账|打款|汇款|[0-9]|$)/u',
            '/(?:采购|购买|报销)\s*([^，。,.；;：:\s]{1,30}?)(?:花了|支付|付款|[0-9]|$)/u',
        ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $text, $matches) === 1) {
            return clean_counterparty_name((string) $matches[1]);
        }
    }

    return '';
}

function clean_counterparty_name(string $counterparty): string
{
    $counterparty = preg_replace('/^(了|给|向|从)/u', '', trim($counterparty)) ?? trim($counterparty);
    $counterparty = preg_replace('/(付款|支付|转账|打款|汇款|回款|收款)$/u', '', $counterparty) ?? $counterparty;

    return trim($counterparty);
}

function detect_bookkeeping_category(string $text, string $type): string
{
    $incomePatterns = [
        '订阅收入' => '/(订阅|续费|SaaS|saas)/u',
        '项目预付款' => '/(预付款|预收|首付款|定金)/u',
        '项目回款' => '/(回款|收款|到账|尾款|合同款|项目款)/u',
    ];

    $expensePatterns = [
        '工资发放' => '/(工资|薪资|社保|公积金|奖金)/u',
        '云资源' => '/(云|服务器|带宽|短信|存储|cdn|CDN)/u',
        '市场投放' => '/(投放|广告|推广|获客)/u',
        '办公支出' => '/(办公|文具|耗材|打印|物业|房租|水电)/u',
        '外包测试' => '/(外包|测试|开发|设计外包)/u',
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

function infer_bookkeeping_category(string $text, string $type): string
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

function detect_bookkeeping_date(string $text): string
{
    if (preg_match('/(\d{4})[年\-\/](\d{1,2})[月\-\/](\d{1,2})日?/u', $text, $matches) === 1) {
        return sprintf('%04d-%02d-%02d', (int) $matches[1], (int) $matches[2], (int) $matches[3]);
    }

    if (preg_match('/(\d{1,2})月(\d{1,2})日/u', $text, $matches) === 1) {
        return sprintf('%04d-%02d-%02d', (int) date('Y'), (int) $matches[1], (int) $matches[2]);
    }

    if (str_contains($text, '今天')) {
        return date('Y-m-d');
    }

    if (str_contains($text, '昨天')) {
        return date('Y-m-d', strtotime('-1 day'));
    }

    if (str_contains($text, '前天')) {
        return date('Y-m-d', strtotime('-2 day'));
    }

    return '';
}
