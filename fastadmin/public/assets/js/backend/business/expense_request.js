define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var parseJson = function (value, fallback) {
        if (!value) {
            return fallback;
        }
        try {
            return JSON.parse(value);
        } catch (e) {
            return fallback;
        }
    };

    var buildPresetQuery = function () {
        var query = new URLSearchParams(window.location.search);
        var preset = {filter: {}, op: {}};
        ['supplier_id', 'customer_id', 'contract_id', 'status', 'approval_status', 'owner_admin_id'].forEach(function (field) {
            if (!query.get(field)) {
                return;
            }
            preset.filter[field] = query.get(field);
            preset.op[field] = '=';
        });
        return preset;
    };

    var presetQuery = buildPresetQuery();

    var expenseTypeMap = {
        procurement: '采购付款',
        travel: '差旅费用',
        marketing: '投放费用',
        service: '服务采购',
        software: '软件订阅',
        outsourcing: '外包合作',
        office: '办公费用',
        refund: '退款支出',
        other: '其他'
    };

    var statusMap = {
        draft: '草稿',
        pending_approval: '审批中',
        approved: '已批准',
        processing: '付款处理中',
        paid: '已付款',
        rejected: '已驳回',
        cancelled: '已取消'
    };

    var approvalStatusMap = {
        none: '未发起',
        pending: '待审批',
        approved: '已通过',
        rejected: '已驳回',
        cancelled: '已撤回'
    };

    var approvalCreateUrl = function (row) {
        return 'business/approval/add?object_type=expense_request&object_id=' + row.id;
    };

    var approvalHistoryUrl = function (row) {
        return 'business/approval/index?object_type=expense_request&object_id=' + row.id;
    };

    var paymentPlanUrl = function (row) {
        return 'business/payment_plan/index?ids=' + row.payment_plan_id;
    };

    var Controller = {
        index: function () {
            Table.api.init({
                extend: {
                    index_url: 'business/expense_request/index' + location.search,
                    add_url: 'business/expense_request/add',
                    edit_url: 'business/expense_request/edit',
                    del_url: 'business/expense_request/del',
                    multi_url: 'business/expense_request/multi',
                    table: 'business_expense_request'
                }
            });

            var table = $('#table');

            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'requested_at',
                sortOrder: 'desc',
                fixedColumns: true,
                fixedRightNumber: 1,
                queryParams: function (params) {
                    var filter = parseJson(params.filter, {});
                    var op = parseJson(params.op, {});
                    $.extend(filter, presetQuery.filter);
                    $.extend(op, presetQuery.op);
                    params.filter = JSON.stringify(filter);
                    params.op = JSON.stringify(op);
                    return params;
                },
                columns: [[
                    {checkbox: true},
                    {field: 'request_no', title: '申请编号', operate: 'LIKE'},
                    {field: 'title', title: '申请标题', operate: 'LIKE'},
                    {field: 'expense_type', title: '费用类型', searchList: expenseTypeMap, formatter: Table.api.formatter.normal},
                    {field: 'status', title: '当前状态', searchList: statusMap, formatter: Table.api.formatter.status},
                    {field: 'approval_status', title: '审批状态', searchList: approvalStatusMap, formatter: Table.api.formatter.status},
                    {field: 'owner', title: '负责人', operate: 'LIKE'},
                    {field: 'request_amount', title: '申请金额', operate: 'BETWEEN'},
                    {field: 'supplier_name', title: '供应商', operate: 'LIKE'},
                    {field: 'customer_name', title: '客户', operate: 'LIKE'},
                    {field: 'contract_name', title: '合同', operate: 'LIKE'},
                    {field: 'requested_at', title: '申请时间', operate: 'RANGE', addclass: 'datetimerange', autocomplete: false},
                    {field: 'expected_pay_date', title: '计划付款日期', operate: 'RANGE', addclass: 'datetimerange', autocomplete: false},
                    {
                        field: 'operate',
                        title: __('Operate'),
                        table: table,
                        events: Table.api.events.operate,
                        buttons: [
                            {
                                name: 'approval_create',
                                text: '发起审批',
                                title: '发起审批',
                                icon: 'fa fa-check-square-o',
                                classname: 'btn btn-primary btn-xs btn-addtabs',
                                url: approvalCreateUrl,
                                visible: function (row) {
                                    return row.approval_status !== 'pending' && (row.status === 'draft' || row.status === 'rejected');
                                }
                            },
                            {
                                name: 'approval_history',
                                text: '审批记录',
                                title: '审批记录',
                                icon: 'fa fa-list-alt',
                                classname: 'btn btn-info btn-xs btn-addtabs',
                                url: approvalHistoryUrl,
                                visible: function (row) {
                                    return row.approval_status && row.approval_status !== 'none';
                                }
                            },
                            {
                                name: 'create_payment_plan',
                                text: '生成付款计划',
                                title: '生成付款计划',
                                icon: 'fa fa-credit-card',
                                classname: 'btn btn-success btn-xs btn-ajax',
                                url: function (row) {
                                    return 'business/expense_request/createpaymentplan/ids/' + row.id;
                                },
                                confirm: '确认根据这条费用申请生成付款计划吗？',
                                visible: function (row) {
                                    return row.approval_status === 'approved' && row.status === 'approved' && (!row.payment_plan_id || row.payment_plan_id === 0);
                                }
                            },
                            {
                                name: 'view_payment_plan',
                                text: '付款计划',
                                title: '查看付款计划',
                                icon: 'fa fa-credit-card',
                                classname: 'btn btn-warning btn-xs btn-addtabs',
                                url: paymentPlanUrl,
                                visible: function (row) {
                                    return row.payment_plan_id && row.payment_plan_id > 0;
                                }
                            }
                        ],
                        formatter: Table.api.formatter.operate
                    }
                ]]
            });

            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($('form[role=form]'));
            }
        }
    };

    return Controller;
});
