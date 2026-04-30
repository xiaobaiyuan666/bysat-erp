define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var escapeHtml = function (value) {
        return $('<div/>').text(value || '').html();
    };

    var projectUrl = function (row) {
        return 'app/project/edit/ids/' + row.app_project_id;
    };

    var formatBlockers = function (value) {
        if (!value) {
            return '<span class="text-muted">无阻塞</span>';
        }

        return '<span class="label label-danger">有阻塞</span><div style="margin-top:6px;white-space:normal;line-height:1.7;color:#555;">' + escapeHtml(value) + '</div>';
    };

    var Controller = {
        index: function () {
            Table.api.init({
                extend: {
                    index_url: 'app/report/index' + location.search,
                    add_url: 'app/report/add',
                    edit_url: 'app/report/edit',
                    del_url: 'app/report/del',
                    multi_url: 'app/report/multi',
                    import_url: 'app/report/import',
                    table: 'app_report'
                }
            });

            var table = $('#table');

            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'report_date',
                sortOrder: 'desc',
                fixedColumns: true,
                fixedRightNumber: 1,
                columns: [[
                    {checkbox: true},
                    {field: 'report_date', title: '汇报日期', operate: 'RANGE', addclass: 'datetimerange', autocomplete: false},
                    {field: 'owner', title: '汇报人', operate: 'LIKE'},
                    {field: 'summary', title: '本周概述', operate: 'LIKE', class: 'autocontent', formatter: Table.api.formatter.content},
                    {field: 'result', title: '阶段结果', operate: 'LIKE', class: 'autocontent', formatter: Table.api.formatter.content},
                    {field: 'blockers', title: '阻塞事项', operate: 'LIKE', formatter: formatBlockers},
                    {
                        field: 'operate',
                        title: __('Operate'),
                        table: table,
                        events: Table.api.events.operate,
                        buttons: [
                            {
                                name: 'app_project',
                                text: '所属项目',
                                title: '查看所属项目',
                                icon: 'fa fa-sitemap',
                                classname: 'btn btn-primary btn-xs btn-dialog',
                                url: projectUrl,
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
