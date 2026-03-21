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
        if (query.get('template_id')) {
            preset.filter.template_id = query.get('template_id');
            preset.op.template_id = '=';
        }
        return preset;
    };

    var presetQuery = buildPresetQuery();

    var Controller = {
        index: function () {
            Table.api.init({
                extend: {
                    index_url: 'business/approval_template_step/index' + location.search,
                    add_url: 'business/approval_template_step/add' + (location.search || ''),
                    edit_url: 'business/approval_template_step/edit',
                    del_url: 'business/approval_template_step/del',
                    multi_url: 'business/approval_template_step/multi',
                    table: 'business_approval_template_step'
                }
            });

            var table = $('#table');

            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'template_id',
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
                    {field: 'template_name', title: '审批模板', operate: 'LIKE'},
                    {field: 'object_type', title: '审批对象', searchList: {
                        contract: '合同审批',
                        payment_plan: '付款审批',
                        expense_request: '费用审批',
                        purchase_order: '采购审批'
                    }, formatter: Table.api.formatter.normal},
                    {field: 'step_no', title: '审批层级', operate: false},
                    {field: 'step_name', title: '节点名称', operate: 'LIKE'},
                    {field: 'approver_name', title: '审批人', operate: 'LIKE'},
                    {
                        field: 'status',
                        title: '节点状态',
                        searchList: {active: '启用', inactive: '停用'},
                        formatter: Table.api.formatter.status
                    },
                    {field: 'notes', title: '节点说明', operate: 'LIKE'},
                    {
                        field: 'operate',
                        title: __('Operate'),
                        table: table,
                        events: Table.api.events.operate,
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
