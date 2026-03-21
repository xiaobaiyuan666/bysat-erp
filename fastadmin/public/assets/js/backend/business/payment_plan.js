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

    var formatDate = function (date) {
        var year = date.getFullYear();
        var month = String(date.getMonth() + 1).padStart(2, '0');
        var day = String(date.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    };

    var buildPresetQuery = function () {
        var query = new URLSearchParams(window.location.search);
        var preset = {filter: {}, op: {}};

        ['customer_id', 'contract_id', 'ids', 'status', 'approval_status', 'owner_admin_id'].forEach(function (field) {
            if (!query.get(field)) {
                return;
            }
            var targetField = field === 'ids' ? 'id' : field;
            preset.filter[targetField] = query.get(field);
            preset.op[targetField] = '=';
        });

        if (query.get('due_scope') === 'week') {
            var start = new Date();
            var day = start.getDay() || 7;
            start.setDate(start.getDate() - day + 1);
            var end = new Date(start.getTime());
            end.setDate(start.getDate() + 6);
            preset.filter.due_date = formatDate(start) + ' - ' + formatDate(end);
            preset.op.due_date = 'RANGE';
        }

        if (query.get('due_scope') === 'overdue' && !preset.filter.status) {
            preset.filter.status = 'overdue';
            preset.op.status = '=';
        }

        return preset;
    };

    var presetQuery = buildPresetQuery();

    var planTypeMap = {
        supplier: '供应商付款',
        implementation: '实施成本',
        commission: '渠道返佣',
        service: '服务采购',
        refund: '退款支出',
        other: '其他'
    };

    var statusMap = {
        pending: '待付款',
        processing: '跟进中',
        paid: '已付款',
        overdue: '已逾期',
        cancelled: '已取消'
    };

    var approvalStatusMap = {
        none: '未发起',
        pending: '待审批',
        approved: '已通过',
        rejected: '已驳回',
        cancelled: '已撤回'
    };

    var approvalHistoryUrl = function (row) {
        return 'business/approval/index?object_type=payment_plan&object_id=' + row.id;
    };

    var approvalCreateUrl = function (row) {
        return 'business/approval/add?object_type=payment_plan&object_id=' + row.id;
    };

    var Controller = {
        index: function () {
            Table.api.init({
                extend: {
                    index_url: 'business/payment_plan/index' + location.search,
                    add_url: 'business/payment_plan/add',
                    edit_url: 'business/payment_plan/edit',
                    del_url: 'business/payment_plan/del',
                    multi_url: 'business/payment_plan/multi',
                    table: 'business_payment_plan'
                }
            });

            var table = $('#table');

            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'due_date',
                sortOrder: 'asc',
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
                    {field: 'title', title: '计划标题', operate: 'LIKE'},
                    {field: 'payee_name', title: '付款对象', operate: 'LIKE'},
                    {field: 'plan_type', title: '付款类型', searchList: planTypeMap, formatter: Table.api.formatter.normal},
                    {field: 'status', title: '状态', searchList: statusMap, formatter: Table.api.formatter.status},
                    {field: 'approval_status', title: '审批状态', searchList: approvalStatusMap, formatter: Table.api.formatter.status},
                    {field: 'owner', title: '负责人', operate: 'LIKE'},
                    {field: 'amount', title: '应付款金额', operate: 'BETWEEN'},
                    {field: 'customer_name', title: '客户', operate: 'LIKE'},
                    {field: 'contract_name', title: '合同', operate: 'LIKE'},
                    {field: 'purchase_order_title', title: '采购单', operate: 'LIKE'},
                    {field: 'expense_request_title', title: '费用申请', operate: 'LIKE'},
                    {field: 'due_date', title: '计划付款日期', operate: 'RANGE', addclass: 'datetimerange', autocomplete: false},
                    {field: 'actual_paid_at', title: '实际付款时间', operate: 'RANGE', addclass: 'datetimerange', autocomplete: false},
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
                                    return row.approval_status !== 'pending' && (row.status === 'pending' || row.status === 'overdue');
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
