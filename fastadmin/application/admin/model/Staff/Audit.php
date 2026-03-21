<?php

namespace app\admin\model\Staff;

use think\Model;


class Audit extends Model
{

    

    

    // 表名
    protected $name = 'staff_audit';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];

    // 类型转换
    protected $type = [

    ];
    

    







}
