<?php

namespace app\admin\model\Business;

use think\Model;

class PaymentPlan extends Model
{
    protected $name = 'business_payment_plan';
    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    public function getPlanTypeList()
    {
        return [
            'supplier' => '供应商付款',
            'implementation' => '实施成本',
            'commission' => '渠道返佣',
            'service' => '服务采购',
            'refund' => '退款支出',
            'other' => '其他',
        ];
    }

    public function getStatusList()
    {
        return [
            'pending' => '待付款',
            'processing' => '跟进中',
            'paid' => '已付款',
            'overdue' => '已逾期',
            'cancelled' => '已取消',
        ];
    }
}
