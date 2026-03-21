<?php

namespace app\admin\model\Business;

use think\Model;

class CustomerFollowup extends Model
{
    protected $name = 'business_customer_followup';
    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    public function getFollowupTypeList()
    {
        return [
            'call' => '电话沟通',
            'wechat' => '微信/IM',
            'meeting' => '会议沟通',
            'visit' => '上门拜访',
            'proposal' => '方案推进',
            'payment' => '回款跟进',
            'service' => '服务回访',
            'other' => '其他',
        ];
    }

    public function getStatusList()
    {
        return [
            'planned' => '待跟进',
            'done' => '已完成',
            'waiting' => '待客户回复',
            'closed' => '已关闭',
        ];
    }
}
