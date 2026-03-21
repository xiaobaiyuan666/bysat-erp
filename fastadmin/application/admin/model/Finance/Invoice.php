<?php

namespace app\admin\model\Finance;

use think\Model;


class Invoice extends Model
{

    

    

    // 表名
    protected $name = 'finance_invoice';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'kind_text',
        'status_text'
    ];

    // 类型转换
    protected $type = [

    ];
    

    
    public function getKindList()
    {
        return ['receivable' => __('Kind receivable'), 'payable' => __('Kind payable')];
    }

    public function getStatusList()
    {
        return ['pending' => __('Status pending'), 'partial' => __('Status partial'), 'paid' => __('Status paid'), 'overdue' => __('Status overdue'), 'cancelled' => __('Status cancelled')];
    }


    public function getKindTextAttr($value, $data)
    {
        $value = $value ?: ($data['kind'] ?? '');
        $list = $this->getKindList();
        return $list[$value] ?? '';
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['status'] ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
    }




}
