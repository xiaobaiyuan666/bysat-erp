define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            Table.api.init({
                extend: {
                    index_url: 'business/approval_template/index' + location.search,
                    add_url: 'business/approval_template/add',
                    edit_url: 'business/approval_template/edit',
                    del_url: 'business/approval_template/del',
                    multi_url: 'business/approval_template/multi',
                    table: 'business_approval_template'
                }
            });

            var table = $('#table');

            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [[
                    {checkbox: true},
                    {field: 'name', title: '模板名称', operate: 'LIKE'},
                    {
                        field: 'object_type',
                        title: '审批对象',
                        searchList: {
                            contract: '合同审批',
                            payment_plan: '付款审批',
                            expense_request: '费用审批',
                            purchase_order: '采购审批'
                        },
                        formatter: Table.api.formatter.normal
                    },
                    {field: 'step_count', title: '有效节点数', operate: false},
                    {
                        field: 'is_default',
                        title: '默认模板',
                        searchList: {0: '普通模板', 1: '默认模板'},
                        formatter: function (value) {
                            return value == 1 ? '<span class="label label-success">默认模板</span>' : '<span class="label label-default">普通模板</span>';
                        }
                    },
                    {
                        field: 'amount_range',
                        title: '金额范围',
                        operate: false,
                        formatter: function (value, row) {
                            var min = parseFloat(row.min_amount || 0).toFixed(2);
                            var maxRaw = parseFloat(row.max_amount || 0);
                            if (maxRaw > 0) {
                                return min + ' - ' + maxRaw.toFixed(2);
                            }
                            return min + ' 起 / 不限上限';
                        }
                    },
                    {
                        field: 'status',
                        title: '模板状态',
                        searchList: {active: '启用', inactive: '停用'},
                        formatter: Table.api.formatter.status
                    },
                    {field: 'description', title: '模板说明', operate: 'LIKE'},
                    {
                        field: 'operate',
                        title: __('Operate'),
                        table: table,
                        events: Table.api.events.operate,
                        buttons: [
                            {
                                name: 'steps',
                                text: '配置节点',
                                title: '配置审批节点',
                                icon: 'fa fa-list-ol',
                                classname: 'btn btn-info btn-xs btn-addtabs',
                                url: function (row) {
                                    return 'business/approval_template_step/index?template_id=' + row.id;
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
