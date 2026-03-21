<?php

namespace app\admin\model\Ai;

use think\Model;


class Conversation extends Model
{

    

    

    // 表名
    protected $name = 'ai_conversation';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'role_text'
    ];

    // 类型转换
    protected $type = [

    ];
    

    
    public function getRoleList()
    {
        return ['system' => __('Role system'), 'user' => __('Role user'), 'assistant' => __('Role assistant')];
    }


    public function getRoleTextAttr($value, $data)
    {
        $value = $value ?: ($data['role'] ?? '');
        $list = $this->getRoleList();
        return $list[$value] ?? '';
    }




}
