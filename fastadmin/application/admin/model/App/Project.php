<?php

namespace app\admin\model\App;

use think\Model;

class Project extends Model
{
    protected $name = 'app_project';
    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    protected $append = [
        'project_type_text',
        'lifecycle_stage_text',
        'status_text',
        'priority_text',
    ];

    protected $type = [];

    public function getProjectTypeList()
    {
        return [
            'app' => __('Project_type app'),
            'miniprogram' => __('Project_type miniprogram'),
            'website' => __('Project_type website'),
            'campaign' => __('Project_type campaign'),
            'private_domain' => __('Project_type private_domain'),
            'other' => __('Project_type other'),
        ];
    }

    public function getLifecycleStageList()
    {
        return [
            'idea' => __('Lifecycle_stage idea'),
            'validation' => __('Lifecycle_stage validation'),
            'launch' => __('Lifecycle_stage launch'),
            'growth' => __('Lifecycle_stage growth'),
            'retention' => __('Lifecycle_stage retention'),
            'mature' => __('Lifecycle_stage mature'),
            'sunset' => __('Lifecycle_stage sunset'),
        ];
    }

    public function getStatusList()
    {
        return [
            'planning' => __('Status planning'),
            'running' => __('Status running'),
            'paused' => __('Status paused'),
            'completed' => __('Status completed'),
            'archived' => __('Status archived'),
        ];
    }

    public function getPriorityList()
    {
        return [
            'low' => __('Priority low'),
            'medium' => __('Priority medium'),
            'high' => __('Priority high'),
            'urgent' => __('Priority urgent'),
        ];
    }

    public function getProjectTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['project_type'] ?? 'app');
        $list = $this->getProjectTypeList();

        return $list[$value] ?? '';
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
