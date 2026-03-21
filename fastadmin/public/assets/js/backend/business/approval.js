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
        ['approver_admin_id', 'status', 'object_type', 'object_id'].forEach(function (field) {
            if (!query.get(field)) {
                return;
            }
            preset.filter[field] = query.get(field);
            preset.op[field] = '=';
        });
        return preset;
    };

    var presetQuery = buildPresetQuery();
    var templateMap = window.approvalTemplateMap || {};

    var approvalTypeMap = {
        contract: '合同审批',
        payment_plan: '付款计划审批',
        expense_request: '费用申请审批',
        purchase_order: '采购单审批',
        payment_request: '付款申请审批'
    };

    var approvalStatusMap = {
        pending: '待审批',
        approved: '已通过',
        rejected: '已驳回',
        cancelled: '已撤回'
    };

    var toggleTargetFields = function () {
        var type = $('#c-object_type').val();
        $('[data-approval-target]').hide().find('select').prop('disabled', true);
        if (type) {
            $('[data-approval-target="' + type + '"]').show().find('select').prop('disabled', false);
        }
        $('.selectpicker').selectpicker('refresh');
    };

    var syncTemplateOptions = function () {
        var objectType = $('#c-object_type').val();
        var templateSelect = $('#c-template_id');
        if (!templateSelect.length) {
            return;
        }

        var currentValue = templateSelect.val();
        templateSelect.find('option').each(function () {
            var option = $(this);
            var value = option.attr('value');
            if (!value) {
                option.prop('disabled', false).show();
                return;
            }
            var item = templateMap[value];
            var visible = !objectType || (item && item.object_type === objectType);
            option.prop('disabled', !visible);
            option.toggle(visible);
        });

        if (currentValue && templateSelect.find('option[value="' + currentValue + '"]:enabled').length) {
            templateSelect.val(currentValue);
        } else {
            templateSelect.val('');
        }
        templateSelect.selectpicker('refresh');
    };

    var renderTemplatePreview = function () {
        var preview = $('#approval-template-preview');
        if (!preview.length) {
            return;
        }

        var templateId = $('#c-template_id').val();
        var approverText = $('#c-approver_admin_id option:selected').text();
        if (!templateId) {
            var lines = ['<span class="text-muted">未选择模板时，系统会先按金额区间自动匹配；没有命中时再回落到默认模板。</span>'];
            if ($('#c-approver_admin_id').val()) {
                lines.push('<div class="text-info" style="margin-top:6px;">当前已指定手动审批人：' + approverText + '。只有没有命中模板时，才会按手动单级审批处理。</div>');
            }
            preview.html(lines.join(''));
            return;
        }

        var item = templateMap[templateId];
        if (!item) {
            preview.html('<span class="text-warning">当前模板信息未加载，请刷新后重试。</span>');
            return;
        }

        if (!item.steps || !item.steps.length) {
            preview.html('<span class="text-danger">当前模板还没有配置审批节点，请先到审批模板里补齐节点。</span>');
            return;
        }

        var lines = ['<strong>' + item.name + '</strong>'];
        var minAmount = parseFloat(item.min_amount || 0).toFixed(2);
        var maxAmount = parseFloat(item.max_amount || 0);
        if (maxAmount > 0) {
            lines.push('<div class="text-muted">适用金额：' + minAmount + ' - ' + maxAmount.toFixed(2) + '</div>');
        } else if (parseFloat(item.min_amount || 0) > 0) {
            lines.push('<div class="text-muted">适用金额：' + minAmount + ' 起</div>');
        } else if (item.is_default) {
            lines.push('<div class="text-muted">默认模板：未命中区间模板时自动使用</div>');
        }

        item.steps.forEach(function (step) {
            var label = '第 ' + step.step_no + ' 级：' + step.step_name;
            if (step.approver_name) {
                label += ' / ' + step.approver_name;
            }
            lines.push('<div>' + label + '</div>');
        });

        preview.html(lines.join(''));
    };

    var bindFormEvents = function () {
        toggleTargetFields();
        syncTemplateOptions();
        renderTemplatePreview();
        $('#c-object_type').on('changed.bs.select change', function () {
            toggleTargetFields();
            syncTemplateOptions();
            renderTemplatePreview();
        });
        $('#c-template_id').on('changed.bs.select change', renderTemplatePreview);
        $('#c-approver_admin_id').on('changed.bs.select change', renderTemplatePreview);
    };

    var Controller = {
        index: function () {
            Table.api.init({
                extend: {
                    index_url: 'business/approval/index' + location.search,
                    add_url: 'business/approval/add',
                    edit_url: 'business/approval/edit',
                    del_url: 'business/approval/del',
                    multi_url: 'business/approval/multi',
                    table: 'business_approval'
                }
            });

            var table = $('#table');

            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'applied_at',
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
                    {field: 'approval_no', title: '审批编号', operate: 'LIKE'},
                    {field: 'object_title', title: '审批对象', operate: 'LIKE'},
                    {
                        field: 'object_type',
                        title: '审批类型',
                        searchList: approvalTypeMap,
                        formatter: Table.api.formatter.normal
                    },
                    {field: 'template_name', title: '审批模板', operate: 'LIKE'},
                    {
                        field: 'progress',
                        title: '当前进度',
                        operate: false,
                        formatter: function (value, row) {
                            return '第 ' + (row.current_step || 1) + ' / ' + (row.total_steps || 1) + ' 级';
                        }
                    },
                    {field: 'current_step_name', title: '当前节点', operate: 'LIKE'},
                    {field: 'approver_name', title: '当前审批人', operate: 'LIKE'},
                    {field: 'applicant_name', title: '发起人', operate: 'LIKE'},
                    {field: 'applied_at', title: '发起时间', operate: 'RANGE', addclass: 'datetimerange', autocomplete: false},
                    {field: 'decided_at', title: '最近处理时间', operate: 'RANGE', addclass: 'datetimerange', autocomplete: false},
                    {
                        field: 'status',
                        title: '审批状态',
                        searchList: approvalStatusMap,
                        formatter: Table.api.formatter.status
                    },
                    {
                        field: 'operate',
                        title: __('Operate'),
                        table: table,
                        events: Table.api.events.operate,
                        buttons: [
                            {
                                name: 'approve',
                                text: '通过',
                                title: '通过审批',
                                icon: 'fa fa-check',
                                classname: 'btn btn-success btn-xs btn-ajax',
                                url: function (row) {
                                    return 'business/approval/approve/ids/' + row.id;
                                },
                                confirm: '确认通过这条审批吗？',
                                visible: function (row) {
                                    return row.status === 'pending';
                                }
                            },
                            {
                                name: 'reject',
                                text: '驳回',
                                title: '驳回审批',
                                icon: 'fa fa-ban',
                                classname: 'btn btn-warning btn-xs btn-ajax',
                                url: function (row) {
                                    return 'business/approval/reject/ids/' + row.id;
                                },
                                confirm: '确认驳回这条审批吗？',
                                visible: function (row) {
                                    return row.status === 'pending';
                                }
                            },
                            {
                                name: 'cancel',
                                text: '撤回',
                                title: '撤回审批',
                                icon: 'fa fa-undo',
                                classname: 'btn btn-default btn-xs btn-ajax',
                                url: function (row) {
                                    return 'business/approval/cancel/ids/' + row.id;
                                },
                                confirm: '确认撤回这条审批吗？',
                                visible: function (row) {
                                    return row.status === 'pending';
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
            bindFormEvents();
            Controller.api.bindevent();
        },
        edit: function () {
            toggleTargetFields();
            syncTemplateOptions();
            renderTemplatePreview();
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
