<?php

namespace app\admin\controller\app;

use think\Db;

/**
 * 项目运营工作台
 *
 * @icon fa fa-mobile
 */
class Workbench extends Base
{
    public function index()
    {
        $profile = $this->getCurrentUserProfile();

        $this->view->assign([
            'currentUser' => $this->buildCurrentUser($profile),
            'summaryCards' => $this->buildSummaryCards($profile),
            'actionCards' => $this->buildActionCards($profile),
            'moduleLinks' => $this->buildModuleLinks(),
            'todoItems' => $this->buildTodoItems($profile),
            'projectPulses' => $this->buildProjectPulses($profile),
            'milestoneItems' => $this->buildMilestoneItems($profile),
            'riskItems' => $this->buildRiskItems($profile),
            'reportItems' => $this->buildReportItems($profile),
            'materials' => $this->buildMaterials($profile),
            'usageTips' => $this->buildUsageTips($profile),
        ]);

        return $this->view->fetch();
    }

    protected function getCurrentUserProfile(): array
    {
        $adminId = (int) ($this->auth->id ?? 0);
        $info = $this->auth->getUserInfo($adminId);
        $profile = [];

        if ($this->tableExists('staff_profile')) {
            $profile = (array) Db::name('staff_profile')
                ->field('admin_id,account,name,title,department,role_key')
                ->where('admin_id', $adminId)
                ->find();
        }

        return [
            'admin_id' => $adminId,
            'name' => $profile['name'] ?? ($info['nickname'] ?? ($info['username'] ?? '系统用户')),
            'title' => $profile['title'] ?? '项目运营岗位',
            'department' => $profile['department'] ?? '运营部',
            'role_key' => $profile['role_key'] ?? 'operations',
        ];
    }

    protected function buildCurrentUser(array $profile): array
    {
        return [
            'name' => $profile['name'],
            'title' => $profile['title'],
            'department' => $profile['department'],
            'guide' => $this->buildRoleGuide((string) $profile['role_key']),
        ];
    }

    protected function buildRoleGuide(string $roleKey): string
    {
        $map = [
            'operations' => '先处理当前项目问题，再联动研发，最后盯版本发布和资料沉淀。',
            'service' => '先处理客户反馈，再补充跟进记录，需要研发时直接提交联动单。',
            'tech' => '优先看研发待办和待发布版本，再回看项目反馈闭环。',
            'admin' => '先看积压问题和待发布版本，再检查整体项目节奏。',
            'viewer' => '先看待办，再进入对应页面处理，不需要记住太多入口。',
        ];

        return $map[$roleKey] ?? '先看待办，再进入对应页面处理。';
    }

    protected function buildSummaryCards(array $profile): array
    {
        $adminId = (int) ($profile['admin_id'] ?? 0);
        $roleKey = (string) ($profile['role_key'] ?? 'operations');
        $projectIds = $this->resolveRelevantProjectIds($profile);

        return [
            [
                'title' => '待跟进问题',
                'value' => $this->safeCount('app_issue', function ($query) use ($adminId, $roleKey) {
                    $query->where('status', 'in', ['new', 'processing', 'waiting_customer', 'escalated']);
                    if ($adminId > 0 && !in_array($roleKey, ['admin', 'viewer'], true)) {
                        $query->where('assignee_admin_id', $adminId);
                    }
                }),
                'hint' => '先处理当前卡住客户或内部推进的问题',
                'url' => (string) url('app/issue/index', ['assignee_admin_id' => $adminId]),
            ],
            [
                'title' => '待研发处理',
                'value' => $this->safeCount('app_tech_ticket', function ($query) use ($adminId, $roleKey) {
                    $query->where('status', 'in', ['pending', 'processing', 'testing', 'ready']);
                    if ($adminId > 0 && !in_array($roleKey, ['admin', 'viewer'], true)) {
                        $query->where('owner_admin_id', $adminId);
                    }
                }),
                'hint' => '包含处理中、待测试和待发布的研发联动',
                'url' => (string) url('app/tech_ticket/index'),
            ],
            [
                'title' => '关键里程碑',
                'value' => $this->countMilestoneSummary($projectIds),
                'hint' => '近期到期、进行中或阻塞的项目节点',
                'url' => (string) url('app/milestone/index'),
            ],
            [
                'title' => '未关闭风险',
                'value' => $this->countRiskSummary($projectIds),
                'hint' => '需要盯进度的风险、变更和依赖项',
                'url' => (string) url('app/risk/index'),
            ],
        ];
    }

    protected function buildActionCards(array $profile): array
    {
        $roleKey = (string) ($profile['role_key'] ?? 'operations');

        return [
            [
                'title' => '记录问题',
                'desc' => '客服、运营、客户或内部反馈都先落到问题记录。',
                'icon' => 'fa fa-bug',
                'url' => (string) url('app/issue/add'),
                'btn' => '马上登记',
            ],
            [
                'title' => '提交研发',
                'desc' => 'Bug、优化、升级需求统一转成研发联动单。',
                'icon' => 'fa fa-code-fork',
                'url' => (string) url('app/tech_ticket/add'),
                'btn' => $roleKey === 'tech' ? '记录处理' : '发给研发',
            ],
            [
                'title' => '安排版本',
                'desc' => '把测试、发布时间和客户同步放在一个版本单里。',
                'icon' => 'fa fa-rocket',
                'url' => (string) url('app/release/add'),
                'btn' => '新增版本',
            ],
            [
                'title' => '更新里程碑',
                'desc' => '项目节点、交付物和负责人统一在里程碑里维护。',
                'icon' => 'fa fa-flag-checkered',
                'url' => (string) url('app/milestone/add'),
                'btn' => '登记节点',
            ],
            [
                'title' => '提交汇报',
                'desc' => '把阶段结果、下步动作和阻塞事项沉淀成标准汇报。',
                'icon' => 'fa fa-file-text-o',
                'url' => (string) url('app/report/add'),
                'btn' => '新增汇报',
            ],
            [
                'title' => '问 AI',
                'desc' => '让 AI 直接帮你整理今日待办、客户回复或联动建议。',
                'icon' => 'fa fa-comments-o',
                'url' => (string) url('ai/conversation/index', ['focus' => 'app']),
                'btn' => '打开 AI',
            ],
        ];
    }

    protected function buildModuleLinks(): array
    {
        return [
            [
                'title' => '项目列表',
                'desc' => '查看所有运营项目，区分 APP、小程序、官网或活动项目。',
                'url' => (string) url('app/project/index'),
                'btn' => '打开项目',
                'icon' => 'fa fa-sitemap',
            ],
            [
                'title' => '里程碑',
                'desc' => '跟踪交付节点、负责人、进度和交付物。',
                'url' => (string) url('app/milestone/index'),
                'btn' => '查看节点',
                'icon' => 'fa fa-flag-checkered',
            ],
            [
                'title' => '风险与变更',
                'desc' => '统一记录项目风险、临时需求和外部依赖。',
                'url' => (string) url('app/risk/index'),
                'btn' => '查看风险',
                'icon' => 'fa fa-exclamation-triangle',
            ],
            [
                'title' => '项目汇报',
                'desc' => '汇总阶段结果、阻塞事项和下步动作。',
                'url' => (string) url('app/report/index'),
                'btn' => '查看汇报',
                'icon' => 'fa fa-file-text-o',
            ],
        ];
    }

    protected function buildTodoItems(array $profile): array
    {
        $items = array_merge(
            $this->buildIssueTodoItems($profile),
            $this->buildTechTodoItems($profile),
            $this->buildReleaseTodoItems(),
            $this->buildMilestoneTodoItems($profile),
            $this->buildRiskTodoItems($profile)
        );

        usort($items, function ($left, $right) {
            if ($left['order_rank'] !== $right['order_rank']) {
                return $left['order_rank'] <=> $right['order_rank'];
            }

            if ($left['time_sort'] !== $right['time_sort']) {
                return $left['time_sort'] <=> $right['time_sort'];
            }

            return strcmp((string) $left['title'], (string) $right['title']);
        });

        return array_slice($items, 0, 12);
    }

    protected function buildIssueTodoItems(array $profile): array
    {
        if (!$this->tableExists('app_issue')) {
            return [];
        }

        $adminId = (int) ($profile['admin_id'] ?? 0);
        $roleKey = (string) ($profile['role_key'] ?? 'operations');
        $query = Db::name('app_issue')
            ->field('id,ticket_no,title,customer,status,priority,last_follow_up_at,resolve_due_at')
            ->where('status', 'in', ['new', 'processing', 'waiting_customer', 'escalated'])
            ->orderRaw("FIELD(status, 'escalated', 'waiting_customer', 'new', 'processing')")
            ->order('resolve_due_at', 'asc')
            ->limit(6);

        if ($adminId > 0 && !in_array($roleKey, ['admin', 'viewer'], true)) {
            $query->where('assignee_admin_id', $adminId);
        }

        $rows = $query->select();
        $statusMap = [
            'new' => '新问题',
            'processing' => '处理中',
            'waiting_customer' => '待客户回复',
            'escalated' => '已升级',
        ];
        $rankMap = [
            'escalated' => 10,
            'waiting_customer' => 20,
            'new' => 30,
            'processing' => 40,
        ];

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'type' => '问题',
                'type_class' => 'issue',
                'title' => (string) $row['title'],
                'subtitle' => trim(((string) $row['ticket_no']) . ' / ' . ((string) ($row['customer'] ?: '未填写客户')), ' /'),
                'status_text' => $statusMap[$row['status']] ?? (string) $row['status'],
                'time_text' => (string) ($row['resolve_due_at'] ?: $row['last_follow_up_at'] ?: '-'),
                'action_label' => '去处理',
                'action_url' => (string) url('app/issue/edit', ['ids' => $row['id']]),
                'order_rank' => $rankMap[$row['status']] ?? 90,
                'time_sort' => $this->normalizeTimeSort((string) ($row['resolve_due_at'] ?: $row['last_follow_up_at'] ?: '')),
            ];
        }

        return $items;
    }

    protected function buildTechTodoItems(array $profile): array
    {
        if (!$this->tableExists('app_tech_ticket')) {
            return [];
        }

        $adminId = (int) ($profile['admin_id'] ?? 0);
        $roleKey = (string) ($profile['role_key'] ?? 'operations');
        $query = Db::name('app_tech_ticket')
            ->field('id,title,type,status,priority,owner,due_date')
            ->where('status', 'in', ['pending', 'processing', 'testing', 'ready'])
            ->orderRaw("FIELD(status, 'ready', 'testing', 'processing', 'pending')")
            ->orderRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")
            ->order('due_date', 'asc')
            ->limit(6);

        if ($adminId > 0 && !in_array($roleKey, ['admin', 'viewer'], true)) {
            $query->where('owner_admin_id', $adminId);
        }

        $rows = $query->select();
        $typeMap = [
            'bug' => 'Bug',
            'improvement' => '优化',
            'upgrade' => '升级',
            'task' => '任务',
        ];
        $statusMap = [
            'pending' => '待处理',
            'processing' => '处理中',
            'testing' => '待测试',
            'ready' => '待发布',
        ];
        $rankMap = [
            'ready' => 15,
            'testing' => 25,
            'processing' => 35,
            'pending' => 45,
        ];

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'type' => '研发',
                'type_class' => 'tech',
                'title' => (string) $row['title'],
                'subtitle' => trim(($typeMap[$row['type']] ?? (string) $row['type']) . ' / ' . ((string) ($row['owner'] ?: '未分配')), ' /'),
                'status_text' => $statusMap[$row['status']] ?? (string) $row['status'],
                'time_text' => (string) ($row['due_date'] ?: '-'),
                'action_label' => '看联动单',
                'action_url' => (string) url('app/tech_ticket/edit', ['ids' => $row['id']]),
                'order_rank' => $rankMap[$row['status']] ?? 95,
                'time_sort' => $this->normalizeTimeSort((string) ($row['due_date'] ?: '')),
            ];
        }

        return $items;
    }

    protected function buildReleaseTodoItems(): array
    {
        if (!$this->tableExists('app_release')) {
            return [];
        }

        $rows = Db::name('app_release')
            ->field('id,version,title,status,owner,release_date')
            ->where('status', 'in', ['planned', 'ready', 'testing'])
            ->orderRaw("FIELD(status, 'ready', 'testing', 'planned')")
            ->order('release_date', 'asc')
            ->limit(5)
            ->select();

        $statusMap = [
            'planned' => '已计划',
            'ready' => '待发布',
            'testing' => '测试中',
        ];
        $rankMap = [
            'ready' => 18,
            'testing' => 28,
            'planned' => 48,
        ];

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'type' => '版本',
                'type_class' => 'release',
                'title' => (string) $row['title'],
                'subtitle' => trim(((string) $row['version']) . ' / ' . ((string) ($row['owner'] ?: '未填写负责人')), ' /'),
                'status_text' => $statusMap[$row['status']] ?? (string) $row['status'],
                'time_text' => (string) ($row['release_date'] ?: '-'),
                'action_label' => '看版本单',
                'action_url' => (string) url('app/release/edit', ['ids' => $row['id']]),
                'order_rank' => $rankMap[$row['status']] ?? 98,
                'time_sort' => $this->normalizeTimeSort((string) ($row['release_date'] ?: '')),
            ];
        }

        return $items;
    }

    protected function buildMilestoneTodoItems(array $profile): array
    {
        if (!$this->tableExists('app_milestone')) {
            return [];
        }

        $projectIds = $this->resolveRelevantProjectIds($profile);
        $query = Db::name('app_milestone')
            ->field('id,title,app_project_id,status,progress,owner,due_date')
            ->where('status', 'in', ['pending', 'doing', 'review', 'blocked'])
            ->orderRaw("FIELD(status, 'blocked', 'review', 'doing', 'pending')")
            ->order('due_date', 'asc')
            ->limit(4);
        $this->applyProjectScope($query, $projectIds, 'app_project_id');

        $rows = $query->select();
        $statusMap = [
            'pending' => '待开始',
            'doing' => '进行中',
            'review' => '待确认',
            'blocked' => '已阻塞',
        ];
        $projectMap = $this->loadProjectDictionary($projectIds);

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'id' => (int) $row['id'],
                'type' => '里程碑',
                'type_class' => 'milestone',
                'title' => (string) $row['title'],
                'subtitle' => $projectMap[(int) ($row['app_project_id'] ?? 0)]['full_name'] ?? '未关联项目',
                'status_text' => $statusMap[$row['status']] ?? (string) $row['status'],
                'time_text' => (string) ($row['due_date'] ?: '-'),
                'action_label' => '更新节点',
                'action_url' => (string) url('app/milestone/edit', ['ids' => $row['id']]),
                'order_rank' => $row['status'] === 'blocked' ? 22 : ($row['status'] === 'review' ? 34 : 56),
                'time_sort' => $this->normalizeTimeSort((string) ($row['due_date'] ?: '')),
            ];
        }

        return $items;
    }

    protected function buildRiskTodoItems(array $profile): array
    {
        if (!$this->tableExists('app_risk')) {
            return [];
        }

        $projectIds = $this->resolveRelevantProjectIds($profile);
        $query = Db::name('app_risk')
            ->field('id,title,app_project_id,type,level,status,owner,due_date')
            ->where('status', 'in', ['open', 'tracking'])
            ->orderRaw("FIELD(level, 'critical', 'high', 'medium', 'low')")
            ->order('due_date', 'asc')
            ->limit(4);
        $this->applyProjectScope($query, $projectIds, 'app_project_id');

        $rows = $query->select();
        $typeMap = [
            'risk' => '风险',
            'issue' => '问题',
            'change' => '变更',
            'dependency' => '依赖',
        ];
        $levelWeight = [
            'critical' => 12,
            'high' => 24,
            'medium' => 44,
            'low' => 64,
        ];
        $projectMap = $this->loadProjectDictionary($projectIds);

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'id' => (int) $row['id'],
                'type' => '风险',
                'type_class' => 'risk',
                'title' => (string) $row['title'],
                'subtitle' => trim(
                    ($projectMap[(int) ($row['app_project_id'] ?? 0)]['short_name'] ?? '未关联项目') .
                    ' / ' .
                    ($typeMap[$row['type']] ?? (string) $row['type']),
                    ' /'
                ),
                'status_text' => strtoupper((string) $row['level']) . ' / ' . (string) $row['status'],
                'time_text' => (string) ($row['due_date'] ?: '-'),
                'action_label' => '看风险',
                'action_url' => (string) url('app/risk/edit', ['ids' => $row['id']]),
                'order_rank' => $levelWeight[$row['level']] ?? 68,
                'time_sort' => $this->normalizeTimeSort((string) ($row['due_date'] ?: '')),
            ];
        }

        return $items;
    }

    protected function buildProjectPulses(array $profile): array
    {
        if (!$this->tableExists('app_project')) {
            return [];
        }

        $projectIds = $this->resolveRelevantProjectIds($profile);
        $query = Db::name('app_project')
            ->field($this->getProjectFieldList())
            ->where('status', 'in', ['planning', 'running', 'paused', 'completed'])
            ->orderRaw("FIELD(status, 'running', 'planning', 'paused', 'completed')")
            ->order('id', 'desc')
            ->limit(5);
        $this->applyProjectScope($query, $projectIds, 'id');

        $rows = $query->select();
        $projectMap = $this->loadProjectDictionary($projectIds);
        $items = [];
        foreach ($rows as $row) {
            $projectId = (int) $row['id'];
            $base = $projectMap[$projectId] ?? [];
            $nextMilestone = $this->fetchNextMilestone($projectId);
            $latestReport = $this->fetchLatestReport($projectId);
            $items[] = [
                'id' => $projectId,
                'name' => $base['short_name'] ?? ((string) ($row['app_name'] ?: $row['name'] ?: '未命名项目')),
                'full_name' => $base['full_name'] ?? ((string) ($row['app_name'] ?: $row['name'] ?: '未命名项目')),
                'project_type_text' => $base['project_type_text'] ?? '其他',
                'status_text' => $base['status_text'] ?? (string) $row['status'],
                'lifecycle_text' => $base['lifecycle_text'] ?? (string) $row['lifecycle_stage'],
                'manager' => (string) ($row['manager'] ?: '未分配'),
                'version_text' => (string) ($row['app_version'] ?: '未填写版本'),
                'risk_count' => $this->countProjectOpenRisks($projectId),
                'next_milestone' => $nextMilestone['title'] ?? '暂无关键节点',
                'next_milestone_due' => $nextMilestone['due_date'] ?? '-',
                'latest_report' => $latestReport['summary'] ?? '最近未提交项目汇报',
                'latest_report_date' => $latestReport['report_date'] ?? '-',
                'latest_report_has_blockers' => !empty($latestReport['blockers']),
                'url' => (string) url('app/project/edit', ['ids' => $projectId]),
            ];
        }

        return $items;
    }

    protected function buildMilestoneItems(array $profile): array
    {
        if (!$this->tableExists('app_milestone')) {
            return [];
        }

        $projectIds = $this->resolveRelevantProjectIds($profile);
        $query = Db::name('app_milestone')
            ->field('id,title,app_project_id,status,progress,owner,due_date,deliverable')
            ->where('status', 'in', ['pending', 'doing', 'review', 'blocked'])
            ->orderRaw("FIELD(status, 'blocked', 'review', 'doing', 'pending')")
            ->order('due_date', 'asc')
            ->limit(5);
        $this->applyProjectScope($query, $projectIds, 'app_project_id');
        $rows = $query->select();

        $statusMap = [
            'pending' => '待开始',
            'doing' => '进行中',
            'review' => '待确认',
            'blocked' => '已阻塞',
        ];
        $projectMap = $this->loadProjectDictionary($projectIds);

        $items = [];
        foreach ($rows as $row) {
            $project = $projectMap[(int) ($row['app_project_id'] ?? 0)] ?? [];
            $items[] = [
                'title' => (string) $row['title'],
                'project_name' => $project['short_name'] ?? '未关联项目',
                'status_text' => $statusMap[$row['status']] ?? (string) $row['status'],
                'owner' => (string) ($row['owner'] ?: '未分配'),
                'progress' => (int) ($row['progress'] ?? 0),
                'due_date' => (string) ($row['due_date'] ?: '-'),
                'deliverable' => (string) ($row['deliverable'] ?: '未填写交付物'),
                'url' => (string) url('app/milestone/edit', ['ids' => $row['id']]),
            ];
        }

        return $items;
    }

    protected function buildRiskItems(array $profile): array
    {
        if (!$this->tableExists('app_risk')) {
            return [];
        }

        $projectIds = $this->resolveRelevantProjectIds($profile);
        $query = Db::name('app_risk')
            ->field('id,title,app_project_id,type,level,status,owner,due_date,impact')
            ->where('status', 'in', ['open', 'tracking'])
            ->orderRaw("FIELD(level, 'critical', 'high', 'medium', 'low')")
            ->order('due_date', 'asc')
            ->limit(5);
        $this->applyProjectScope($query, $projectIds, 'app_project_id');
        $rows = $query->select();

        $projectMap = $this->loadProjectDictionary($projectIds);
        $typeMap = [
            'risk' => '风险',
            'issue' => '问题',
            'change' => '变更',
            'dependency' => '依赖',
        ];

        $items = [];
        foreach ($rows as $row) {
            $project = $projectMap[(int) ($row['app_project_id'] ?? 0)] ?? [];
            $items[] = [
                'title' => (string) $row['title'],
                'project_name' => $project['short_name'] ?? '未关联项目',
                'type_text' => $typeMap[$row['type']] ?? (string) $row['type'],
                'level_text' => strtoupper((string) $row['level']),
                'status_text' => (string) $row['status'],
                'owner' => (string) ($row['owner'] ?: '未分配'),
                'due_date' => (string) ($row['due_date'] ?: '-'),
                'impact' => $this->excerpt((string) ($row['impact'] ?: '未填写影响说明')),
                'url' => (string) url('app/risk/edit', ['ids' => $row['id']]),
            ];
        }

        return $items;
    }

    protected function buildReportItems(array $profile): array
    {
        if (!$this->tableExists('app_report')) {
            return [];
        }

        $projectIds = $this->resolveRelevantProjectIds($profile);
        $query = Db::name('app_report')
            ->field('id,app_project_id,report_date,owner,summary,result,next_actions,blockers')
            ->order('report_date', 'desc')
            ->order('id', 'desc')
            ->limit(5);
        $this->applyProjectScope($query, $projectIds, 'app_project_id');
        $rows = $query->select();

        $projectMap = $this->loadProjectDictionary($projectIds);

        $items = [];
        foreach ($rows as $row) {
            $project = $projectMap[(int) ($row['app_project_id'] ?? 0)] ?? [];
            $items[] = [
                'project_name' => $project['short_name'] ?? '未关联项目',
                'report_date' => (string) ($row['report_date'] ?: '-'),
                'owner' => (string) ($row['owner'] ?: '未填写'),
                'summary' => $this->excerpt((string) ($row['summary'] ?: '未填写本周概述')),
                'result' => $this->excerpt((string) ($row['result'] ?: '未填写阶段结果')),
                'next_actions' => $this->excerpt((string) ($row['next_actions'] ?: '未填写下步动作')),
                'blockers' => $this->excerpt((string) ($row['blockers'] ?: '')),
                'has_blockers' => !empty($row['blockers']),
                'url' => (string) url('app/report/edit', ['ids' => $row['id']]),
            ];
        }

        return $items;
    }

    protected function buildMaterials(array $profile = []): array
    {
        if (!$this->tableExists('app_material')) {
            return [];
        }

        $projectIds = $this->resolveRelevantProjectIds($profile);
        $query = Db::name('app_material')
            ->field('id,app_project_id,title,category,owner,version_tag,updated_on,download_url,file_path')
            ->where('archive_status', '<>', 'archived')
            ->order('updated_on', 'desc')
            ->limit(5);
        $this->applyProjectScope($query, $projectIds, 'app_project_id');

        $rows = $query->select();
        $categoryMap = [
            'manual' => '操作手册',
            'faq' => 'FAQ',
            'training' => '培训资料',
            'script' => '脚本',
            'report' => '报告',
            'other' => '其他',
        ];
        $projectMap = $this->loadProjectDictionary($projectIds);

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'id' => (int) $row['id'],
                'title' => (string) $row['title'],
                'project_name' => $projectMap[(int) ($row['app_project_id'] ?? 0)]['short_name'] ?? '未关联项目',
                'category_text' => $categoryMap[$row['category']] ?? (string) $row['category'],
                'owner' => (string) ($row['owner'] ?: '未填写'),
                'version_tag' => (string) ($row['version_tag'] ?: '-'),
                'updated_on' => (string) ($row['updated_on'] ?: '-'),
                'download_url' => (string) ($row['download_url'] ?: $row['file_path'] ?: ''),
                'edit_url' => (string) url('app/material/edit', ['ids' => $row['id']]),
            ];
        }

        return $items;
    }

    protected function buildUsageTips(array $profile): array
    {
        $roleKey = (string) ($profile['role_key'] ?? 'operations');

        $tips = [
            '先在“我的待办”里处理当前最急的事项，不要先翻全部模块。',
            '客户反馈先记问题，再决定是否需要提交研发联动单。',
            '涉及上线就放到“版本发布”，不要把发布细节写在问题备注里。',
            '处理完的经验、口径和附件，最后放进“内部资料”统一沉淀。',
        ];

        if ($roleKey === 'tech') {
            $tips[1] = '研发处理时先改状态，再补测试结论和发布时间。';
        }

        return $tips;
    }

    protected function resolveRelevantProjectIds(array $profile): ?array
    {
        if (!$this->tableExists('app_project')) {
            return [];
        }

        $adminId = (int) ($profile['admin_id'] ?? 0);
        $roleKey = (string) ($profile['role_key'] ?? 'operations');
        if ($adminId <= 0 || in_array($roleKey, ['admin', 'viewer'], true)) {
            return null;
        }

        $ids = Db::name('app_project')
            ->where('manager_admin_id', $adminId)
            ->column('id');

        if ($this->tableExists('app_issue')
            && $this->tableColumnExists('app_issue', 'assignee_admin_id')
            && $this->tableColumnExists('app_issue', 'app_project_id')
        ) {
            $ids = array_merge($ids, Db::name('app_issue')->where('assignee_admin_id', $adminId)->column('app_project_id'));
        }

        if ($this->tableExists('app_tech_ticket')
            && $this->tableColumnExists('app_tech_ticket', 'owner_admin_id')
            && $this->tableColumnExists('app_tech_ticket', 'app_project_id')
        ) {
            $ids = array_merge($ids, Db::name('app_tech_ticket')->where('owner_admin_id', $adminId)->column('app_project_id'));
        }

        if ($this->tableExists('app_report')
            && $this->tableColumnExists('app_report', 'owner_admin_id')
            && $this->tableColumnExists('app_report', 'app_project_id')
        ) {
            $ids = array_merge($ids, Db::name('app_report')->where('owner_admin_id', $adminId)->column('app_project_id'));
        }

        $ids = $this->normalizeIds($ids);
        return $ids ?: [];
    }

    protected function applyProjectScope($query, ?array $projectIds, string $field = 'app_project_id'): void
    {
        if ($projectIds === null) {
            return;
        }

        if ($projectIds === []) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->where($field, 'in', $projectIds);
    }

    protected function countMilestoneSummary(?array $projectIds): int
    {
        return $this->safeCount('app_milestone', function ($query) use ($projectIds) {
            $query->where('status', 'in', ['pending', 'doing', 'review', 'blocked']);
            $this->applyProjectScope($query, $projectIds, 'app_project_id');
        });
    }

    protected function countRiskSummary(?array $projectIds): int
    {
        return $this->safeCount('app_risk', function ($query) use ($projectIds) {
            $query->where('status', 'in', ['open', 'tracking']);
            $this->applyProjectScope($query, $projectIds, 'app_project_id');
        });
    }

    protected function countProjectOpenRisks(int $projectId): int
    {
        if ($projectId <= 0 || !$this->tableExists('app_risk')) {
            return 0;
        }

        return (int) Db::name('app_risk')
            ->where('app_project_id', $projectId)
            ->where('status', 'in', ['open', 'tracking'])
            ->count();
    }

    protected function fetchNextMilestone(int $projectId): array
    {
        if ($projectId <= 0 || !$this->tableExists('app_milestone')) {
            return [];
        }

        return (array) Db::name('app_milestone')
            ->field('id,title,due_date,status')
            ->where('app_project_id', $projectId)
            ->where('status', 'in', ['pending', 'doing', 'review', 'blocked'])
            ->orderRaw("FIELD(status, 'blocked', 'review', 'doing', 'pending')")
            ->order('due_date', 'asc')
            ->find();
    }

    protected function fetchLatestReport(int $projectId): array
    {
        if ($projectId <= 0 || !$this->tableExists('app_report')) {
            return [];
        }

        return (array) Db::name('app_report')
            ->field('id,report_date,summary,blockers')
            ->where('app_project_id', $projectId)
            ->order('report_date', 'desc')
            ->order('id', 'desc')
            ->find();
    }

    protected function loadProjectDictionary(?array $projectIds = null): array
    {
        if (!$this->tableExists('app_project')) {
            return [];
        }

        $query = Db::name('app_project')->field($this->getProjectFieldList());
        $this->applyProjectScope($query, $projectIds, 'id');
        $rows = $query->select();

        $statusMap = [
            'planning' => '筹备中',
            'running' => '进行中',
            'paused' => '已暂停',
            'completed' => '已完成',
            'archived' => '已归档',
        ];
        $lifecycleMap = [
            'idea' => '想法期',
            'validation' => '验证期',
            'launch' => '上线期',
            'growth' => '增长期',
            'retention' => '留存期',
            'mature' => '成熟期',
            'sunset' => '退场期',
        ];
        $typeMap = [
            'app' => 'APP',
            'miniprogram' => '小程序',
            'website' => '官网/网站',
            'campaign' => '活动投放',
            'private_domain' => '私域运营',
            'other' => '其他',
        ];

        $items = [];
        foreach ($rows as $row) {
            $projectType = $typeMap[$row['project_type'] ?? 'other'] ?? '其他';
            $shortName = (string) ($row['app_name'] ?: $row['name'] ?: '未命名项目');
            $fullName = '[' . $projectType . '] ' . $shortName;
            if (!empty($row['name']) && $row['name'] !== $shortName) {
                $fullName .= ' / ' . $row['name'];
            }

            $items[(int) $row['id']] = [
                'id' => (int) $row['id'],
                'short_name' => $shortName,
                'full_name' => $fullName,
                'project_type_text' => $projectType,
                'status_text' => $statusMap[$row['status']] ?? (string) $row['status'],
                'lifecycle_text' => $lifecycleMap[$row['lifecycle_stage']] ?? (string) $row['lifecycle_stage'],
            ];
        }

        return $items;
    }

    protected function getProjectFieldList(): string
    {
        $fields = ['id', 'app_name', 'name', 'lifecycle_stage', 'status', 'manager', 'app_version'];
        if ($this->tableColumnExists('app_project', 'project_type')) {
            $fields[] = 'project_type';
        }

        return implode(',', $fields);
    }

    protected function normalizeIds(array $ids): array
    {
        $items = [];
        foreach ($ids as $id) {
            $value = (int) $id;
            if ($value > 0) {
                $items[$value] = $value;
            }
        }

        return array_values($items);
    }

    protected function excerpt(string $text, int $length = 42): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text));
        if ($text === '') {
            return '';
        }

        if (function_exists('mb_strimwidth')) {
            return mb_strimwidth($text, 0, $length, '...');
        }

        return strlen($text) > $length ? substr($text, 0, $length) . '...' : $text;
    }

    protected function normalizeTimeSort(string $value): int
    {
        if ($value === '') {
            return PHP_INT_MAX;
        }

        $timestamp = strtotime($value);
        return $timestamp ?: PHP_INT_MAX;
    }

    protected function safeCount(string $table, ?callable $callback = null): int
    {
        if (!$this->tableExists($table)) {
            return 0;
        }

        $query = Db::name($table);
        if ($callback) {
            $callback($query);
        }

        return (int) $query->count();
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

    protected function tableColumnExists(string $table, string $column): bool
    {
        static $cache = [];
        $cacheKey = $table . '.' . $column;
        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        $fullTable = config('database.prefix') . $table;
        $cache[$cacheKey] = !empty(Db::query("SHOW COLUMNS FROM `{$fullTable}` LIKE '{$column}'"));

        return $cache[$cacheKey];
    }
}
