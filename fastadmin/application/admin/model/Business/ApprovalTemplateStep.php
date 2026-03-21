<?php

namespace app\admin\model\Business;

use think\Model;

class ApprovalTemplateStep extends Model
{
    protected $name = 'business_approval_template_step';
    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    public function getStatusList()
    {
        return [
            'active' => '启用',
            'inactive' => '停用',
        ];
    }
}
