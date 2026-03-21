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
        ['purchase_order_id', 'settlement_id', 'payment_plan_id', 'ids', 'status', 'approval_status', 'owner_admin_id'].forEach(function (field) {
            if (!query.get(field)) {
                return;
            }
            var targetField = field === 'ids' ? 'id' : field;
            preset.filter[targetField] = query.get(field);
            preset.op[targetField] = '=';
        });
        return preset;
    };

    var presetQuery = buildPresetQuery();

    var statusMap = {
        draft: '草稿',
        pending_approval: '待审批',
        approved: '已批准',
        paid: '已付款',
        rejected: '已驳回',
        cancelled: '已取消'
    };

    var approvalStatusMap = {
        none: '未发起',
        pending: '审批中',
        approved: '已通过',
        rejected: '已驳回',
        cancelled: '已撤回'
    };

    var Controller = {
        index: function () {
            Table.api.init({
                extend: {
                    index_url: 'business/payment_request/index' + location.search,
                    add_url: 'business/payment_request/add',
                    edit_url: 'business/payment_request/edit',
                    del_url: 'business/payment_request/del',
                    multi_url: 'business/payment_request/multi',
                    table: 'business_payment_request'
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
                    {field: 'request_no', title: '申请单号', operate: 'LIKE'},
                    {field: 'title', title: '申请标题', operate: 'LIKE'},
                    {field: 'status', title: '当前状态', searchList: statusMap, formatter: Table.api.formatter.status},
                    {field: 'approval_status', title: '审批状态', searchList: approvalStatusMap, formatter: Table.api.formatter.status},
                    {field: 'owner', title: '负责人', operate: 'LIKE'},
                    {field: 'supplier_name', title: '供应商', operate: 'LIKE'},
                    {field: 'settlement_title', title: '采购结算', operate: 'LIKE'},
                    {field: 'payment_plan_title', title: '付款计划', operate: 'LIKE'},
                    {field: 'request_amount', title: '申请金额', operate: 'BETWEEN'},
                    {field: 'paid_amount', title: '已付金额', operate: 'BETWEEN'},
                    {field: 'requested_at', title: '申请时间', operate: 'RANGE', addclass: 'datetimerange', autocomplete: false},
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
                                url: function (row) {
                                    return 'business/approval/add?object_type=payment_request&object_id=' + row.id;
                                },
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
                                url: function (row) {
                                    return 'business/approval/index?object_type=payment_request&object_id=' + row.id;
                                },
                                visible: function (row) {
                                    return row.approval_status && row.approval_status !== 'none';
                                }
                            },
                            {
                                name: 'mark_paid',
                                text: '标记已付款',
                                title: '标记已付款',
                                icon: 'fa fa-check',
                                classname: 'btn btn-success btn-xs btn-ajax',
                                url: function (row) {
                                    return 'business/payment_request/markpaid/ids/' + row.id;
                                },
                                confirm: '确认将这条付款申请标记为已付款吗？',
                                visible: function (row) {
                                    return row.approval_status === 'approved' && row.status === 'approved';
                                }
                            },
                            {
                                name: 'view_settlement',
                                text: '采购结算',
                                title: '查看采购结算',
                                icon: 'fa fa-balance-scale',
                                classname: 'btn btn-warning btn-xs btn-addtabs',
                                url: function (row) {
                                    return 'business/purchase_settlement/index?ids=' + row.settlement_id;
                                },
                                visible: function (row) {
                                    return row.settlement_id && row.settlement_id > 0;
                                }
                            },
                            {
                                name: 'view_payment_plan',
                                text: '付款计划',
                                title: '查看付款计划',
                                icon: 'fa fa-credit-card',
                                classname: 'btn btn-default btn-xs btn-addtabs',
                                url: function (row) {
                                    return 'business/payment_plan/index?ids=' + row.payment_plan_id;
                                },
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
