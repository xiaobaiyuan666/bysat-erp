define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var appProjectUrl = function (row) {
        return 'app/project/edit/ids/' + row.app_project_id;
    };

    var Controller = {
        index: function () {
            Table.api.init({
                extend: {
                    index_url: 'app/risk/index' + location.search,
                    add_url: 'app/risk/add',
                    edit_url: 'app/risk/edit',
                    del_url: 'app/risk/del',
                    multi_url: 'app/risk/multi',
                    import_url: 'app/risk/import',
                    table: 'app_risk'
                }
            });

            var table = $('#table');

            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                fixedColumns: true,
                fixedRightNumber: 1,
                columns: [[
                    {checkbox: true},
                    {field: 'title', title: __('Title'), operate: 'LIKE'},
                    {field: 'type', title: __('Type'), searchList: {risk: __('Type risk'), issue: __('Type issue'), change: __('Type change'), dependency: __('Type dependency')}, formatter: Table.api.formatter.normal},
                    {field: 'level', title: __('Level'), searchList: {low: __('Level low'), medium: __('Level medium'), high: __('Level high'), critical: __('Level critical')}, formatter: Table.api.formatter.normal},
                    {field: 'status', title: __('Status'), searchList: {open: __('Status open'), tracking: __('Status tracking'), resolved: __('Status resolved'), closed: __('Status closed')}, formatter: Table.api.formatter.status},
                    {field: 'owner', title: __('Owner'), operate: 'LIKE'},
                    {field: 'due_date', title: __('Due_date'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false},
                    {
                        field: 'operate',
                        title: __('Operate'),
                        table: table,
                        events: Table.api.events.operate,
                        buttons: [
                            {
                                name: 'app_project',
                                text: '所属项目',
                                title: '查看所属运营项目',
                                icon: 'fa fa-sitemap',
                                classname: 'btn btn-primary btn-xs btn-dialog',
                                url: appProjectUrl,
                                visible: function (row) {
                                    return parseInt(row.app_project_id, 10) > 0;
                                },
                                extend: 'data-area=["82%","88%"]'
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
