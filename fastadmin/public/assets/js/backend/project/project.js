define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'project/project/index' + location.search,
                    add_url: 'project/project/add',
                    edit_url: 'project/project/edit',
                    del_url: 'project/project/del',
                    multi_url: 'project/project/multi',
                    import_url: 'project/project/import',
                    table: 'project',
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
                columns: [
                    [
                        {checkbox: true},
                        {field: 'name', title: __('Name'), operate: 'LIKE'},
                        {field: 'client', title: __('Client'), operate: 'LIKE'},
                        {field: 'owner', title: __('Owner'), operate: 'LIKE'},
                        {field: 'status', title: __('Status'), searchList: {"planning":__('Status planning'),"active":__('Status active'),"delivery":__('Status delivery'),"completed":__('Status completed'),"paused":__('Status paused'),"closed":__('Status closed')}, formatter: Table.api.formatter.status},
                        {field: 'priority', title: __('Priority'), searchList: {"low":__('Priority low'),"medium":__('Priority medium'),"high":__('Priority high'),"urgent":__('Priority urgent')}, formatter: Table.api.formatter.normal},
                        {field: 'budget', title: __('Budget'), operate:'BETWEEN'},
                        {field: 'start_date', title: __('Start_date'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        {field: 'due_date', title: __('Due_date'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
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
