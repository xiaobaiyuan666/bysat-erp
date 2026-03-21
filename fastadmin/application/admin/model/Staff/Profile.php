<?php

namespace app\admin\model\Staff;

use think\Model;


class Profile extends Model
{

    

    

    // 表名
    protected $name = 'staff_profile';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'role_key_text',
        'status_text'
    ];

    // 类型转换
    protected $type = [

    ];
    

    
    public function getRoleKeyList()
    {
        return ['admin' => __('Role_key admin'), 'finance' => __('Role_key finance'), 'project' => __('Role_key project'), 'operations' => __('Role_key operations'), 'service' => __('Role_key service'), 'tech' => __('Role_key tech'), 'viewer' => __('Role_key viewer')];
    }

    public function getStatusList()
    {
        return ['active' => __('Status active'), 'inactive' => __('Status inactive')];
    }


    public function getRoleKeyTextAttr($value, $data)
    {
        $value = $value ?: ($data['role_key'] ?? '');
        $list = $this->getRoleKeyList();
        return $list[$value] ?? '';
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['status'] ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
    }




}
