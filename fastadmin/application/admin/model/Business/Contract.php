<?php

namespace app\admin\model\Business;

use think\Model;

class Contract extends Model
{
    protected $name = 'business_contract';
    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    public function getCategoryList()
    {
        return [
            'implementation' => '实施交付',
            'subscription' => '订阅服务',
            'maintenance' => '维护续费',
            'custom' => '定制开发',
            'service' => '咨询服务',
            'other' => '其他',
        ];
    }

    public function getStatusList()
    {
        return [
            'draft' => '草稿',
            'review' => '审批中',
            'active' => '生效中',
            'delivering' => '履约中',
            'completed' => '已完成',
            'cancelled' => '已取消',
            'expired' => '已到期',
        ];
    }
}
