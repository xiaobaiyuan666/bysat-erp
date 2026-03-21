<?php

namespace app\admin\model\Business;

use think\Model;

class Supplier extends Model
{
    protected $name = 'business_supplier';
    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    public function getCategoryList()
    {
        return [
            'software' => '软件服务',
            'cloud' => '云资源',
            'service' => '专业服务',
            'marketing' => '投放渠道',
            'outsourcing' => '外包合作',
            'hardware' => '硬件设备',
            'other' => '其他',
        ];
    }

    public function getLevelList()
    {
        return [
            'strategic' => '战略供应商',
            'core' => '核心供应商',
            'normal' => '常规供应商',
            'backup' => '备选供应商',
        ];
    }

    public function getStatusList()
    {
        return [
            'active' => '合作中',
            'paused' => '暂停合作',
            'blacklist' => '黑名单',
        ];
    }

    public function getSettlementCycleList()
    {
        return [
            'advance' => '预付款',
            'monthly' => '月结',
            'quarterly' => '季结',
            'on_delivery' => '交付后结算',
            'other' => '其他',
        ];
    }
}
