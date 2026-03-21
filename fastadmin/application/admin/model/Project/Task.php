<?php

namespace app\admin\model\Project;

use think\Model;


class Task extends Model
{

    

    

    // 表名
    protected $name = 'project_task';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'status_text',
        'priority_text'
    ];

    // 类型转换
    protected $type = [

    ];
    

    
    public function getStatusList()
    {
        return ['todo' => __('Status todo'), 'doing' => __('Status doing'), 'review' => __('Status review'), 'done' => __('Status done'), 'blocked' => __('Status blocked'), 'overdue' => __('Status overdue')];
    }

    public function getPriorityList()
    {
        return ['low' => __('Priority low'), 'medium' => __('Priority medium'), 'high' => __('Priority high'), 'urgent' => __('Priority urgent')];
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
