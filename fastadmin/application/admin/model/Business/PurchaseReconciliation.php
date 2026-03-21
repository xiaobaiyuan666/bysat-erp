<?php

namespace app\admin\model\Business;

use think\Model;

class PurchaseReconciliation extends Model
{
    protected $name = 'business_purchase_reconciliation';
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
            'disputed' => '有差异',
            'closed' => '已关闭',
        ];
    }
}
