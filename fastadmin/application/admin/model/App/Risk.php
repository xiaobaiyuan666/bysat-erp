<?php

namespace app\admin\model\App;

use think\Model;


class Risk extends Model
{

    

    

    // 表名
    protected $name = 'app_risk';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'type_text',
        'level_text',
        'status_text'
    ];

    // 类型转换
    protected $type = [

    ];
    

    
    public function getTypeList()
    {
        return ['risk' => __('Type risk'), 'issue' => __('Type issue'), 'change' => __('Type change'), 'dependency' => __('Type dependency')];
    }

    public function getLevelList()
    {
        return ['low' => __('Level low'), 'medium' => __('Level medium'), 'high' => __('Level high'), 'critical' => __('Level critical')];
    }

    public function getStatusList()
    {
        return ['open' => __('Status open'), 'tracking' => __('Status tracking'), 'resolved' => __('Status resolved'), 'closed' => __('Status closed')];
    }


    public function getTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['type'] ?? '');
        $list = $this->getTypeList();
        return $list[$value] ?? '';
    }


    public function getLevelTextAttr($value, $data)
    {
        $value = $value ?: ($data['level'] ?? '');
        $list = $this->getLevelList();
        return $list[$value] ?? '';
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['status'] ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
    }




}
