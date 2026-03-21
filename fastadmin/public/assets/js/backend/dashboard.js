define(['jquery', 'bootstrap', 'backend'], function ($, undefined, Backend) {

    var state = {
        focus: 'overview',
        presetKey: '',
        sending: false,
        suggestions: []
    };

    var $root = null;

    var escapeHtml = function (value) {
        return $('<div />').text(value || '').html();
    };

    var nl2br = function (value) {
        return escapeHtml(value || '').replace(/\n/g, '<br>');
    };

    var fallbackCopy = function (text, successText) {
        var $temp = $('<textarea readonly></textarea>').css({
            position: 'fixed',
            top: '-9999px',
            left: '-9999px'
        }).val(text).appendTo('body');

        $temp[0].focus();
        $temp[0].select();
        try {
            document.execCommand('copy');
            Toastr.success(successText || '已复制');
        } catch (err) {
            Toastr.error('复制失败，请手动复制');
        }
        $temp.remove();
    };

    var copyText = function (text, successText) {
        text = String(text || '');
        if (!text) {
            return;
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () {
                Toastr.success(successText || '已复制');
            }, function () {
                fallbackCopy(text, successText);
            });
            return;
        }

        fallbackCopy(text, successText);
    };

    var openTab = function (url, title, icon) {
        if (!url) {
            return;
        }

        if (Backend && Backend.api && typeof Backend.api.addtabs === 'function') {
            Backend.api.addtabs(url, title || '打开页面', icon || '');
            return;
        }

        window.location.href = url;
    };

    var renderFocuses = function () {
        $root.find('[data-dashboard-focus]').each(function () {
            var $item = $(this);
            var focus = $item.data('dashboard-focus');
            if (!focus || $item.closest('.erp-quick-list').length) {
                return;
            }

            $item.toggleClass('is-active', focus === state.focus);
        });
    };

    var renderDiagnostic = function (diagnostic) {
        var $box = $root.find('[data-role="dashboard-ai-diagnostic"]');
        if (!diagnostic || !diagnostic.message) {
            $box.hide().empty();
            return;
        }

        var panelClass = 'alert-info';
        if (diagnostic.type === 'success') {
            panelClass = 'alert-success';
        } else if (diagnostic.type === 'warning') {
            panelClass = 'alert-warning';
        } else if (diagnostic.type === 'danger') {
            panelClass = 'alert-danger';
        }

        $box
            .removeClass('alert-info alert-success alert-warning alert-danger')
            .addClass(panelClass)
            .html('<strong>' + escapeHtml(diagnostic.title || '提示') + '</strong><div style="margin-top:6px;line-height:1.8;">' + escapeHtml(diagnostic.message || '') + '</div>')
            .show();
    };

    var renderSuggestions = function (items) {
        state.suggestions = items || [];
        var $wrap = $root.find('[data-role="dashboard-ai-actions"]');
        if (!state.suggestions.length) {
            $wrap.empty();
            return;
        }

        var html = [];
        $.each(state.suggestions, function (index, item) {
            html.push(
                '<button type="button" class="btn btn-default btn-sm" data-suggestion-index="' + index + '">' +
                escapeHtml(item.label || '操作') +
                '</button>'
            );
        });
        $wrap.html(html.join(''));
    };

    var renderAnswer = function (answer, diagnostic, suggestions) {
        var $box = $root.find('[data-role="dashboard-ai-answer-box"]');
        $box.addClass('is-visible');
        renderDiagnostic(diagnostic || null);
        $root.find('[data-role="dashboard-ai-answer"]').html(nl2br(answer || ''));
        renderSuggestions(suggestions || []);
    };

    var clearAnswer = function () {
        state.presetKey = '';
        state.suggestions = [];
        $root.find('[data-role="dashboard-ai-prompt"]').val('');
        $root.find('[data-role="dashboard-ai-answer-box"]').removeClass('is-visible');
        $root.find('[data-role="dashboard-ai-answer"]').empty();
        $root.find('[data-role="dashboard-ai-actions"]').empty();
        $root.find('[data-role="dashboard-ai-diagnostic"]').hide().empty();
    };

    var updateSendState = function () {
        var configured = String($root.data('ai-configured')) === '1';
        $root.find('[data-action="dashboard-ai-send"]').prop('disabled', state.sending || !configured);
    };

    var sendPrompt = function () {
        if (state.sending) {
            return;
        }

        if (String($root.data('ai-configured')) !== '1') {
            Toastr.warning('当前 AI 还没有配置好，请先完成 AI 配置。');
            return;
        }

        var prompt = $.trim($root.find('[data-role="dashboard-ai-prompt"]').val());
        if (!prompt && !state.presetKey) {
            Toastr.warning('请先输入问题，或者点一个快捷分析。');
            return;
        }

        state.sending = true;
        updateSendState();
        $root.find('[data-action="dashboard-ai-send"]').html('<i class="fa fa-spinner fa-spin"></i> 分析中');

        $.ajax({
            url: Config.dashboardAiAskUrl,
            type: 'POST',
            dataType: 'json',
            timeout: 22000,
            data: {
                prompt: prompt,
                focus: state.focus,
                preset_key: state.presetKey,
                setting_id: $root.data('ai-setting-id') || '',
                quick_mode: 1
            }
        }).done(function (ret) {
            if (ret.code !== 1) {
                renderAnswer('', ret.data ? ret.data.diagnostic : null, []);
                Toastr.error(ret.msg || 'AI 分析失败');
                return;
            }

            var data = ret.data || {};
            renderAnswer(data.answer || '', data.diagnostic || null, data.suggestions || []);
            state.presetKey = '';
        }).fail(function (xhr, status) {
            if (status === 'timeout') {
                renderAnswer('', {
                    type: 'warning',
                    title: '首页 AI 等待超时',
                    message: '首页已经按快速模式请求，但网关没有在限定时间内返回。你可以直接重试，或者进入完整 AI 工作台做更长分析。'
                }, []);
                Toastr.warning('首页 AI 等待超时');
                return;
            }

            Toastr.error('AI 分析失败，请稍后重试');
        }).always(function () {
            state.sending = false;
            updateSendState();
            $root.find('[data-action="dashboard-ai-send"]').html('<i class="fa fa-send"></i> 直接分析');
        });
    };

    var handleSuggestion = function (index) {
        var item = state.suggestions[index];
        if (!item) {
            return;
        }

        if (item.kind === 'copy') {
            copyText(item.content || '', '已复制');
            return;
        }

        if (item.kind === 'prompt') {
            state.presetKey = '';
            $root.find('[data-role="dashboard-ai-prompt"]').val(item.prompt || '').focus();
            Toastr.success('已带入输入框');
            return;
        }

        if (item.kind === 'link') {
            openTab(item.url, item.label, item.icon || '');
        }
    };

    var bindEvents = function () {
        $(document).on('click', '.btn-refresh', function () {
            window.location.reload();
        });

        $root.on('click', '.erp-focus-chip', function () {
            state.focus = $(this).data('dashboard-focus') || 'overview';
            renderFocuses();
        });

        $root.on('click', '.erp-quick-chip', function () {
            state.focus = $(this).data('dashboard-focus') || state.focus;
            state.presetKey = $(this).data('dashboard-preset') || '';
            renderFocuses();
            sendPrompt();
        });

        $root.on('click', '[data-action="dashboard-ai-send"]', sendPrompt);
        $root.on('click', '[data-action="dashboard-ai-clear"]', clearAnswer);

        $root.on('click', '[data-suggestion-index]', function () {
            handleSuggestion(parseInt($(this).data('suggestion-index'), 10));
        });

        $root.on('keydown', '[data-role="dashboard-ai-prompt"]', function (event) {
            if ((event.ctrlKey || event.metaKey) && event.keyCode === 13) {
                event.preventDefault();
                sendPrompt();
            }
        });
    };

    var init = function () {
        $root = $('#erp-dashboard-page');
        if (!$root.length) {
            return;
        }

        renderFocuses();
        updateSendState();
        bindEvents();
    };

    return {
        index: init
    };
});
