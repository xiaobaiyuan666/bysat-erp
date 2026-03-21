<?php

namespace app\admin\controller\project;

use app\common\controller\Backend;
use think\Db;

/**
 * 项目工作台
 *
 * @icon fa fa-dashboard
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
            'myTasks' => $this->buildMyTasks($profile),
            'myProjects' => $this->buildMyProjects($profile),
            'riskTasks' => $this->buildRiskTasks(),
            'ownerLoads' => $this->buildOwnerLoads(),
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
            'title' => $profile['title'] ?? '项目岗位',
            'department' => $profile['department'] ?? '交付部',
            'role_key' => $profile['role_key'] ?? 'project',
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
            'project' => '先处理我的任务和风险，再推进项目台账。',
            'admin' => '先看全局高风险任务，再处理项目经理负荷。',
            'viewer' => '先看自己参与的任务，再回到项目台账。',
        ];

        return $map[$roleKey] ?? '先处理我的任务，再推进项目交付。';
    }

    protected function buildSummaryCards(array $profile): array
    {
        $adminId = (int)($profile['admin_id'] ?? 0);

        return [
            [
                'title' => '我的任务',
                'value' => $this->safeCount('project_task', function ($query) use ($adminId) {
                    $query->where('assignee_admin_id', $adminId)->where('status', '<>', 'done');
                }),
                'hint' => '今天优先处理自己名下还未完成的任务。',
                'url' => (string)url('project/task/index', ['assignee_admin_id' => $adminId]),
            ],
            [
                'title' => '我的逾期任务',
                'value' => $this->safeCount('project_task', function ($query) use ($adminId) {
                    $query->where('assignee_admin_id', $adminId)->where(function ($subQuery) {
                        $subQuery->where('status', 'overdue')
                            ->whereOr(function ($inner) {
                                $inner->where('status', '<>', 'done')
                                    ->where('due_date', '<', date('Y-m-d'));
                            });
                    });
                }),
                'hint' => '先把已经超期或临近失控的任务清掉。',
                'url' => (string)url('project/task/index', ['assignee_admin_id' => $adminId, 'status' => 'overdue']),
            ],
            [
                'title' => '待验收任务',
                'value' => $this->safeCount('project_task', function ($query) use ($adminId) {
                    $query->where('status', 'review');
                    if ($adminId > 0) {
                        $query->where('assignee_admin_id', $adminId);
                    }
                }),
                'hint' => '本周要优先过验收，避免项目拖尾。',
                'url' => (string)url('project/task/index', ['assignee_admin_id' => $adminId, 'status' => 'review']),
            ],
            [
                'title' => '我负责项目',
                'value' => $this->safeCount('project', function ($query) use ($adminId) {
                    $query->where('owner_admin_id', $adminId)->where('status', 'in', ['planning', 'active', 'delivery', 'paused']);
                }),
                'hint' => '快速查看自己负责的项目节奏和交付日期。',
                'url' => (string)url('project/project/index', ['owner_admin_id' => $adminId]),
            ],
        ];
    }

    protected function buildQuickPanels(array $profile): array
    {
        $adminId = (int)($profile['admin_id'] ?? 0);

        return [
            [
                'title' => '今天优先入口',
                'hint' => '先看我负责的关键待办，避免在全部任务里迷路。',
                'items' => [
                    ['title' => '我的待办任务', 'url' => (string)url('project/task/index', ['assignee_admin_id' => $adminId])],
                    ['title' => '逾期优先处理', 'url' => (string)url('project/task/index', ['assignee_admin_id' => $adminId, 'status' => 'overdue'])],
                    ['title' => '待验收', 'url' => (string)url('project/task/index', ['assignee_admin_id' => $adminId, 'status' => 'review'])],
                ],
            ],
            [
                'title' => '项目视图',
                'hint' => '按我负责的项目入口进入，直接进入列表筛选状态。',
                'items' => [
                    ['title' => '我负责项目', 'url' => (string)url('project/project/index', ['owner_admin_id' => $adminId])],
                    ['title' => '打开项目台账', 'url' => (string)url('project/project/index')],
                    ['title' => '项目AI看板', 'url' => (string)url('ai/conversation/index', ['focus' => 'project'])],
                ],
            ],
        ];
    }

    protected function buildUsageGuide(): array
    {
        return [
            '开门顺序',
            '先点【我的待办任务】，确认今天自己负责的未完成项。',
            '再点【逾期优先处理】，优先处理会拖后腿的任务。',
            '最后再看【负责人负荷】，快速发现异常项目。',
        ];
    }

    protected function buildQuickActions(): array
    {
        return [
            ['title' => '新增任务', 'icon' => 'fa fa-plus-circle', 'url' => (string)url('project/task/add')],
            ['title' => '新增项目', 'icon' => 'fa fa-plus-square-o', 'url' => (string)url('project/project/add')],
            ['title' => '任务清单', 'icon' => 'fa fa-tasks', 'url' => (string)url('project/task/index')],
            ['title' => '项目台账', 'icon' => 'fa fa-folder-open', 'url' => (string)url('project/project/index')],
            ['title' => 'AI 项目分析', 'icon' => 'fa fa-comments-o', 'url' => (string)url('ai/conversation/index', ['focus' => 'project'])],
        ];
    }

    protected function buildMyTasks(array $profile): array
    {
        if (!$this->tableExists('project_task')) {
            return [];
        }

        $adminId = (int)($profile['admin_id'] ?? 0);
        $rows = Db::name('project_task')
            ->field('id,title,assignee,status,priority,due_date,project_id')
            ->where('assignee_admin_id', $adminId)
            ->where('status', '<>', 'done')
            ->orderRaw("FIELD(status, 'overdue', 'blocked', 'review', 'doing', 'todo')")
            ->order('due_date', 'asc')
            ->limit(8)
            ->select();

        if (!$rows && in_array((string)$profile['role_key'], ['admin', 'viewer'], true)) {
            $rows = Db::name('project_task')
                ->field('id,title,assignee,status,priority,due_date,project_id')
                ->where('status', '<>', 'done')
                ->orderRaw("FIELD(status, 'overdue', 'blocked', 'review', 'doing', 'todo')")
                ->order('due_date', 'asc')
                ->limit(8)
                ->select();
        }

        return $this->formatTaskRows($rows);
    }

    protected function buildMyProjects(array $profile): array
    {
        if (!$this->tableExists('project')) {
            return [];
        }

        $adminId = (int)($profile['admin_id'] ?? 0);
        $rows = Db::name('project')
            ->field('id,name,client,owner,status,priority,budget,due_date')
            ->where('owner_admin_id', $adminId)
            ->where('status', 'in', ['planning', 'active', 'delivery', 'paused'])
            ->orderRaw("FIELD(status, 'delivery', 'active', 'planning', 'paused')")
            ->order('due_date', 'asc')
            ->limit(8)
            ->select();

        if (!$rows && in_array((string)$profile['role_key'], ['admin', 'viewer'], true)) {
            $rows = Db::name('project')
                ->field('id,name,client,owner,status,priority,budget,due_date')
                ->where('status', '<>', 'closed')
                ->orderRaw("FIELD(status, 'delivery', 'active', 'planning', 'paused', 'completed')")
                ->order('due_date', 'asc')
                ->limit(8)
                ->select();
        }

        return $this->formatProjectRows($rows);
    }

    protected function buildRiskTasks(): array
    {
        if (!$this->tableExists('project_task')) {
            return [];
        }

        $rows = Db::name('project_task')
            ->field('id,title,assignee,status,priority,due_date,project_id')
            ->where(function ($query) {
                $query->where('status', 'in', ['blocked', 'review', 'overdue'])
                    ->whereOr(function ($inner) {
                        $inner->where('status', '<>', 'done')
                            ->where('due_date', '<', date('Y-m-d'));
                    });
            })
            ->orderRaw("FIELD(status, 'overdue', 'blocked', 'review', 'doing', 'todo')")
            ->order('due_date', 'asc')
            ->limit(8)
            ->select();

        return $this->formatTaskRows($rows);
    }

    protected function buildOwnerLoads(): array
    {
        if (!$this->tableExists('project_task')) {
            return [];
        }

        $rows = Db::name('project_task')
            ->field("assignee, count(*) as total_count, sum(case when status in ('doing','review','blocked','overdue','todo') then 1 else 0 end) as open_count, sum(case when status = 'overdue' or (status <> 'done' and due_date < '" . date('Y-m-d') . "') then 1 else 0 end) as overdue_count")
            ->where('assignee', '<>', '')
            ->group('assignee')
            ->order('open_count', 'desc')
            ->order('overdue_count', 'desc')
            ->limit(8)
            ->select();

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'assignee' => (string)$row['assignee'],
                'total_count' => (int)$row['total_count'],
                'open_count' => (int)$row['open_count'],
                'overdue_count' => (int)$row['overdue_count'],
            ];
        }

        return $items;
    }

    protected function formatTaskRows($rows): array
    {
        $projectMap = $this->loadProjectNames();
        $statusMap = [
            'todo' => '待处理',
            'doing' => '进行中',
            'review' => '待验收',
            'done' => '已完成',
            'blocked' => '阻塞',
            'overdue' => '已逾期',
        ];
        $priorityMap = [
            'low' => '低',
            'medium' => '中',
            'high' => '高',
            'urgent' => '紧急',
        ];

        $items = [];
        foreach ($rows as $row) {
            $row['project_name'] = $projectMap[(int)$row['project_id']] ?? '未关联项目';
            $row['status_text'] = $statusMap[$row['status']] ?? $row['status'];
            $row['priority_text'] = $priorityMap[$row['priority']] ?? $row['priority'];
            $items[] = $row;
        }

        return $items;
    }

    protected function formatProjectRows($rows): array
    {
        $statusMap = [
            'planning' => '规划中',
            'active' => '执行中',
            'delivery' => '交付中',
            'completed' => '已完成',
            'paused' => '已暂停',
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
            $row['budget_text'] = number_format((float)$row['budget'], 2);
            $items[] = $row;
        }

        return $items;
    }

    protected function loadProjectNames(): array
    {
        if (!$this->tableExists('project')) {
            return [];
        }

        $rows = Db::name('project')->field('id,name')->select();
        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['id']] = (string)$row['name'];
        }

        return $map;
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
