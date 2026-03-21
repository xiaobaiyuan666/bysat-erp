define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var parseIds = function (value) {
        if (!value) {
            return [];
        }
        if ($.isArray(value)) {
            return value;
        }
        try {
            var parsed = JSON.parse(value);
            return $.isArray(parsed) ? parsed : [];
        } catch (e) {
            return String(value).split(',').filter(Boolean);
        }
    };

    var issueUrl = function (row) {
        return 'app/issue/index?ids=' + parseIds(row.service_ticket_ids_json).join(',');
    };

    var techTicketUrl = function (row) {
        return 'app/tech_ticket/index?ids=' + parseIds(row.tech_ticket_ids_json).join(',');
    };

    var appProjectUrl = function (row) {
        return 'app/project/edit/ids/' + row.app_project_id;
    };

    var Controller = {
        index: function () {
            Table.api.init({
                extend: {
                    index_url: 'app/release/index' + location.search,
                    add_url: 'app/release/add',
                    edit_url: 'app/release/edit',
                    del_url: 'app/release/del',
                    multi_url: 'app/release/multi',
                    import_url: 'app/release/import',
                    table: 'app_release'
                }
            });

            var table = $('#table');

            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'release_date',
                fixedColumns: true,
                fixedRightNumber: 1,
                columns: [[
                    {checkbox: true},
                    {field: 'version', title: __('Version'), operate: 'LIKE'},
                    {field: 'title', title: __('Title'), operate: 'LIKE'},
                    {field: 'status', title: __('Status'), searchList: {'planned': __('Status planned'), 'ready': __('Status ready'), 'testing': __('Status testing'), 'released': __('Status released'), 'rollback': __('Status rollback'), 'closed': __('Status closed')}, formatter: Table.api.formatter.status},
                    {field: 'owner', title: __('Owner'), operate: 'LIKE'},
                    {field: 'release_date', title: __('Release_date'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false},
                    {field: 'channel', title: __('Channel'), operate: 'LIKE'},
                    {field: 'customer_sync_status', title: __('Customer_sync_status'), searchList: {'pending': __('Customer_sync_status pending'), 'done': __('Customer_sync_status done'), 'skip': __('Customer_sync_status skip')}, formatter: Table.api.formatter.status},
                    {field: 'rollback_ready', title: __('Rollback_ready'), formatter: Table.api.formatter.flag},
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
                                classname: 'btn btn-primary btn-xs btn-dialog',
                                url: appProjectUrl,
                                visible: function (row) {
                                    return parseInt(row.app_project_id, 10) > 0;
                                },
                                extend: 'data-area=["82%","88%"]'
                            },
                            {
                                name: 'related_tech',
                                text: '关联研发',
                                title: '查看关联研发待办',
                                icon: 'fa fa-code-fork',
                                classname: 'btn btn-info btn-xs btn-dialog',
                                url: techTicketUrl,
                                visible: function (row) {
                                    return parseIds(row.tech_ticket_ids_json).length > 0;
                                },
                                extend: 'data-area=["90%","88%"]'
                            },
                            {
                                name: 'related_issue',
                                text: '关联问题',
                                title: '查看关联问题记录',
                                icon: 'fa fa-comments',
                                classname: 'btn btn-success btn-xs btn-dialog',
                                url: issueUrl,
                                visible: function (row) {
                                    return parseIds(row.service_ticket_ids_json).length > 0;
                                },
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
