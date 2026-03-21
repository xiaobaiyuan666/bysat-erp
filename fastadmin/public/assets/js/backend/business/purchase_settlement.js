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
        ['purchase_order_id', 'payment_plan_id', 'ids', 'status'].forEach(function (field) {
            if (query.get(field)) {
                var targetField = field === 'ids' ? 'id' : field;
                preset.filter[targetField] = query.get(field);
                preset.op[targetField] = '=';
            }
        });
        return preset;
    };

    var presetQuery = buildPresetQuery();

    var purchaseOrderUrl = function (row) {
        return 'business/purchase_order/index?ids=' + row.purchase_order_id;
    };

    var paymentPlanUrl = function (row) {
        return 'business/payment_plan/index?ids=' + row.payment_plan_id;
    };

    var invoiceUrl = function (row) {
        return 'business/purchase_invoice/index?settlement_id=' + row.id;
    };

    var paymentRequestCreateUrl = function (row) {
        var url = 'business/payment_request/add?settlement_id=' + row.id;
        if (row.payment_plan_id && row.payment_plan_id > 0) {
            url += '&payment_plan_id=' + row.payment_plan_id;
        }
        if (row.purchase_order_id && row.purchase_order_id > 0) {
            url += '&purchase_order_id=' + row.purchase_order_id;
        }
        return url;
    };

    var paymentRequestUrl = function (row) {
        return 'business/payment_request/index?settlement_id=' + row.id;
    };

    var Controller = {
        index: function () {
            Table.api.init({
                extend: {
                    index_url: 'business/purchase_settlement/index' + location.search,
                    add_url: 'business/purchase_settlement/add',
                    edit_url: 'business/purchase_settlement/edit',
                    del_url: 'business/purchase_settlement/del',
                    multi_url: 'business/purchase_settlement/multi',
                    table: 'business_purchase_settlement'
                }
            });

            var table = $('#table');

            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
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
                    {field: 'settlement_no', title: '结算编号', operate: 'LIKE'},
                    {field: 'title', title: '结算标题', operate: 'LIKE'},
                    {field: 'purchase_order_title', title: '采购单', operate: 'LIKE'},
                    {field: 'payment_plan_title', title: '付款计划', operate: 'LIKE'},
                    {field: 'supplier_name', title: '供应商', operate: 'LIKE'},
                    {field: 'settlement_amount', title: '结算金额', operate: 'BETWEEN'},
                    {field: 'paid_amount', title: '已付金额', operate: 'BETWEEN'},
                    {field: 'balance_amount', title: '未结金额', operate: 'BETWEEN'},
                    {
                        field: 'invoice_status',
                        title: '发票状态',
                        searchList: {
                            none: '未到票',
                            partial: '部分到票',
                            received: '已到票'
                        },
                        formatter: Table.api.formatter.status
                    },
                    {
                        field: 'status',
                        title: '结算状态',
                        searchList: {
                            draft: '草稿',
                            reconciling: '对账中',
                            confirmed: '已确认',
                            settled: '已结清',
                            cancelled: '已取消'
                        },
                        formatter: Table.api.formatter.status
                    },
                    {field: 'owner', title: '负责人', operate: 'LIKE'},
                    {field: 'settled_at', title: '结清时间', operate: 'RANGE', addclass: 'datetimerange', autocomplete: false},
                    {
                        field: 'operate',
                        title: __('Operate'),
                        table: table,
                        events: Table.api.events.operate,
                        buttons: [
                            {
                                name: 'view_purchase_order',
                                text: '查看采购单',
                                title: '查看采购单',
                                icon: 'fa fa-shopping-cart',
                                classname: 'btn btn-info btn-xs btn-addtabs',
                                url: purchaseOrderUrl,
                                visible: function (row) {
                                    return row.purchase_order_id && row.purchase_order_id > 0;
                                }
                            },
                            {
                                name: 'view_payment_plan',
                                text: '查看付款计划',
                                title: '查看付款计划',
                                icon: 'fa fa-credit-card',
                                classname: 'btn btn-warning btn-xs btn-addtabs',
                                url: paymentPlanUrl,
                                visible: function (row) {
                                    return row.payment_plan_id && row.payment_plan_id > 0;
                                }
                            },
                            {
                                name: 'create_payment_request',
                                text: '发起付款申请',
                                title: '发起付款申请',
                                icon: 'fa fa-credit-card-alt',
                                classname: 'btn btn-success btn-xs btn-addtabs',
                                url: paymentRequestCreateUrl,
                                visible: function (row) {
                                    return row.payment_plan_id && row.payment_plan_id > 0 && row.status !== 'cancelled';
                                }
                            },
                            {
                                name: 'view_payment_request',
                                text: '查看付款申请',
                                title: '查看付款申请',
                                icon: 'fa fa-list-alt',
                                classname: 'btn btn-default btn-xs btn-addtabs',
                                url: paymentRequestUrl,
                                visible: function (row) {
                                    return row.id && row.id > 0;
                                }
                            },
                            {
                                name: 'view_invoice',
                                text: '查看发票',
                                title: '查看采购发票',
                                icon: 'fa fa-file-text',
                                classname: 'btn btn-warning btn-xs btn-addtabs',
                                url: invoiceUrl,
                                visible: function (row) {
                                    return row.id && row.id > 0;
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
