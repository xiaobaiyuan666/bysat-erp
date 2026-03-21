<?php

namespace app\admin\model\App;

use think\Model;


class TechTicket extends Model
{

    

    

    // 表名
    protected $name = 'app_tech_ticket';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'type_text',
        'status_text',
        'priority_text',
        'severity_text',
        'source_text'
    ];

    // 类型转换
    protected $type = [

    ];
    

    
    public function getTypeList()
    {
        return ['bug' => __('Type bug'), 'improvement' => __('Type improvement'), 'upgrade' => __('Type upgrade'), 'task' => __('Type task')];
    }

    public function getStatusList()
    {
        return ['pending' => __('Status pending'), 'processing' => __('Status processing'), 'testing' => __('Status testing'), 'ready' => __('Status ready'), 'done' => __('Status done'), 'closed' => __('Status closed')];
    }

    public function getPriorityList()
    {
        return ['low' => __('Priority low'), 'medium' => __('Priority medium'), 'high' => __('Priority high'), 'urgent' => __('Priority urgent')];
    }

    public function getSeverityList()
    {
        return ['low' => __('Severity low'), 'medium' => __('Severity medium'), 'high' => __('Severity high'), 'blocker' => __('Severity blocker')];
    }

    public function getSourceList()
    {
        return ['operations' => __('Source operations'), 'product' => __('Source product'), 'customer' => __('Source customer'), 'sales' => __('Source sales'), 'service' => __('Source service')];
    }


    public function getTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['type'] ?? '');
        $list = $this->getTypeList();
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


    public function getSeverityTextAttr($value, $data)
    {
        $value = $value ?: ($data['severity'] ?? '');
        $list = $this->getSeverityList();
        return $list[$value] ?? '';
    }


    public function getSourceTextAttr($value, $data)
    {
        $value = $value ?: ($data['source'] ?? '');
        $list = $this->getSourceList();
        return $list[$value] ?? '';
    }




}
