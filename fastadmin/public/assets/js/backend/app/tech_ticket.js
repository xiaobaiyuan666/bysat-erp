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
        if (query.get('ids')) {
            preset.filter.id = query.get('ids');
            preset.op.id = 'IN';
        }
        if (query.get('app_project_id')) {
            preset.filter.app_project_id = query.get('app_project_id');
            preset.op.app_project_id = '=';
        }
        return preset;
    };

    var relatedIssueUrl = function (row) {
        return 'app/issue/index?tech_ticket_id=' + row.id;
    };

    var presetQuery = buildPresetQuery();

    var Controller = {
        index: function () {
            Table.api.init({
                extend: {
                    index_url: 'app/tech_ticket/index' + location.search,
                    add_url: 'app/tech_ticket/add',
                    edit_url: 'app/tech_ticket/edit',
                    del_url: 'app/tech_ticket/del',
                    multi_url: 'app/tech_ticket/multi',
                    import_url: 'app/tech_ticket/import',
                    table: 'app_tech_ticket'
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
                    {field: 'title', title: __('Title'), operate: 'LIKE'},
                    {field: 'type', title: __('Type'), searchList: {'bug': __('Type bug'), 'improvement': __('Type improvement'), 'upgrade': __('Type upgrade'), 'task': __('Type task')}, formatter: Table.api.formatter.normal},
                    {field: 'status', title: __('Status'), searchList: {'pending': __('Status pending'), 'processing': __('Status processing'), 'testing': __('Status testing'), 'ready': __('Status ready'), 'done': __('Status done'), 'closed': __('Status closed')}, formatter: Table.api.formatter.status},
                    {field: 'priority', title: __('Priority'), searchList: {'low': __('Priority low'), 'medium': __('Priority medium'), 'high': __('Priority high'), 'urgent': __('Priority urgent')}, formatter: Table.api.formatter.normal},
                    {field: 'severity', title: __('Severity'), searchList: {'low': __('Severity low'), 'medium': __('Severity medium'), 'high': __('Severity high'), 'blocker': __('Severity blocker')}, formatter: Table.api.formatter.normal},
                    {field: 'source', title: __('Source'), searchList: {'operations': __('Source operations'), 'product': __('Source product'), 'customer': __('Source customer'), 'sales': __('Source sales'), 'service': __('Source service')}, formatter: Table.api.formatter.normal},
                    {field: 'app_module', title: __('App_module'), operate: 'LIKE'},
                    {field: 'app_version', title: __('App_version'), operate: 'LIKE'},
                    {field: 'owner', title: __('Owner'), operate: 'LIKE'},
                    {field: 'reporter', title: __('Reporter'), operate: 'LIKE'},
                    {field: 'due_date', title: __('Due_date'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false},
                    {
                        field: 'operate',
                        title: __('Operate'),
                        table: table,
                        events: Table.api.events.operate,
                        buttons: [
                            {
                                name: 'app_project',
                                text: '所属APP',
                                title: '查看所属APP项目',
                                icon: 'fa fa-mobile',
                                classname: 'btn btn-info btn-xs btn-dialog',
                                url: 'app/project/edit/ids/{app_project_id}',
                                visible: function (row) {
                                    return parseInt(row.app_project_id, 10) > 0;
                                },
                                extend: 'data-area=["82%","88%"]'
                            },
                            {
                                name: 'related_issue',
                                text: '相关问题',
                                title: '查看关联问题记录',
                                icon: 'fa fa-comments',
                                classname: 'btn btn-success btn-xs btn-dialog',
                                url: relatedIssueUrl,
                                extend: 'data-area=["90%","88%"]'
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
