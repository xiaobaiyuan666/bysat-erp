define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'app/risk/index' + location.search,
                    add_url: 'app/risk/add',
                    edit_url: 'app/risk/edit',
                    del_url: 'app/risk/del',
                    multi_url: 'app/risk/multi',
                    import_url: 'app/risk/import',
                    table: 'app_risk',
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
                        {field: 'title', title: __('Title'), operate: 'LIKE'},
                        {field: 'type', title: __('Type'), searchList: {"risk":__('Type risk'),"issue":__('Type issue'),"change":__('Type change'),"dependency":__('Type dependency')}, formatter: Table.api.formatter.normal},
                        {field: 'level', title: __('Level'), searchList: {"low":__('Level low'),"medium":__('Level medium'),"high":__('Level high'),"critical":__('Level critical')}, formatter: Table.api.formatter.normal},
                        {field: 'status', title: __('Status'), searchList: {"open":__('Status open'),"tracking":__('Status tracking'),"resolved":__('Status resolved'),"closed":__('Status closed')}, formatter: Table.api.formatter.status},
                        {field: 'owner', title: __('Owner'), operate: 'LIKE'},
                        {field: 'due_date', title: __('Due_date'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
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
