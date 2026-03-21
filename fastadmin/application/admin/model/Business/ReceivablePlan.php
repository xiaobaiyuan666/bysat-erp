<?php

namespace app\admin\model\Business;

use think\Model;

class ReceivablePlan extends Model
{
    protected $name = 'business_receivable_plan';
    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    public function getStatusList()
    {
        return [
            'pending' => '待回款',
            'processing' => '跟进中',
            'received' => '已到账',
            'overdue' => '已逾期',
            'cancelled' => '已取消',
        ];
    }
}
