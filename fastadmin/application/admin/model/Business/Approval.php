<?php

namespace app\admin\model\Business;

use think\Model;

class Approval extends Model
{
    protected $name = 'business_approval';
    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    public function getObjectTypeList()
    {
        return [
            'contract' => '合同审批',
            'payment_plan' => '付款计划审批',
            'expense_request' => '费用审批',
            'purchase_order' => '采购审批',
            'payment_request' => '付款申请审批',
        ];
    }

    public function getStatusList()
    {
        return [
            'pending' => '待审批',
            'approved' => '已通过',
            'rejected' => '已驳回',
            'cancelled' => '已撤回',
        ];
    }
}
