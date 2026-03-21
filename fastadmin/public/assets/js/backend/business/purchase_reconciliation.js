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

    var settlementAddUrl = function (row) {
        var url = 'business/purchase_settlement/add?purchase_order_id=' + row.purchase_order_id;
        if (row.payment_plan_id && row.payment_plan_id > 0) {
            url += '&payment_plan_id=' + row.payment_plan_id;
        }
        return url;
    };

    var settlementUrl = function (row) {
        return 'business/purchase_settlement/index?purchase_order_id=' + row.purchase_order_id;
    };

    var bindMathEvents = function () {
        var recalc = function () {
            var orderAmount = parseFloat($('#c-order_amount').val() || 0);
            var confirmedAmount = parseFloat($('#c-confirmed_amount').val() || 0);
            $('#c-variance_amount').val((confirmedAmount - orderAmount).toFixed(2));
        };
        $('#c-order_amount,#c-confirmed_amount').on('input change', recalc);
        recalc();
    };

    var Controller = {
        index: function () {
            Table.api.init({
                extend: {
                    index_url: 'business/purchase_reconciliation/index' + location.search,
                    add_url: 'business/purchase_reconciliation/add',
                    edit_url: 'business/purchase_reconciliation/edit',
                    del_url: 'business/purchase_reconciliation/del',
                    multi_url: 'business/purchase_reconciliation/multi',
                    table: 'business_purchase_reconciliation'
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
                    {field: 'reconcile_no', title: '对账编号', operate: 'LIKE'},
                    {field: 'title', title: '对账标题', operate: 'LIKE'},
                    {field: 'purchase_order_title', title: '采购单', operate: 'LIKE'},
                    {field: 'payment_plan_title', title: '付款计划', operate: 'LIKE'},
                    {field: 'supplier_name', title: '供应商', operate: 'LIKE'},
                    {field: 'order_amount', title: '系统金额', operate: 'BETWEEN'},
                    {field: 'confirmed_amount', title: '确认金额', operate: 'BETWEEN'},
                    {field: 'variance_amount', title: '差异金额', operate: 'BETWEEN'},
                    {
                        field: 'status',
                        title: '对账状态',
                        searchList: {
                            draft: '草稿',
                            reconciling: '对账中',
                            confirmed: '已确认',
                            disputed: '有差异',
                            closed: '已关闭'
                        },
                        formatter: Table.api.formatter.status
                    },
                    {field: 'owner', title: '负责人', operate: 'LIKE'},
                    {field: 'reconciled_at', title: '对账时间', operate: 'RANGE', addclass: 'datetimerange', autocomplete: false},
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
                                name: 'create_settlement',
                                text: '发起结算',
                                title: '发起采购结算',
                                icon: 'fa fa-balance-scale',
                                classname: 'btn btn-success btn-xs btn-addtabs',
                                url: settlementAddUrl,
                                visible: function (row) {
                                    return row.purchase_order_id && row.purchase_order_id > 0;
                                }
                            },
                            {
                                name: 'view_settlement',
                                text: '查看结算',
                                title: '查看采购结算',
                                icon: 'fa fa-file-text-o',
                                classname: 'btn btn-info btn-xs btn-addtabs',
                                url: settlementUrl,
                                visible: function (row) {
                                    return row.purchase_order_id && row.purchase_order_id > 0;
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
            bindMathEvents();
            Controller.api.bindevent();
        },
        edit: function () {
            bindMathEvents();
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
