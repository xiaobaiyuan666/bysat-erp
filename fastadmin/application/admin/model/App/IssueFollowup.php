<?php

namespace app\admin\model\App;

use think\Model;

class IssueFollowup extends Model
{
    // 表名
    protected $name = 'app_issue_followup';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'type_text',
        'visibility_text',
        'status_text',
    ];

    // 类型转换
    protected $type = [];

    public function getTypeList()
    {
        return [
            'status' => __('Type status'),
            'follow_up' => __('Type follow_up'),
            'internal' => __('Type internal'),
            'leader' => __('Type leader'),
            'release' => __('Type release'),
        ];
    }

    public function getVisibilityList()
    {
        return [
            'internal' => __('Visibility internal'),
            'customer' => __('Visibility customer'),
            'leader' => __('Visibility leader'),
        ];
    }

    public function getStatusList()
    {
        return [
            'new' => __('Status new'),
            'processing' => __('Status processing'),
            'waiting_customer' => __('Status waiting_customer'),
            'escalated' => __('Status escalated'),
            'resolved' => __('Status resolved'),
            'closed' => __('Status closed'),
        ];
    }

    public function getTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['type'] ?? '');
        $list = $this->getTypeList();
        return $list[$value] ?? '';
    }

    public function getVisibilityTextAttr($value, $data)
    {
        $value = $value ?: ($data['visibility'] ?? '');
        $list = $this->getVisibilityList();
        return $list[$value] ?? '';
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['status'] ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
    }
}
