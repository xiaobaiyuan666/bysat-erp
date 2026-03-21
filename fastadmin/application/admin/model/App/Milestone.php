<?php

namespace app\admin\model\App;

use think\Model;


class Milestone extends Model
{

    

    

    // 表名
    protected $name = 'app_milestone';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'status_text'
    ];

    // 类型转换
    protected $type = [

    ];
    

    
    public function getStatusList()
    {
        return ['pending' => __('Status pending'), 'doing' => __('Status doing'), 'review' => __('Status review'), 'done' => __('Status done'), 'blocked' => __('Status blocked')];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['status'] ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
    }




}
