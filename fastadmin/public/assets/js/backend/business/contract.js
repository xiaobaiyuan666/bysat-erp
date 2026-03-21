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

    var parseAttachmentIds = function (value) {
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
            return [];
        }
    };

    var buildPresetQuery = function () {
        var query = new URLSearchParams(window.location.search);
        var preset = {filter: {}, op: {}};
        ['customer_id', 'project_id', 'app_project_id', 'status', 'approval_status'].forEach(function (field) {
            if (!query.get(field)) {
                return;
            }
            preset.filter[field] = query.get(field);
            preset.op[field] = '=';
        });
        return preset;
    };

    var presetQuery = buildPresetQuery();

    var categoryMap = {
        implementation: '实施交付',
        subscription: '订阅服务',
        maintenance: '维护续费',
        custom: '定制开发',
        service: '咨询服务',
        other: '其他'
    };

    var statusMap = {
        draft: '草稿',
        review: '审批中',
        active: '生效中',
        delivering: '履约中',
        completed: '已完成',
        cancelled: '已取消',
        expired: '已到期'
    };

    var approvalStatusMap = {
        none: '未发起',
        pending: '待审批',
        approved: '已通过',
        rejected: '已驳回',
        cancelled: '已撤回'
    };

    var bindAttachmentField = function () {
        var $input = $('#c-attachment_ids_json');
        var $summary = $('#c-attachment_summary');
        var $view = $('#btn-view-attachments');
        var openApi = window.parent && window.parent.Fast ? window.parent.Fast.api : Fast.api;

        var refresh = function () {
            var ids = parseAttachmentIds($input.val());
            $summary.val(ids.length > 0 ? ('已关联 ' + ids.length + ' 个附件') : '未选择附件');
            $view.prop('disabled', ids.length === 0);
        };

        $('#btn-choose-attachments').on('click', function () {
            openApi.open('general/attachment/select?multiple=true', '选择附件', {
                area: ['90%', '88%'],
                callback: function (data) {
                    var ids = data && data.ids ? data.ids : [];
                    $input.val(JSON.stringify(ids));
                    refresh();
                }
            });
            return false;
        });

        $view.on('click', function () {
            var ids = parseAttachmentIds($input.val());
            if (!ids.length) {
                return false;
            }
            openApi.open('general/attachment/index?ids=' + ids.join(','), '查看附件', {
                area: ['90%', '88%']
            });
            return false;
        });

        refresh();
    };

    var attachmentUrl = function (row) {
        return 'general/attachment/index?ids=' + parseAttachmentIds(row.attachment_ids_json).join(',');
    };

    var approvalHistoryUrl = function (row) {
        return 'business/approval/index?object_type=contract&object_id=' + row.id;
    };

    var approvalCreateUrl = function (row) {
        return 'business/approval/add?object_type=contract&object_id=' + row.id;
    };

    var Controller = {
        index: function () {
            Table.api.init({
                extend: {
                    index_url: 'business/contract/index' + location.search,
                    add_url: 'business/contract/add',
                    edit_url: 'business/contract/edit',
                    del_url: 'business/contract/del',
                    multi_url: 'business/contract/multi',
                    table: 'business_contract'
                }
            });

            var table = $('#table');

            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'signed_at',
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
                    {field: 'contract_no', title: '合同编号', operate: 'LIKE'},
                    {field: 'name', title: '合同名称', operate: 'LIKE'},
                    {field: 'customer_name', title: '所属客户', operate: 'LIKE'},
                    {
                        field: 'category',
                        title: '合同分类',
                        searchList: categoryMap,
                        formatter: Table.api.formatter.normal
                    },
                    {
                        field: 'status',
                        title: '合同状态',
                        searchList: statusMap,
                        formatter: Table.api.formatter.status
                    },
                    {
                        field: 'approval_status',
                        title: '审批状态',
                        searchList: approvalStatusMap,
                        formatter: Table.api.formatter.status
                    },
                    {field: 'owner', title: '负责人', operate: 'LIKE'},
                    {field: 'amount', title: '合同金额', operate: 'BETWEEN'},
                    {field: 'received_total', title: '已回款', operate: 'BETWEEN'},
                    {field: 'pending_total', title: '待回款', operate: 'BETWEEN'},
                    {field: 'signed_at', title: '签约日期', operate: 'RANGE', addclass: 'datetimerange', autocomplete: false},
                    {field: 'end_date', title: '结束日期', operate: 'RANGE', addclass: 'datetimerange', autocomplete: false},
                    {
                        field: 'operate',
                        title: __('Operate'),
                        table: table,
                        events: Table.api.events.operate,
                        buttons: [
                            {
                                name: 'approval_create',
                                text: '发起审批',
                                title: '发起审批',
                                icon: 'fa fa-check-square-o',
                                classname: 'btn btn-primary btn-xs btn-addtabs',
                                url: approvalCreateUrl,
                                visible: function (row) {
                                    return row.approval_status !== 'pending' && (row.status === 'draft' || row.status === 'review');
                                }
                            },
                            {
                                name: 'approval_history',
                                text: '审批记录',
                                title: '审批记录',
                                icon: 'fa fa-list-alt',
                                classname: 'btn btn-info btn-xs btn-addtabs',
                                url: approvalHistoryUrl,
                                visible: function (row) {
                                    return row.approval_status && row.approval_status !== 'none';
                                }
                            },
                            {
                                name: 'receivable_plan',
                                text: '回款计划',
                                title: '查看回款计划',
                                icon: 'fa fa-calendar-check-o',
                                classname: 'btn btn-success btn-xs btn-addtabs',
                                url: function (row) {
                                    return 'business/receivable_plan/index?contract_id=' + row.id;
                                }
                            },
                            {
                                name: 'attachments',
                                text: '附件',
                                title: '查看附件',
                                icon: 'fa fa-paperclip',
                                classname: 'btn btn-default btn-xs btn-dialog',
                                url: attachmentUrl,
                                visible: function (row) {
                                    return parseAttachmentIds(row.attachment_ids_json).length > 0;
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
            bindAttachmentField();
            Controller.api.bindevent();
        },
        edit: function () {
            bindAttachmentField();
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
