<?php

namespace app\admin\model\Business;

use think\Model;

class PaymentRequest extends Model
{
    protected $name = 'business_payment_request';
    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    public function getStatusList()
    {
        return [
            'draft' => '草稿',
            'pending_approval' => '待审批',
            'approved' => '已批准',
            'paid' => '已付款',
            'rejected' => '已驳回',
            'cancelled' => '已取消',
        ];
    }
}
