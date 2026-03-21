<?php

namespace app\admin\model\Business;

use think\Model;

class PurchaseSettlement extends Model
{
    protected $name = 'business_purchase_settlement';
    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    public function getStatusList()
    {
        return [
            'draft' => '草稿',
            'reconciling' => '对账中',
            'confirmed' => '已确认',
            'settled' => '已结算',
            'cancelled' => '已取消',
        ];
    }

    public function getInvoiceStatusList()
    {
        return [
            'none' => '未到票',
            'partial' => '部分到票',
            'received' => '已到票',
        ];
    }
}
