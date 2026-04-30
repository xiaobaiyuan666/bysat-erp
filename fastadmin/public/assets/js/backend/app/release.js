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
                    {field: 'version', title: '版本号', operate: 'LIKE'},
                    {field: 'title', title: '发布标题', operate: 'LIKE'},
                    {field: 'owner', title: '负责人', operate: 'LIKE'},
                    {
                        field: 'status',
                        title: '发布状态',
                        searchList: {
                            planned: '待排期',
                            ready: '待发布',
                            testing: '测试中',
                            released: '已发布',
                            rollback: '已回滚',
                            closed: '已关闭'
                        },
                        formatter: Table.api.formatter.status
                    },
                    {field: 'release_date', title: '发布时间', operate: 'RANGE', addclass: 'datetimerange', autocomplete: false},
                    {
                        field: 'customer_sync_status',
                        title: '客户同步',
                        searchList: {pending: '待回告', done: '已回告', skip: '无需回告'},
                        formatter: Table.api.formatter.status
                    },
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
