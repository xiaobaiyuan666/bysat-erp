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
        ['issue_id', 'type', 'visibility', 'status', 'created_by_admin_id'].forEach(function (field) {
            if (!query.get(field)) {
                return;
            }
            preset.filter[field] = query.get(field);
            preset.op[field] = '=';
        });
        return preset;
    };

    var presetQuery = buildPresetQuery();

    var typeMap = {
        status: '状态更新',
        follow_up: '跟进记录',
        internal: '内部备注',
        leader: '领导同步',
        release: '版本说明'
    };

    var visibilityMap = {
        internal: '内部可见',
        customer: '客户回告',
        leader: '领导可见'
    };

    var statusMap = {
        new: '新建',
        processing: '处理中',
        waiting_customer: '待客户确认',
        escalated: '已升级',
        resolved: '已解决',
        closed: '已关闭'
    };

    var Controller = {
        index: function () {
            Table.api.init({
                extend: {
                    index_url: 'app/issue_followup/index' + location.search,
                    add_url: 'app/issue_followup/add' + location.search,
                    edit_url: 'app/issue_followup/edit',
                    del_url: 'app/issue_followup/del',
                    multi_url: 'app/issue_followup/multi',
                    import_url: 'app/issue_followup/import',
                    table: 'app_issue_followup'
                }
            });

            var table = $('#table');

            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'record_updated_at',
                sortOrder: 'desc',
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
                    {
                        field: 'issue_id',
                        title: '问题编号',
                        operate: '=',
                        formatter: function (value, row) {
                            return '<span class="text-muted">' + (row.issue_legacy_id ? row.issue_legacy_id : ('#' + value)) + '</span>';
                        }
                    },
                    {field: 'type', title: '记录类型', searchList: typeMap, formatter: Table.api.formatter.normal},
                    {field: 'visibility', title: '可见范围', searchList: visibilityMap, formatter: Table.api.formatter.normal},
                    {field: 'status', title: '问题状态', searchList: statusMap, formatter: Table.api.formatter.normal},
                    {field: 'content', title: '跟进内容', operate: 'LIKE', class: 'autocontent', formatter: Table.api.formatter.content},
                    {field: 'next_action', title: '下一步动作', operate: 'LIKE', class: 'autocontent', formatter: Table.api.formatter.content},
                    {field: 'created_by_name', title: '记录人', operate: 'LIKE'},
                    {field: 'record_updated_at', title: '更新时间', operate: 'RANGE', addclass: 'datetimerange', autocomplete: false},
                    {
                        field: 'operate',
                        title: __('Operate'),
                        table: table,
                        events: Table.api.events.operate,
                        buttons: [
                            {
                                name: 'view_issue',
                                text: '所属问题',
                                title: '查看所属问题',
                                icon: 'fa fa-ticket',
                                classname: 'btn btn-info btn-xs btn-dialog',
                                url: 'app/issue/edit/ids/{issue_id}',
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
