<?php

declare(strict_types=1);

date_default_timezone_set('Asia/Shanghai');

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function text_contains_ci(string $haystack, string $needle): bool
{
    if ($needle === '') {
        return true;
    }

    if (function_exists('mb_stripos')) {
        return mb_stripos($haystack, $needle) !== false;
    }

    return stripos($haystack, $needle) !== false;
}

function text_length(string $text): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($text);
    }

    return strlen($text);
}

function text_slice(string $text, int $start, ?int $length = null): string
{
    if (function_exists('mb_substr')) {
        return $length === null ? mb_substr($text, $start) : mb_substr($text, $start, $length);
    }

    return $length === null ? substr($text, $start) : substr($text, $start, $length);
}

function money(float $amount): string
{
    return '¥' . number_format($amount, 2);
}

function normalize_page(string $page): string
{
    $allowed = ['dashboard', 'finance', 'projects'];

    return in_array($page, $allowed, true) ? $page : 'dashboard';
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function pull_flash(): ?array
{
    if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function redirect_with_flash(string $page, string $type, string $message, array $query = []): void
{
    set_flash($type, $message);
    $params = array_merge(['page' => $page], $query);
    $params = array_filter($params, static fn($value): bool => $value !== '' && $value !== null);
    header('Location: index.php?' . http_build_query($params));
    exit;
}

function input_string(array $source, string $key, string $default = ''): string
{
    return trim((string) ($source[$key] ?? $default));
}

function input_float(array $source, string $key, float $default = 0.0): float
{
    $raw = str_replace(',', '', input_string($source, $key, (string) $default));

    return is_numeric($raw) ? (float) $raw : $default;
}

function input_bool(array $source, string $key, bool $default = false): bool
{
    if (!array_key_exists($key, $source)) {
        return $default;
    }

    $value = $source[$key];

    if (is_bool($value)) {
        return $value;
    }

    return in_array((string) $value, ['1', 'true', 'on', 'yes'], true);
}

function selected_if(string $value, string $expected): string
{
    return $value === $expected ? 'selected' : '';
}

function checked_if(bool $value): string
{
    return $value ? 'checked' : '';
}

function project_status_options(): array
{
    return [
        'planning' => '规划中',
        'active' => '进行中',
        'delivery' => '交付中',
        'paused' => '已暂停',
        'done' => '已完成',
    ];
}

function task_status_options(): array
{
    return [
        'todo' => '待开始',
        'doing' => '进行中',
        'review' => '待验收',
        'done' => '已完成',
    ];
}

function priority_options(): array
{
    return [
        'high' => '高',
        'medium' => '中',
        'low' => '低',
    ];
}

function invoice_kind_options(): array
{
    return [
        'receivable' => '应收',
        'payable' => '应付',
    ];
}

function invoice_status_options(string $kind): array
{
    return [
        'pending' => $kind === 'receivable' ? '待回款' : '待付款',
        'partial' => '部分结清',
        'paid' => '已完成',
    ];
}

function transaction_type_options(): array
{
    return [
        'income' => '收入',
        'expense' => '支出',
    ];
}

function payment_method_options(): array
{
    return [
        'bank' => '银行转账',
        'alipay' => '支付宝',
        'wechat' => '微信',
        'cash' => '现金',
        'other' => '其他',
    ];
}

function transaction_category_suggestions(): array
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

function project_lookup(array $projects): array
{
    $lookup = [];

    foreach ($projects as $project) {
        $lookup[(string) $project['id']] = $project;
    }

    return $lookup;
}

function project_name(array $lookup, ?string $projectId): string
{
    if ($projectId === null || $projectId === '' || !isset($lookup[$projectId])) {
        return '通用事项';
    }

    return (string) $lookup[$projectId]['name'];
}

function project_status_label(string $status): string
{
    $labels = project_status_options();

    return $labels[$status] ?? '未知状态';
}

function task_status_label(string $status): string
{
    $labels = task_status_options();

    return $labels[$status] ?? '未知状态';
}

function priority_label(string $priority): string
{
    $labels = priority_options();

    return $labels[$priority] ?? '未设置';
}

function invoice_kind_label(string $kind): string
{
    $labels = invoice_kind_options();

    return $labels[$kind] ?? '未知';
}

function invoice_status_label(string $kind, string $status): string
{
    $labels = invoice_status_options($kind);

    return $labels[$status] ?? '未知状态';
}

function project_status_tone(string $status): string
{
    $mapping = [
        'planning' => 'neutral',
        'active' => 'info',
        'delivery' => 'warning',
        'paused' => 'danger',
        'done' => 'success',
    ];

    return $mapping[$status] ?? 'neutral';
}

function task_status_tone(string $status): string
{
    $mapping = [
        'todo' => 'neutral',
        'doing' => 'info',
        'review' => 'warning',
        'done' => 'success',
    ];

    return $mapping[$status] ?? 'neutral';
}

function priority_tone(string $priority): string
{
    $mapping = [
        'high' => 'danger',
        'medium' => 'warning',
        'low' => 'success',
    ];

    return $mapping[$priority] ?? 'neutral';
}

function invoice_status_tone(string $status, bool $overdue = false): string
{
    if ($overdue) {
        return 'danger';
    }

    $mapping = [
        'pending' => 'warning',
        'partial' => 'info',
        'paid' => 'success',
    ];

    return $mapping[$status] ?? 'neutral';
}

function percent(float $value, float $total, float $cap = 100.0): float
{
    if ($total <= 0.0) {
        return 0.0;
    }

    $ratio = ($value / $total) * 100;

    return min($cap, round($ratio, 1));
}

function display_date(string $date): string
{
    if ($date === '') {
        return '-';
    }

    $timestamp = strtotime($date);

    return $timestamp === false ? $date : date('Y-m-d', $timestamp);
}

function format_month_label(string $month): string
{
    $parts = explode('-', $month);

    if (count($parts) !== 2) {
        return $month;
    }

    return $parts[0] . '年' . ltrim($parts[1], '0') . '月';
}

function short_month_label(string $month): string
{
    $parts = explode('-', $month);

    if (count($parts) !== 2) {
        return $month;
    }

    return ltrim($parts[1], '0') . '月';
}

function is_overdue(string $date, string $status, array $completedStatuses = ['paid', 'done']): bool
{
    if ($date === '' || in_array($status, $completedStatuses, true)) {
        return false;
    }

    return $date < date('Y-m-d');
}

function trim_text(string $text, int $length = 120): string
{
    if (text_length($text) <= $length) {
        return $text;
    }

    return text_slice($text, 0, $length - 3) . '...';
}

function find_record_by_id(array $records, string $id): ?array
{
    foreach ($records as $record) {
        if ((string) ($record['id'] ?? '') === $id) {
            return $record;
        }
    }

    return null;
}

function find_record_index_by_id(array $records, string $id): ?int
{
    foreach ($records as $index => $record) {
        if ((string) ($record['id'] ?? '') === $id) {
            return $index;
        }
    }

    return null;
}
