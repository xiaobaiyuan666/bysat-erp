<?php

namespace app\admin\controller\app;

use app\common\controller\Backend;
use think\Db;

/**
 * APP 运营工作台
 *
 * @icon fa fa-mobile
 */
class Workbench extends Backend
{
    public function index()
    {
        $profile = $this->getCurrentUserProfile();

        $this->view->assign([
            'currentUser' => $this->buildCurrentUser($profile),
            'summaryCards' => $this->buildSummaryCards($profile),
            'quickActions' => $this->buildQuickActions(),
            'quickPanels' => $this->buildQuickPanels($profile),
            'usageGuide' => $this->buildUsageGuide(),
            'myIssues' => $this->buildMyIssues($profile),
            'myTechTickets' => $this->buildMyTechTickets($profile),
            'releaseTodos' => $this->buildReleaseTodos(),
            'myAppProjects' => $this->buildMyAppProjects($profile),
        ]);

        return $this->view->fetch();
    }

    protected function getCurrentUserProfile(): array
    {
        $adminId = (int)($this->auth->id ?? 0);
        $info = $this->auth->getUserInfo($adminId);
        $profile = Db::name('staff_profile')
            ->field('admin_id,account,name,title,department,role_key')
            ->where('admin_id', $adminId)
            ->find();

        return [
            'admin_id' => $adminId,
            'name' => $profile['name'] ?? ($info['nickname'] ?? ($info['username'] ?? '系统用户')),
            'title' => $profile['title'] ?? 'APP 运营岗位',
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
            'guide' => $this->buildRoleGuide((string)$profile['role_key']),
        ];
    }

    protected function buildRoleGuide(string $roleKey): string
    {
        $map = [
            'operations' => '先接问题，再挂研发，再盯版本和 APP 节奏。',
            'service' => '先处理我的问题和客户回告，再同步研发。',
            'tech' => '先看我的研发联动和待发版版本，再回看 APP 台账。',
            'admin' => '先看待发布和问题积压，再推进 APP 项目节奏。',
        ];

        return $map[$roleKey] ?? '先处理我的问题，再回到 APP 台账。';
    }

    protected function buildSummaryCards(array $profile): array
    {
        $adminId = (int)($profile['admin_id'] ?? 0);
        $roleKey = (string)($profile['role_key'] ?? 'operations');

        return [
            [
                'title' => '我的问题',
                'value' => $this->safeCount('app_issue', function ($query) use ($adminId) {
                    $query->where('assignee_admin_id', $adminId)
                        ->where('status', 'in', ['new', 'processing', 'waiting_customer', 'escalated', 'resolved']);
                }),
                'hint' => '先处理自己名下的问题单和客户回告。',
                'url' => (string)url('app/issue/index', ['assignee_admin_id' => $adminId]),
            ],
            [
                'title' => '待客户回告',
                'value' => $this->safeCount('app_issue', function ($query) use ($adminId) {
                    $query->where('status', 'waiting_customer');
                    if ($adminId > 0) {
                        $query->where('assignee_admin_id', $adminId);
                    }
                }),
                'hint' => '这些问题要尽快回给客户，避免久拖。',
                'url' => (string)url('app/issue/index', ['assignee_admin_id' => $adminId, 'status' => 'waiting_customer']),
            ],
            [
                'title' => '我的研发联动',
                'value' => $this->safeCount('app_tech_ticket', function ($query) use ($adminId) {
                    $query->where('owner_admin_id', $adminId)
                        ->where('status', 'in', ['pending', 'processing', 'testing', 'ready']);
                }),
                'hint' => '同步我的研发待办、测试和待发版事项。',
                'url' => (string)url('app/tech_ticket/index'),
            ],
            [
                'title' => $roleKey === 'tech' ? '待发版版本' : '我负责 APP',
                'value' => $roleKey === 'tech'
                    ? $this->safeCount('app_release', function ($query) {
                        $query->where('status', 'in', ['planned', 'ready', 'testing']);
                    })
                    : $this->safeCount('app_project', function ($query) use ($adminId) {
                        $query->where('manager_admin_id', $adminId)->where('status', 'in', ['planning', 'running', 'paused']);
                    }),
                'hint' => $roleKey === 'tech'
                    ? '优先确认测试、发版窗口和回滚风险。'
                    : '快速查看我负责 APP 的阶段、版本和状态。',
                'url' => $roleKey === 'tech'
                    ? (string)url('app/release/index')
                    : (string)url('app/project/index'),
            ],
        ];
    }

    protected function buildQuickPanels(array $profile): array
    {
        $adminId = (int)($profile['admin_id'] ?? 0);
        $roleKey = (string)($profile['role_key'] ?? 'operations');

        return [
            [
                'title' => '先处理今日问题',
                'hint' => '先看我的问题，再看待客户回告。',
                'items' => [
                    ['title' => '我处理中的问题', 'url' => (string)url('app/issue/index', ['assignee_admin_id' => $adminId, 'status' => 'processing'])],
                    ['title' => '待客户回告', 'url' => (string)url('app/issue/index', ['assignee_admin_id' => $adminId, 'status' => 'waiting_customer'])],
                    ['title' => '待升级问题', 'url' => (string)url('app/issue/index', ['assignee_admin_id' => $adminId, 'status' => 'escalated'])],
                ],
            ],
            [
                'title' => '研发与发布',
                'hint' => '把研发联动、测试、发布集中到一处处理。',
                'items' => [
                    ['title' => '我的研发联动', 'url' => (string)url('app/tech_ticket/index')],
                    ['title' => '待发布版本', 'url' => (string)url('app/release/index')],
                    ['title' => '我的 APP 项目', 'url' => (string)url('app/project/index')],
                ],
            ],
            [
                'title' => 'AI 助理',
                'hint' => '客服反馈和联动建议统一在对话区沉淀。',
                'items' => [
                    ['title' => '打开 AI 对话', 'url' => (string)url('ai/conversation/index', ['focus' => 'app'])],
                    ['title' => $roleKey === 'tech' ? '记录研发反馈' : '提交领导反馈', 'url' => (string)url($roleKey === 'tech' ? 'app/tech_ticket/add' : 'app/issue/add')],
                ],
            ],
        ];
    }

    protected function buildUsageGuide(): array
    {
        return [
            '开门顺序',
            '先处理【我处理中的问题】，问题是日常工作的主链路。',
            '再看【我的研发联动】，确认研发反馈和发布安排。',
            '有沟通材料和反馈时，优先同步到 AI 对话里沉淀。',
        ];
    }

    protected function buildQuickActions(): array
    {
        return [
            ['title' => '新建问题', 'icon' => 'fa fa-bug', 'url' => (string)url('app/issue/add')],
            ['title' => '新建研发待办', 'icon' => 'fa fa-code-fork', 'url' => (string)url('app/tech_ticket/add')],
            ['title' => '新增版本发布', 'icon' => 'fa fa-rocket', 'url' => (string)url('app/release/add')],
            ['title' => '新增内部资料', 'icon' => 'fa fa-folder-open-o', 'url' => (string)url('app/material/add')],
            ['title' => 'AI APP 分析', 'icon' => 'fa fa-comments-o', 'url' => (string)url('ai/conversation/index', ['focus' => 'app'])],
        ];
    }

    protected function buildMyIssues(array $profile): array
    {
        if (!$this->tableExists('app_issue')) {
            return [];
        }

        $adminId = (int)($profile['admin_id'] ?? 0);
        $rows = Db::name('app_issue')
            ->field('id,ticket_no,title,customer,status,priority,assignee,last_follow_up_at,resolve_due_at')
            ->where('assignee_admin_id', $adminId)
            ->where('status', 'in', ['new', 'processing', 'waiting_customer', 'escalated', 'resolved'])
            ->orderRaw("FIELD(status, 'escalated', 'waiting_customer', 'new', 'processing', 'resolved')")
            ->order('resolve_due_at', 'asc')
            ->limit(8)
            ->select();

        if (!$rows && in_array((string)$profile['role_key'], ['admin', 'viewer'], true)) {
            $rows = Db::name('app_issue')
                ->field('id,ticket_no,title,customer,status,priority,assignee,last_follow_up_at,resolve_due_at')
                ->where('status', 'in', ['new', 'processing', 'waiting_customer', 'escalated', 'resolved'])
                ->orderRaw("FIELD(status, 'escalated', 'waiting_customer', 'new', 'processing', 'resolved')")
                ->order('resolve_due_at', 'asc')
                ->limit(8)
                ->select();
        }

        return $this->formatIssueRows($rows);
    }

    protected function buildMyTechTickets(array $profile): array
    {
        if (!$this->tableExists('app_tech_ticket')) {
            return [];
        }

        $adminId = (int)($profile['admin_id'] ?? 0);
        $rows = Db::name('app_tech_ticket')
            ->field('id,title,type,status,priority,owner,due_date')
            ->where('owner_admin_id', $adminId)
            ->where('status', 'in', ['pending', 'processing', 'testing', 'ready'])
            ->orderRaw("FIELD(status, 'ready', 'testing', 'processing', 'pending')")
            ->orderRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")
            ->order('due_date', 'asc')
            ->limit(8)
            ->select();

        if (!$rows && in_array((string)$profile['role_key'], ['admin', 'viewer'], true)) {
            $rows = Db::name('app_tech_ticket')
                ->field('id,title,type,status,priority,owner,due_date')
                ->where('status', 'in', ['pending', 'processing', 'testing', 'ready'])
                ->orderRaw("FIELD(status, 'ready', 'testing', 'processing', 'pending')")
                ->orderRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")
                ->order('due_date', 'asc')
                ->limit(8)
                ->select();
        }

        return $this->formatTechRows($rows);
    }

    protected function buildReleaseTodos(): array
    {
        if (!$this->tableExists('app_release')) {
            return [];
        }

        $rows = Db::name('app_release')
            ->field('id,version,title,status,owner,release_date,customer_sync_status')
            ->where('status', 'in', ['planned', 'ready', 'testing'])
            ->orderRaw("FIELD(status, 'ready', 'testing', 'planned')")
            ->order('release_date', 'asc')
            ->limit(8)
            ->select();

        return $this->formatReleaseRows($rows);
    }

    protected function buildMyAppProjects(array $profile): array
    {
        if (!$this->tableExists('app_project')) {
            return [];
        }

        $adminId = (int)($profile['admin_id'] ?? 0);
        $rows = Db::name('app_project')
            ->field('id,app_name,name,lifecycle_stage,status,manager,app_version')
            ->where('manager_admin_id', $adminId)
            ->where('status', 'in', ['planning', 'running', 'paused'])
            ->orderRaw("FIELD(status, 'running', 'planning', 'paused')")
            ->order('id', 'desc')
            ->limit(8)
            ->select();

        if (!$rows && in_array((string)$profile['role_key'], ['admin', 'viewer'], true)) {
            $rows = Db::name('app_project')
                ->field('id,app_name,name,lifecycle_stage,status,manager,app_version')
                ->where('status', '<>', 'archived')
                ->orderRaw("FIELD(status, 'running', 'planning', 'paused', 'completed')")
                ->order('id', 'desc')
                ->limit(8)
                ->select();
        }

        return $this->formatProjectRows($rows);
    }

    protected function formatIssueRows($rows): array
    {
        $statusMap = [
            'new' => '新问题',
            'processing' => '处理中',
            'waiting_customer' => '待客户回告',
            'escalated' => '已升级',
            'resolved' => '已解决',
            'closed' => '已关闭',
        ];
        $priorityMap = [
            'low' => '低',
            'medium' => '中',
            'high' => '高',
            'urgent' => '紧急',
        ];

        $items = [];
        foreach ($rows as $row) {
            $row['status_text'] = $statusMap[$row['status']] ?? $row['status'];
            $row['priority_text'] = $priorityMap[$row['priority']] ?? $row['priority'];
            $items[] = $row;
        }

        return $items;
    }

    protected function formatTechRows($rows): array
    {
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
            'ready' => '待发版',
            'done' => '已完成',
            'closed' => '已关闭',
        ];
        $priorityMap = [
            'low' => '低',
            'medium' => '中',
            'high' => '高',
            'urgent' => '紧急',
        ];

        $items = [];
        foreach ($rows as $row) {
            $row['type_text'] = $typeMap[$row['type']] ?? $row['type'];
            $row['status_text'] = $statusMap[$row['status']] ?? $row['status'];
            $row['priority_text'] = $priorityMap[$row['priority']] ?? $row['priority'];
            $items[] = $row;
        }

        return $items;
    }

    protected function formatReleaseRows($rows): array
    {
        $statusMap = [
            'planned' => '已计划',
            'ready' => '待发版',
            'testing' => '测试中',
            'released' => '已发布',
            'rollback' => '已回滚',
            'closed' => '已关闭',
        ];
        $syncMap = [
            'pending' => '待回告',
            'done' => '已回告',
            'skip' => '无需回告',
        ];

        $items = [];
        foreach ($rows as $row) {
            $row['status_text'] = $statusMap[$row['status']] ?? $row['status'];
            $row['customer_sync_status_text'] = $syncMap[$row['customer_sync_status']] ?? ($row['customer_sync_status'] ?: '-');
            $items[] = $row;
        }

        return $items;
    }

    protected function formatProjectRows($rows): array
    {
        $statusMap = [
            'planning' => '筹备中',
            'running' => '进行中',
            'paused' => '已暂停',
            'completed' => '已完成',
            'closed' => '已关闭',
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

        $items = [];
        foreach ($rows as $row) {
            $row['status_text'] = $statusMap[$row['status']] ?? $row['status'];
            $row['lifecycle_text'] = $lifecycleMap[$row['lifecycle_stage']] ?? $row['lifecycle_stage'];
            $items[] = $row;
        }

        return $items;
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

        return (int)$query->count();
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
}
