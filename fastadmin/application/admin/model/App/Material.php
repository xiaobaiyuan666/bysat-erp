<?php

namespace app\admin\model\App;

use think\Model;


class Material extends Model
{

    

    

    // 表名
    protected $name = 'app_material';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'category_text',
        'archive_status_text'
    ];

    // 类型转换
    protected $type = [
        'file_size' => 'string'
    ];
    

    
    public function getCategoryList()
    {
        return ['manual' => __('Category manual'), 'faq' => __('Category faq'), 'training' => __('Category training'), 'script' => __('Category script'), 'report' => __('Category report'), 'other' => __('Category other')];
    }

    public function getArchiveStatusList()
    {
        return ['active' => __('Archive_status active'), 'archived' => __('Archive_status archived')];
    }


    public function getCategoryTextAttr($value, $data)
    {
        $value = $value ?: ($data['category'] ?? '');
        $list = $this->getCategoryList();
        return $list[$value] ?? '';
    }


    public function getArchiveStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['archive_status'] ?? '');
        $list = $this->getArchiveStatusList();
        return $list[$value] ?? '';
    }




}
