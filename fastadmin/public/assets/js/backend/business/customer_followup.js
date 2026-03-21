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

    var formatDateTime = function (date) {
        var year = date.getFullYear();
        var month = String(date.getMonth() + 1).padStart(2, '0');
        var day = String(date.getDate()).padStart(2, '0');
        var hour = String(date.getHours()).padStart(2, '0');
        var minute = String(date.getMinutes()).padStart(2, '0');
        var second = String(date.getSeconds()).padStart(2, '0');
        return year + '-' + month + '-' + day + ' ' + hour + ':' + minute + ':' + second;
    };

    var buildPresetQuery = function () {
        var query = new URLSearchParams(window.location.search);
        var preset = {filter: {}, op: {}};

        ['customer_id', 'contract_id', 'owner_admin_id', 'status'].forEach(function (field) {
            if (!query.get(field)) {
                return;
            }
            preset.filter[field] = query.get(field);
            preset.op[field] = '=';
        });

        if (query.get('time_scope') === 'week') {
            var start = new Date();
            var day = start.getDay() || 7;
            start.setDate(start.getDate() - day + 1);
            start.setHours(0, 0, 0, 0);
            var end = new Date(start.getTime());
            end.setDate(start.getDate() + 6);
            end.setHours(23, 59, 59, 999);
            preset.filter.follow_up_at = formatDateTime(start) + ' - ' + formatDateTime(end);
            preset.op.follow_up_at = 'RANGE';
        }

        return preset;
    };

    var presetQuery = buildPresetQuery();

    var typeMap = {
        call: '电话沟通',
        wechat: '微信/IM',
        meeting: '会议沟通',
        visit: '上门拜访',
        proposal: '方案推进',
        payment: '回款跟进',
        service: '服务回访',
        other: '其他'
    };

    var statusMap = {
        planned: '待跟进',
        done: '已完成',
        waiting: '待客户回复',
        closed: '已关闭'
    };

    var Controller = {
        index: function () {
            Table.api.init({
                extend: {
                    index_url: 'business/customer_followup/index' + location.search,
                    add_url: 'business/customer_followup/add',
                    edit_url: 'business/customer_followup/edit',
                    del_url: 'business/customer_followup/del',
                    multi_url: 'business/customer_followup/multi',
                    table: 'business_customer_followup'
                }
            });

            var table = $('#table');

            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'follow_up_at',
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
                    {field: 'title', title: '跟进标题', operate: 'LIKE'},
                    {field: 'customer_name', title: '客户', operate: 'LIKE'},
                    {field: 'contract_name', title: '合同', operate: 'LIKE'},
                    {field: 'followup_type', title: '跟进类型', searchList: typeMap, formatter: Table.api.formatter.normal},
                    {field: 'status', title: '状态', searchList: statusMap, formatter: Table.api.formatter.status},
                    {field: 'owner', title: '负责人', operate: 'LIKE'},
                    {field: 'contact_name', title: '对接人', operate: 'LIKE'},
                    {field: 'follow_up_at', title: '跟进时间', operate: 'RANGE', addclass: 'datetimerange', autocomplete: false},
                    {field: 'next_follow_up_at', title: '下次跟进时间', operate: 'RANGE', addclass: 'datetimerange', autocomplete: false},
                    {field: 'result_summary', title: '跟进结果', operate: 'LIKE'},
                    {
                        field: 'operate',
                        title: __('Operate'),
                        table: table,
                        events: Table.api.events.operate,
                        buttons: [
                            {
                                name: 'customer',
                                text: '客户档案',
                                title: '查看客户档案',
                                icon: 'fa fa-address-book-o',
                                classname: 'btn btn-info btn-xs btn-addtabs',
                                url: function (row) {
                                    return 'business/customer/index?customer_id=' + row.customer_id;
                                }
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
