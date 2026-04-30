<?php

namespace app\admin\controller\app;

use app\admin\library\ErpModuleService;
use app\common\controller\Backend;

abstract class Base extends Backend
{
    protected $moduleService = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->moduleService = new ErpModuleService();
        $this->moduleService->ensureStorage();
        if (!$this->moduleService->isEnabled('app')) {
            $this->success('项目运营插件已关闭，请先到 系统资料 -> 模块中心 开启后再使用。', url('general/module/index'));
        }
    }
}
