<?php

namespace app\admin\model\Business;

use think\Model;

class Customer extends Model
{
    protected $name = 'business_customer';
    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    public function getCustomerLevelList()
    {
        return [
            'a' => 'A 级',
            'b' => 'B 级',
            'c' => 'C 级',
            'd' => 'D 级',
        ];
    }

    public function getSourceList()
    {
        return [
            'direct' => '直客',
            'referral' => '转介绍',
            'channel' => '渠道',
            'existing' => '老客户',
            'other' => '其他',
        ];
    }

    public function getStageList()
    {
        return [
            'lead' => '线索阶段',
            'proposal' => '方案沟通',
            'contracted' => '合同阶段',
            'delivery' => '交付中',
            'repeat' => '复购阶段',
            'lost' => '流失',
        ];
    }

    public function getStatusList()
    {
        return [
            'active' => '正常',
            'paused' => '暂缓',
            'lost' => '流失',
        ];
    }
}
