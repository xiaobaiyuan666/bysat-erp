define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'app/milestone/index' + location.search,
                    add_url: 'app/milestone/add',
                    edit_url: 'app/milestone/edit',
                    del_url: 'app/milestone/del',
                    multi_url: 'app/milestone/multi',
                    import_url: 'app/milestone/import',
                    table: 'app_milestone',
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
                        {field: 'status', title: __('Status'), searchList: {"pending":__('Status pending'),"doing":__('Status doing'),"review":__('Status review'),"done":__('Status done'),"blocked":__('Status blocked')}, formatter: Table.api.formatter.status},
                        {field: 'progress', title: __('Progress')},
                        {field: 'owner', title: __('Owner'), operate: 'LIKE'},
                        {field: 'due_date', title: __('Due_date'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        {field: 'deliverable', title: __('Deliverable'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
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
