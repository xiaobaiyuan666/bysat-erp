define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var appProjectUrl = function (row) {
        return 'app/project/edit/ids/' + row.app_project_id;
    };

    var Controller = {
        index: function () {
            Table.api.init({
                extend: {
                    index_url: 'app/milestone/index' + location.search,
                    add_url: 'app/milestone/add',
                    edit_url: 'app/milestone/edit',
                    del_url: 'app/milestone/del',
                    multi_url: 'app/milestone/multi',
                    import_url: 'app/milestone/import',
                    table: 'app_milestone'
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
                    {field: 'status', title: __('Status'), searchList: {pending: __('Status pending'), doing: __('Status doing'), review: __('Status review'), done: __('Status done'), blocked: __('Status blocked')}, formatter: Table.api.formatter.status},
                    {field: 'progress', title: __('Progress')},
                    {field: 'owner', title: __('Owner'), operate: 'LIKE'},
                    {field: 'due_date', title: __('Due_date'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false},
                    {field: 'deliverable', title: __('Deliverable'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
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
