<?php

return [
    'brand' => [
        'display_name' => 'ERP AI 管理系统',
        'system_name' => '企业 ERP AI 智能管理系统',
        'short_name' => 'ERP AI',
        'website' => '',
        'website_label' => '',
        'copyright_notice' => '',
    ],
    'core_capabilities' => [
        '财务记账',
        '客户与合同',
        '项目协同',
        '员工权限',
        '审批日志',
        'AI 助手',
    ],
    'module_categories' => [
        'operations' => [
            'key' => 'operations',
            'title' => '项目与运营',
            'description' => '适合公司内部的软件开发、交付与项目运营类业务。APP 只是项目类型之一，后续也可以扩展到小程序、官网、私域或活动项目。',
            'sort_no' => 10,
        ],
        'industry' => [
            'key' => 'industry',
            'title' => '行业插件',
            'description' => '仅在公司内部确有业务需要时再启用，用于承接特定行业场景的扩展模块。',
            'sort_no' => 20,
        ],
        'other' => [
            'key' => 'other',
            'title' => '其他插件',
            'description' => '用于放置临时扩展、测试插件或后续新行业能力。',
            'sort_no' => 999,
        ],
    ],
    'modules' => [
        'app' => [
            'key' => 'app',
            'title' => '项目运营插件',
            'short_title' => '项目运营',
            'category_key' => 'operations',
            'type' => 'plugin',
            'icon' => 'fa fa-sitemap',
            'description' => '适合公司内部跟踪互联网项目的全生命周期。APP 只是项目类型之一，后续可继续扩展到小程序、官网、活动投放和其他运营项目。',
            'default_enabled' => 1,
            'locked' => 0,
            'route' => 'app/workbench/index',
            'rule_prefixes' => ['app'],
            'sort_no' => 10,
        ],
    ],
];
