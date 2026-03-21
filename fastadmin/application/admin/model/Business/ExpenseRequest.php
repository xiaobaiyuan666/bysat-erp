<?php

namespace app\admin\model\Business;

use think\Model;

class ExpenseRequest extends Model
{
    protected $name = 'business_expense_request';
    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    public function getExpenseTypeList()
    {
        return [
            'procurement' => '采购付款',
            'travel' => '差旅费用',
            'marketing' => '投放费用',
            'service' => '服务采购',
            'software' => '软件订阅',
            'outsourcing' => '外包合作',
            'office' => '办公费用',
            'refund' => '退款支出',
            'other' => '其他',
        ];
    }

    public function getStatusList()
    {
        return [
            'draft' => '草稿',
            'pending_approval' => '审批中',
            'approved' => '已批准',
            'processing' => '付款处理中',
            'paid' => '已付款',
            'rejected' => '已驳回',
            'cancelled' => '已取消',
        ];
    }
}
