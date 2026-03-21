<?php

namespace app\admin\model\App;

use think\Model;


class Project extends Model
{

    

    

    // 表名
    protected $name = 'app_project';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'lifecycle_stage_text',
        'status_text',
        'priority_text'
    ];

    // 类型转换
    protected $type = [

    ];
    

    
    public function getLifecycleStageList()
    {
        return ['idea' => __('Lifecycle_stage idea'), 'validation' => __('Lifecycle_stage validation'), 'launch' => __('Lifecycle_stage launch'), 'growth' => __('Lifecycle_stage growth'), 'retention' => __('Lifecycle_stage retention'), 'mature' => __('Lifecycle_stage mature'), 'sunset' => __('Lifecycle_stage sunset')];
    }

    public function getStatusList()
    {
        return ['planning' => __('Status planning'), 'running' => __('Status running'), 'paused' => __('Status paused'), 'completed' => __('Status completed'), 'archived' => __('Status archived')];
    }

    public function getPriorityList()
    {
        return ['low' => __('Priority low'), 'medium' => __('Priority medium'), 'high' => __('Priority high'), 'urgent' => __('Priority urgent')];
    }


    public function getLifecycleStageTextAttr($value, $data)
    {
        $value = $value ?: ($data['lifecycle_stage'] ?? '');
        $list = $this->getLifecycleStageList();
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
