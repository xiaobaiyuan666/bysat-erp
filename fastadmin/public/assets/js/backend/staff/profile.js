define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var roleSearchList = {
        admin: '管理员',
        finance: '财务',
        project: '项目',
        operations: '运营',
        service: '客服',
        tech: '技术',
        viewer: '只读'
    };

    var statusSearchList = {
        active: '在职',
        inactive: '停用'
    };

    var canOpenAdmin = function (row) {
        return parseInt(row.admin_id, 10) > 0;
    };

    var Controller = {
        index: function () {
            Table.api.init({
                extend: {
                    index_url: 'staff/profile/index' + location.search,
                    add_url: 'staff/profile/add',
                    edit_url: 'staff/profile/edit',
                    del_url: 'staff/profile/del',
                    multi_url: 'staff/profile/multi',
                    import_url: 'staff/profile/import',
                    table: 'staff_profile'
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
                    {field: 'account', title: '登录账号', operate: 'LIKE'},
                    {field: 'employee_no', title: '工号', operate: 'LIKE'},
                    {field: 'name', title: '姓名', operate: 'LIKE'},
                    {field: 'department', title: '部门', operate: 'LIKE'},
                    {field: 'title', title: '岗位', operate: 'LIKE'},
                    {
                        field: 'role_key',
                        title: '角色组',
                        searchList: roleSearchList,
                        formatter: Table.api.formatter.normal
                    },
                    {field: 'phone', title: '手机号', operate: 'LIKE'},
                    {field: 'email', title: '邮箱', operate: 'LIKE'},
                    {
                        field: 'status',
                        title: '状态',
                        searchList: statusSearchList,
                        formatter: Table.api.formatter.status
                    },
                    {field: 'last_login_at', title: '最近登录时间', operate: 'RANGE', addclass: 'datetimerange', autocomplete: false},
                    {
                        field: 'operate',
                        title: __('Operate'),
                        table: table,
                        events: Table.api.events.operate,
                        buttons: [
                            {
                                name: 'account_permission',
                                text: '账号权限',
                                title: '查看后台账号与权限组',
                                icon: 'fa fa-user',
                                classname: 'btn btn-info btn-xs btn-dialog',
                                url: 'auth/admin/edit/ids/{admin_id}',
                                visible: function (row) {
                                    return canOpenAdmin(row);
                                },
                                extend: 'data-area=["72%","85%"]'
                            },
                            {
                                name: 'recent_adminlog',
                                text: '后台日志',
                                title: '查看后台访问和登录记录',
                                icon: 'fa fa-history',
                                classname: 'btn btn-success btn-xs btn-dialog',
                                url: 'auth/adminlog/index?admin_id={admin_id}',
                                visible: function (row) {
                                    return canOpenAdmin(row);
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
