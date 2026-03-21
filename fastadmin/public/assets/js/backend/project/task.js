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
        return preset;
    };

    var presetQuery = buildPresetQuery();

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'project/task/index' + location.search,
                    add_url: 'project/task/add',
                    edit_url: 'project/task/edit',
                    del_url: 'project/task/del',
                    multi_url: 'project/task/multi',
                    import_url: 'project/task/import',
                    table: 'project_task',
                }
            });

            var table = $("#table");

            // 初始化表格
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
                columns: [
                    [
                        {checkbox: true},
                        {field: 'project_id', title: __('Project_id')},
                        {field: 'title', title: __('Title'), operate: 'LIKE'},
                        {field: 'assignee', title: __('Assignee'), operate: 'LIKE'},
                        {field: 'status', title: __('Status'), searchList: {"todo":__('Status todo'),"doing":__('Status doing'),"review":__('Status review'),"done":__('Status done'),"blocked":__('Status blocked'),"overdue":__('Status overdue')}, formatter: Table.api.formatter.status},
                        {field: 'priority', title: __('Priority'), searchList: {"low":__('Priority low'),"medium":__('Priority medium'),"high":__('Priority high'),"urgent":__('Priority urgent')}, formatter: Table.api.formatter.normal},
                        {field: 'due_date', title: __('Due_date'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        {field: 'estimate_hours', title: __('Estimate_hours'), operate:'BETWEEN'},
                        {field: 'actual_hours', title: __('Actual_hours'), operate:'BETWEEN'},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
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
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
