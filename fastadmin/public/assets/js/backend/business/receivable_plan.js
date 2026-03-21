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

        ['customer_id', 'contract_id', 'owner_admin_id', 'status'].forEach(function (field) {
            if (!query.get(field)) {
                return;
            }
            preset.filter[field] = query.get(field);
            preset.op[field] = '=';
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

    var statusMap = {
        pending: '待回款',
        processing: '跟进中',
        received: '已到账',
        overdue: '已逾期',
        cancelled: '已取消'
    };

    var Controller = {
        index: function () {
            Table.api.init({
                extend: {
                    index_url: 'business/receivable_plan/index' + location.search,
                    add_url: 'business/receivable_plan/add',
                    edit_url: 'business/receivable_plan/edit',
                    del_url: 'business/receivable_plan/del',
                    multi_url: 'business/receivable_plan/multi',
                    table: 'business_receivable_plan'
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
                    {field: 'customer_name', title: '客户', operate: 'LIKE'},
                    {field: 'contract_name', title: '合同', operate: 'LIKE'},
                    {field: 'status', title: '状态', searchList: statusMap, formatter: Table.api.formatter.status},
                    {field: 'owner', title: '负责人', operate: 'LIKE'},
                    {field: 'amount', title: '应回款金额', operate: 'BETWEEN'},
                    {field: 'due_date', title: '计划回款日期', operate: 'RANGE', addclass: 'datetimerange', autocomplete: false},
                    {field: 'actual_received_at', title: '实际回款时间', operate: 'RANGE', addclass: 'datetimerange', autocomplete: false},
                    {field: 'notes', title: '备注', operate: 'LIKE'},
                    {
                        field: 'operate',
                        title: __('Operate'),
                        table: table,
                        events: Table.api.events.operate,
                        buttons: [
                            {
                                name: 'contract',
                                text: '合同台账',
                                title: '查看合同台账',
                                icon: 'fa fa-file-text-o',
                                classname: 'btn btn-info btn-xs btn-addtabs',
                                url: function (row) {
                                    return 'business/contract/index?contract_id=' + row.contract_id;
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
