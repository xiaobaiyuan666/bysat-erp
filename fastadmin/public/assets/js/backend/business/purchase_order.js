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

    var purchaseTypeMap = {
        software: '软件订阅',
        cloud: '云资源采购',
        service: '服务采购',
        outsourcing: '外包合作',
        marketing: '营销投放',
        hardware: '硬件设备',
        office: '办公采购',
        other: '其他'
    };

    var statusMap = {
        draft: '草稿',
        pending_approval: '审批中',
        approved: '已批准',
        processing: '处理中',
        completed: '已完成',
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
        return 'business/approval/add?object_type=purchase_order&object_id=' + row.id;
    };

    var approvalHistoryUrl = function (row) {
        return 'business/approval/index?object_type=purchase_order&object_id=' + row.id;
    };

    var paymentPlanUrl = function (row) {
        return 'business/payment_plan/index?ids=' + row.payment_plan_id;
    };

    var reconciliationAddUrl = function (row) {
        var url = 'business/purchase_reconciliation/add?purchase_order_id=' + row.id;
        if (row.payment_plan_id && row.payment_plan_id > 0) {
            url += '&payment_plan_id=' + row.payment_plan_id;
        }
        return url;
    };

    var reconciliationUrl = function (row) {
        return row.reconciliation_id && row.reconciliation_id > 0
            ? 'business/purchase_reconciliation/index?ids=' + row.reconciliation_id
            : 'business/purchase_reconciliation/index?purchase_order_id=' + row.id;
    };

    var settlementAddUrl = function (row) {
        var url = 'business/purchase_settlement/add?purchase_order_id=' + row.id;
        if (row.payment_plan_id && row.payment_plan_id > 0) {
            url += '&payment_plan_id=' + row.payment_plan_id;
        }
        return url;
    };

    var settlementUrl = function (row) {
        return row.settlement_id && row.settlement_id > 0
            ? 'business/purchase_settlement/index?ids=' + row.settlement_id
            : 'business/purchase_settlement/index?purchase_order_id=' + row.id;
    };

    var invoiceUrl = function (row) {
        return row.settlement_id && row.settlement_id > 0
            ? 'business/purchase_invoice/index?settlement_id=' + row.settlement_id
            : 'business/purchase_invoice/index?purchase_order_id=' + row.id;
    };

    var Controller = {
        index: function () {
            Table.api.init({
                extend: {
                    index_url: 'business/purchase_order/index' + location.search,
                    add_url: 'business/purchase_order/add',
                    edit_url: 'business/purchase_order/edit',
                    del_url: 'business/purchase_order/del',
                    multi_url: 'business/purchase_order/multi',
                    table: 'business_purchase_order'
                }
            });

            var table = $('#table');

            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'ordered_at',
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
                    {field: 'order_no', title: '采购编号', operate: 'LIKE'},
                    {field: 'title', title: '采购标题', operate: 'LIKE'},
                    {field: 'supplier_name', title: '供应商', operate: 'LIKE'},
                    {field: 'purchase_type', title: '采购类型', searchList: purchaseTypeMap, formatter: Table.api.formatter.normal},
                    {field: 'status', title: '采购状态', searchList: statusMap, formatter: Table.api.formatter.status},
                    {field: 'approval_status', title: '审批状态', searchList: approvalStatusMap, formatter: Table.api.formatter.status},
                    {field: 'owner', title: '负责人', operate: 'LIKE'},
                    {field: 'order_amount', title: '采购金额', operate: 'BETWEEN'},
                    {field: 'customer_name', title: '客户', operate: 'LIKE'},
                    {field: 'contract_name', title: '合同', operate: 'LIKE'},
                    {field: 'payment_plan_title', title: '付款计划', operate: 'LIKE'},
                    {field: 'reconciliation_title', title: '采购对账', operate: 'LIKE'},
                    {field: 'settlement_title', title: '采购结算', operate: 'LIKE'},
                    {field: 'ordered_at', title: '下单时间', operate: 'RANGE', addclass: 'datetimerange', autocomplete: false},
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
                                    return 'business/purchase_order/createpaymentplan/ids/' + row.id;
                                },
                                confirm: '确认根据这条采购单生成付款计划吗？',
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
                            },
                            {
                                name: 'create_reconciliation',
                                text: '发起对账',
                                title: '发起采购对账',
                                icon: 'fa fa-random',
                                classname: 'btn btn-success btn-xs btn-addtabs',
                                url: reconciliationAddUrl,
                                visible: function (row) {
                                    return row.payment_plan_id && row.payment_plan_id > 0 && (!row.reconciliation_id || row.reconciliation_id === 0);
                                }
                            },
                            {
                                name: 'view_reconciliation',
                                text: '采购对账',
                                title: '查看采购对账',
                                icon: 'fa fa-exchange',
                                classname: 'btn btn-info btn-xs btn-addtabs',
                                url: reconciliationUrl,
                                visible: function (row) {
                                    return row.reconciliation_id && row.reconciliation_id > 0;
                                }
                            },
                            {
                                name: 'create_settlement',
                                text: '发起结算',
                                title: '发起采购结算',
                                icon: 'fa fa-balance-scale',
                                classname: 'btn btn-success btn-xs btn-addtabs',
                                url: settlementAddUrl,
                                visible: function (row) {
                                    return row.payment_plan_id && row.payment_plan_id > 0 && (!row.settlement_id || row.settlement_id === 0);
                                }
                            },
                            {
                                name: 'view_settlement',
                                text: '采购结算',
                                title: '查看采购结算',
                                icon: 'fa fa-balance-scale',
                                classname: 'btn btn-default btn-xs btn-addtabs',
                                url: settlementUrl,
                                visible: function (row) {
                                    return row.settlement_id && row.settlement_id > 0;
                                }
                            },
                            {
                                name: 'view_invoice',
                                text: '采购发票',
                                title: '查看采购发票',
                                icon: 'fa fa-file-text-o',
                                classname: 'btn btn-default btn-xs btn-addtabs',
                                url: invoiceUrl
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
