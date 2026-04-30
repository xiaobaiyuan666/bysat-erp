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
                    {field: 'title', title: '标题', operate: 'LIKE'},
                    {field: 'app_module', title: '模块', operate: 'LIKE'},
                    {
                        field: 'type',
                        title: '类型',
                        searchList: {bug: 'Bug', improvement: '优化', upgrade: '升级', task: '任务'},
                        formatter: Table.api.formatter.normal
                    },
                    {
                        field: 'status',
                        title: '状态',
                        searchList: {pending: '待处理', processing: '处理中', testing: '待测试', ready: '待发布', done: '已完成', closed: '已关闭'},
                        formatter: Table.api.formatter.status
                    },
                    {
                        field: 'priority',
                        title: '优先级',
                        searchList: {low: '低', medium: '中', high: '高', urgent: '紧急'},
                        formatter: Table.api.formatter.normal
                    },
                    {field: 'owner', title: '负责人', operate: 'LIKE'},
                    {field: 'due_date', title: '截止日期', operate: 'RANGE', addclass: 'datetimerange', autocomplete: false},
                    {
                        field: 'operate',
                        title: __('Operate'),
                        table: table,
                        events: Table.api.events.operate,
                        buttons: [
                            {
                                name: 'app_project',
                                text: '所属 APP',
                                title: '查看所属 APP 项目',
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
