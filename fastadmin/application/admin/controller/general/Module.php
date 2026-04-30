<?php

namespace app\admin\controller\general;

use app\admin\library\ErpModuleService;
use app\common\controller\Backend;

class Module extends Backend
{
    protected $moduleService = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->moduleService = new ErpModuleService();
    }

    public function index()
    {
        $this->moduleService->ensureStorage();
        $brand = $this->moduleService->getBrand();

        $this->view->assign([
            'brand' => $brand,
            'modules' => $this->moduleService->getSwitchableModules(),
            'groupedModules' => $this->moduleService->getGroupedModules(),
            'coreCapabilities' => $this->moduleService->getCoreCapabilities(),
        ]);

        return $this->view->fetch();
    }

    public function save()
    {
        if (!$this->request->isPost()) {
            $this->error('请求方式错误');
        }

        $row = $this->request->post('row/a', []);
        $modules = $this->moduleService->saveSwitches($row);
        $this->success('模块开关已保存', null, [
            'modules' => array_values($modules),
        ]);
    }
}
