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
        ['admin_id', 'username', 'title'].forEach(function (field) {
            var value = query.get(field);
            if (value !== null && value !== '') {
                preset.filter[field] = value;
                preset.op[field] = '=';
            }
        });
        return preset;
    };

    var presetQuery = buildPresetQuery();

    var Controller = {
        index: function () {
            Table.api.init({
                extend: {
                    index_url: 'auth/adminlog/index',
                    add_url: '',
                    edit_url: '',
                    del_url: 'auth/adminlog/del',
                    multi_url: 'auth/adminlog/multi'
                }
            });

            var table = $("#table");

            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
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
                    {field: 'state', checkbox: true},
                    {field: 'id', title: 'ID', operate: false},
                    {field: 'admin_id', title: __('Admin_id'), formatter: Table.api.formatter.search, visible: false},
                    {field: 'username', title: __('Username'), formatter: Table.api.formatter.search},
                    {field: 'title', title: __('Title'), operate: 'LIKE %...%', placeholder: '模糊搜索'},
                    {field: 'url', title: __('Url'), formatter: Table.api.formatter.url},
                    {field: 'ip', title: __('IP'), events: Table.api.events.ip, formatter: Table.api.formatter.search},
                    {field: 'createtime', title: __('Create time'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                    {
                        field: 'operate',
                        title: __('Operate'),
                        table: table,
                        events: Table.api.events.operate,
                        buttons: [{
                            name: 'detail',
                            text: __('Detail'),
                            icon: 'fa fa-list',
                            classname: 'btn btn-info btn-xs btn-detail btn-dialog',
                            url: 'auth/adminlog/detail'
                        }],
                        formatter: Table.api.formatter.operate
                    }
                ]]
            });

            Table.api.bindevent(table);
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            formatter: {
                browser: function (value, row, index) {
                    return '<a class="btn btn-xs btn-browser">' + row.useragent.split(" ")[0] + '</a>';
                }
            }
        }
    };
    return Controller;
});
