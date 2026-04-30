define(['jquery', 'bootstrap', 'backend'], function ($, undefined, Backend) {

    var state = {
        focus: 'overview',
        presetKey: '',
        sending: false,
        suggestions: [],
        pendingTaskId: 0,
        taskPollTimer: null,
        answerStreamTimer: null,
        answerStreamNonce: 0
    };

    var $root = null;

    var escapeHtml = function (value) {
        return $('<div />').text(value || '').html();
    };

    var formatInlineText = function (value) {
        return escapeHtml(value || '')
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/`([^`]+)`/g, '<code>$1</code>');
    };

    var formatAnswerContent = function (value) {
        var lines = String(value || '').replace(/\r\n?/g, '\n').split('\n');
        var html = [];
        var paragraphs = [];
        var listItems = [];

        var flushParagraphs = function () {
            if (!paragraphs.length) {
                return;
            }
            html.push('<p>' + formatInlineText(paragraphs.join(' ')) + '</p>');
            paragraphs = [];
        };

        var flushList = function () {
            if (!listItems.length) {
                return;
            }
            html.push('<ul>' + $.map(listItems, function (item) {
                return '<li>' + formatInlineText(item) + '</li>';
            }).join('') + '</ul>');
            listItems = [];
        };

        $.each(lines, function (_, rawLine) {
            var line = $.trim(rawLine || '');
            if (!line) {
                flushParagraphs();
                flushList();
                return;
            }

            if (/^#{1,4}\s+/.test(line)) {
                flushParagraphs();
                flushList();
                html.push('<h4>' + formatInlineText(line.replace(/^#{1,4}\s+/, '')) + '</h4>');
                return;
            }

            if (/^(\-|\*)\s+/.test(line)) {
                flushParagraphs();
                listItems.push(line.replace(/^(\-|\*)\s+/, ''));
                return;
            }

            flushList();
            paragraphs.push(line);
        });

        flushParagraphs();
        flushList();

        return html.length ? html.join('') : '<p>' + formatInlineText(value || '暂无结果') + '</p>';
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

    var syncPromptHeight = function () {
        var $prompt = $root.find('[data-role="dashboard-ai-prompt"]');
        if (!$prompt.length) {
            return;
        }

        var el = $prompt[0];
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 220) + 'px';
    };

    var renderFocuses = function () {
        $root.find('[data-dashboard-focus]').each(function () {
            var $item = $(this);
            var focus = $item.data('dashboard-focus');
            if (!focus || $item.data('dashboard-preset')) {
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
            .html(
                '<strong>' + escapeHtml(diagnostic.title || '提示') + '</strong>' +
                '<div style="margin-top:6px;line-height:1.8;">' + escapeHtml(diagnostic.message || '') + '</div>'
            )
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

    var stopAnswerStream = function () {
        if (state.answerStreamTimer) {
            window.clearTimeout(state.answerStreamTimer);
            state.answerStreamTimer = null;
        }
        state.answerStreamNonce += 1;
    };

    var getAnswerStreamChunkSize = function (remaining) {
        if (remaining > 900) {
            return 38;
        }
        if (remaining > 500) {
            return 24;
        }
        if (remaining > 220) {
            return 16;
        }
        return 9;
    };

    var getAnswerStreamDelay = function (remaining) {
        if (remaining > 900) {
            return 12;
        }
        if (remaining > 500) {
            return 16;
        }
        if (remaining > 220) {
            return 22;
        }
        return 30;
    };

    var renderAnswerMeta = function (task) {
        var $meta = $root.find('[data-role="dashboard-ai-answer-meta"]');
        if (!$meta.length) {
            return;
        }

        if (!task || !task.id) {
            $meta.hide().empty();
            return;
        }

        var parts = ['首页本地快捷入口', '轻量上下文', '仍调用当前配置模型'];
        if (task.duration_label) {
            parts.push(task.status === 'done' ? '回答用时 ' + task.duration_label : '已等待 ' + task.duration_label);
        } else if (task.status === 'failed') {
            parts.push('本次执行失败');
        }

        $meta.text(parts.join(' · ')).css('display', 'block');
    };

    var renderAnswer = function (answer, diagnostic, suggestions) {
        var $box = $root.find('[data-role="dashboard-ai-answer-box"]');
        $box.addClass('is-visible');
        renderDiagnostic(diagnostic || null);
        $root.find('[data-role="dashboard-ai-answer"]').html(formatAnswerContent(answer || '暂无可展示的分析结果。'));
        renderSuggestions(suggestions || []);
    };

    var streamAnswer = function (answer, diagnostic, suggestions) {
        stopAnswerStream();

        var content = String(answer || '');
        if (content.length < 80) {
            renderAnswer(content, diagnostic, suggestions);
            return;
        }

        var $box = $root.find('[data-role="dashboard-ai-answer-box"]');
        var $answer = $root.find('[data-role="dashboard-ai-answer"]');

        $box.addClass('is-visible');
        renderDiagnostic(diagnostic || null);
        renderSuggestions([]);

        var nonce = ++state.answerStreamNonce;
        var cursor = 0;

        var tick = function () {
            if (nonce !== state.answerStreamNonce) {
                return;
            }

            cursor = Math.min(content.length, cursor + getAnswerStreamChunkSize(content.length - cursor));
            $answer.html(formatAnswerContent(content.slice(0, cursor)) + (cursor < content.length ? '<span class="erp-stream-cursor"></span>' : ''));

            if (cursor >= content.length) {
                stopAnswerStream();
                renderAnswer(content, diagnostic, suggestions);
                return;
            }

            state.answerStreamTimer = window.setTimeout(tick, getAnswerStreamDelay(content.length - cursor));
        };

        state.answerStreamTimer = window.setTimeout(tick, 36);
    };

    var clearAnswer = function () {
        stopAnswerStream();
        state.presetKey = '';
        state.suggestions = [];
        $root.find('[data-role="dashboard-ai-prompt"]').val('');
        syncPromptHeight();
        $root.find('[data-role="dashboard-ai-answer-box"]').removeClass('is-visible');
        $root.find('[data-role="dashboard-ai-answer"]').empty();
        $root.find('[data-role="dashboard-ai-actions"]').empty();
        $root.find('[data-role="dashboard-ai-diagnostic"]').hide().empty();
        $root.find('[data-role="dashboard-ai-answer-meta"]').hide().empty();
    };

    var updateSendState = function () {
        var configured = String($root.data('ai-configured')) === '1';
        var label = state.sending ? '<i class="fa fa-spinner fa-spin"></i> 分析中' : '<i class="fa fa-send"></i> 直接分析';

        $root.find('[data-action="dashboard-ai-send"]')
            .prop('disabled', state.sending || !configured)
            .html(label);
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
                    title: '首页 AI 响应超时',
                    message: '首页只走快速分析，当前模型在限定时间内没有返回结果。你可以直接重试，或者进入完整 AI 工作台做更长分析。'
                }, []);
                Toastr.warning('首页 AI 响应超时');
                return;
            }

            Toastr.error('AI 分析失败，请稍后重试');
        }).always(function () {
            state.sending = false;
            updateSendState();
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
            syncPromptHeight();
            Toastr.success('已带入输入框');
            return;
        }

        if (item.kind === 'link') {
            openTab(item.url, item.label, item.icon || '');
        }
    };

    var stopTaskPolling = function () {
        if (state.taskPollTimer) {
            window.clearTimeout(state.taskPollTimer);
            state.taskPollTimer = null;
        }
        state.pendingTaskId = 0;
    };

    var renderTaskState = function (task) {
        var $wrap = $root.find('[data-role="dashboard-ai-task-wrap"]');
        if (!$wrap.length) {
            return;
        }

        if (!task || !task.id) {
            $wrap.hide();
            return;
        }

        $root.find('[data-role="dashboard-ai-task-badge"]').text(task.status_text || '后台中');
        $root.find('[data-role="dashboard-ai-task-title"]').text(task.quick_mode ? 'AI 后台快速任务' : 'AI 后台深度任务');
        $root.find('[data-role="dashboard-ai-task-text"]').text(task.status_message || '当前分析会在后台继续执行，你可以继续浏览其他页面。');
        $wrap.css('display', 'flex');
    };

    renderTaskState = function (task) {
        var $wrap = $root.find('[data-role="dashboard-ai-task-wrap"]');
        if (!$wrap.length) {
            return;
        }

        if (!task || !task.id) {
            $wrap.hide();
            return;
        }

        var taskText = task.status_message || '当前分析会在后台继续执行，你可以继续浏览其他页面。';
        if (task.duration_label) {
            taskText += (task.status === 'done' ? ' 本次回答用时 ' : ' 已等待 ') + task.duration_label;
        }

        $root.find('[data-role="dashboard-ai-task-badge"]').text(task.status_text || '后台中');
        $root.find('[data-role="dashboard-ai-task-title"]').text('AI 后台首页快捷任务');
        $root.find('[data-role="dashboard-ai-task-text"]').text(taskText);
        $wrap.css('display', 'flex');
    };

    updateSendState = function () {
        var configured = String($root.data('ai-configured')) === '1';
        var label = state.sending ? '<i class="fa fa-spinner fa-spin"></i> 后台分析中' : '<i class="fa fa-send"></i> 直接分析';

        $root.find('[data-action="dashboard-ai-send"]')
            .prop('disabled', state.sending || !configured)
            .html(label);
        $root.find('[data-action="dashboard-ai-clear"]').prop('disabled', state.sending);
        $root.find('[data-role="dashboard-ai-prompt"]').prop('disabled', !configured);
    };

    var renderPendingAnswer = function (task) {
        stopAnswerStream();
        renderAnswerMeta(task);
        renderTaskState(task);
        renderAnswer('', {
            type: 'info',
            title: task.status_text || '后台处理中',
            message: task.status_message || 'AI 正在后台分析，你可以继续浏览其他页面。'
        }, []);
    };

    var applyTaskResult = function (payload, task) {
        var data = payload || {};
        renderTaskState(null);
        renderAnswerMeta(task || null);
        streamAnswer(data.answer || '', data.diagnostic || null, data.suggestions || []);
        state.presetKey = '';
    };

    var scheduleTaskPoll = function (taskId) {
        state.taskPollTimer = window.setTimeout(function () {
            $.ajax({
                url: Config.dashboardAiStatusUrl,
                type: 'GET',
                dataType: 'json',
                timeout: 20000,
                data: {
                    task_id: taskId
                }
            }).done(function (ret) {
                if (ret.code !== 1) {
                    state.sending = false;
                    stopTaskPolling();
                    updateSendState();
                    Toastr.error(ret.msg || '获取 AI 任务状态失败');
                    return;
                }

                var data = ret.data || {};
                var task = data.task || {};
                if (!task.id) {
                    state.sending = false;
                    stopTaskPolling();
                    updateSendState();
                    Toastr.error('AI 任务不存在或已失效');
                    return;
                }

                if (task.status === 'queued' || task.status === 'processing') {
                    renderPendingAnswer(task);
                    scheduleTaskPoll(taskId);
                    return;
                }

                state.sending = false;
                stopTaskPolling();
                updateSendState();
                renderTaskState(null);

                if (task.status === 'done') {
                    applyTaskResult(data.result || task.result || {}, task);
                    Toastr.success('AI 分析完成');
                    return;
                }

                renderAnswer('', {
                    type: 'danger',
                    title: 'AI 后台分析失败',
                    message: task.error_message || task.status_message || '请稍后重试。'
                }, []);
                Toastr.error(task.error_message || 'AI 后台分析失败');
            }).fail(function () {
                if (!state.pendingTaskId || state.pendingTaskId !== taskId) {
                    return;
                }
                scheduleTaskPoll(taskId);
            });
        }, 1400);
    };

    var startTaskPolling = function (taskId) {
        stopTaskPolling();
        state.pendingTaskId = taskId;
        scheduleTaskPoll(taskId);
    };

    var sendBeaconRequest = function (url, payload) {
        if (!navigator.sendBeacon || typeof FormData === 'undefined') {
            return false;
        }

        try {
            var form = new FormData();
            $.each(payload || {}, function (key, value) {
                form.append(key, value);
            });
            return navigator.sendBeacon(url, form);
        } catch (err) {
            return false;
        }
    };

    var runTaskInBackground = function (taskId) {
        if (sendBeaconRequest(Config.dashboardAiRunUrl, {task_id: taskId})) {
            return;
        }

        $.ajax({
            url: Config.dashboardAiRunUrl,
            type: 'POST',
            dataType: 'json',
            timeout: 8000,
            data: {
                task_id: taskId
            }
        }).fail(function () {
        });
    };

    sendPrompt = function () {
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

        $.ajax({
            url: Config.dashboardAiSubmitUrl,
            type: 'POST',
            dataType: 'json',
            timeout: 20000,
            data: {
                prompt: prompt,
                focus: state.focus,
                preset_key: state.presetKey,
                setting_id: $root.data('ai-setting-id') || '',
                quick_mode: 1
            }
        }).done(function (ret) {
            if (ret.code !== 1) {
                state.sending = false;
                updateSendState();
                renderAnswer('', ret.data ? ret.data.diagnostic : null, []);
                Toastr.error(ret.msg || 'AI 任务提交失败');
                return;
            }

            var data = ret.data || {};
            var task = data.task || {};
            if (!task.id) {
                state.sending = false;
                updateSendState();
                Toastr.error('AI 任务创建失败');
                return;
            }

            renderPendingAnswer(task);
            $root.find('[data-role="dashboard-ai-prompt"]').val('');
            syncPromptHeight();
            state.presetKey = '';
            startTaskPolling(task.id);
            runTaskInBackground(task.id);
        }).fail(function () {
            state.sending = false;
            updateSendState();
            Toastr.error('AI 任务提交失败，请稍后重试');
        });
    };

    var baseClearAnswer = clearAnswer;
    clearAnswer = function () {
        if (state.sending) {
            Toastr.warning('AI 正在后台分析，请稍后再清空结果。');
            return;
        }

        stopTaskPolling();
        renderTaskState(null);
        baseClearAnswer();
    };

    var bindEvents = function () {
        $(document).on('click', '.btn-refresh', function () {
            window.location.reload();
        });

        $root.on('click', '[data-dashboard-focus]', function () {
            if ($(this).data('dashboard-preset')) {
                return;
            }
            state.focus = $(this).data('dashboard-focus') || 'overview';
            renderFocuses();
        });

        $root.on('click', '[data-dashboard-preset]', function () {
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

        $root.on('input', '[data-role="dashboard-ai-prompt"]', syncPromptHeight);

        document.addEventListener('visibilitychange', function () {
            if (!document.hidden && state.pendingTaskId) {
                if (state.taskPollTimer) {
                    window.clearTimeout(state.taskPollTimer);
                    state.taskPollTimer = null;
                }
                scheduleTaskPoll(state.pendingTaskId);
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
        syncPromptHeight();
    };

    return {
        index: init
    };
});
