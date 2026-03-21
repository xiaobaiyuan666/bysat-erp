<?php

namespace app\admin\model\Business;

use think\Model;

class ApprovalTemplate extends Model
{
    protected $name = 'business_approval_template';
    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    public function getObjectTypeList()
    {
        return [
            'contract' => '合同审批',
            'payment_plan' => '付款审批',
            'expense_request' => '费用审批',
            'purchase_order' => '采购审批',
            'payment_request' => '付款申请审批',
        ];
    }

    public function getStatusList()
    {
        return [
            'active' => '启用',
            'inactive' => '停用',
        ];
    }

    public function getIsDefaultList()
    {
        return [
            0 => '普通模板',
            1 => '默认模板',
        ];
    }
}
