<?php

declare(strict_types=1);

function console_module_labels(): array
{
    return [
        'dashboard' => '经营驾驶舱',
        'finance' => '财务中心',
        'projects' => '项目交付',
        'operations' => 'APP 运营',
        'team' => '人员权限',
        'ai' => 'AI 助手',
    ];
}

function console_module_label(string $module): string
{
    return (string) (console_module_labels()[$module] ?? $module);
}

function permission_group_definitions(): array
{
    return [
        'dashboard' => [
            'label' => '经营驾驶舱',
            'description' => '只看汇总、提醒和风险，不在这里录入业务。',
            'module' => 'dashboard',
            'permissions' => [
                'dashboard.view' => ['label' => '查看经营驾驶舱'],
            ],
        ],
        'finance' => [
            'label' => '财务中心',
            'description' => '管理收支、应收应付、凭证附件和智能记账。',
            'module' => 'finance',
            'permissions' => [
                'finance.view' => ['label' => '查看财务中心'],
                'finance.edit' => ['label' => '新增和编辑财务记录'],
            ],
        ],
        'projects' => [
            'label' => '项目交付',
            'description' => '管理项目进度、任务分派、交付风险和资源负荷。',
            'module' => 'projects',
            'permissions' => [
                'projects.view' => ['label' => '查看项目交付'],
                'projects.edit' => ['label' => '新增和编辑项目任务'],
            ],
        ],
        'operations' => [
            'label' => 'APP 运营与协同',
            'description' => '负责 APP 生命周期、问题记录、版本发布、资料中心和研发联动。',
            'module' => 'operations',
            'permissions' => [
                'operations.view' => ['label' => '查看 APP 运营'],
                'operations.edit' => ['label' => '新增和编辑 APP 运营'],
                'service.view' => ['label' => '查看问题记录'],
                'service.edit' => ['label' => '新增和编辑问题记录'],
                'tech.view' => ['label' => '查看研发联动'],
                'tech.edit' => ['label' => '新增和编辑研发联动'],
            ],
        ],
        'ai' => [
            'label' => 'AI 助手',
            'description' => '使用 AI 分析经营数据，并配置兼容 OpenAI 协议的模型。',
            'module' => 'ai',
            'permissions' => [
                'ai.use' => ['label' => '使用 AI 助手'],
                'ai.manage' => ['label' => '管理 AI 配置'],
            ],
        ],
        'staff' => [
            'label' => '人员与系统权限',
            'description' => '管理员工账号、权限组、身份切换和操作日志。',
            'module' => 'team',
            'permissions' => [
                'audit.view' => ['label' => '查看操作日志'],
                'staff.manage' => ['label' => '管理员工、角色和权限'],
            ],
        ],
    ];
}

function permission_group_rows(): array
{
    $rows = [];

    foreach (permission_group_definitions() as $key => $definition) {
        $permissions = [];

        foreach (($definition['permissions'] ?? []) as $value => $permission) {
            $permissions[] = [
                'value' => $value,
                'label' => (string) ($permission['label'] ?? $value),
                'group' => $key,
                'group_label' => (string) ($definition['label'] ?? $key),
                'module' => (string) ($definition['module'] ?? ''),
                'module_label' => console_module_label((string) ($definition['module'] ?? '')),
            ];
        }

        $rows[] = [
            'value' => $key,
            'key' => $key,
            'label' => (string) ($definition['label'] ?? $key),
            'description' => (string) ($definition['description'] ?? ''),
            'module' => (string) ($definition['module'] ?? ''),
            'module_label' => console_module_label((string) ($definition['module'] ?? '')),
            'permissions' => $permissions,
            'permission_count' => count($permissions),
        ];
    }

    return $rows;
}

function permission_catalog(): array
{
    $catalog = [];

    foreach (permission_group_definitions() as $group => $definition) {
        foreach (($definition['permissions'] ?? []) as $value => $permission) {
            $catalog[$value] = [
                'label' => (string) ($permission['label'] ?? $value),
                'module' => (string) ($definition['module'] ?? ''),
                'module_label' => console_module_label((string) ($definition['module'] ?? '')),
                'group' => $group,
                'group_label' => (string) ($definition['label'] ?? $group),
                'group_description' => (string) ($definition['description'] ?? ''),
            ];
        }
    }

    return $catalog;
}

function role_group_definitions(): array
{
    return [
        'system' => [
            'label' => '系统管理组',
            'description' => '负责账号、权限组、日志和系统配置。',
        ],
        'finance' => [
            'label' => '财务权限组',
            'description' => '负责资金、单据、附件和回款付款跟进。',
        ],
        'delivery' => [
            'label' => '项目交付组',
            'description' => '负责项目进度、任务推进和交付风险。',
        ],
        'operations' => [
            'label' => 'APP 运营组',
            'description' => '负责 APP 生命周期、问题闭环、版本发布和资料维护。',
        ],
        'support' => [
            'label' => '客服反馈组',
            'description' => '负责收集反馈、记录问题并推动处理结果回告。',
        ],
        'technology' => [
            'label' => '研发协同组',
            'description' => '负责 Bug、升级需求、发版验证和技术协同。',
        ],
        'readonly' => [
            'label' => '只读查看组',
            'description' => '只查看经营和业务信息，不进行录入和修改。',
        ],
    ];
}

function role_group_label(string $group): string
{
    $definitions = role_group_definitions();

    return (string) ($definitions[$group]['label'] ?? '未分类权限组');
}

function role_group_description(string $group): string
{
    $definitions = role_group_definitions();

    return (string) ($definitions[$group]['description'] ?? '');
}

function role_definitions(): array
{
    return [
        'admin' => [
            'label' => '系统管理员',
            'group' => 'system',
            'home_module' => 'team',
            'summary' => '负责人员、权限、日志、AI 配置和系统治理。',
            'guide_steps' => [
                '先在人员权限里维护账号、角色和岗位信息。',
                '新增员工优先按权限组分配，再补充少量额外权限。',
                '发生争议时回到操作日志核对是谁改过数据。',
            ],
            'permissions' => ['*'],
        ],
        'finance' => [
            'label' => '财务人员',
            'group' => 'finance',
            'home_module' => 'finance',
            'summary' => '只管资金和单据，其他模块主要用于协同查看。',
            'guide_steps' => [
                '先记流水，再记应收应付，最后补附件。',
                '涉及项目或 APP 的背景信息，只在对应台账查看，不在财务页重复录入。',
                '每天先看待办和逾期单据，再处理新增记录。',
            ],
            'permissions' => [
                'dashboard.view',
                'finance.view',
                'finance.edit',
                'projects.view',
                'operations.view',
                'service.view',
                'tech.view',
                'ai.use',
                'audit.view',
            ],
        ],
        'project' => [
            'label' => '项目经理',
            'group' => 'delivery',
            'home_module' => 'projects',
            'summary' => '围绕项目状态、任务推进和交付风险开展工作。',
            'guide_steps' => [
                '先看逾期任务和高风险项目，再更新项目状态。',
                '任务变更一定落到任务台账，不要只写在群里。',
                '需要 APP 侧联动时再进入 APP 运营模块查看问题和版本信息。',
            ],
            'permissions' => [
                'dashboard.view',
                'projects.view',
                'projects.edit',
                'operations.view',
                'service.view',
                'tech.view',
                'ai.use',
            ],
        ],
        'operations' => [
            'label' => '运营负责人',
            'group' => 'operations',
            'home_module' => 'operations',
            'summary' => '围绕 APP 生命周期、问题闭环、资料和版本发布推进工作。',
            'guide_steps' => [
                '先记问题，再挂研发联动，最后回写版本和回告结果。',
                '内部资料统一放资料中心，不要散落在聊天工具里。',
                '需要跨部门协同时，只保留一个主问题单避免重复记录。',
            ],
            'permissions' => [
                'dashboard.view',
                'operations.view',
                'operations.edit',
                'service.view',
                'service.edit',
                'projects.view',
                'tech.view',
                'ai.use',
            ],
        ],
        'service' => [
            'label' => '客服负责人',
            'group' => 'support',
            'home_module' => 'operations',
            'summary' => '负责接收用户、领导和内部反馈，并跟踪回告结果。',
            'guide_steps' => [
                '所有反馈先记问题记录，再决定是否需要研发处理。',
                '每次回告都写明对象、方式和结果，避免口头闭环。',
                '需要查资料时优先从资料中心下载，不要自己留本地旧版本。',
            ],
            'permissions' => [
                'dashboard.view',
                'operations.view',
                'service.view',
                'service.edit',
                'tech.view',
                'ai.use',
            ],
        ],
        'tech' => [
            'label' => '技术负责人',
            'group' => 'technology',
            'home_module' => 'operations',
            'summary' => '围绕 Bug、升级需求、版本发布和研发待办协同工作。',
            'guide_steps' => [
                '先看研发联动和待发布版本，再处理具体 Bug 或升级项。',
                '技术处理结果要写回问题单或版本记录，不要停留在口头同步。',
                '影响交付时同步项目交付页的风险和任务状态。',
            ],
            'permissions' => [
                'dashboard.view',
                'projects.view',
                'operations.view',
                'service.view',
                'tech.view',
                'tech.edit',
                'ai.use',
            ],
        ],
        'viewer' => [
            'label' => '只读人员',
            'group' => 'readonly',
            'home_module' => 'dashboard',
            'summary' => '只查看业务进展和经营结果，不参与录入。',
            'guide_steps' => [
                '默认从经营驾驶舱进入，按需跳到对应业务模块查看。',
                '发现问题后通知对应负责人，不要直接在系统里修改业务数据。',
                '使用 AI 助手前先确认问题范围，避免无关提问过多。',
            ],
            'permissions' => [
                'dashboard.view',
                'finance.view',
                'projects.view',
                'operations.view',
                'service.view',
                'tech.view',
                'ai.use',
            ],
        ],
    ];
}

function role_label(string $role): string
{
    $definitions = role_definitions();

    return (string) ($definitions[$role]['label'] ?? '未知角色');
}

function role_summary(string $role): string
{
    $definitions = role_definitions();

    return (string) ($definitions[$role]['summary'] ?? '');
}

function role_home_module(string $role): string
{
    $definitions = role_definitions();

    return (string) ($definitions[$role]['home_module'] ?? 'dashboard');
}

function role_guide_steps(string $role): array
{
    $definitions = role_definitions();

    return auth_string_array($definitions[$role]['guide_steps'] ?? []);
}

function role_options(): array
{
    $rows = [];

    foreach (role_definitions() as $role => $definition) {
        $rows[$role] = (string) ($definition['label'] ?? $role);
    }

    return $rows;
}

function role_permission_groups(array $permissions): array
{
    $definitions = permission_group_definitions();
    $catalog = permission_catalog();
    $wildcard = in_array('*', $permissions, true);
    $rows = [];

    foreach ($definitions as $group => $definition) {
        $groupPermissions = [];

        foreach (($definition['permissions'] ?? []) as $value => $permission) {
            if (!$wildcard && !in_array($value, $permissions, true)) {
                continue;
            }

            $groupPermissions[] = [
                'value' => $value,
                'label' => (string) ($catalog[$value]['label'] ?? $value),
            ];
        }

        if ($groupPermissions === []) {
            continue;
        }

        $rows[] = [
            'value' => $group,
            'key' => $group,
            'label' => (string) ($definition['label'] ?? $group),
            'description' => (string) ($definition['description'] ?? ''),
            'module' => (string) ($definition['module'] ?? ''),
            'module_label' => console_module_label((string) ($definition['module'] ?? '')),
            'permissions' => $groupPermissions,
            'permission_count' => count($groupPermissions),
        ];
    }

    return $rows;
}

function role_rows(array $data): array
{
    $counts = [];

    foreach ($data['users'] ?? [] as $user) {
        $role = (string) ($user['role'] ?? 'viewer');
        $status = (string) ($user['status'] ?? 'active');

        if ($status !== 'active') {
            continue;
        }

        $counts[$role] = (int) ($counts[$role] ?? 0) + 1;
    }

    $rows = [];

    foreach (role_definitions() as $role => $definition) {
        $permissions = role_permissions($role);
        $group = (string) ($definition['group'] ?? 'readonly');

        $rows[] = [
            'value' => $role,
            'label' => (string) ($definition['label'] ?? $role),
            'group' => $group,
            'group_label' => role_group_label($group),
            'group_description' => role_group_description($group),
            'home_module' => role_home_module($role),
            'home_module_label' => console_module_label(role_home_module($role)),
            'summary' => role_summary($role),
            'guide_steps' => role_guide_steps($role),
            'member_count' => (int) ($counts[$role] ?? 0),
            'permissions' => $permissions,
            'permission_labels' => permission_labels($permissions),
            'permission_groups' => role_permission_groups($permissions),
            'permission_group_count' => count(role_permission_groups($permissions)),
        ];
    }

    return $rows;
}

function user_status_options(): array
{
    return [
        'active' => '在岗',
        'inactive' => '停用',
    ];
}

function user_status_label(string $status): string
{
    return (string) (user_status_options()[$status] ?? '未知状态');
}

function user_status_tone(string $status): string
{
    return [
        'active' => 'success',
        'inactive' => 'danger',
    ][$status] ?? 'neutral';
}

function permission_label(string $permission): string
{
    return (string) (permission_catalog()[$permission]['label'] ?? $permission);
}

function permission_rows(): array
{
    $rows = [];

    foreach (permission_catalog() as $value => $definition) {
        $rows[] = [
            'value' => $value,
            'label' => (string) ($definition['label'] ?? $value),
            'module' => (string) ($definition['module'] ?? ''),
            'module_label' => (string) ($definition['module_label'] ?? ''),
            'group' => (string) ($definition['group'] ?? ''),
            'group_label' => (string) ($definition['group_label'] ?? ''),
            'group_description' => (string) ($definition['group_description'] ?? ''),
        ];
    }

    return $rows;
}

function permission_labels(array $permissions): array
{
    if (in_array('*', $permissions, true)) {
        return ['全部权限'];
    }

    $labels = [];

    foreach ($permissions as $permission) {
        $labels[] = permission_label((string) $permission);
    }

    return $labels;
}

function role_permissions(string $role): array
{
    $definitions = role_definitions();
    $definition = $definitions[$role] ?? $definitions['viewer'];
    $permissions = auth_string_array($definition['permissions'] ?? []);

    if (in_array('*', $permissions, true)) {
        return ['*'];
    }

    $catalog = array_keys(permission_catalog());

    return array_values(array_intersect($permissions, $catalog));
}

function auth_string_array(mixed $value): array
{
    if (is_string($value)) {
        $value = array_map('trim', explode(',', $value));
    }

    if (!is_array($value)) {
        return [];
    }

    $rows = [];

    foreach ($value as $item) {
        $string = trim((string) $item);

        if ($string !== '') {
            $rows[] = $string;
        }
    }

    return array_values(array_unique($rows));
}

function normalize_login_identity(string $value): string
{
    $value = trim($value);

    if ($value === '') {
        return '';
    }

    return function_exists('mb_strtolower')
        ? mb_strtolower($value, 'UTF-8')
        : strtolower($value);
}

function default_initial_password(): string
{
    return 'Start@123';
}

function hash_user_password(string $password): string
{
    $hash = password_hash($password, PASSWORD_DEFAULT);

    if (!is_string($hash) || $hash === '') {
        throw new RuntimeException('密码加密失败');
    }

    return $hash;
}

function verify_user_password(array $user, string $password): bool
{
    $hash = (string) ($user['password_hash'] ?? '');

    if ($hash === '' || $password === '') {
        return false;
    }

    return password_verify($password, $hash);
}

function user_lookup(array $users): array
{
    $lookup = [];

    foreach ($users as $user) {
        $id = (string) ($user['id'] ?? '');

        if ($id === '') {
            continue;
        }

        $lookup[$id] = $user;
    }

    return $lookup;
}

function user_login_candidates(array $user): array
{
    return array_values(array_filter(array_unique([
        normalize_login_identity((string) ($user['account'] ?? '')),
        normalize_login_identity((string) ($user['employee_no'] ?? '')),
        normalize_login_identity((string) ($user['email'] ?? '')),
    ]), static fn(string $value): bool => $value !== ''));
}

function find_user_by_login_identity(array $data, string $identity): ?array
{
    $identity = normalize_login_identity($identity);

    if ($identity === '') {
        return null;
    }

    foreach ($data['users'] ?? [] as $user) {
        if (in_array($identity, user_login_candidates($user), true)) {
            return $user;
        }
    }

    return null;
}

function default_current_user_id(array $data): string
{
    foreach ($data['users'] ?? [] as $user) {
        if ((string) ($user['status'] ?? 'active') === 'active' && (string) ($user['role'] ?? '') === 'admin') {
            return (string) ($user['id'] ?? '');
        }
    }

    foreach ($data['users'] ?? [] as $user) {
        if ((string) ($user['status'] ?? 'active') === 'active') {
            return (string) ($user['id'] ?? '');
        }
    }

    return '';
}

function session_auth_user_id(): string
{
    return trim((string) ($_SESSION['auth_user_id'] ?? ''));
}

function set_session_auth_user_id(string $userId): void
{
    $_SESSION['auth_user_id'] = $userId;
}

function clear_session_auth_user_id(): void
{
    unset($_SESSION['auth_user_id']);
}

function session_current_user_id(): string
{
    return trim((string) ($_SESSION['current_user_id'] ?? ''));
}

function set_session_current_user_id(string $userId): void
{
    if ($userId === '') {
        unset($_SESSION['current_user_id']);
        return;
    }

    $_SESSION['current_user_id'] = $userId;
}

function clear_session_current_user_id(): void
{
    unset($_SESSION['current_user_id']);
}

function clear_session_identity(): void
{
    clear_session_auth_user_id();
    clear_session_current_user_id();
}

function authenticated_user_or_null(array $data): ?array
{
    $authUserId = session_auth_user_id();

    if ($authUserId === '') {
        return null;
    }

    $lookup = user_lookup($data['users'] ?? []);
    $user = $lookup[$authUserId] ?? null;

    if (!is_array($user) || (string) ($user['status'] ?? 'inactive') !== 'active') {
        return null;
    }

    return $user;
}

function authenticated_user(array $data): array
{
    $user = authenticated_user_or_null($data);

    if ($user === null) {
        throw new RuntimeException('当前未登录');
    }

    return $user;
}

function session_user_can_impersonate(array $data): bool
{
    $authUser = authenticated_user_or_null($data);

    if ($authUser === null) {
        return false;
    }

    return user_has_permission($authUser, 'staff.manage');
}

function current_user(array $data, string $preferredId = ''): array
{
    $authUser = authenticated_user($data);
    $lookup = user_lookup($data['users'] ?? []);
    $candidateIds = array_values(array_filter([
        $preferredId,
        session_current_user_id(),
        (string) ($authUser['id'] ?? ''),
    ], static fn(string $value): bool => $value !== ''));

    foreach ($candidateIds as $userId) {
        if (!isset($lookup[$userId])) {
            continue;
        }

        if ((string) ($lookup[$userId]['status'] ?? 'inactive') !== 'active') {
            continue;
        }

        if ($userId !== (string) ($authUser['id'] ?? '') && !session_user_can_impersonate($data)) {
            continue;
        }

        return $lookup[$userId];
    }

    return $authUser;
}

function effective_user_permissions(array $user): array
{
    $permissions = array_merge(
        role_permissions((string) ($user['role'] ?? 'viewer')),
        auth_string_array($user['permissions'] ?? [])
    );

    if (in_array('*', $permissions, true)) {
        return ['*'];
    }

    $catalog = array_keys(permission_catalog());
    $permissions = array_values(array_unique(array_intersect($permissions, $catalog)));
    sort($permissions);

    return $permissions;
}

function user_has_permission(array $user, string $permission): bool
{
    $permissions = effective_user_permissions($user);

    return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
}

function user_rows(array $data): array
{
    $rows = [];
    $lookup = user_lookup($data['users'] ?? []);

    foreach ($data['users'] ?? [] as $user) {
        $role = (string) ($user['role'] ?? 'viewer');
        $group = (string) (role_definitions()[$role]['group'] ?? 'readonly');
        $effectivePermissions = effective_user_permissions($user);
        $managerId = (string) ($user['manager_id'] ?? '');
        $directPermissions = auth_string_array($user['permissions'] ?? []);

        $rows[] = [
            'id' => (string) ($user['id'] ?? ''),
            'account' => (string) ($user['account'] ?? ''),
            'employee_no' => (string) ($user['employee_no'] ?? ''),
            'name' => (string) ($user['name'] ?? ''),
            'title' => (string) ($user['title'] ?? ''),
            'department' => (string) ($user['department'] ?? ''),
            'role' => $role,
            'role_label' => role_label($role),
            'role_group' => $group,
            'role_group_label' => role_group_label($group),
            'role_group_description' => role_group_description($group),
            'role_home_module' => role_home_module($role),
            'role_home_label' => console_module_label(role_home_module($role)),
            'role_summary' => role_summary($role),
            'role_guide_steps' => role_guide_steps($role),
            'status' => (string) ($user['status'] ?? 'active'),
            'status_label' => user_status_label((string) ($user['status'] ?? 'active')),
            'status_tone' => user_status_tone((string) ($user['status'] ?? 'active')),
            'phone' => (string) ($user['phone'] ?? ''),
            'email' => (string) ($user['email'] ?? ''),
            'hire_date' => (string) ($user['hire_date'] ?? ''),
            'manager_id' => $managerId,
            'manager_name' => $managerId !== '' ? (string) ($lookup[$managerId]['name'] ?? '') : '',
            'permissions' => $directPermissions,
            'permission_labels' => permission_labels($directPermissions),
            'permission_groups' => role_permission_groups($directPermissions),
            'effective_permissions' => $effectivePermissions,
            'effective_permission_labels' => permission_labels($effectivePermissions),
            'effective_permission_groups' => role_permission_groups($effectivePermissions),
            'permission_count' => in_array('*', $effectivePermissions, true) ? count(permission_catalog()) : count($effectivePermissions),
            'permission_group_count' => count(role_permission_groups($effectivePermissions)),
            'last_login_at' => (string) ($user['last_login_at'] ?? ''),
            'created_at' => (string) ($user['created_at'] ?? ''),
            'updated_at' => (string) ($user['updated_at'] ?? ''),
        ];
    }

    usort($rows, static function (array $left, array $right): int {
        if ((string) ($left['status'] ?? '') !== (string) ($right['status'] ?? '')) {
            return (string) ($left['status'] ?? '') === 'active' ? -1 : 1;
        }

        return strcmp((string) ($left['employee_no'] ?? ''), (string) ($right['employee_no'] ?? ''));
    });

    return $rows;
}

function user_payload_from_request(array $source, array $current = []): array
{
    $password = input_string($source, 'password');
    $passwordHash = $password !== ''
        ? hash_user_password($password)
        : (string) ($current['password_hash'] ?? '');

    if ($passwordHash === '') {
        $passwordHash = hash_user_password(default_initial_password());
    }

    return [
        'id' => (string) ($current['id'] ?? next_id('user')),
        'account' => normalize_login_identity(input_string($source, 'account', (string) ($current['account'] ?? ''))),
        'employee_no' => input_string($source, 'employee_no', (string) ($current['employee_no'] ?? '')),
        'name' => input_string($source, 'name', (string) ($current['name'] ?? '')),
        'title' => input_string($source, 'title', (string) ($current['title'] ?? '')),
        'department' => input_string($source, 'department', (string) ($current['department'] ?? '')),
        'role' => input_string($source, 'role', (string) ($current['role'] ?? 'viewer')),
        'permissions' => auth_string_array($source['permissions'] ?? ($current['permissions'] ?? [])),
        'status' => input_string($source, 'status', (string) ($current['status'] ?? 'active')),
        'phone' => input_string($source, 'phone', (string) ($current['phone'] ?? '')),
        'email' => normalize_login_identity(input_string($source, 'email', (string) ($current['email'] ?? ''))),
        'hire_date' => input_string($source, 'hire_date', (string) ($current['hire_date'] ?? '')),
        'manager_id' => input_string($source, 'manager_id', (string) ($current['manager_id'] ?? '')),
        'password_hash' => $passwordHash,
        'last_login_at' => (string) ($current['last_login_at'] ?? ''),
        'created_at' => (string) ($current['created_at'] ?? ''),
        'updated_at' => (string) ($current['updated_at'] ?? ''),
    ];
}

function apply_created_audit_fields(array $payload, array $actor): array
{
    $now = date('Y-m-d H:i:s');
    $actorId = (string) ($actor['id'] ?? '');
    $actorName = (string) ($actor['name'] ?? '系统');

    $payload['created_at'] = (string) ($payload['created_at'] ?? '') !== '' ? (string) $payload['created_at'] : $now;
    $payload['created_by'] = (string) ($payload['created_by'] ?? $actorId);
    $payload['created_by_name'] = (string) ($payload['created_by_name'] ?? $actorName);
    $payload['updated_at'] = $now;
    $payload['updated_by'] = $actorId;
    $payload['updated_by_name'] = $actorName;

    return $payload;
}

function apply_updated_audit_fields(array $current, array $payload, array $actor): array
{
    $now = date('Y-m-d H:i:s');
    $actorId = (string) ($actor['id'] ?? '');
    $actorName = (string) ($actor['name'] ?? '系统');

    $payload['created_at'] = (string) ($current['created_at'] ?? ($payload['created_at'] ?? ''));
    $payload['created_by'] = (string) ($current['created_by'] ?? ($payload['created_by'] ?? ''));
    $payload['created_by_name'] = (string) ($current['created_by_name'] ?? ($payload['created_by_name'] ?? '系统初始化'));
    $payload['updated_at'] = $now;
    $payload['updated_by'] = $actorId;
    $payload['updated_by_name'] = $actorName;

    return $payload;
}

function touch_record_audit_fields(array $current, array $actor): array
{
    return apply_updated_audit_fields($current, $current, $actor);
}

function audit_user_name(array $lookup, string $userId, string $fallback = ''): string
{
    if ($userId !== '' && isset($lookup[$userId])) {
        return (string) ($lookup[$userId]['name'] ?? $fallback);
    }

    return $fallback !== '' ? $fallback : '系统初始化';
}

function record_audit_fields(array $row, array $userLookup): array
{
    $createdAt = (string) ($row['created_at'] ?? '');
    $updatedAt = (string) ($row['updated_at'] ?? '');
    $createdByName = (string) ($row['created_by_name'] ?? '');
    $updatedByName = (string) ($row['updated_by_name'] ?? '');

    return [
        'created_at' => $createdAt,
        'created_by' => (string) ($row['created_by'] ?? ''),
        'created_by_name' => audit_user_name($userLookup, (string) ($row['created_by'] ?? ''), $createdByName !== '' ? $createdByName : '系统初始化'),
        'updated_at' => $updatedAt !== '' ? $updatedAt : $createdAt,
        'updated_by' => (string) ($row['updated_by'] ?? ''),
        'updated_by_name' => audit_user_name($userLookup, (string) ($row['updated_by'] ?? ''), $updatedByName !== '' ? $updatedByName : ($createdByName !== '' ? $createdByName : '系统初始化')),
    ];
}

function audit_log(array &$data, array $actor, string $module, string $action, string $targetType, string $targetId, string $summary, array $meta = []): void
{
    $data['audit_logs'] ??= [];
    $data['audit_logs'][] = [
        'id' => next_id('log'),
        'occurred_at' => date('Y-m-d H:i:s'),
        'user_id' => (string) ($actor['id'] ?? ''),
        'user_name' => (string) ($actor['name'] ?? '系统'),
        'module' => $module,
        'action' => $action,
        'target_type' => $targetType,
        'target_id' => $targetId,
        'summary' => $summary,
        'meta' => $meta,
    ];

    if (count($data['audit_logs']) > 500) {
        $data['audit_logs'] = array_slice($data['audit_logs'], -500);
    }
}

function audit_action_label(string $action): string
{
    return [
        'create' => '新增',
        'update' => '编辑',
        'delete' => '删除',
        'status' => '状态变更',
        'attachment' => '附件变更',
        'switch_user' => '切换身份',
        'settings' => '配置变更',
        'ask_ai' => 'AI 提问',
        'login' => '登录',
        'logout' => '退出',
        'reset_password' => '重置密码',
    ][$action] ?? $action;
}

function audit_module_label(string $module): string
{
    return [
        'finance' => '财务中心',
        'projects' => '项目交付',
        'operations' => 'APP 运营',
        'service' => '问题记录',
        'tech' => '研发联动',
        'staff' => '人员权限',
        'ai' => 'AI 助手',
        'auth' => '登录认证',
    ][$module] ?? $module;
}

function audit_log_rows(array $data): array
{
    $rows = [];

    foreach ($data['audit_logs'] ?? [] as $row) {
        $rows[] = [
            'id' => (string) ($row['id'] ?? ''),
            'occurred_at' => (string) ($row['occurred_at'] ?? ''),
            'user_id' => (string) ($row['user_id'] ?? ''),
            'user_name' => (string) ($row['user_name'] ?? '系统'),
            'module' => (string) ($row['module'] ?? ''),
            'module_label' => audit_module_label((string) ($row['module'] ?? '')),
            'action' => (string) ($row['action'] ?? ''),
            'action_label' => audit_action_label((string) ($row['action'] ?? '')),
            'target_type' => (string) ($row['target_type'] ?? ''),
            'target_id' => (string) ($row['target_id'] ?? ''),
            'summary' => (string) ($row['summary'] ?? ''),
        ];
    }

    usort($rows, static function (array $left, array $right): int {
        return strcmp((string) ($right['occurred_at'] ?? ''), (string) ($left['occurred_at'] ?? ''));
    });

    return $rows;
}
