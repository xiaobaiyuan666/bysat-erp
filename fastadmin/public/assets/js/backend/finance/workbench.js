define(['jquery', 'bootstrap', 'backend'], function ($, undefined, Backend) {

    var state = {
        bootstrap: null,
        parsed: null,
        parsing: false,
        saving: false
    };

    var $root = null;

    var escapeHtml = function (value) {
        return $('<div />').text(value || '').html();
    };

    var request = function (options) {
        return $.ajax($.extend({dataType: 'json'}, options));
    };

    var getMap = function (key) {
        return state.bootstrap && state.bootstrap[key] ? state.bootstrap[key] : {};
    };

    var toOptions = function (map, selectedValue, placeholder) {
        var html = [];
        if (typeof placeholder !== 'undefined') {
            html.push('<option value="">' + escapeHtml(placeholder) + '</option>');
        }

        $.each(map || {}, function (value, label) {
            html.push('<option value="' + escapeHtml(value) + '"' + (String(selectedValue) === String(value) ? ' selected' : '') + '>' + escapeHtml(label) + '</option>');
        });

        return html.join('');
    };

    var renderModelStatus = function (setting) {
        var $badge = $root.find('[data-role="model-badge"]');
        var $hint = $root.find('[data-role="model-hint"]');

        if (!setting) {
            $badge.text('未配置模型');
            $hint.text('当前没有可用模型配置，将直接按规则解析。');
            return;
        }

        $badge.text(setting.label || '未配置模型');
        $hint.text(setting.hint || setting.mode || '模型优先，规则兜底');
    };

    var renderProjects = function (projects) {
        var html = ['<option value="">不指定项目</option>'];

        $.each(projects || [], function (_, item) {
            html.push('<option value="' + item.id + '">' + escapeHtml(item.label || item.name || ('项目 #' + item.id)) + '</option>');
        });

        $root.find('[data-role="project-select"]').html(html.join(''));
    };

    var renderExamples = function (examples) {
        var html = [];

        $.each(examples || [], function (_, text) {
            html.push('<button type="button" class="btn btn-default btn-sm" data-example="' + escapeHtml(text) + '">' + escapeHtml(text) + '</button>');
        });

        $root.find('[data-role="examples"]').html(html.join(''));
    };

    var renderEmptyResult = function (text) {
        $root.find('[data-role="result-panel"]').removeClass('is-ready').html(
            '<div class="wb-smart-empty">' + escapeHtml(text || '先输入一句话，再点“智能解析”。这里会先生成草稿，确认无误后再写入系统，不会直接落账。') + '</div>'
        );
    };

    var renderSaveSuccess = function (data) {
        var typeText = data.record_type === 'invoice' ? '应收应付' : '财务流水';
        var reviewText = data.needs_review
            ? '<div class="alert alert-warning wb-smart-note" style="margin-bottom:12px;">这笔记录已写入，但系统建议你再复核一下金额、往来对象和分类。</div>'
            : '';

        $root.find('[data-role="result-panel"]').addClass('is-ready').html([
            '<div class="wb-smart-result-top">',
            '<div>',
            '<h4 style="margin:0 0 6px;">已写入' + escapeHtml(typeText) + '</h4>',
            '<div class="wb-help">来源：' + escapeHtml(data.source_label || '智能记账') + '</div>',
            '</div>',
            '<span class="label label-success">保存成功</span>',
            '</div>',
            reviewText,
            '<div class="wb-smart-summary">' + escapeHtml(data.summary || '') + '</div>',
            '<div class="wb-smart-result-actions">',
            '<a href="' + escapeHtml(data.edit_url || '#') + '" class="btn btn-primary addtabsit"><i class="fa fa-pencil"></i> 继续编辑</a>',
            '<a href="' + escapeHtml(data.index_url || '#') + '" class="btn btn-default addtabsit"><i class="fa fa-list"></i> 打开台账</a>',
            '<button type="button" class="btn btn-default" data-action="reset-result"><i class="fa fa-plus"></i> 再记一笔</button>',
            '</div>'
        ].join(''));
    };

    var renderDraft = function (payload) {
        var draft = payload.draft || {};
        var isInvoice = payload.record_type === 'invoice';
        var reviewBlock = payload.needs_review
            ? '<div class="alert alert-warning wb-smart-note">系统建议复核这笔草稿，确认后再写入系统。</div>'
            : '';
        var fallbackBlock = payload.fallback_error
            ? '<div class="alert alert-info wb-smart-note">模型这次没有直接给出可用结构，已自动回退为规则解析。</div>'
            : '';

        var html = [
            '<div class="wb-smart-result-top">',
            '<div>',
            '<h4 style="margin:0 0 6px;">记账草稿已生成</h4>',
            '<div class="wb-help">来源：' + escapeHtml(payload.source_label || '规则兜底') + '；确认后才会真正写入系统。</div>',
            '</div>',
            '<span class="wb-smart-badge">' + escapeHtml(isInvoice ? '应收应付草稿' : '财务流水草稿') + '</span>',
            '</div>',
            payload.summary ? '<div class="wb-smart-summary">' + escapeHtml(payload.summary) + '</div>' : '',
            reviewBlock,
            fallbackBlock
        ];

        if (isInvoice) {
            html.push(
                '<div class="wb-smart-result-grid">',
                '<div class="form-group"><label class="control-label">单据类型</label><select class="form-control" data-field="kind">' + toOptions(getMap('invoice_kinds'), draft.kind) + '</select></div>',
                '<div class="form-group"><label class="control-label">状态</label><select class="form-control" data-field="status">' + toOptions(getMap('invoice_statuses'), draft.status) + '</select></div>',
                '<div class="form-group" style="grid-column:span 2;"><label class="control-label">单据标题</label><input type="text" class="form-control" data-field="title" value="' + escapeHtml(draft.title || '') + '"></div>',
                '<div class="form-group"><label class="control-label">往来对象</label><input type="text" class="form-control" data-field="counterparty" value="' + escapeHtml(draft.counterparty || '') + '"></div>',
                '<div class="form-group"><label class="control-label">金额</label><input type="number" step="0.01" min="0" class="form-control" data-field="amount" value="' + escapeHtml(draft.amount || '') + '"></div>',
                '<div class="form-group"><label class="control-label">到期日期</label><input type="date" class="form-control" data-field="due_date" value="' + escapeHtml(draft.due_date || '') + '"></div>',
                '<div class="form-group"><label class="control-label">关联项目</label><select class="form-control" data-field="project_id">' + buildProjectOptionsHtml(draft.project_id) + '</select></div>',
                '<div class="form-group" style="grid-column:span 2;"><label class="control-label">备注</label><textarea class="form-control" data-field="notes">' + escapeHtml(draft.notes || '') + '</textarea></div>',
                '</div>'
            );
        } else {
            html.push(
                '<div class="wb-smart-result-grid">',
                '<div class="form-group"><label class="control-label">记账日期</label><input type="date" class="form-control" data-field="transaction_date" value="' + escapeHtml(draft.transaction_date || '') + '"></div>',
                '<div class="form-group"><label class="control-label">收支类型</label><select class="form-control" data-field="type">' + toOptions(getMap('transaction_types'), draft.type) + '</select></div>',
                '<div class="form-group"><label class="control-label">往来对象</label><input type="text" class="form-control" data-field="counterparty" value="' + escapeHtml(draft.counterparty || '') + '"></div>',
                '<div class="form-group"><label class="control-label">金额</label><input type="number" step="0.01" min="0" class="form-control" data-field="amount" value="' + escapeHtml(draft.amount || '') + '"></div>',
                '<div class="form-group"><label class="control-label">分类</label><select class="form-control" data-field="category">' + buildCategoryOptionsHtml(draft.category) + '</select></div>',
                '<div class="form-group"><label class="control-label">支付方式</label><select class="form-control" data-field="payment_method">' + toOptions(getMap('payment_methods'), draft.payment_method) + '</select></div>',
                '<div class="form-group"><label class="control-label">关联项目</label><select class="form-control" data-field="project_id">' + buildProjectOptionsHtml(draft.project_id) + '</select></div>',
                '<div class="form-group" style="grid-column:span 2;"><label class="control-label">备注</label><textarea class="form-control" data-field="notes">' + escapeHtml(draft.notes || '') + '</textarea></div>',
                '</div>'
            );
        }

        html.push(
            '<div class="wb-smart-result-actions">',
            '<button type="button" class="btn btn-primary" data-action="save"><i class="fa fa-check"></i> 写入系统</button>',
            '<button type="button" class="btn btn-default" data-action="reset-result"><i class="fa fa-refresh"></i> 重新解析</button>',
            '</div>'
        );

        $root.find('[data-role="result-panel"]').addClass('is-ready').html(html.join(''));
    };

    var buildProjectOptionsHtml = function (selectedValue) {
        var html = ['<option value="0">不关联项目</option>'];

        $.each((state.bootstrap && state.bootstrap.projects) || [], function (_, item) {
            html.push('<option value="' + item.id + '"' + (String(selectedValue || 0) === String(item.id) ? ' selected' : '') + '>' + escapeHtml(item.label || item.name || ('项目 #' + item.id)) + '</option>');
        });

        return html.join('');
    };

    var buildCategoryOptionsHtml = function (selectedValue) {
        var html = ['<option value="">请选择分类</option>'];

        $.each((state.bootstrap && state.bootstrap.categories) || [], function (_, item) {
            html.push('<option value="' + escapeHtml(item) + '"' + (String(selectedValue) === String(item) ? ' selected' : '') + '>' + escapeHtml(item) + '</option>');
        });

        return html.join('');
    };

    var collectDraft = function () {
        if (!state.parsed) {
            return null;
        }

        var payload = $.extend(true, {}, state.parsed);
        var $panel = $root.find('[data-role="result-panel"]');
        var draft = {};

        $panel.find('[data-field]').each(function () {
            var $field = $(this);
            draft[$field.data('field')] = $.trim($field.val());
        });

        draft.project_id = parseInt(draft.project_id || '0', 10) || 0;
        payload.draft = draft;
        return payload;
    };

    var updateParseButton = function () {
        var disabled = state.parsing;
        $root.find('[data-action="parse"]').prop('disabled', disabled).html(disabled ? '<i class="fa fa-spinner fa-spin"></i> 解析中' : '<i class="fa fa-magic"></i> 智能解析');
    };

    var updateSaveButton = function () {
        var disabled = state.saving;
        $root.find('[data-action="save"]').prop('disabled', disabled).html(disabled ? '<i class="fa fa-spinner fa-spin"></i> 写入中' : '<i class="fa fa-check"></i> 写入系统');
    };

    var parseSmartBookkeeping = function () {
        if (state.parsing) {
            return;
        }

        var text = $.trim($root.find('[data-role="smart-text"]').val());
        if (!text) {
            Toastr.warning('请先输入一句话记账描述。');
            return;
        }

        state.parsing = true;
        updateParseButton();

        request({
            url: Config.smartBookkeepingParseUrl,
            type: 'POST',
            data: {
                text: text,
                project_id: $root.find('[data-role="project-select"]').val() || ''
            }
        }).done(function (ret) {
            if (ret.code !== 1) {
                renderEmptyResult(ret.msg || '解析失败，请换一种说法再试。');
                Toastr.error(ret.msg || '解析失败');
                return;
            }

            state.parsed = ret.data || null;
            renderDraft(state.parsed || {});
            Toastr.success(ret.msg || '已生成记账草稿');
        }).fail(function () {
            renderEmptyResult('解析失败，请稍后重试。');
            Toastr.error('解析失败，请稍后重试。');
        }).always(function () {
            state.parsing = false;
            updateParseButton();
        });
    };

    var saveSmartBookkeeping = function () {
        if (state.saving) {
            return;
        }

        var payload = collectDraft();
        if (!payload) {
            Toastr.warning('当前还没有可保存的草稿。');
            return;
        }

        state.saving = true;
        updateSaveButton();

        request({
            url: Config.smartBookkeepingSaveUrl,
            type: 'POST',
            data: {
                payload_json: JSON.stringify(payload)
            }
        }).done(function (ret) {
            if (ret.code !== 1) {
                Toastr.error(ret.msg || '写入失败');
                return;
            }

            state.parsed = null;
            renderSaveSuccess(ret.data || {});
            Toastr.success(ret.msg || '已写入系统');
        }).fail(function () {
            Toastr.error('写入失败，请稍后重试。');
        }).always(function () {
            state.saving = false;
            updateSaveButton();
        });
    };

    var resetResult = function () {
        state.parsed = null;
        renderEmptyResult('');
    };

    var clearForm = function () {
        $root.find('[data-role="smart-text"]').val('');
        $root.find('[data-role="project-select"]').val('');
        resetResult();
    };

    var loadBootstrap = function () {
        request({
            url: Config.smartBookkeepingBootstrapUrl,
            type: 'GET'
        }).done(function (ret) {
            if (ret.code !== 1) {
                Toastr.error(ret.msg || '智能记账初始化失败');
                return;
            }

            state.bootstrap = ret.data || {};
            renderModelStatus(state.bootstrap.setting || null);
            renderProjects(state.bootstrap.projects || []);
            renderExamples(state.bootstrap.examples || []);
            renderEmptyResult('');
        }).fail(function () {
            Toastr.error('智能记账初始化失败');
        });
    };

    var bindEvents = function () {
        $root.on('click', '[data-example]', function () {
            $root.find('[data-role="smart-text"]').val($(this).data('example')).focus();
        });

        $root.on('click', '[data-action="parse"]', parseSmartBookkeeping);
        $root.on('click', '[data-action="save"]', saveSmartBookkeeping);
        $root.on('click', '[data-action="clear"]', clearForm);
        $root.on('click', '[data-action="reset-result"]', resetResult);

        $root.on('keydown', '[data-role="smart-text"]', function (event) {
            if ((event.ctrlKey || event.metaKey) && event.keyCode === 13) {
                event.preventDefault();
                parseSmartBookkeeping();
            }
        });
    };

    var Controller = {
        index: function () {
            $root = $('#smart-bookkeeping');
            if (!$root.length) {
                return;
            }

            bindEvents();
            loadBootstrap();
        }
    };

    return Controller;
});
