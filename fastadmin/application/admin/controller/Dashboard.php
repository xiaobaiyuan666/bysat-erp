<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;

/**
 * 企业 ERP AI 智能管理系统首页
 *
 * @icon fa fa-dashboard
 */
class Dashboard extends Backend
{
    public function index()
    {
        $this->assignconfig('dashboardAiAskUrl', url('ai/conversation/ask'));
        $this->assignconfig('dashboardAiConversationUrl', url('ai/conversation/index'));
        $this->assignconfig('dashboardAiSettingUrl', url('ai/setting/index'));

        $profile = $this->getCurrentUserProfile();

        $this->view->assign([
            'currentUser' => $this->buildCurrentUser($profile),
            'aiCenter' => $this->buildAiCenter(),
            'primarySummary' => $this->buildPrimarySummary($profile),
            'myWorkPanels' => $this->buildMyWorkPanels($profile),
            'todayQueue' => $this->buildTodayQueue($profile),
            'workbenchCards' => $this->buildWorkbenchCards($profile),
        ]);

        return $this->view->fetch();
    }

    protected function buildCurrentUser(array $profile): array
    {
        return [
            'name' => $profile['name'],
            'title' => $profile['title'],
            'department' => $profile['department'],
            'role_label' => $this->mapRoleLabel($profile['role_key']),
            'role_tip' => $this->buildRoleTip($profile['role_key']),
            'quick_links' => $this->buildUserQuickLinks($profile['role_key']),
        ];
    }

    protected function buildAiCenter(): array
    {
        $setting = $this->getDefaultAiSetting();
        $configured = $setting
            && trim((string)($setting['base_url'] ?? '')) !== ''
            && trim((string)($setting['api_key'] ?? '')) !== ''
            && trim((string)($setting['model'] ?? '')) !== '';

        $modelLabel = $configured
            ? trim((string)($setting['provider_name'] ?? 'OpenAI Compatible') . ' / ' . (string)($setting['model'] ?? ''), ' /')
            : '还没有完成 AI 配置';

        return [
            'title' => '企业 ERP AI 智能管理系统',
            'subtitle' => '参考陀螺匠这类系统的工作台思路，把待办、审批和业务入口收在一个首页里。你先问 AI，再进入对应工作台处理。', // phpcs:ignore Generic.Files.LineLength.TooLong
            'model_label' => $modelLabel,
            'model_hint' => $configured
                ? '当前可以直接分析财务、项目、APP 运营和客户采购数据。'
                : '先补齐 Base URL、API Key 和模型名称，再让 AI 接管分析。',
            'configured' => $configured,
            'setting_id' => (int)($setting['id'] ?? 0),
            'open_url' => $this->buildAiUrl(),
            'config_url' => (string)url('ai/setting/index'),
            'focuses' => [
                ['key' => 'overview', 'label' => '综合经营'],
                ['key' => 'finance', 'label' => '财务'],
                ['key' => 'business', 'label' => '客户与采购'],
                ['key' => 'project', 'label' => '项目'],
                ['key' => 'app', 'label' => 'APP 运营'],
            ],
            'quick_prompts' => [
                ['title' => '今天先做什么', 'focus' => 'overview', 'preset_key' => 'daily-brief'],
                ['title' => '回款与付款风险', 'focus' => 'finance', 'preset_key' => 'cash-risk'],
                ['title' => '项目复盘', 'focus' => 'project', 'preset_key' => 'project-risk'],
                ['title' => 'APP 问题分析', 'focus' => 'app', 'preset_key' => 'app-review'],
            ],
        ];
    }

    protected function buildTodoSummary(): array
    {
        $items = [];

        if ($this->hasAccess('finance/workbench/index')) {
            $items[] = [
                'title' => '待回款',
                'value' => $this->safeCount('finance_invoice', function ($query) {
                    $query->where('kind', 'receivable')->where('status', 'in', ['pending', 'partial', 'overdue']);
                }),
                'url' => (string)url('finance/workbench/index'),
            ];
        }

        if ($this->hasAccess('business/approval/index')) {
            $items[] = [
                'title' => '待审批',
                'value' => $this->safeCount('business_approval', function ($query) {
                    $query->where('status', 'pending');
                }),
                'url' => (string)url('business/approval/index'),
            ];
        }

        if ($this->hasAccess('business/payment_plan/index') || $this->hasAccess('business/payment_request/index')) {
            $items[] = [
                'title' => '待付款',
                'value' => $this->safeCount('business_payment_plan', function ($query) {
                    $query->where('status', 'in', ['pending', 'processing', 'overdue']);
                }) + $this->safeCount('business_payment_request', function ($query) {
                    $query->where('status', 'pending_approval');
                }),
                'url' => $this->hasAccess('business/payment_request/index')
                    ? (string)url('business/payment_request/index')
                    : (string)url('business/payment_plan/index'),
            ];
        }

        if ($this->hasAccess('project/workbench/index')) {
            $items[] = [
                'title' => '任务待处理',
                'value' => $this->safeCount('project_task', function ($query) {
                    $query->where('status', '<>', 'done');
                }),
                'url' => (string)url('project/workbench/index'),
            ];
        }

        if ($this->hasAccess('app/workbench/index')) {
            $items[] = [
                'title' => 'APP 问题',
                'value' => $this->safeCount('app_issue', function ($query) {
                    $query->where('status', 'in', ['new', 'processing', 'waiting_customer', 'escalated']);
                }),
                'url' => (string)url('app/workbench/index'),
            ];
        }

        return $items;
    }

    protected function buildPrimarySummary(array $profile): array
    {
        $adminId = (int)($profile['admin_id'] ?? 0);
        $roleKey = (string)($profile['role_key'] ?? 'viewer');

        switch ($roleKey) {
            case 'finance':
                return array_values(array_filter([
                    $this->buildSummaryItem('待我审批', $this->countPendingApprovals($adminId), 'business/approval/index'),
                    $this->buildSummaryItem('待回款', $this->safeCount('finance_invoice', function ($query) {
                        $query->where('kind', 'receivable')->where('status', 'in', ['pending', 'partial', 'overdue']);
                    }), 'finance/workbench/index'),
                    $this->buildSummaryItem('待付款', $this->safeCount('business_payment_plan', function ($query) {
                        $query->where('status', 'in', ['pending', 'processing', 'overdue']);
                    }) + $this->safeCount('business_payment_request', function ($query) {
                        $query->where('status', 'pending_approval');
                    }), 'business/workbench/index'),
                    $this->buildSummaryItem('本月支出', $this->formatCurrency($this->sumAmount('finance_transaction', function ($query) {
                        $query->where('type', 'expense')->whereTime('transaction_date', 'month');
                    })), 'finance/transaction/index'),
                ]));

            case 'project':
                return array_values(array_filter([
                    $this->buildSummaryItem('我的任务', $this->safeCount('project_task', function ($query) use ($adminId) {
                        $query->where('assignee_admin_id', $adminId)->where('status', '<>', 'done');
                    }), 'project/task/index'),
                    $this->buildSummaryItem('高优任务', $this->safeCount('project_task', function ($query) use ($adminId) {
                        $query->where('assignee_admin_id', $adminId)->where('status', '<>', 'done')->where('priority', 'in', ['high', 'urgent']);
                    }), 'project/task/index'),
                    $this->buildSummaryItem('我负责项目', $this->safeCount('project', function ($query) use ($adminId) {
                        $query->where('owner_admin_id', $adminId)->where('status', 'in', ['planning', 'active', 'delivery', 'paused']);
                    }), 'project/project/index'),
                    $this->buildSummaryItem('待我审批', $this->countPendingApprovals($adminId), 'business/approval/index'),
                ]));

            case 'operations':
                return array_values(array_filter([
                    $this->buildSummaryItem('我负责客户', $this->safeCount('business_customer', function ($query) use ($adminId) {
                        $query->where('owner_admin_id', $adminId)->where('status', 'active');
                    }), 'business/customer/index'),
                    $this->buildSummaryItem('我的采购单', $this->safeCount('business_purchase_order', function ($query) use ($adminId) {
                        $query->where('owner_admin_id', $adminId)->where('status', 'in', ['draft', 'pending_approval', 'approved', 'processing']);
                    }), 'business/purchase_order/index'),
                    $this->buildSummaryItem('我的 APP', $this->safeCount('app_project', function ($query) use ($adminId) {
                        $query->where('manager_admin_id', $adminId)->where('status', 'in', ['planning', 'running', 'paused']);
                    }), 'app/project/index'),
                    $this->buildSummaryItem('待我审批', $this->countPendingApprovals($adminId), 'business/approval/index'),
                ]));

            case 'service':
                return array_values(array_filter([
                    $this->buildSummaryItem('我的问题', $this->safeCount('app_issue', function ($query) use ($adminId) {
                        $query->where('assignee_admin_id', $adminId)->where('status', 'in', ['new', 'processing', 'waiting_customer', 'escalated', 'resolved']);
                    }), 'app/issue/index'),
                    $this->buildSummaryItem('待客户回告', $this->safeCount('app_issue', function ($query) use ($adminId) {
                        $query->where('assignee_admin_id', $adminId)->where('status', 'waiting_customer');
                    }), 'app/issue/index'),
                    $this->buildSummaryItem('待关闭问题', $this->safeCount('app_issue', function ($query) use ($adminId) {
                        $query->where('assignee_admin_id', $adminId)->where('status', 'resolved');
                    }), 'app/issue/index'),
                    $this->buildSummaryItem('待我审批', $this->countPendingApprovals($adminId), 'business/approval/index'),
                ]));

            case 'tech':
                return array_values(array_filter([
                    $this->buildSummaryItem('我的研发单', $this->safeCount('app_tech_ticket', function ($query) use ($adminId) {
                        $query->where('owner_admin_id', $adminId)->where('status', 'in', ['pending', 'processing', 'testing', 'ready']);
                    }), 'app/tech_ticket/index'),
                    $this->buildSummaryItem('待测试', $this->safeCount('app_tech_ticket', function ($query) use ($adminId) {
                        $query->where('owner_admin_id', $adminId)->where('status', 'testing');
                    }), 'app/tech_ticket/index'),
                    $this->buildSummaryItem('待发版', $this->safeCount('app_release', function ($query) {
                        $query->where('status', 'in', ['planned', 'ready', 'testing']);
                    }), 'app/release/index'),
                    $this->buildSummaryItem('待我审批', $this->countPendingApprovals($adminId), 'business/approval/index'),
                ]));

            case 'admin':
            case 'viewer':
            default:
                return array_values(array_filter([
                    $this->buildSummaryItem('待我审批', $this->countPendingApprovals($adminId), 'business/approval/index'),
                    $this->buildSummaryItem('待回款', $this->safeCount('finance_invoice', function ($query) {
                        $query->where('kind', 'receivable')->where('status', 'in', ['pending', 'partial', 'overdue']);
                    }), 'finance/workbench/index'),
                    $this->buildSummaryItem('待付款', $this->safeCount('business_payment_plan', function ($query) {
                        $query->where('status', 'in', ['pending', 'processing', 'overdue']);
                    }) + $this->safeCount('business_payment_request', function ($query) {
                        $query->where('status', 'pending_approval');
                    }), 'business/workbench/index'),
                    $this->buildSummaryItem('待处理问题', $this->safeCount('app_issue', function ($query) {
                        $query->where('status', 'in', ['new', 'processing', 'waiting_customer', 'escalated']);
                    }), 'app/workbench/index'),
                ]));
        }
    }

    protected function buildTodayQueue(array $profile): array
    {
        $items = [];
        $adminId = (int)($profile['admin_id'] ?? 0);
        $roleKey = (string)($profile['role_key'] ?? 'viewer');

        $this->appendQueueItem($items, $this->buildMyApprovalTodo($adminId));

        switch ($roleKey) {
            case 'finance':
                $this->appendQueueItem($items, $this->buildReceivableTodo());
                $this->appendQueueItem($items, $this->buildPaymentTodo());
                break;

            case 'project':
                $this->appendQueueItem($items, $this->buildMyTaskTodo($adminId));
                $this->appendQueueItem($items, $this->buildMyProjectTodo($adminId));
                break;

            case 'operations':
                $this->appendQueueItem($items, $this->buildMyCustomerTodo($adminId));
                $this->appendQueueItem($items, $this->buildMyPurchaseTodo($adminId));
                $this->appendQueueItem($items, $this->buildMyAppProjectTodo($adminId));
                break;

            case 'service':
                $this->appendQueueItem($items, $this->buildMyIssueTodo($adminId));
                $this->appendQueueItem($items, $this->buildIssueTodo());
                break;

            case 'tech':
                $this->appendQueueItem($items, $this->buildMyTechTicketTodo($adminId));
                $this->appendQueueItem($items, $this->buildReleaseTodo());
                break;

            case 'admin':
            case 'viewer':
            default:
                $this->appendQueueItem($items, $this->buildReceivableTodo());
                $this->appendQueueItem($items, $this->buildPaymentTodo());
                $this->appendQueueItem($items, $this->buildProjectTodo());
                $this->appendQueueItem($items, $this->buildIssueTodo());
                break;
        }

        $this->appendQueueItem($items, $this->buildReceivableTodo());
        $this->appendQueueItem($items, $this->buildPaymentTodo());
        $this->appendQueueItem($items, $this->buildProjectTodo());
        $this->appendQueueItem($items, $this->buildIssueTodo());

        return array_slice(array_values($items), 0, 4);
    }

    protected function buildMyWorkPanels(array $profile): array
    {
        $adminId = (int)($profile['admin_id'] ?? 0);
        $roleKey = (string)($profile['role_key'] ?? 'viewer');
        $panels = [];

        $this->appendMyWorkPanel($panels, $this->buildApprovalPanel($adminId, $roleKey));
        $this->appendMyWorkPanel($panels, $this->buildCustomerPanel($adminId, $roleKey));
        $this->appendMyWorkPanel($panels, $this->buildTaskPanel($adminId, $roleKey));
        $this->appendMyWorkPanel($panels, $this->buildIssuePanel($adminId, $roleKey));

        return $panels;
    }

    protected function appendMyWorkPanel(array &$panels, ?array $panel): void
    {
        if (!$panel) {
            return;
        }

        $panels[] = $panel;
    }

    protected function buildApprovalPanel(int $adminId, string $roleKey): ?array
    {
        if (!$this->hasAccess('business/approval/index') || !$this->tableExists('business_approval')) {
            return null;
        }

        $count = $this->safeCount('business_approval', function ($query) use ($adminId) {
            $query->where('status', 'pending')->where('approver_admin_id', $adminId);
        });

        $rows = Db::name('business_approval')
            ->field('object_type,object_title,current_step_name,applicant_name,applied_at')
            ->where('status', 'pending')
            ->where('approver_admin_id', $adminId)
            ->order('applied_at', 'asc')
            ->order('id', 'asc')
            ->limit(3)
            ->select();

        if (!$rows && in_array($roleKey, ['admin', 'viewer'], true)) {
            $rows = Db::name('business_approval')
                ->field('object_type,object_title,current_step_name,applicant_name,applied_at')
                ->where('status', 'pending')
                ->order('applied_at', 'asc')
                ->order('id', 'asc')
                ->limit(3)
                ->select();
        }

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'title' => $row['object_title'] ?: '待审批事项',
                'meta' => implode(' · ', array_filter([
                    $this->mapApprovalType((string)$row['object_type']),
                    trim((string)$row['current_step_name']) ?: '当前节点',
                    trim((string)$row['applicant_name']) ? ('发起人 ' . trim((string)$row['applicant_name'])) : '',
                    trim((string)$row['applied_at']) ? date('m-d H:i', strtotime((string)$row['applied_at'])) : '',
                ])),
            ];
        }

        return [
            'title' => '我的审批',
            'count' => $count,
            'desc' => $count > 0 ? '今天先把分配给我的审批处理掉。' : '当前没有分配给我的审批，可直接查看审批中心。',
            'empty' => '当前没有需要我处理的审批。',
            'button' => '打开审批中心',
            'url' => $count > 0
                ? (string)url('business/approval/index', ['approver_admin_id' => $adminId, 'status' => 'pending'])
                : (string)url('business/approval/index'),
            'quick_links' => [
                [
                    'title' => '待审批',
                    'url' => $adminId > 0 && !in_array($roleKey, ['admin', 'viewer'], true)
                        ? (string)url('business/approval/index', ['approver_admin_id' => $adminId, 'status' => 'pending'])
                        : (string)url('business/approval/index', ['status' => 'pending']),
                ],
                [
                    'title' => '合同审批',
                    'url' => $adminId > 0 && !in_array($roleKey, ['admin', 'viewer'], true)
                        ? (string)url('business/approval/index', ['approver_admin_id' => $adminId, 'status' => 'pending', 'object_type' => 'contract'])
                        : (string)url('business/approval/index', ['status' => 'pending', 'object_type' => 'contract']),
                ],
                [
                    'title' => '付款审批',
                    'url' => $adminId > 0 && !in_array($roleKey, ['admin', 'viewer'], true)
                        ? (string)url('business/approval/index', ['approver_admin_id' => $adminId, 'status' => 'pending', 'object_type' => 'payment_request'])
                        : (string)url('business/approval/index', ['status' => 'pending', 'object_type' => 'payment_request']),
                ],
            ],
            'items' => $items,
        ];
    }

    protected function buildCustomerPanel(int $adminId, string $roleKey): ?array
    {
        if (!$this->hasAccess('business/customer/index') || !$this->tableExists('business_customer')) {
            return null;
        }

        $personalOnly = !in_array($roleKey, ['admin', 'finance', 'viewer'], true);
        $count = $this->safeCount('business_customer', function ($query) use ($adminId, $personalOnly) {
            $query->where('status', 'active');
            if ($personalOnly) {
                $query->where('owner_admin_id', $adminId);
            }
        });

        $query = Db::name('business_customer')
            ->field('company_name,owner,last_follow_up_at,stage')
            ->where('status', 'active');
        if ($personalOnly) {
            $query->where('owner_admin_id', $adminId);
        }

        $rows = $query
            ->orderRaw('ISNULL(last_follow_up_at) DESC, last_follow_up_at ASC')
            ->order('id', 'desc')
            ->limit(3)
            ->select();

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'title' => $row['company_name'] ?: '待跟进客户',
                'meta' => implode(' · ', array_filter([
                    $this->mapCustomerStage((string)$row['stage']),
                    trim((string)$row['owner']) ? ('负责人 ' . trim((string)$row['owner'])) : '',
                    trim((string)$row['last_follow_up_at']) ? ('最近跟进 ' . date('m-d H:i', strtotime((string)$row['last_follow_up_at']))) : '还没有跟进记录',
                ])),
            ];
        }

        return [
            'title' => $personalOnly ? '我的客户' : '客户跟进',
            'count' => $count,
            'desc' => $personalOnly ? '先跟进我负责的客户，再推进合同和采购。' : '这里看最近要推进的客户和跟进节奏。',
            'empty' => $personalOnly ? '当前没有分配给我的活跃客户。' : '当前没有需要优先跟进的客户。',
            'button' => '打开客户档案',
            'url' => $personalOnly
                ? (string)url('business/customer/index', ['owner_admin_id' => $adminId, 'status' => 'active'])
                : (string)url('business/customer/index'),
            'quick_links' => [
                [
                    'title' => $personalOnly ? '我的客户' : '客户档案',
                    'url' => $personalOnly
                        ? (string)url('business/customer/index', ['owner_admin_id' => $adminId, 'status' => 'active'])
                        : (string)url('business/customer/index', ['status' => 'active']),
                ],
                [
                    'title' => '待跟进',
                    'url' => $personalOnly
                        ? (string)url('business/customer_followup/index', ['owner_admin_id' => $adminId, 'status' => 'planned'])
                        : (string)url('business/customer_followup/index', ['status' => 'planned']),
                ],
                [
                    'title' => '本周跟进',
                    'url' => $personalOnly
                        ? (string)url('business/customer_followup/index', ['owner_admin_id' => $adminId, 'time_scope' => 'week'])
                        : (string)url('business/customer_followup/index', ['time_scope' => 'week']),
                ],
            ],
            'items' => $items,
        ];
    }

    protected function buildTaskPanel(int $adminId, string $roleKey): ?array
    {
        if (!$this->hasAccess('project/task/index') || !$this->tableExists('project_task')) {
            return null;
        }

        $personalOnly = !in_array($roleKey, ['admin', 'viewer'], true);
        $count = $this->safeCount('project_task', function ($query) use ($adminId, $personalOnly) {
            $query->where('status', '<>', 'done');
            if ($personalOnly) {
                $query->where('assignee_admin_id', $adminId);
            }
        });

        $query = Db::name('project_task')
            ->field('title,assignee,priority,status,due_date')
            ->where('status', '<>', 'done');
        if ($personalOnly) {
            $query->where('assignee_admin_id', $adminId);
        }

        $rows = $query
            ->order('due_date', 'asc')
            ->order('id', 'asc')
            ->limit(3)
            ->select();

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'title' => $row['title'] ?: '待处理任务',
                'meta' => implode(' · ', array_filter([
                    trim((string)$row['assignee']) ? ('负责人 ' . trim((string)$row['assignee'])) : '',
                    $this->mapTaskPriority((string)$row['priority']),
                    $this->mapProjectTaskStatus((string)$row['status']),
                    $this->formatDateTag('截止', $row['due_date']),
                ])),
            ];
        }

        return [
            'title' => $personalOnly ? '我的任务' : '任务跟进',
            'count' => $count,
            'desc' => $personalOnly ? '先清理我自己的任务，再回到完整项目工作台。' : '这里看今天最需要处理的项目任务。',
            'empty' => $personalOnly ? '当前没有分配给我的未完成任务。' : '当前没有需要优先推进的项目任务。',
            'button' => '打开任务清单',
            'url' => $personalOnly
                ? (string)url('project/task/index', ['assignee_admin_id' => $adminId])
                : (string)url('project/task/index'),
            'quick_links' => [
                [
                    'title' => $personalOnly ? '我的任务' : '任务清单',
                    'url' => $personalOnly
                        ? (string)url('project/task/index', ['assignee_admin_id' => $adminId])
                        : (string)url('project/task/index'),
                ],
                [
                    'title' => '进行中',
                    'url' => $personalOnly
                        ? (string)url('project/task/index', ['assignee_admin_id' => $adminId, 'status' => 'doing'])
                        : (string)url('project/task/index', ['status' => 'doing']),
                ],
                [
                    'title' => '已阻塞',
                    'url' => $personalOnly
                        ? (string)url('project/task/index', ['assignee_admin_id' => $adminId, 'status' => 'blocked'])
                        : (string)url('project/task/index', ['status' => 'blocked']),
                ],
            ],
            'items' => $items,
        ];
    }

    protected function buildIssuePanel(int $adminId, string $roleKey): ?array
    {
        if (!$this->hasAccess('app/issue/index') || !$this->tableExists('app_issue')) {
            return null;
        }

        $personalOnly = !in_array($roleKey, ['admin', 'viewer'], true);
        $count = $this->safeCount('app_issue', function ($query) use ($adminId, $personalOnly) {
            $query->where('status', 'in', ['new', 'processing', 'waiting_customer', 'escalated', 'resolved']);
            if ($personalOnly) {
                $query->where('assignee_admin_id', $adminId);
            }
        });

        $query = Db::name('app_issue')
            ->field('title,assignee,priority,status,resolve_due_at')
            ->where('status', 'in', ['new', 'processing', 'waiting_customer', 'escalated', 'resolved']);
        if ($personalOnly) {
            $query->where('assignee_admin_id', $adminId);
        }

        $rows = $query
            ->order('resolve_due_at', 'asc')
            ->order('id', 'asc')
            ->limit(3)
            ->select();

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'title' => $row['title'] ?: '待跟进问题',
                'meta' => implode(' · ', array_filter([
                    trim((string)$row['assignee']) ? ('负责人 ' . trim((string)$row['assignee'])) : '',
                    $this->mapIssueStatus((string)$row['status']),
                    $this->mapIssuePriority((string)$row['priority']),
                    $this->formatDateTag('承诺时间', $row['resolve_due_at']),
                ])),
            ];
        }

        return [
            'title' => $personalOnly ? '我的问题' : '问题跟进',
            'count' => $count,
            'desc' => $personalOnly ? '先处理自己负责的问题，再去完整 APP 工作台。' : '这里看当前最需要处理的 APP 问题。',
            'empty' => $personalOnly ? '当前没有分配给我的问题。' : '当前没有需要优先推进的问题。',
            'button' => '打开问题记录',
            'url' => $personalOnly
                ? (string)url('app/issue/index', ['assignee_admin_id' => $adminId])
                : (string)url('app/issue/index'),
            'quick_links' => [
                [
                    'title' => $personalOnly ? '我的问题' : '问题记录',
                    'url' => $personalOnly
                        ? (string)url('app/issue/index', ['assignee_admin_id' => $adminId])
                        : (string)url('app/issue/index'),
                ],
                [
                    'title' => '待客户确认',
                    'url' => $personalOnly
                        ? (string)url('app/issue/index', ['assignee_admin_id' => $adminId, 'status' => 'waiting_customer'])
                        : (string)url('app/issue/index', ['status' => 'waiting_customer']),
                ],
                [
                    'title' => '已升级',
                    'url' => $personalOnly
                        ? (string)url('app/issue/index', ['assignee_admin_id' => $adminId, 'status' => 'escalated'])
                        : (string)url('app/issue/index', ['status' => 'escalated']),
                ],
            ],
            'items' => $items,
        ];
    }

    protected function buildWorkbenchCards(array $profile = []): array
    {
        $cards = [];

        if ($this->hasAccess('finance/workbench/index')) {
            $cards[] = [
                'title' => '财务中心',
                'desc' => '先看回款、付款和台账，再处理智能记账和附件。',
                'primary_title' => '打开财务工作台',
                'primary_url' => (string)url('finance/workbench/index'),
                'links' => [
                    ['title' => '资金流水', 'url' => (string)url('finance/transaction/index')],
                    ['title' => '应收应付', 'url' => (string)url('finance/invoice/index')],
                    ['title' => '完整 AI 工作台', 'url' => $this->buildAiUrl(['focus' => 'finance'])],
                ],
            ];
        }

        if ($this->hasAccess('business/workbench/index')) {
            $cards[] = [
                'title' => '客户与采购',
                'desc' => '把客户、合同、审批、采购、付款申请收在一条业务线上。',
                'primary_title' => '打开采购工作台',
                'primary_url' => (string)url('business/workbench/index'),
                'links' => [
                    ['title' => '客户档案', 'url' => (string)url('business/customer/index')],
                    ['title' => '合同台账', 'url' => (string)url('business/contract/index')],
                    ['title' => '审批中心', 'url' => (string)url('business/approval/index')],
                    ['title' => '付款申请', 'url' => (string)url('business/payment_request/index')],
                ],
            ];
        }

        if ($this->hasAccess('project/workbench/index')) {
            $cards[] = [
                'title' => '项目交付',
                'desc' => '统一查看项目、任务、交付风险和负责人负荷。',
                'primary_title' => '打开项目工作台',
                'primary_url' => (string)url('project/workbench/index'),
                'links' => [
                    ['title' => '项目台账', 'url' => (string)url('project/project/index')],
                    ['title' => '任务清单', 'url' => (string)url('project/task/index')],
                    ['title' => 'AI 项目分析', 'url' => $this->buildAiUrl(['focus' => 'project'])],
                ],
            ];
        }

        if ($this->hasAccess('app/workbench/index')) {
            $cards[] = [
                'title' => 'APP 运营',
                'desc' => '问题记录、研发联动、发版和资料统一从这里进入。',
                'primary_title' => '打开 APP 工作台',
                'primary_url' => (string)url('app/workbench/index'),
                'links' => [
                    ['title' => '问题记录', 'url' => (string)url('app/issue/index')],
                    ['title' => '研发联动', 'url' => (string)url('app/tech_ticket/index')],
                    ['title' => '版本发布', 'url' => (string)url('app/release/index')],
                    ['title' => '内部资料', 'url' => (string)url('app/material/index')],
                ],
            ];
        }

        $roleKey = (string)($profile['role_key'] ?? 'admin');
        $sortMap = [
            'finance' => ['财务中心' => 1, '客户与采购' => 2, '项目交付' => 3, 'APP 运营' => 4],
            'project' => ['项目交付' => 1, '客户与采购' => 2, 'APP 运营' => 3, '财务中心' => 4],
            'operations' => ['客户与采购' => 1, 'APP 运营' => 2, '项目交付' => 3, '财务中心' => 4],
            'service' => ['APP 运营' => 1, '客户与采购' => 2, '项目交付' => 3, '财务中心' => 4],
            'tech' => ['APP 运营' => 1, '项目交付' => 2, '财务中心' => 3, '客户与采购' => 4],
            'viewer' => ['项目交付' => 1, 'APP 运营' => 2, '客户与采购' => 3, '财务中心' => 4],
            'admin' => ['客户与采购' => 1, '财务中心' => 2, '项目交付' => 3, 'APP 运营' => 4],
        ];
        $weights = $sortMap[$roleKey] ?? $sortMap['admin'];

        usort($cards, function ($left, $right) use ($weights) {
            $leftWeight = $weights[$left['title']] ?? 99;
            $rightWeight = $weights[$right['title']] ?? 99;
            return $leftWeight <=> $rightWeight;
        });

        return $cards;
    }

    protected function buildApprovalTodo(): ?array
    {
        if (!$this->tableExists('business_approval')) {
            return null;
        }

        $row = Db::name('business_approval')
            ->field('id,object_type,object_title,current_step_name,approver_name,applicant_name,applied_at')
            ->where('status', 'pending')
            ->order('applied_at', 'asc')
            ->order('id', 'asc')
            ->find();

        if (!$row) {
            return null;
        }

        return [
            'tag' => '审批待办',
            'title' => $row['object_title'] ?: '待审批事项',
            'meta' => implode(' · ', array_filter([
                $this->mapApprovalType((string)$row['object_type']),
                trim((string)$row['current_step_name']) ?: '当前节点未命名',
                trim((string)$row['approver_name']) ? ('审批人 ' . trim((string)$row['approver_name'])) : '',
                trim((string)$row['applied_at']) ? ('发起于 ' . date('m-d H:i', strtotime((string)$row['applied_at']))) : '',
            ])),
            'url' => (string)url('business/approval/index'),
            'button' => '去审批中心',
        ];
    }

    protected function buildReceivableTodo(): ?array
    {
        if (!$this->tableExists('finance_invoice')) {
            return null;
        }

        $row = Db::name('finance_invoice')
            ->field('id,title,counterparty,amount,due_date,status')
            ->where('kind', 'receivable')
            ->where('status', 'in', ['pending', 'partial', 'overdue'])
            ->order('due_date', 'asc')
            ->order('id', 'asc')
            ->find();

        if (!$row) {
            return null;
        }

        return [
            'tag' => '待回款',
            'title' => $row['title'] ?: '待回款单据',
            'meta' => implode(' · ', array_filter([
                trim((string)$row['counterparty']),
                '金额 ' . $this->formatCurrency((float)$row['amount']),
                $this->formatDateTag('到期', $row['due_date']),
            ])),
            'url' => (string)url('finance/workbench/index'),
            'button' => '去财务工作台',
        ];
    }

    protected function buildPaymentTodo(): ?array
    {
        if ($this->tableExists('business_payment_request')) {
            $request = Db::name('business_payment_request')
                ->field('id,title,supplier_name,request_amount,requested_at,owner')
                ->where('status', 'pending_approval')
                ->order('requested_at', 'asc')
                ->order('id', 'asc')
                ->find();

            if ($request) {
                return [
                    'tag' => '付款申请',
                    'title' => $request['title'] ?: '待审批付款申请',
                    'meta' => implode(' · ', array_filter([
                        trim((string)$request['supplier_name']),
                        '金额 ' . $this->formatCurrency((float)$request['request_amount']),
                        trim((string)$request['owner']) ? ('负责人 ' . trim((string)$request['owner'])) : '',
                    ])),
                    'url' => (string)url('business/payment_request/index'),
                    'button' => '去付款申请',
                ];
            }
        }

        if (!$this->tableExists('business_payment_plan')) {
            return null;
        }

        $row = Db::name('business_payment_plan')
            ->field('id,title,amount,due_date,owner,status')
            ->where('status', 'in', ['pending', 'processing', 'overdue'])
            ->order('due_date', 'asc')
            ->order('id', 'asc')
            ->find();

        if (!$row) {
            return null;
        }

        return [
            'tag' => '待付款',
            'title' => $row['title'] ?: '待付款计划',
            'meta' => implode(' · ', array_filter([
                trim((string)$row['owner']) ? ('负责人 ' . trim((string)$row['owner'])) : '',
                '金额 ' . $this->formatCurrency((float)$row['amount']),
                $this->formatDateTag('计划日', $row['due_date']),
            ])),
            'url' => (string)url('business/payment_plan/index'),
            'button' => '去付款计划',
        ];
    }

    protected function buildProjectTodo(): ?array
    {
        if (!$this->tableExists('project_task')) {
            return null;
        }

        $row = Db::name('project_task')
            ->field('id,title,assignee,status,priority,due_date')
            ->where('status', '<>', 'done')
            ->order('due_date', 'asc')
            ->order('id', 'asc')
            ->find();

        if (!$row) {
            return null;
        }

        return [
            'tag' => '项目任务',
            'title' => $row['title'] ?: '待处理任务',
            'meta' => implode(' · ', array_filter([
                trim((string)$row['assignee']) ? ('负责人 ' . trim((string)$row['assignee'])) : '',
                $this->mapTaskPriority((string)$row['priority']),
                $this->formatDateTag('截止', $row['due_date']),
            ])),
            'url' => (string)url('project/workbench/index'),
            'button' => '去项目工作台',
        ];
    }

    protected function buildIssueTodo(): ?array
    {
        if (!$this->tableExists('app_issue')) {
            return null;
        }

        $row = Db::name('app_issue')
            ->field('id,title,assignee,status,priority,resolve_due_at')
            ->where('status', 'in', ['new', 'processing', 'waiting_customer', 'escalated'])
            ->order('resolve_due_at', 'asc')
            ->order('id', 'asc')
            ->find();

        if (!$row) {
            return null;
        }

        return [
            'tag' => 'APP 问题',
            'title' => $row['title'] ?: '待处理问题',
            'meta' => implode(' · ', array_filter([
                trim((string)$row['assignee']) ? ('负责人 ' . trim((string)$row['assignee'])) : '',
                $this->mapIssuePriority((string)$row['priority']),
                $this->formatDateTag('承诺时间', $row['resolve_due_at']),
            ])),
            'url' => (string)url('app/workbench/index'),
            'button' => '去 APP 工作台',
        ];
    }

    protected function buildMyApprovalTodo(int $adminId): ?array
    {
        if ($adminId <= 0 || !$this->tableExists('business_approval')) {
            return null;
        }

        $row = Db::name('business_approval')
            ->field('id,object_type,object_title,current_step_name,applicant_name,applied_at')
            ->where('status', 'pending')
            ->where('approver_admin_id', $adminId)
            ->order('applied_at', 'asc')
            ->order('id', 'asc')
            ->find();

        if (!$row) {
            return null;
        }

        return [
            'tag' => '待我审批',
            'title' => $row['object_title'] ?: '待审批事项',
            'meta' => implode(' · ', array_filter([
                $this->mapApprovalType((string)$row['object_type']),
                trim((string)$row['current_step_name']) ?: '当前审批节点',
                trim((string)$row['applicant_name']) ? ('发起人 ' . trim((string)$row['applicant_name'])) : '',
                trim((string)$row['applied_at']) ? ('提交于 ' . date('m-d H:i', strtotime((string)$row['applied_at']))) : '',
            ])),
            'url' => (string)url('business/approval/index'),
            'button' => '去审批中心',
        ];
    }

    protected function buildMyTaskTodo(int $adminId): ?array
    {
        if ($adminId <= 0 || !$this->tableExists('project_task')) {
            return null;
        }

        $row = Db::name('project_task')
            ->field('id,title,priority,due_date,status')
            ->where('assignee_admin_id', $adminId)
            ->where('status', '<>', 'done')
            ->order('due_date', 'asc')
            ->order('id', 'asc')
            ->find();

        if (!$row) {
            return null;
        }

        return [
            'tag' => '我的任务',
            'title' => $row['title'] ?: '待处理任务',
            'meta' => implode(' · ', array_filter([
                $this->mapTaskPriority((string)$row['priority']),
                $this->formatDateTag('截止', $row['due_date']),
            ])),
            'url' => (string)url('project/task/index'),
            'button' => '去任务清单',
        ];
    }

    protected function buildMyProjectTodo(int $adminId): ?array
    {
        if ($adminId <= 0 || !$this->tableExists('project')) {
            return null;
        }

        $row = Db::name('project')
            ->field('id,name,status,due_date')
            ->where('owner_admin_id', $adminId)
            ->where('status', 'in', ['planning', 'active', 'delivery', 'paused'])
            ->order('due_date', 'asc')
            ->order('id', 'asc')
            ->find();

        if (!$row) {
            return null;
        }

        return [
            'tag' => '我负责项目',
            'title' => $row['name'] ?: '负责中的项目',
            'meta' => implode(' · ', array_filter([
                $this->mapProjectStatus((string)$row['status']),
                $this->formatDateTag('交付日', $row['due_date']),
            ])),
            'url' => (string)url('project/project/index'),
            'button' => '去项目台账',
        ];
    }

    protected function buildMyIssueTodo(int $adminId): ?array
    {
        if ($adminId <= 0 || !$this->tableExists('app_issue')) {
            return null;
        }

        $row = Db::name('app_issue')
            ->field('id,title,status,priority,resolve_due_at')
            ->where('assignee_admin_id', $adminId)
            ->where('status', 'in', ['new', 'processing', 'waiting_customer', 'escalated', 'resolved'])
            ->order('resolve_due_at', 'asc')
            ->order('id', 'asc')
            ->find();

        if (!$row) {
            return null;
        }

        return [
            'tag' => '我的问题',
            'title' => $row['title'] ?: '待跟进问题',
            'meta' => implode(' · ', array_filter([
                $this->mapIssueStatus((string)$row['status']),
                $this->mapIssuePriority((string)$row['priority']),
                $this->formatDateTag('承诺时间', $row['resolve_due_at']),
            ])),
            'url' => (string)url('app/issue/index'),
            'button' => '去问题记录',
        ];
    }

    protected function buildMyTechTicketTodo(int $adminId): ?array
    {
        if ($adminId <= 0 || !$this->tableExists('app_tech_ticket')) {
            return null;
        }

        $row = Db::name('app_tech_ticket')
            ->field('id,title,status,priority,due_date,type')
            ->where('owner_admin_id', $adminId)
            ->where('status', 'in', ['pending', 'processing', 'testing', 'ready'])
            ->order('due_date', 'asc')
            ->order('id', 'asc')
            ->find();

        if (!$row) {
            return null;
        }

        return [
            'tag' => '我的研发单',
            'title' => $row['title'] ?: '待处理研发单',
            'meta' => implode(' · ', array_filter([
                $this->mapTechTicketType((string)$row['type']),
                $this->mapTechTicketStatus((string)$row['status']),
                $this->formatDateTag('截止', $row['due_date']),
            ])),
            'url' => (string)url('app/tech_ticket/index'),
            'button' => '去研发联动',
        ];
    }

    protected function buildMyCustomerTodo(int $adminId): ?array
    {
        if ($adminId <= 0 || !$this->tableExists('business_customer')) {
            return null;
        }

        $row = Db::name('business_customer')
            ->field('id,company_name,status,last_follow_up_at')
            ->where('owner_admin_id', $adminId)
            ->where('status', 'active')
            ->orderRaw('ISNULL(last_follow_up_at) DESC, last_follow_up_at ASC')
            ->order('id', 'asc')
            ->find();

        if (!$row) {
            return null;
        }

        return [
            'tag' => '我的客户',
            'title' => $row['company_name'] ?: '待跟进客户',
            'meta' => trim((string)$row['last_follow_up_at'])
                ? '最近跟进 ' . date('m-d H:i', strtotime((string)$row['last_follow_up_at']))
                : '还没有跟进记录',
            'url' => (string)url('business/customer/index'),
            'button' => '去客户档案',
        ];
    }

    protected function buildMyPurchaseTodo(int $adminId): ?array
    {
        if ($adminId <= 0 || !$this->tableExists('business_purchase_order')) {
            return null;
        }

        $row = Db::name('business_purchase_order')
            ->field('id,title,status,order_amount,supplier_name')
            ->where('owner_admin_id', $adminId)
            ->where('status', 'in', ['draft', 'pending_approval', 'approved', 'processing'])
            ->order('id', 'desc')
            ->find();

        if (!$row) {
            return null;
        }

        return [
            'tag' => '我的采购单',
            'title' => $row['title'] ?: '待推进采购单',
            'meta' => implode(' · ', array_filter([
                trim((string)$row['supplier_name']),
                '金额 ' . $this->formatCurrency((float)$row['order_amount']),
            ])),
            'url' => (string)url('business/purchase_order/index'),
            'button' => '去采购单',
        ];
    }

    protected function buildMyAppProjectTodo(int $adminId): ?array
    {
        if ($adminId <= 0 || !$this->tableExists('app_project')) {
            return null;
        }

        $row = Db::name('app_project')
            ->field('id,name,status,app_version,lifecycle_stage')
            ->where('manager_admin_id', $adminId)
            ->where('status', 'in', ['planning', 'running', 'paused'])
            ->order('id', 'desc')
            ->find();

        if (!$row) {
            return null;
        }

        return [
            'tag' => '我的 APP',
            'title' => $row['name'] ?: '负责中的 APP',
            'meta' => implode(' · ', array_filter([
                trim((string)$row['app_version']) ? ('版本 ' . trim((string)$row['app_version'])) : '',
                $this->mapAppLifecycle((string)$row['lifecycle_stage']),
            ])),
            'url' => (string)url('app/project/index'),
            'button' => '去 APP 台账',
        ];
    }

    protected function buildReleaseTodo(): ?array
    {
        if (!$this->tableExists('app_release')) {
            return null;
        }

        $row = Db::name('app_release')
            ->field('id,title,status,release_version,planned_release_at,owner')
            ->where('status', 'in', ['planned', 'ready', 'testing'])
            ->order('planned_release_at', 'asc')
            ->order('id', 'asc')
            ->find();

        if (!$row) {
            return null;
        }

        return [
            'tag' => '待发版',
            'title' => $row['title'] ?: '待发布版本',
            'meta' => implode(' · ', array_filter([
                trim((string)$row['release_version']) ? ('版本 ' . trim((string)$row['release_version'])) : '',
                trim((string)$row['owner']) ? ('负责人 ' . trim((string)$row['owner'])) : '',
                $this->formatDateTag('计划发版', $row['planned_release_at']),
            ])),
            'url' => (string)url('app/release/index'),
            'button' => '去版本发布',
        ];
    }

    protected function appendQueueItem(array &$items, ?array $item): void
    {
        if (!$item) {
            return;
        }

        $key = md5(($item['tag'] ?? '') . '|' . ($item['title'] ?? '') . '|' . ($item['url'] ?? ''));
        if (!isset($items[$key])) {
            $items[$key] = $item;
        }
    }

    protected function buildSummaryItem(string $title, $value, string $rule): ?array
    {
        if (!$this->hasAccess($rule)) {
            return null;
        }

        return [
            'title' => $title,
            'value' => $value,
            'url' => (string)url($rule),
        ];
    }

    protected function countPendingApprovals(int $adminId): int
    {
        return $this->safeCount('business_approval', function ($query) use ($adminId) {
            $query->where('status', 'pending')->where('approver_admin_id', $adminId);
        });
    }

    protected function getCurrentUserProfile(): array
    {
        $adminId = (int)$this->auth->id;
        $info = $this->auth->getUserInfo($adminId);
        $profile = Db::name('staff_profile')
            ->field('admin_id,account,name,title,department,role_key')
            ->where('admin_id', $adminId)
            ->find();

        return [
            'admin_id' => $adminId,
            'account' => $profile['account'] ?? ($info['username'] ?? ''),
            'name' => $profile['name'] ?? ($info['nickname'] ?? ($info['username'] ?? '系统用户')),
            'title' => $profile['title'] ?? '系统角色',
            'department' => $profile['department'] ?? '默认部门',
            'role_key' => $profile['role_key'] ?? 'viewer',
        ];
    }

    protected function mapRoleLabel(string $roleKey): string
    {
        $map = [
            'admin' => '系统管理员',
            'finance' => '财务岗位',
            'project' => '项目岗位',
            'operations' => '运营岗位',
            'service' => '客服岗位',
            'tech' => '技术岗位',
            'viewer' => '观察岗位',
        ];

        return $map[$roleKey] ?? '业务岗位';
    }

    protected function buildRoleTip(string $roleKey): string
    {
        $map = [
            'admin' => '你看到的是全局经营视角，优先处理审批、资金和关键问题。',
            'finance' => '先看待回款、待付款和审批，再处理台账与智能记账。',
            'project' => '先清理自己负责的任务和项目风险，再补项目台账。',
            'operations' => '先推进客户和采购，再看 APP 运营和版本节奏。',
            'service' => '先处理自己负责的问题和客户回告，再同步研发或客户。',
            'tech' => '先处理研发联动和测试待办，再关注发版风险。',
            'viewer' => '先看你自己的项目、任务和 APP，再进入完整工作台。',
        ];

        return $map[$roleKey] ?? '先看今天的待办，再进入对应工作台处理。';
    }

    protected function buildUserQuickLinks(string $roleKey): array
    {
        $map = [
            'admin' => ['business/approval/index', 'finance/workbench/index', 'ai/conversation/index'],
            'finance' => ['finance/workbench/index', 'business/approval/index', 'ai/conversation/index'],
            'project' => ['project/workbench/index', 'project/task/index', 'ai/conversation/index'],
            'operations' => ['business/workbench/index', 'app/workbench/index', 'ai/conversation/index'],
            'service' => ['app/workbench/index', 'app/issue/index', 'ai/conversation/index'],
            'tech' => ['app/tech_ticket/index', 'app/release/index', 'ai/conversation/index'],
            'viewer' => ['project/project/index', 'app/project/index', 'ai/conversation/index'],
        ];

        $titles = [
            'business/approval/index' => '审批中心',
            'finance/workbench/index' => '财务工作台',
            'project/workbench/index' => '项目工作台',
            'project/task/index' => '任务清单',
            'business/workbench/index' => '采购工作台',
            'app/workbench/index' => 'APP 工作台',
            'app/issue/index' => '问题记录',
            'app/tech_ticket/index' => '研发联动',
            'app/release/index' => '版本发布',
            'project/project/index' => '项目台账',
            'app/project/index' => 'APP 台账',
            'ai/conversation/index' => 'AI 工作台',
        ];

        $links = [];
        foreach (($map[$roleKey] ?? $map['admin']) as $rule) {
            if ($this->hasAccess($rule)) {
                $links[] = [
                    'title' => $titles[$rule] ?? '工作台入口',
                    'url' => (string)url($rule),
                ];
            }
        }

        return $links;
    }

    protected function getDefaultAiSetting(): ?array
    {
        if (!$this->tableExists('ai_setting')) {
            return null;
        }

        $rows = Db::name('ai_setting')->order('id', 'desc')->select();
        if (!$rows) {
            return null;
        }

        foreach ($rows as $row) {
            $meta = json_decode((string)($row['workspace_json'] ?? ''), true);
            if (is_array($meta) && !empty($meta['is_default'])) {
                return $row;
            }
        }

        return $rows[0];
    }

    protected function buildAiUrl(array $params = []): string
    {
        return (string)url('ai/conversation/index', $params);
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

    protected function sumAmount(string $table, ?callable $callback = null, string $field = 'amount'): float
    {
        if (!$this->tableExists($table)) {
            return 0.0;
        }

        $query = Db::name($table);
        if ($callback) {
            $callback($query);
        }

        return (float)$query->sum($field);
    }

    protected function formatCurrency(float $value): string
    {
        return '￥' . number_format($value, 2);
    }

    protected function formatMoney(float $value): string
    {
        return '￥' . number_format($value, 2);
    }

    protected function formatDateTag(string $prefix, $value): string
    {
        if (!$value) {
            return '';
        }

        $text = is_numeric($value)
            ? date('m-d', (int)$value)
            : date('m-d', strtotime((string)$value));

        return $prefix . ' ' . $text;
    }

    protected function mapApprovalType(string $type): string
    {
        $map = [
            'contract' => '合同审批',
            'payment_plan' => '付款审批',
            'expense_request' => '费用审批',
            'purchase_order' => '采购审批',
            'payment_request' => '付款申请审批',
        ];

        return $map[$type] ?? '业务审批';
    }

    protected function mapCustomerStage(string $stage): string
    {
        $map = [
            'lead' => '线索期',
            'proposal' => '方案期',
            'contracted' => '已签约',
            'delivery' => '交付中',
            'repeat' => '复购中',
            'lost' => '已流失',
        ];

        return $map[$stage] ?? '客户跟进中';
    }

    protected function mapTaskPriority(string $priority): string
    {
        $map = [
            'low' => '低优先级',
            'medium' => '中优先级',
            'high' => '高优先级',
            'urgent' => '紧急任务',
        ];

        return $map[$priority] ?? '普通优先级';
    }

    protected function mapProjectTaskStatus(string $status): string
    {
        $map = [
            'pending' => '待开始',
            'doing' => '进行中',
            'blocked' => '已阻塞',
            'testing' => '待验收',
            'done' => '已完成',
            'paused' => '已暂停',
        ];

        return $map[$status] ?? '任务处理中';
    }

    protected function mapIssuePriority(string $priority): string
    {
        $map = [
            'low' => '低优先级',
            'medium' => '中优先级',
            'high' => '高优先级',
            'urgent' => '紧急问题',
        ];

        return $map[$priority] ?? '普通优先级';
    }

    protected function mapIssueStatus(string $status): string
    {
        $map = [
            'new' => '新建',
            'processing' => '处理中',
            'waiting_customer' => '待客户回告',
            'escalated' => '已升级',
            'resolved' => '已解决待关闭',
            'closed' => '已关闭',
        ];

        return $map[$status] ?? '问题处理中';
    }

    protected function mapProjectStatus(string $status): string
    {
        $map = [
            'planning' => '规划中',
            'active' => '进行中',
            'delivery' => '交付中',
            'completed' => '已完成',
            'paused' => '已暂停',
            'closed' => '已关闭',
        ];

        return $map[$status] ?? '项目进行中';
    }

    protected function mapTechTicketType(string $type): string
    {
        $map = [
            'bug' => 'Bug',
            'improvement' => '优化',
            'upgrade' => '升级',
            'task' => '任务',
        ];

        return $map[$type] ?? '研发事项';
    }

    protected function mapTechTicketStatus(string $status): string
    {
        $map = [
            'pending' => '待处理',
            'processing' => '处理中',
            'testing' => '待测试',
            'ready' => '待发版',
            'done' => '已完成',
            'closed' => '已关闭',
        ];

        return $map[$status] ?? '研发处理中';
    }

    protected function mapAppLifecycle(string $stage): string
    {
        $map = [
            'idea' => '想法期',
            'validation' => '验证期',
            'launch' => '上线期',
            'growth' => '增长期',
            'retention' => '留存期',
            'mature' => '成熟期',
            'sunset' => '退场期',
        ];

        return $map[$stage] ?? '生命周期未设置';
    }

    protected function hasAccess(string $rule): bool
    {
        if (!$this->auth || !method_exists($this->auth, 'check')) {
            return true;
        }

        return (bool)$this->auth->check($rule);
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
