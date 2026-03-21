<?php

$projectTab = input_string($_GET, 'tab', 'projects');
if (!in_array($projectTab, ['projects', 'tasks'], true)) {
    $projectTab = 'projects';
}

$keyword = input_string($_GET, 'keyword');
$statusFilter = input_string($_GET, 'status');
$priorityFilter = input_string($_GET, 'priority');
$ownerFilter = input_string($_GET, 'owner');
$assigneeFilter = input_string($_GET, 'assignee');
$projectFilter = input_string($_GET, 'project_id');
$overdueOnly = input_bool($_GET, 'overdue');
$editProjectId = input_string($_GET, 'edit_project_id');
$editTaskId = input_string($_GET, 'edit_task_id');

$editingProject = $editProjectId === '' ? null : find_record_by_id($data['projects'], $editProjectId);
$editingTask = $editTaskId === '' ? null : find_record_by_id($data['tasks'], $editTaskId);

$totalBudget = 0.0;
$totalSpent = 0.0;
$activeProjectCount = 0;
foreach ($projectRows as $project) {
    $totalBudget += (float) $project['budget'];
    $totalSpent += (float) $project['spent'];

    if (in_array((string) $project['status'], ['planning', 'active', 'delivery'], true)) {
        $activeProjectCount++;
    }
}

$openTaskCount = 0;
$overdueTaskCount = 0;
foreach ($taskRows as $task) {
    if ((string) $task['status'] !== 'done') {
        $openTaskCount++;
    }

    if ((bool) $task['overdue']) {
        $overdueTaskCount++;
    }
}

$filteredProjects = [];
$filteredTasks = [];
$projectStats = [];
$projectRiskItems = [];
$assigneeItems = [];
$taskSegments = [];

$filteredProjects = array_values(array_filter(
    $projectRows,
    static function (array $row) use ($keyword, $statusFilter, $priorityFilter, $ownerFilter): bool {
        if ($statusFilter !== '' && (string) $row['status'] !== $statusFilter) {
            return false;
        }
        if ($priorityFilter !== '' && (string) $row['priority'] !== $priorityFilter) {
            return false;
        }
        if ($ownerFilter !== '' && !text_contains_ci((string) $row['owner'], $ownerFilter)) {
            return false;
        }
        if ($keyword === '') {
            return true;
        }

        $haystack = implode(' ', [(string) $row['name'], (string) $row['client'], (string) $row['description']]);
        return text_contains_ci($haystack, $keyword);
    }
));

$filteredTasks = array_values(array_filter(
    $taskRows,
    static function (array $row) use ($keyword, $statusFilter, $priorityFilter, $assigneeFilter, $projectFilter, $overdueOnly): bool {
        if ($statusFilter !== '' && (string) $row['status'] !== $statusFilter) {
            return false;
        }
        if ($priorityFilter !== '' && (string) $row['priority'] !== $priorityFilter) {
            return false;
        }
        if ($assigneeFilter !== '' && !text_contains_ci((string) $row['assignee'], $assigneeFilter)) {
            return false;
        }
        if ($projectFilter !== '' && (string) $row['project_id'] !== $projectFilter) {
            return false;
        }
        if ($overdueOnly && !(bool) $row['overdue']) {
            return false;
        }
        if ($keyword === '') {
            return true;
        }

        $haystack = implode(' ', [(string) $row['title'], (string) $row['project_name']]);
        return text_contains_ci($haystack, $keyword);
    }
));

$projectStats = [
    ['label' => '项目总数', 'value' => (string) count($projectRows) . ' 个', 'hint' => '活跃项目 ' . $activeProjectCount . ' 个', 'tone' => 'info'],
    ['label' => '预算总额', 'value' => money($totalBudget), 'hint' => '项目组合预算', 'tone' => 'neutral'],
    ['label' => '已发生成本', 'value' => money($totalSpent), 'hint' => '来自关联项目流水', 'tone' => 'warning'],
    ['label' => '未完成任务', 'value' => (string) $openTaskCount . ' 项', 'hint' => '逾期任务 ' . $overdueTaskCount . ' 项', 'tone' => $overdueTaskCount > 0 ? 'danger' : 'success'],
];

$projectRiskMax = max(array_map(static fn(array $row): float => max((float) $row['budget_usage'], (float) $row['progress']), $projectHealthRows) ?: [1.0]);
$projectRiskItems = array_map(static function (array $project) use ($projectRiskMax): array {
    $riskTone = (int) $project['risk_score'] >= 4 ? 'danger' : ((int) $project['risk_score'] >= 2 ? 'warning' : 'success');
    $hint = '预算使用率 ' . number_format((float) $project['budget_usage'], 1) . '% · 进度 ' . $project['progress'] . '%';
    if ((int) $project['task_overdue'] > 0) {
        $hint .= ' · 逾期任务 ' . $project['task_overdue'] . ' 项';
    }

    return [
        'label' => (string) $project['name'],
        'value' => project_status_label((string) $project['status']),
        'hint' => $hint,
        'percent' => percent(max((float) $project['budget_usage'], (float) $project['progress']), $projectRiskMax, 100.0),
        'tone' => $riskTone,
    ];
}, $projectHealthRows);

$assigneeMax = max(array_map(static fn(array $row): float => (float) $row['open_tasks'], $assigneeLoadRows) ?: [1.0]);
$assigneeItems = array_map(static function (array $row) use ($assigneeMax): array {
    $hint = '预估 ' . number_format((float) $row['estimate_hours'], 1) . 'h';
    if ((int) $row['overdue_tasks'] > 0) {
        $hint .= ' · 逾期 ' . $row['overdue_tasks'] . ' 项';
    }

    return [
        'label' => (string) $row['assignee'],
        'value' => (string) $row['open_tasks'] . ' 项',
        'hint' => $hint,
        'percent' => percent((float) $row['open_tasks'], $assigneeMax, 100.0),
        'tone' => (int) $row['overdue_tasks'] > 0 ? 'danger' : 'info',
    ];
}, $assigneeLoadRows);

$taskSegments = array_map(static function (array $row): array {
    return [
        'label' => (string) $row['label'],
        'value' => (float) $row['count'],
        'display' => (string) $row['count'] . ' 项',
        'tone' => (string) $row['tone'],
    ];
}, $taskSummary);
?>
<?php render_stats_grid($projectStats, 'stats-grid--four'); ?>

<section class="panel">
    <div class="table-panel__header">
        <div class="table-panel__main">
            <div class="tabbar">
                <a class="tabbar__item <?= $projectTab === 'projects' ? 'is-active' : ''; ?>" href="index.php?page=projects&tab=projects">项目台账</a>
                <a class="tabbar__item <?= $projectTab === 'tasks' ? 'is-active' : ''; ?>" href="index.php?page=projects&tab=tasks">任务台账</a>
            </div>
            <p class="table-panel__desc"><?= $projectTab === 'projects' ? '从项目视角看预算、成本、进度和交付风险。' : '从任务视角看负责人、优先级、工时和逾期情况。'; ?></p>
        </div>

        <div class="toolbar__group toolbar__group--wrap">
            <a class="button button--primary" href="#" data-modal-open="project-modal">新增项目</a>
            <a class="button button--default" href="#" data-modal-open="task-modal">新增任务</a>
        </div>
    </div>

    <div class="quick-links quick-links--dense">
        <a class="quick-link" href="index.php?page=projects&tab=projects&status=active">
            <strong>进行中项目</strong>
            <span>聚焦当前执行项目</span>
        </a>
        <a class="quick-link" href="index.php?page=projects&tab=projects&status=delivery">
            <strong>交付中项目</strong>
            <span>盯交付节点</span>
        </a>
        <a class="quick-link" href="index.php?page=projects&tab=tasks&status=doing">
            <strong>执行中任务</strong>
            <span>看当前推进中的事项</span>
        </a>
        <a class="quick-link" href="index.php?page=projects&tab=tasks&priority=high">
            <strong>高优先级任务</strong>
            <span>优先处理重要事项</span>
        </a>
        <a class="quick-link" href="index.php?page=projects&tab=tasks&overdue=1">
            <strong>逾期任务</strong>
            <span>先清理交付风险</span>
        </a>
    </div>

    <div class="report-grid">
        <section class="mini-panel">
            <div class="mini-panel__header">
                <h4>项目风险排行</h4>
                <p>综合预算使用率、进度和逾期任务。</p>
            </div>
            <?php render_rank_list($projectRiskItems); ?>
        </section>

        <section class="mini-panel">
            <div class="mini-panel__header">
                <h4>任务状态分布</h4>
                <p>更直观看执行堆积在哪里。</p>
            </div>
            <?php render_donut_chart($taskSegments, (string) count($taskRows) . ' 项', '任务总数'); ?>
        </section>

        <section class="mini-panel">
            <div class="mini-panel__header">
                <h4>负责人负荷</h4>
                <p>看谁任务多、谁在背逾期。</p>
            </div>
            <?php render_rank_list($assigneeItems); ?>
        </section>
    </div>
</section>

<section class="panel">
    <form class="filter-bar" method="get" action="index.php">
        <input type="hidden" name="page" value="projects">
        <input type="hidden" name="tab" value="<?= h($projectTab); ?>">

        <label class="filter-field filter-field--keyword">
            <span>关键词</span>
            <input type="text" name="keyword" value="<?= h($keyword); ?>" placeholder="<?= h($projectTab === 'projects' ? '项目名称、客户、说明' : '任务标题、项目名'); ?>">
        </label>

        <label class="filter-field">
            <span>状态</span>
            <select name="status">
                <option value="">全部</option>
                <?php foreach (($projectTab === 'projects' ? project_status_options() : task_status_options()) as $value => $label): ?>
                    <option value="<?= h($value); ?>" <?= selected_if($statusFilter, $value); ?>><?= h($label); ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label class="filter-field">
            <span>优先级</span>
            <select name="priority">
                <option value="">全部</option>
                <?php foreach (priority_options() as $value => $label): ?>
                    <option value="<?= h($value); ?>" <?= selected_if($priorityFilter, $value); ?>><?= h($label); ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <?php if ($projectTab === 'projects'): ?>
            <label class="filter-field">
                <span>负责人</span>
                <input type="text" name="owner" value="<?= h($ownerFilter); ?>" placeholder="项目 owner">
            </label>
        <?php else: ?>
            <label class="filter-field">
                <span>负责人</span>
                <input type="text" name="assignee" value="<?= h($assigneeFilter); ?>" placeholder="执行人姓名">
            </label>

            <label class="filter-field">
                <span>项目</span>
                <select name="project_id">
                    <option value="">全部项目</option>
                    <?php foreach ($data['projects'] as $project): ?>
                        <option value="<?= h((string) $project['id']); ?>" <?= selected_if($projectFilter, (string) $project['id']); ?>><?= h((string) $project['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="filter-field filter-field--checkbox">
                <span>视角</span>
                <label class="checkline">
                    <input type="checkbox" name="overdue" value="1" <?= checked_if($overdueOnly); ?>>
                    <span>只看逾期</span>
                </label>
            </label>
        <?php endif; ?>

        <div class="filter-actions">
            <button class="button button--primary" type="submit">筛选</button>
            <a class="button button--default" href="index.php?page=projects&tab=<?= h($projectTab); ?>">重置</a>
        </div>
    </form>

    <div class="table-toolbar">
        <div class="table-toolbar__title">
            当前共 <?= h((string) ($projectTab === 'projects' ? count($filteredProjects) : count($filteredTasks))); ?> 条记录
            <?php if ($projectTab === 'tasks' && $overdueOnly): ?>
                <span class="toolbar-tag">逾期视角</span>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($projectTab === 'projects'): ?>
        <div class="table-shell">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>项目</th>
                        <th>客户 / 业务线</th>
                        <th>负责人</th>
                        <th>状态</th>
                        <th>优先级</th>
                        <th>预算 / 成本</th>
                        <th>进度</th>
                        <th>截止日</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filteredProjects as $project): ?>
                        <tr>
                            <td data-label="项目">
                                <strong><?= h((string) $project['name']); ?></strong>
                                <div class="table-subtext"><?= h((string) ($project['description'] === '' ? '-' : trim_text((string) $project['description'], 48))); ?></div>
                            </td>
                            <td data-label="客户 / 业务线"><?= h((string) $project['client']); ?></td>
                            <td data-label="负责人"><?= h((string) $project['owner']); ?></td>
                            <td data-label="状态">
                                <span class="badge badge--<?= h(project_status_tone((string) $project['status'])); ?>">
                                    <?= h(project_status_label((string) $project['status'])); ?>
                                </span>
                            </td>
                            <td data-label="优先级">
                                <span class="badge badge--<?= h(priority_tone((string) $project['priority'])); ?>">
                                    <?= h(priority_label((string) $project['priority'])); ?>
                                </span>
                            </td>
                            <td data-label="预算 / 成本"><?= money((float) $project['budget']); ?> / <?= money((float) $project['spent']); ?></td>
                            <td data-label="进度">
                                <div class="progress-cell">
                                    <?php render_progress_bar((float) $project['progress'], 'info'); ?>
                                    <span><?= h((string) $project['progress']); ?>%</span>
                                </div>
                            </td>
                            <td data-label="截止日"><?= h(display_date((string) $project['due_date'])); ?></td>
                            <td data-label="操作">
                                <div class="record-actions record-actions--stack">
                                    <form class="inline-form" method="post" action="index.php?page=projects">
                                        <input type="hidden" name="action" value="update_project_status">
                                        <input type="hidden" name="return_tab" value="projects">
                                        <input type="hidden" name="project_id" value="<?= h((string) $project['id']); ?>">
                                        <select name="status">
                                            <?php foreach (project_status_options() as $value => $label): ?>
                                                <option value="<?= h($value); ?>" <?= selected_if((string) $project['status'], $value); ?>><?= h($label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="button button--default button--small" type="submit">更新状态</button>
                                    </form>
                                    <div class="record-actions">
                                        <a class="button button--default button--small" href="index.php?page=projects&tab=projects&edit_project_id=<?= h((string) $project['id']); ?>">编辑</a>
                                        <form method="post" action="index.php?page=projects" onsubmit="return confirm('确认删除这个项目吗？如果仍有关联任务或财务记录，系统会阻止删除。');">
                                            <input type="hidden" name="action" value="delete_project">
                                            <input type="hidden" name="return_tab" value="projects">
                                            <input type="hidden" name="project_id" value="<?= h((string) $project['id']); ?>">
                                            <button class="button button--danger button--small" type="submit">删除</button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (count($filteredProjects) === 0): ?>
                        <tr>
                            <td colspan="9">
                                <div class="empty-state">没有符合条件的项目记录。</div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="table-shell">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>任务</th>
                        <th>项目</th>
                        <th>负责人</th>
                        <th>优先级</th>
                        <th>截止日</th>
                        <th>工时</th>
                        <th>状态</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filteredTasks as $task): ?>
                        <tr>
                            <td data-label="任务"><?= h((string) $task['title']); ?></td>
                            <td data-label="项目"><?= h((string) $task['project_name']); ?></td>
                            <td data-label="负责人"><?= h((string) $task['assignee']); ?></td>
                            <td data-label="优先级">
                                <span class="badge badge--<?= h(priority_tone((string) $task['priority'])); ?>">
                                    <?= h(priority_label((string) $task['priority'])); ?>
                                </span>
                            </td>
                            <td data-label="截止日"><?= h(display_date((string) $task['due_date'])); ?></td>
                            <td data-label="工时"><?= h((string) $task['actual_hours']); ?> / <?= h((string) $task['estimate_hours']); ?>h</td>
                            <td data-label="状态">
                                <span class="badge badge--<?= h($task['overdue'] ? 'danger' : task_status_tone((string) $task['status'])); ?>">
                                    <?= h($task['overdue'] ? '已逾期' : task_status_label((string) $task['status'])); ?>
                                </span>
                            </td>
                            <td data-label="操作">
                                <div class="record-actions record-actions--stack">
                                    <form class="inline-form" method="post" action="index.php?page=projects">
                                        <input type="hidden" name="action" value="update_task_status">
                                        <input type="hidden" name="return_tab" value="tasks">
                                        <input type="hidden" name="task_id" value="<?= h((string) $task['id']); ?>">
                                        <select name="status">
                                            <?php foreach (task_status_options() as $value => $label): ?>
                                                <option value="<?= h($value); ?>" <?= selected_if((string) $task['status'], $value); ?>><?= h($label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="button button--default button--small" type="submit">更新状态</button>
                                    </form>
                                    <div class="record-actions">
                                        <a class="button button--default button--small" href="index.php?page=projects&tab=tasks&edit_task_id=<?= h((string) $task['id']); ?>">编辑</a>
                                        <form method="post" action="index.php?page=projects" onsubmit="return confirm('确认删除这条任务吗？');">
                                            <input type="hidden" name="action" value="delete_task">
                                            <input type="hidden" name="return_tab" value="tasks">
                                            <input type="hidden" name="task_id" value="<?= h((string) $task['id']); ?>">
                                            <button class="button button--danger button--small" type="submit">删除</button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (count($filteredTasks) === 0): ?>
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">没有符合条件的任务记录。</div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<div class="modal" id="project-modal" data-modal>
    <div class="modal__dialog">
        <div class="modal__header">
            <div>
                <h3>新增项目</h3>
                <p>用弹窗快速建项目，不拆成单独页面。</p>
            </div>
            <button class="modal__close" type="button" data-modal-close>&times;</button>
        </div>

        <form class="form-grid" method="post" action="index.php?page=projects">
            <input type="hidden" name="action" value="add_project">
            <input type="hidden" name="return_tab" value="projects">
            <label class="form-grid__wide">
                <span>项目名称</span>
                <input type="text" name="name" placeholder="如：企业官网重构" required>
            </label>
            <label>
                <span>客户 / 业务线</span>
                <input type="text" name="client" placeholder="外部客户或内部项目">
            </label>
            <label>
                <span>负责人</span>
                <input type="text" name="owner" placeholder="项目 owner" required>
            </label>
            <label>
                <span>状态</span>
                <select name="status" required>
                    <?php foreach (project_status_options() as $value => $label): ?>
                        <option value="<?= h($value); ?>"><?= h($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>优先级</span>
                <select name="priority" required>
                    <?php foreach (priority_options() as $value => $label): ?>
                        <option value="<?= h($value); ?>"><?= h($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>预算</span>
                <input type="number" name="budget" min="0" step="0.01" placeholder="0.00" required>
            </label>
            <label>
                <span>开始日期</span>
                <input type="date" name="start_date" required>
            </label>
            <label>
                <span>截止日期</span>
                <input type="date" name="due_date" required>
            </label>
            <label class="form-grid__wide">
                <span>项目说明</span>
                <textarea name="description" rows="3" placeholder="记录交付目标、范围和关键节点"></textarea>
            </label>

            <div class="modal__footer">
                <button class="button button--default" type="button" data-modal-close>取消</button>
                <button class="button button--primary" type="submit">创建项目</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="task-modal" data-modal>
    <div class="modal__dialog">
        <div class="modal__header">
            <div>
                <h3>新增任务</h3>
                <p>建完任务后继续在表格里更新状态和优先级。</p>
            </div>
            <button class="modal__close" type="button" data-modal-close>&times;</button>
        </div>

        <form class="form-grid" method="post" action="index.php?page=projects">
            <input type="hidden" name="action" value="add_task">
            <input type="hidden" name="return_tab" value="tasks">
            <label class="form-grid__wide">
                <span>所属项目</span>
                <select name="project_id" required>
                    <?php foreach ($data['projects'] as $project): ?>
                        <option value="<?= h((string) $project['id']); ?>"><?= h((string) $project['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="form-grid__wide">
                <span>任务标题</span>
                <input type="text" name="title" placeholder="如：权限模型设计" required>
            </label>
            <label>
                <span>负责人</span>
                <input type="text" name="assignee" placeholder="执行人姓名" required>
            </label>
            <label>
                <span>状态</span>
                <select name="status" required>
                    <?php foreach (task_status_options() as $value => $label): ?>
                        <option value="<?= h($value); ?>"><?= h($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>优先级</span>
                <select name="priority" required>
                    <?php foreach (priority_options() as $value => $label): ?>
                        <option value="<?= h($value); ?>"><?= h($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>预估工时</span>
                <input type="number" name="estimate_hours" min="0" step="0.5" placeholder="0">
            </label>
            <label class="form-grid__wide">
                <span>截止日期</span>
                <input type="date" name="due_date" required>
            </label>

            <div class="modal__footer">
                <button class="button button--default" type="button" data-modal-close>取消</button>
                <button class="button button--primary" type="submit">添加任务</button>
            </div>
        </form>
    </div>
</div>

<?php if ($editingProject !== null): ?>
    <div class="modal is-open">
        <div class="modal__dialog">
            <div class="modal__header">
                <div>
                    <h3>编辑项目</h3>
                    <p>可以直接改负责人、预算、日期、优先级和说明。</p>
                </div>
                <a class="modal__close" href="index.php?page=projects&tab=projects">&times;</a>
            </div>

            <form class="form-grid" method="post" action="index.php?page=projects">
                <input type="hidden" name="action" value="update_project">
                <input type="hidden" name="return_tab" value="projects">
                <input type="hidden" name="project_id" value="<?= h((string) $editingProject['id']); ?>">
                <label class="form-grid__wide">
                    <span>项目名称</span>
                    <input type="text" name="name" value="<?= h((string) $editingProject['name']); ?>" required>
                </label>
                <label>
                    <span>客户 / 业务线</span>
                    <input type="text" name="client" value="<?= h((string) $editingProject['client']); ?>">
                </label>
                <label>
                    <span>负责人</span>
                    <input type="text" name="owner" value="<?= h((string) $editingProject['owner']); ?>" required>
                </label>
                <label>
                    <span>状态</span>
                    <select name="status" required>
                        <?php foreach (project_status_options() as $value => $label): ?>
                            <option value="<?= h($value); ?>" <?= selected_if((string) $editingProject['status'], $value); ?>><?= h($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>优先级</span>
                    <select name="priority" required>
                        <?php foreach (priority_options() as $value => $label): ?>
                            <option value="<?= h($value); ?>" <?= selected_if((string) $editingProject['priority'], $value); ?>><?= h($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>预算</span>
                    <input type="number" name="budget" min="0" step="0.01" value="<?= h((string) $editingProject['budget']); ?>" required>
                </label>
                <label>
                    <span>开始日期</span>
                    <input type="date" name="start_date" value="<?= h((string) $editingProject['start_date']); ?>" required>
                </label>
                <label>
                    <span>截止日期</span>
                    <input type="date" name="due_date" value="<?= h((string) $editingProject['due_date']); ?>" required>
                </label>
                <label class="form-grid__wide">
                    <span>项目说明</span>
                    <textarea name="description" rows="3"><?= h((string) $editingProject['description']); ?></textarea>
                </label>

                <div class="modal__footer">
                    <a class="button button--default" href="index.php?page=projects&tab=projects">取消</a>
                    <button class="button button--primary" type="submit">保存修改</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($editingTask !== null): ?>
    <div class="modal is-open">
        <div class="modal__dialog">
            <div class="modal__header">
                <div>
                    <h3>编辑任务</h3>
                    <p>可以改负责人、工时、状态、优先级和所属项目。</p>
                </div>
                <a class="modal__close" href="index.php?page=projects&tab=tasks">&times;</a>
            </div>

            <form class="form-grid" method="post" action="index.php?page=projects">
                <input type="hidden" name="action" value="update_task">
                <input type="hidden" name="return_tab" value="tasks">
                <input type="hidden" name="task_id" value="<?= h((string) $editingTask['id']); ?>">
                <label class="form-grid__wide">
                    <span>所属项目</span>
                    <select name="project_id" required>
                        <?php foreach ($data['projects'] as $project): ?>
                            <option value="<?= h((string) $project['id']); ?>" <?= selected_if((string) $editingTask['project_id'], (string) $project['id']); ?>><?= h((string) $project['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="form-grid__wide">
                    <span>任务标题</span>
                    <input type="text" name="title" value="<?= h((string) $editingTask['title']); ?>" required>
                </label>
                <label>
                    <span>负责人</span>
                    <input type="text" name="assignee" value="<?= h((string) $editingTask['assignee']); ?>" required>
                </label>
                <label>
                    <span>状态</span>
                    <select name="status" required>
                        <?php foreach (task_status_options() as $value => $label): ?>
                            <option value="<?= h($value); ?>" <?= selected_if((string) $editingTask['status'], $value); ?>><?= h($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>优先级</span>
                    <select name="priority" required>
                        <?php foreach (priority_options() as $value => $label): ?>
                            <option value="<?= h($value); ?>" <?= selected_if((string) $editingTask['priority'], $value); ?>><?= h($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>预估工时</span>
                    <input type="number" name="estimate_hours" min="0" step="0.5" value="<?= h((string) $editingTask['estimate_hours']); ?>">
                </label>
                <label>
                    <span>实际工时</span>
                    <input type="number" name="actual_hours" min="0" step="0.5" value="<?= h((string) $editingTask['actual_hours']); ?>">
                </label>
                <label class="form-grid__wide">
                    <span>截止日期</span>
                    <input type="date" name="due_date" value="<?= h((string) $editingTask['due_date']); ?>" required>
                </label>

                <div class="modal__footer">
                    <a class="button button--default" href="index.php?page=projects&tab=tasks">取消</a>
                    <button class="button button--primary" type="submit">保存修改</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
