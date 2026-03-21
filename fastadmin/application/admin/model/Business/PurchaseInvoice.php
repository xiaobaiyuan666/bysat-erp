<?php

namespace app\admin\model\Business;

use think\Model;

class PurchaseInvoice extends Model
{
    protected $name = 'business_purchase_invoice';
    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    public function getInvoiceTypeList()
    {
        return [
            'vat_special' => '增值税专票',
            'vat_normal' => '增值税普票',
            'service' => '服务发票',
            'electronic' => '电子发票',
            'other' => '其他',
        ];
    }

    public function getStatusList()
    {
        return [
            'pending' => '待收票',
            'received' => '已收票',
            'verified' => '已验票',
            'returned' => '已退回',
            'cancelled' => '已作废',
        ];
    }
}
