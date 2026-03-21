<?php

declare(strict_types=1);

function chart_color(string $tone = 'primary'): string
{
    $mapping = [
        'primary' => '#2f6fed',
        'neutral' => '#94a3b8',
        'info' => '#409eff',
        'success' => '#16a34a',
        'warning' => '#f59e0b',
        'danger' => '#ef4444',
    ];

    return $mapping[$tone] ?? $mapping['primary'];
}

function render_stats_grid(array $items, string $class = ''): void
{
    $classes = trim('stats-grid ' . $class);
    ?>
    <section class="<?= h($classes); ?>">
        <?php foreach ($items as $item): ?>
            <article class="stat-card <?= isset($item['tone']) ? 'stat-card--' . h((string) $item['tone']) : ''; ?>">
                <span><?= h((string) ($item['label'] ?? '')); ?></span>
                <strong><?= h((string) ($item['value'] ?? '')); ?></strong>
                <p><?= h((string) ($item['hint'] ?? '')); ?></p>
            </article>
        <?php endforeach; ?>
    </section>
    <?php
}

function render_window_header(string $eyebrow, string $title, string $description = '', string $actions = ''): void
{
    ?>
    <div class="window__header">
        <div class="window__title">
            <p class="panel__eyebrow"><?= h($eyebrow); ?></p>
            <h3><?= h($title); ?></h3>
            <?php if ($description !== ''): ?>
                <p class="window__desc"><?= h($description); ?></p>
            <?php endif; ?>
        </div>
        <?php if ($actions !== ''): ?>
            <div class="window__actions"><?= $actions; ?></div>
        <?php endif; ?>
    </div>
    <?php
}

function render_chip(string $label, string $tone = 'neutral'): void
{
    ?>
    <span class="badge badge--<?= h($tone); ?>"><?= h($label); ?></span>
    <?php
}

function render_progress_bar(float $value, string $tone = 'primary'): void
{
    ?>
    <div class="progress progress--thin">
        <span class="progress__fill" style="width: <?= h((string) max(0.0, min(100.0, $value))); ?>%; background: <?= h(chart_color($tone)); ?>;"></span>
    </div>
    <?php
}

function render_trend_chart(array $rows, string $emptyText = '暂无趋势数据'): void
{
    if ($rows === []) {
        ?>
        <div class="empty-state"><?= h($emptyText); ?></div>
        <?php
        return;
    }

    $max = 0.0;
    foreach ($rows as $row) {
        $max = max($max, (float) $row['income'], (float) $row['expense']);
    }
    $max = max($max, 1.0);
    ?>
    <div class="trend-chart">
        <div class="trend-chart__legend">
            <span><i class="trend-chart__dot trend-chart__dot--income"></i>收入</span>
            <span><i class="trend-chart__dot trend-chart__dot--expense"></i>支出</span>
        </div>
        <div class="trend-chart__grid">
            <?php foreach ($rows as $row): ?>
                <div class="trend-chart__item">
                    <div class="trend-chart__bars">
                        <span class="trend-bar trend-bar--income" style="height: <?= h((string) percent((float) $row['income'], $max, 100.0)); ?>%;"></span>
                        <span class="trend-bar trend-bar--expense" style="height: <?= h((string) percent((float) $row['expense'], $max, 100.0)); ?>%;"></span>
                    </div>
                    <div class="trend-chart__month"><?= h(short_month_label((string) $row['month'])); ?></div>
                    <div class="trend-chart__net <?= (float) $row['net'] >= 0 ? 'is-positive' : 'is-negative'; ?>">
                        <?= h(((float) $row['net'] >= 0 ? '+' : '') . money((float) $row['net'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

function render_rank_list(array $items, string $emptyText = '暂无数据'): void
{
    if ($items === []) {
        ?>
        <div class="empty-state"><?= h($emptyText); ?></div>
        <?php
        return;
    }
    ?>
    <div class="rank-list">
        <?php foreach ($items as $item): ?>
            <div class="rank-item">
                <div class="rank-item__meta">
                    <div>
                        <strong><?= h((string) ($item['label'] ?? '')); ?></strong>
                        <?php if (($item['hint'] ?? '') !== ''): ?>
                            <p class="rank-item__hint"><?= h((string) $item['hint']); ?></p>
                        <?php endif; ?>
                    </div>
                    <span><?= h((string) ($item['value'] ?? '')); ?></span>
                </div>
                <?php if (isset($item['percent'])): ?>
                    <?php render_progress_bar((float) $item['percent'], (string) ($item['tone'] ?? 'primary')); ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}

function render_donut_chart(array $segments, string $centerValue, string $centerLabel, string $emptyText = '暂无数据'): void
{
    if ($segments === []) {
        ?>
        <div class="empty-state"><?= h($emptyText); ?></div>
        <?php
        return;
    }

    $total = 0.0;
    foreach ($segments as $segment) {
        $total += (float) ($segment['value'] ?? 0.0);
    }

    if ($total <= 0.0) {
        ?>
        <div class="empty-state"><?= h($emptyText); ?></div>
        <?php
        return;
    }

    $start = 0.0;
    $parts = [];

    foreach ($segments as $segment) {
        $value = (float) ($segment['value'] ?? 0.0);

        if ($value <= 0.0) {
            continue;
        }

        $end = $start + (($value / $total) * 360);
        $parts[] = chart_color((string) ($segment['tone'] ?? 'primary')) . ' ' . round($start, 1) . 'deg ' . round($end, 1) . 'deg';
        $start = $end;
    }

    if ($parts === []) {
        ?>
        <div class="empty-state"><?= h($emptyText); ?></div>
        <?php
        return;
    }
    ?>
    <div class="donut-card">
        <div class="donut-card__visual">
            <div class="donut" style="background: conic-gradient(<?= h(implode(', ', $parts)); ?>);">
                <div class="donut__center">
                    <strong><?= h($centerValue); ?></strong>
                    <span><?= h($centerLabel); ?></span>
                </div>
            </div>
        </div>
        <div class="donut-card__legend">
            <?php foreach ($segments as $segment): ?>
                <?php if ((float) ($segment['value'] ?? 0.0) <= 0.0) {
                    continue;
                } ?>
                <div class="donut-card__legend-item">
                    <i class="donut-card__swatch" style="background: <?= h(chart_color((string) ($segment['tone'] ?? 'primary'))); ?>;"></i>
                    <span><?= h((string) ($segment['label'] ?? '')); ?></span>
                    <strong><?= h((string) ($segment['display'] ?? money((float) $segment['value']))); ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

function render_alert_list(array $alerts): void
{
    if ($alerts === []) {
        ?>
        <div class="empty-state">暂无提醒</div>
        <?php
        return;
    }
    ?>
    <div class="insight-list">
        <?php foreach ($alerts as $alert): ?>
            <div class="insight-list__item">
                <span class="insight-list__icon">!</span>
                <p><?= h((string) $alert); ?></p>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}
