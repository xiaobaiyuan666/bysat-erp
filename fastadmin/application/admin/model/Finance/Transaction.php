<?php

namespace app\admin\model\Finance;

use think\Model;


class Transaction extends Model
{

    

    

    // 表名
    protected $name = 'finance_transaction';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'type_text',
        'payment_method_text'
    ];

    // 类型转换
    protected $type = [

    ];
    

    
    public function getTypeList()
    {
        return ['income' => __('Type income'), 'expense' => __('Type expense')];
    }

    public function getPaymentMethodList()
    {
        return ['bank' => __('Payment_method bank'), 'wechat' => __('Payment_method wechat'), 'alipay' => __('Payment_method alipay'), 'cash' => __('Payment_method cash'), 'other' => __('Payment_method other')];
    }


    public function getTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['type'] ?? '');
        $list = $this->getTypeList();
        return $list[$value] ?? '';
    }


    public function getPaymentMethodTextAttr($value, $data)
    {
        $value = $value ?: ($data['payment_method'] ?? '');
        $list = $this->getPaymentMethodList();
        return $list[$value] ?? '';
    }




}
