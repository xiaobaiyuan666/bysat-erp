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
        ['admin_id', 'actor_admin_id', 'module', 'action'].forEach(function (field) {
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
                    index_url: 'staff/audit/index' + location.search,
                    add_url: '',
                    edit_url: '',
                    del_url: '',
                    multi_url: '',
                    import_url: '',
                    table: 'staff_audit'
                }
            });

            var table = $("#table");

            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'happened_at',
                sortOrder: 'desc',
                search: false,
                commonSearch: true,
                fixedColumns: true,
                fixedRightNumber: 0,
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
                    {field: 'id', title: 'ID', operate: false},
                    {field: 'admin_id', title: __('Admin_id'), visible: false, operate: '='},
                    {field: 'actor_admin_id', title: __('Actor_admin_id'), visible: false, operate: '='},
                    {field: 'actor_name', title: __('Actor_name'), operate: 'LIKE'},
                    {field: 'module', title: __('Module'), operate: 'LIKE'},
                    {field: 'action', title: __('Action'), operate: 'LIKE'},
                    {field: 'object_type', title: __('Object_type'), operate: 'LIKE'},
                    {field: 'object_legacy_id', title: __('Object_legacy_id'), operate: 'LIKE', visible: false},
                    {field: 'content', title: __('Content'), operate: 'LIKE', class: 'autocontent', formatter: Table.api.formatter.content},
                    {field: 'ip', title: __('Ip'), operate: 'LIKE'},
                    {field: 'useragent', title: __('Useragent'), operate: 'LIKE', visible: false, class: 'autocontent', formatter: Table.api.formatter.content},
                    {field: 'happened_at', title: __('Happened_at'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, sortable: true}
                ]]
            });

            Table.api.bindevent(table);
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
