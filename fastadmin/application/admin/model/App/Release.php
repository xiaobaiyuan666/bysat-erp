<?php

namespace app\admin\model\App;

use think\Model;


class Release extends Model
{

    

    

    // 表名
    protected $name = 'app_release';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'status_text',
        'customer_sync_status_text'
    ];

    // 类型转换
    protected $type = [

    ];
    

    
    public function getStatusList()
    {
        return ['planned' => __('Status planned'), 'ready' => __('Status ready'), 'testing' => __('Status testing'), 'released' => __('Status released'), 'rollback' => __('Status rollback'), 'closed' => __('Status closed')];
    }

    public function getCustomerSyncStatusList()
    {
        return ['pending' => __('Customer_sync_status pending'), 'done' => __('Customer_sync_status done'), 'skip' => __('Customer_sync_status skip')];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['status'] ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
    }


    public function getCustomerSyncStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['customer_sync_status'] ?? '');
        $list = $this->getCustomerSyncStatusList();
        return $list[$value] ?? '';
    }




}
