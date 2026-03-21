<?php

namespace app\admin\model\App;

use think\Model;


class Issue extends Model
{

    

    

    // 表名
    protected $name = 'app_issue';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'source_text',
        'channel_text',
        'category_text',
        'status_text',
        'priority_text'
    ];

    // 类型转换
    protected $type = [

    ];
    

    
    public function getSourceList()
    {
        return ['customer' => __('Source customer'), 'training' => __('Source training'), 'sales' => __('Source sales'), 'operations' => __('Source operations'), 'other' => __('Source other')];
    }

    public function getChannelList()
    {
        return ['wechat' => __('Channel wechat'), 'phone' => __('Channel phone'), 'email' => __('Channel email'), 'app' => __('Channel app'), 'onsite' => __('Channel onsite'), 'other' => __('Channel other')];
    }

    public function getCategoryList()
    {
        return ['bug' => __('Category bug'), 'usage' => __('Category usage'), 'billing' => __('Category billing'), 'feature' => __('Category feature'), 'training' => __('Category training'), 'other' => __('Category other')];
    }

    public function getStatusList()
    {
        return ['new' => __('Status new'), 'processing' => __('Status processing'), 'waiting_customer' => __('Status waiting_customer'), 'escalated' => __('Status escalated'), 'resolved' => __('Status resolved'), 'closed' => __('Status closed')];
    }

    public function getPriorityList()
    {
        return ['low' => __('Priority low'), 'medium' => __('Priority medium'), 'high' => __('Priority high'), 'urgent' => __('Priority urgent')];
    }


    public function getSourceTextAttr($value, $data)
    {
        $value = $value ?: ($data['source'] ?? '');
        $list = $this->getSourceList();
        return $list[$value] ?? '';
    }


    public function getChannelTextAttr($value, $data)
    {
        $value = $value ?: ($data['channel'] ?? '');
        $list = $this->getChannelList();
        return $list[$value] ?? '';
    }


    public function getCategoryTextAttr($value, $data)
    {
        $value = $value ?: ($data['category'] ?? '');
        $list = $this->getCategoryList();
        return $list[$value] ?? '';
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['status'] ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
    }


    public function getPriorityTextAttr($value, $data)
    {
        $value = $value ?: ($data['priority'] ?? '');
        $list = $this->getPriorityList();
        return $list[$value] ?? '';
    }




}
