<?php

namespace app\admin\model\Business;

use think\Model;

class PurchaseOrder extends Model
{
    protected $name = 'business_purchase_order';
    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    public function getPurchaseTypeList()
    {
        return [
            'software' => '软件订阅',
            'cloud' => '云资源采购',
            'service' => '服务采购',
            'outsourcing' => '外包合作',
            'marketing' => '营销投放',
            'hardware' => '硬件设备',
            'office' => '办公采购',
            'other' => '其他',
        ];
    }

    public function getStatusList()
    {
        return [
            'draft' => '草稿',
            'pending_approval' => '审批中',
            'approved' => '已批准',
            'processing' => '处理中',
            'completed' => '已完成',
            'rejected' => '已驳回',
            'cancelled' => '已取消',
        ];
    }
}
