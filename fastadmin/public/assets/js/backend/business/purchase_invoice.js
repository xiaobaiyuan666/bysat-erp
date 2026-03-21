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
        ['purchase_order_id', 'settlement_id', 'ids', 'status'].forEach(function (field) {
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

    var settlementUrl = function (row) {
        return 'business/purchase_settlement/index?ids=' + row.settlement_id;
    };

    var bindMathEvents = function () {
        var recalc = function () {
            var invoiceAmount = parseFloat($('#c-invoice_amount').val() || 0);
            var taxAmount = parseFloat($('#c-tax_amount').val() || 0);
            var untaxed = invoiceAmount - taxAmount;
            $('#c-untaxed_amount').val((untaxed > 0 ? untaxed : 0).toFixed(2));
        };
        $('#c-invoice_amount,#c-tax_amount').on('input change', recalc);
        recalc();
    };

    var Controller = {
        index: function () {
            Table.api.init({
                extend: {
                    index_url: 'business/purchase_invoice/index' + location.search,
                    add_url: 'business/purchase_invoice/add',
                    edit_url: 'business/purchase_invoice/edit',
                    del_url: 'business/purchase_invoice/del',
                    multi_url: 'business/purchase_invoice/multi',
                    table: 'business_purchase_invoice'
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
                    {field: 'invoice_no', title: '发票号码', operate: 'LIKE'},
                    {field: 'title', title: '发票标题', operate: 'LIKE'},
                    {field: 'purchase_order_title', title: '采购单', operate: 'LIKE'},
                    {field: 'settlement_title', title: '采购结算', operate: 'LIKE'},
                    {field: 'supplier_name', title: '供应商', operate: 'LIKE'},
                    {
                        field: 'invoice_type',
                        title: '发票类型',
                        searchList: {
                            vat_special: '增值税专票',
                            vat_normal: '增值税普票',
                            service: '服务发票',
                            electronic: '电子发票',
                            other: '其他'
                        },
                        formatter: Table.api.formatter.normal
                    },
                    {field: 'invoice_amount', title: '发票金额', operate: 'BETWEEN'},
                    {field: 'tax_amount', title: '税额', operate: 'BETWEEN'},
                    {
                        field: 'status',
                        title: '状态',
                        searchList: {
                            pending: '待收票',
                            received: '已收票',
                            verified: '已验票',
                            returned: '已退回',
                            cancelled: '已作废'
                        },
                        formatter: Table.api.formatter.status
                    },
                    {field: 'invoiced_at', title: '开票日期', operate: 'RANGE', addclass: 'datetimerange', autocomplete: false},
                    {field: 'received_at', title: '收票时间', operate: 'RANGE', addclass: 'datetimerange', autocomplete: false},
                    {field: 'owner', title: '负责人', operate: 'LIKE'},
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
                                name: 'view_settlement',
                                text: '查看结算',
                                title: '查看采购结算',
                                icon: 'fa fa-balance-scale',
                                classname: 'btn btn-warning btn-xs btn-addtabs',
                                url: settlementUrl,
                                visible: function (row) {
                                    return row.settlement_id && row.settlement_id > 0;
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
