<?php

namespace app\admin\controller\staff;

use app\common\controller\Backend;

/**
 * 操作日志
 *
 * @icon fa fa-circle-o
 */
class Audit extends Backend
{
    /**
     * @var \app\admin\model\Staff\Audit
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Staff\Audit();
    }

    public function add()
    {
        $this->error('操作日志不支持手动新增');
    }

    public function edit($ids = null)
    {
        $this->error('操作日志不支持编辑');
    }

    public function del($ids = null)
    {
        $this->error('操作日志不支持删除');
    }

    public function multi($ids = null)
    {
        $this->error('操作日志不支持批量操作');
    }
}
