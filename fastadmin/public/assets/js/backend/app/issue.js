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
        if (query.get('assignee_admin_id')) {
            preset.filter.assignee_admin_id = query.get('assignee_admin_id');
            preset.op.assignee_admin_id = '=';
        }
        if (query.get('status')) {
            preset.filter.status = query.get('status');
            preset.op.status = '=';
        }
        if (query.get('ids')) {
            preset.filter.id = query.get('ids');
            preset.op.id = 'IN';
        }
        if (query.get('tech_ticket_id')) {
            preset.filter.tech_ticket_id = query.get('tech_ticket_id');
            preset.op.tech_ticket_id = '=';
        }
        if (query.get('app_project_id')) {
            preset.filter.app_project_id = query.get('app_project_id');
            preset.op.app_project_id = '=';
        }
        return preset;
    };

    var presetQuery = buildPresetQuery();

    var yesNoFormatter = function (value) {
        return value ? '<span class="label label-success">是</span>' : '<span class="label label-default">否</span>';
    };

    var Controller = {
        index: function () {
            Table.api.init({
                extend: {
                    index_url: 'app/issue/index' + location.search,
                    add_url: 'app/issue/add',
                    edit_url: 'app/issue/edit',
                    del_url: 'app/issue/del',
                    multi_url: 'app/issue/multi',
                    import_url: 'app/issue/import',
                    table: 'app_issue'
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
                    {field: 'ticket_no', title: '问题单号', operate: 'LIKE'},
                    {field: 'customer', title: '客户名称', operate: 'LIKE'},
                    {field: 'title', title: '问题标题', operate: 'LIKE'},
                    {
                        field: 'category',
                        title: '问题分类',
                        searchList: {
                            bug: 'Bug',
                            usage: '使用咨询',
                            billing: '账单问题',
                            feature: '功能建议',
                            training: '培训问题',
                            other: '其他'
                        },
                        formatter: Table.api.formatter.normal
                    },
                    {
                        field: 'source',
                        title: '来源',
                        searchList: {
                            customer: '客户',
                            training: '培训',
                            sales: '销售',
                            operations: '运营',
                            other: '其他'
                        },
                        formatter: Table.api.formatter.normal
                    },
                    {field: 'assignee', title: '受理人', operate: 'LIKE'},
                    {
                        field: 'status',
                        title: '处理状态',
                        searchList: {
                            'new': '新建',
                            'processing': '处理中',
                            'waiting_customer': '待客户确认',
                            'escalated': '已升级',
                            'resolved': '已解决',
                            'closed': '已关闭'
                        },
                        formatter: Table.api.formatter.status
                    },
                    {
                        field: 'priority',
                        title: '优先级',
                        searchList: {'low': '低', 'medium': '中', 'high': '高', 'urgent': '紧急'},
                        formatter: Table.api.formatter.normal
                    },
                    {field: 'resolve_due_at', title: '承诺解决时间', operate: 'RANGE', addclass: 'datetimerange', autocomplete: false},
                    {field: 'customer_notified', title: '已回告客户', searchList: {0: '否', 1: '是'}, formatter: yesNoFormatter},
                    {field: 'customer_confirmed', title: '客户已确认', searchList: {0: '否', 1: '是'}, formatter: yesNoFormatter},
                    {field: 'last_follow_up_at', title: '最近跟进', operate: 'RANGE', addclass: 'datetimerange', autocomplete: false},
                    {
                        field: 'operate',
                        title: __('Operate'),
                        table: table,
                        events: Table.api.events.operate,
                        buttons: [
                            {
                                name: 'followup',
                                text: '问题跟进',
                                title: '查看问题跟进',
                                icon: 'fa fa-comments',
                                classname: 'btn btn-info btn-xs btn-dialog',
                                url: 'app/issue_followup/index?issue_id={id}',
                                extend: 'data-area=["90%","88%"]'
                            },
                            {
                                name: 'tech_ticket',
                                text: '研发联动',
                                title: '查看研发联动',
                                icon: 'fa fa-code-fork',
                                classname: 'btn btn-success btn-xs btn-dialog',
                                url: 'app/tech_ticket/edit/ids/{tech_ticket_id}',
                                visible: function (row) {
                                    return parseInt(row.tech_ticket_id, 10) > 0;
                                },
                                extend: 'data-area=["88%","88%"]'
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
