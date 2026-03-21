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
        if (query.get('owner_admin_id')) {
            preset.filter.owner_admin_id = query.get('owner_admin_id');
            preset.op.owner_admin_id = '=';
        }
        if (query.get('status')) {
            preset.filter.status = query.get('status');
            preset.op.status = '=';
        }
        return preset;
    };

    var presetQuery = buildPresetQuery();

    var stageMap = {
        lead: '线索期',
        proposal: '方案期',
        contracted: '已签约',
        delivery: '交付中',
        repeat: '复购中',
        lost: '已流失'
    };

    var levelMap = {
        a: 'A 级',
        b: 'B 级',
        c: 'C 级',
        d: 'D 级'
    };

    var sourceMap = {
        direct: '直客',
        referral: '转介绍',
        channel: '渠道',
        existing: '老客户',
        other: '其他'
    };

    var Controller = {
        index: function () {
            Table.api.init({
                extend: {
                    index_url: 'business/customer/index' + location.search,
                    add_url: 'business/customer/add',
                    edit_url: 'business/customer/edit',
                    del_url: 'business/customer/del',
                    multi_url: 'business/customer/multi',
                    table: 'business_customer'
                }
            });

            var table = $('#table');

            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'last_follow_up_at',
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
                    {field: 'company_name', title: '客户全称', operate: 'LIKE'},
                    {field: 'short_name', title: '简称', operate: 'LIKE'},
                    {
                        field: 'stage',
                        title: '阶段',
                        searchList: stageMap,
                        formatter: Table.api.formatter.normal
                    },
                    {
                        field: 'status',
                        title: '状态',
                        searchList: {active: '正常', paused: '暂缓', lost: '流失'},
                        formatter: Table.api.formatter.status
                    },
                    {field: 'owner', title: '负责人', operate: 'LIKE'},
                    {field: 'contact_name', title: '联系人', operate: 'LIKE'},
                    {field: 'contact_phone', title: '联系电话', operate: 'LIKE'},
                    {field: 'industry', title: '行业', operate: 'LIKE'},
                    {
                        field: 'customer_level',
                        title: '等级',
                        searchList: levelMap,
                        formatter: Table.api.formatter.normal
                    },
                    {
                        field: 'source',
                        title: '来源',
                        searchList: sourceMap,
                        formatter: Table.api.formatter.normal
                    },
                    {field: 'city', title: '城市', operate: 'LIKE'},
                    {field: 'last_follow_up_at', title: '最近跟进', operate: 'RANGE', addclass: 'datetimerange', autocomplete: false},
                    {
                        field: 'operate',
                        title: __('Operate'),
                        table: table,
                        events: Table.api.events.operate,
                        buttons: [
                            {
                                name: 'followups',
                                text: '跟进记录',
                                title: '查看客户跟进记录',
                                icon: 'fa fa-commenting-o',
                                classname: 'btn btn-primary btn-xs btn-addtabs',
                                url: function (row) {
                                    return 'business/customer_followup/index?customer_id=' + row.id;
                                }
                            },
                            {
                                name: 'contracts',
                                text: '合同台账',
                                title: '查看合同台账',
                                icon: 'fa fa-file-text-o',
                                classname: 'btn btn-info btn-xs btn-addtabs',
                                url: function (row) {
                                    return 'business/contract/index?customer_id=' + row.id;
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
