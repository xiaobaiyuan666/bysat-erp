define(['jquery', 'bootstrap', 'backend'], function ($, undefined, Backend) {

    var state = {
        focus: 'overview',
        presetKey: '',
        currentSetting: null,
        sending: false,
        quickMode: true,
        suggestions: [],
        workspaceActions: [],
        focuses: [],
        presets: [],
        messages: [],
        initialApplied: false,
        pendingTaskId: 0,
        taskPollTimer: null,
        messageStreamTimer: null,
        messageStreamNonce: 0
    };

    var $root = null;
    var initialEntry = Config.initialAiEntry || {};

    var escapeHtml = function (value) {
        return $('<div />').text(value || '').html();
    };

    var formatInlineText = function (value) {
        return escapeHtml(value || '')
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/`([^`]+)`/g, '<code>$1</code>');
    };

    var formatMessageContent = function (value) {
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

        return html.length ? html.join('') : '<p>' + formatInlineText(value || '') + '</p>';
    };

    var request = function (options) {
        return $.ajax($.extend({
            dataType: 'json',
            timeout: 120000
        }, options));
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

    var copyText = function (value, successText) {
        var text = String(value || '');
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

    var roleLabel = function (role) {
        if (role === 'user') {
            return '我';
        }
        if (role === 'assistant') {
            return 'AI 助手';
        }
        return '系统';
    };

    var roleIcon = function (role) {
        if (role === 'user') {
            return 'fa fa-user';
        }
        if (role === 'assistant') {
            return 'fa fa-magic';
        }
        return 'fa fa-info-circle';
    };

    var getSendLabel = function () {
        if (state.sending) {
            return state.quickMode ? '<i class="fa fa-spinner fa-spin"></i> 快速分析中' : '<i class="fa fa-spinner fa-spin"></i> 深度分析中';
        }

        return state.quickMode ? '<i class="fa fa-bolt"></i> 快速发送' : '<i class="fa fa-search"></i> 深度分析';
    };

    var updateSendState = function () {
        var disabled = state.sending || !state.currentSetting || !state.currentSetting.configured;
        $root.find('[data-action="send"]').prop('disabled', disabled).html(getSendLabel());
        $root.find('[data-role="prompt"]').prop('disabled', state.sending);
    };

    var renderAnswerMeta = function (task) {
        var $meta = $root.find('[data-role="answer-meta"]');
        if (!$meta.length) {
            return;
        }

        if (!task || !task.id) {
            $meta.hide().empty();
            return;
        }

        var parts = ['完整 AI 对话页', task.quick_mode ? '快速模式（轻量上下文）' : '深度模式', '仍调用当前配置模型'];
        if ((task.status === 'queued' || task.status === 'processing') && task.duration_label) {
            parts.push('已等待 ' + task.duration_label);
        } else if (task.status === 'done' && task.duration_label) {
            parts.push('回答用时 ' + task.duration_label);
        } else if (task.status === 'failed') {
            parts.push('本次执行失败');
        }

        $meta.text(parts.join(' · ')).css('display', 'block');
    };

    var syncPromptHeight = function () {
        var $prompt = $root.find('[data-role="prompt"]');
        if (!$prompt.length) {
            return;
        }

        var el = $prompt[0];
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 240) + 'px';
    };

    var renderModeState = function () {
        $root.find('[data-mode]').each(function () {
            var $button = $(this);
            var isQuick = $button.data('mode') === 'quick';
            var active = state.quickMode ? isQuick : !isQuick;

            $button
                .toggleClass('btn-primary', active)
                .toggleClass('btn-default', !active)
                .attr('aria-pressed', active ? 'true' : 'false');
        });

        $root.find('[data-role="mode-hint"]').text(
            state.quickMode
                ? '快速模式优先返回可执行结果，适合日常处理。'
                : '深度模式会保留更多上下文，适合复杂分析和复盘。'
        );

        updateSendState();
    };

    renderModeState = function () {
        $root.find('[data-mode]').each(function () {
            var $button = $(this);
            var isQuick = $button.data('mode') === 'quick';
            var active = state.quickMode ? isQuick : !isQuick;

            $button
                .toggleClass('btn-primary', active)
                .toggleClass('btn-default', !active)
                .attr('aria-pressed', active ? 'true' : 'false');
        });

        $root.find('[data-role="mode-hint"]').text(
            state.quickMode
                ? '快速模式只带轻量上下文，仍调用系统默认大模型，不是本地离线模型；适合当前完整对话里快问快答。'
                : '深度模式会保留更多上下文，仍调用系统默认大模型，适合复盘、拆解和复杂分析。'
        );

        updateSendState();
    };

    var renderDiagnostic = function (diagnostic) {
        var $box = $root.find('[data-role="diagnostic"]');
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

        var actionHtml = '';
        if (diagnostic.action_url) {
            actionHtml = '<div style="margin-top:10px;"><button type="button" class="btn btn-default btn-xs" data-open-url="' +
                escapeHtml(diagnostic.action_url) +
                '" data-open-title="' +
                escapeHtml(diagnostic.action_label || '打开配置') +
                '">' +
                escapeHtml(diagnostic.action_label || '打开配置') +
                '</button></div>';
        }

        $box
            .removeClass('alert-info alert-success alert-warning alert-danger')
            .addClass(panelClass)
            .html(
                '<strong>' + escapeHtml(diagnostic.title || '提示') + '</strong>' +
                '<div style="margin-top:6px;line-height:1.8;">' + escapeHtml(diagnostic.message) + '</div>' +
                actionHtml
            )
            .show();
    };

    var buildAccessibleHint = function () {
        var labels = $.map(state.focuses || [], function (item) {
            if (!item || !item.key || item.key === 'overview') {
                return null;
            }
            return item.label || null;
        });

        if (!labels.length) {
            return '可以直接问当前账号能看到的经营问题，AI 会自动带上对应业务数据。';
        }

        return '可以直接问' + labels.join('、') + '这些模块的问题，AI 会自动带上你当前权限范围内的业务数据。';
    };

    var renderSetting = function (setting) {
        var $name = $root.find('[data-role="setting-name"]');
        var $meta = $root.find('[data-role="setting-meta"]');
        var $badge = $root.find('[data-role="setting-badge"]');
        var $hint = $root.find('[data-role="chat-hint"]');

        state.currentSetting = setting || null;

        if (!setting) {
            $name.text('AI 服务未配置');
            $meta.text('请到 AI 配置里新增 OpenAI 兼容模型，并设置默认模型。');
            $badge.removeClass().addClass('label label-warning').text('未配置');
            $hint.text('当前还没有模型配置。先补齐 Base URL、API Key 和模型名称，再回来提问。');
            updateSendState();
            return;
        }

        $name.text(setting.provider_name + (setting.model ? ' / ' + setting.model : ''));
        $meta.text('当前使用系统默认模型。模型地址、Key、测试连接都统一在 AI 配置页维护。');

        if (setting.configured) {
            $badge.removeClass().addClass('label label-success').text('可用');
            $hint.text(buildAccessibleHint());
        } else {
            $badge.removeClass().addClass('label label-warning').text('待补配置');
            $hint.text('当前模型记录还没有配完整。补齐 Base URL、API Key 和模型名称后，这里就能直接分析。');
        }

        updateSendState();
    };

    var renderContextSections = function (sections) {
        var html = [];
        $.each(sections || {}, function (key, text) {
            html.push('<li>' + escapeHtml(text || key) + '</li>');
        });
        $root.find('[data-role="context-list"]').html(html.join(''));
    };

    var renderFocusMeta = function () {
        var current = null;
        $.each(state.focuses || [], function (_, item) {
            if (item.key === state.focus) {
                current = item;
                return false;
            }
        });

        $root.find('[data-role="focus-desc"]').text(current ? current.description : '');
    };

    var renderFocuses = function () {
        var html = [];
        $.each(state.focuses || [], function (_, item) {
            html.push(
                '<button type="button" class="ai-chip ' + (state.focus === item.key ? 'is-active' : '') + '" data-focus="' + escapeHtml(item.key) + '">' +
                escapeHtml(item.label) +
                '</button>'
            );
        });
        $root.find('[data-role="focuses"]').html(html.join(''));
        renderFocusMeta();
        renderWorkspaceActions();
    };

    var renderPresets = function () {
        var html = [];
        $.each(state.presets || [], function (_, item) {
            html.push(
                '<button type="button" class="ai-chip ' + (state.presetKey === item.key ? 'is-active' : '') + '" data-preset="' + escapeHtml(item.key) + '" title="' + escapeHtml(item.description || '') + '">' +
                escapeHtml(item.label) +
                '</button>'
            );
        });
        $root.find('[data-role="presets"]').html(html.join(''));
    };

    var renderExamples = function (items) {
        var html = [];
        $.each(items || [], function (_, item) {
            html.push('<button type="button" class="btn btn-default btn-xs" data-example="' + escapeHtml(item) + '">' + escapeHtml(item) + '</button>');
        });
        $root.find('[data-role="examples"]').html(html.join(''));
    };

    var renderSummaryCards = function (cards) {
        var html = [];
        $.each(cards || [], function (_, item) {
            html.push(
                '<div class="ai-summary-card">' +
                '<span>' + escapeHtml(item.label) + '</span>' +
                '<strong>' + escapeHtml(item.value) + '</strong>' +
                '<small class="ai-meta">' + escapeHtml(item.hint || '') + '</small>' +
                '</div>'
            );
        });
        $root.find('[data-role="summary-cards"]').html(html.join(''));
    };

    var renderWorkspaceActions = function () {
        var html = [];
        $.each(state.workspaceActions || [], function (_, item) {
            if (state.focus !== 'overview' && item.focuses && $.inArray(state.focus, item.focuses) === -1) {
                return;
            }

            html.push(
                '<button type="button" class="btn btn-default btn-block" data-open-url="' + escapeHtml(item.url) + '" data-open-title="' + escapeHtml(item.label) + '" data-open-icon="' + escapeHtml(item.icon || '') + '">' +
                '<i class="' + escapeHtml(item.icon || 'fa fa-link') + '"></i> ' +
                escapeHtml(item.label) +
                '<div class="ai-meta" style="margin-top:4px;">' + escapeHtml(item.hint || '') + '</div>' +
                '</button>'
            );
        });
        $root.find('[data-role="workspace-actions"]').html(html.join(''));
    };

    var renderActionSuggestions = function () {
        var $wrap = $root.find('[data-role="action-suggestions-wrap"]');
        var $list = $root.find('[data-role="action-suggestions"]');

        if (!state.suggestions.length) {
            $wrap.removeClass('is-visible').hide();
            $list.empty();
            return;
        }

        var html = [];
        $.each(state.suggestions, function (index, item) {
            html.push(
                '<button type="button" class="btn btn-default" data-suggestion-index="' + index + '">' +
                (item.icon ? '<i class="' + escapeHtml(item.icon) + '"></i> ' : '') +
                escapeHtml(item.label) +
                '</button>'
            );
        });

        $list.html(html.join(''));
        $wrap.addClass('is-visible').show();
    };

    var renderMessages = function (messages) {
        state.messages = messages || [];
        var $wrap = $root.find('[data-role="messages"]');

        if (!state.messages.length) {
            $wrap.html(
                '<div class="ai-empty">' +
                '<i class="fa fa-comments-o"></i>' +
                '<div class="ai-title" style="font-size:18px;">先从一个问题开始</div>' +
                '<div class="ai-empty-hint">你可以直接点一个常用分析，或者自己输入问题开始。</div>' +
                '</div>'
            );
            return;
        }

        var html = [];
        $.each(state.messages, function (index, item) {
            var tools = '';
            if (item.role === 'assistant') {
                tools = '<div class="ai-message-tools"><button type="button" class="btn btn-link btn-xs" data-copy-message="' + index + '">复制回答</button></div>';
            }

            html.push(
                '<div class="ai-message is-' + escapeHtml(item.role) + '">' +
                '<div class="ai-message-bubble">' +
                '<div class="ai-message-role"><i class="' + roleIcon(item.role) + '"></i>' + roleLabel(item.role) + '</div>' +
                '<div class="ai-message-content">' + formatMessageContent(item.content || '') + '</div>' +
                tools +
                '<div class="ai-message-time">' + escapeHtml(item.message_at || '') + '</div>' +
                '</div>' +
                '</div>'
            );
        });

        $wrap.html(html.join(''));
        if ($wrap.length && $wrap[0]) {
            $wrap.scrollTop($wrap[0].scrollHeight);
        }
    };

    var findFocus = function (focusKey) {
        var found = null;
        $.each(state.focuses || [], function (_, item) {
            if (item.key === focusKey) {
                found = item;
                return false;
            }
        });
        return found;
    };

    var findPreset = function (presetKey) {
        var found = null;
        $.each(state.presets || [], function (_, item) {
            if (item.key === presetKey) {
                found = item;
                return false;
            }
        });
        return found;
    };

    var applyInitialEntry = function () {
        if (state.initialApplied) {
            return;
        }
        state.initialApplied = true;

        if (initialEntry.focus && findFocus(initialEntry.focus)) {
            state.focus = initialEntry.focus;
        }

        if (initialEntry.preset_key && findPreset(initialEntry.preset_key)) {
            state.presetKey = initialEntry.preset_key;
        }

        if (initialEntry.prompt) {
            $root.find('[data-role="prompt"]').val(initialEntry.prompt);
            syncPromptHeight();
        }

        renderFocuses();
        renderPresets();

        if (initialEntry.auto_ask && !state.messages.length) {
            window.setTimeout(function () {
                sendPrompt();
            }, 120);
        }
    };

    var fillWorkbench = function (data) {
        state.focuses = data.focuses || [];
        state.presets = data.presets || [];
        state.workspaceActions = data.workspace_actions || [];
        state.suggestions = data.suggestions || [];

        if (!findFocus(state.focus)) {
            state.focus = state.focuses.length ? state.focuses[0].key : 'overview';
        }

        renderDiagnostic(data.diagnostic || (data.setting ? data.setting.diagnostic : null));
        renderSetting(data.setting || null);
        renderContextSections(data.context_sections || {});
        renderFocuses();
        renderPresets();
        renderExamples(data.examples || []);
        renderSummaryCards(data.summary_cards || []);
        renderMessages(data.messages || []);
        renderActionSuggestions();
        renderModeState();
        applyInitialEntry();
    };

    var loadBootstrap = function () {
        request({
            url: Config.bootstrapUrl,
            type: 'GET'
        }).done(function (ret) {
            if (ret.code !== 1) {
                Toastr.error(ret.msg || 'AI 工作台加载失败');
                return;
            }
            fillWorkbench(ret.data || {});
        }).fail(function () {
            Toastr.error('AI 工作台加载失败');
        });
    };

    var sendPrompt = function () {
        if (state.sending) {
            return;
        }

        if (!state.currentSetting || !state.currentSetting.configured) {
            Toastr.warning('当前模型还没有配置好，请先补齐 AI 配置。');
            return;
        }

        var prompt = $.trim($root.find('[data-role="prompt"]').val());
        if (!prompt && !state.presetKey) {
            Toastr.warning('请输入问题，或者先点一个常用分析。');
            return;
        }

        state.sending = true;
        updateSendState();

        request({
            url: Config.askUrl,
            type: 'POST',
            data: {
                prompt: prompt,
                focus: state.focus,
                preset_key: state.presetKey,
                quick_mode: state.quickMode ? 1 : 0
            }
        }).done(function (ret) {
            if (ret.code !== 1) {
                if (ret.data) {
                    renderDiagnostic(ret.data.diagnostic || null);
                    if (ret.data.messages) {
                        renderMessages(ret.data.messages);
                    }
                    if (ret.data.summary_cards) {
                        renderSummaryCards(ret.data.summary_cards);
                    }
                }
                Toastr.error(ret.msg || '发送失败');
                return;
            }

            var data = ret.data || {};
            if (data.focus && data.focus.key) {
                state.focus = data.focus.key;
            }
            if (data.workspace_actions) {
                state.workspaceActions = data.workspace_actions;
            }

            state.suggestions = data.suggestions || [];
            renderDiagnostic(data.diagnostic || null);
            renderMessages(data.messages || []);
            renderSetting(data.setting || state.currentSetting);
            renderSummaryCards(data.summary_cards || []);
            renderFocuses();
            renderActionSuggestions();

            $root.find('[data-role="prompt"]').val('');
            syncPromptHeight();
            state.presetKey = '';
            renderPresets();
        }).fail(function () {
            Toastr.error('发送失败，请稍后重试');
        }).always(function () {
            state.sending = false;
            updateSendState();
        });
    };

    var clearConversation = function () {
        Layer.confirm('确认清空当前 AI 会话吗？', {icon: 3, title: '确认'}, function (index) {
            request({
                url: Config.clearUrl,
                type: 'POST'
            }).done(function (ret) {
                if (ret.code !== 1) {
                    Toastr.error(ret.msg || '清空失败');
                    return;
                }

                state.suggestions = [];
                renderMessages([]);
                renderActionSuggestions();
                Toastr.success('会话已清空');
                Layer.close(index);
            }).fail(function () {
                Toastr.error('清空失败');
            });
        });
    };

    var handleSuggestion = function (index) {
        var item = state.suggestions[index];
        if (!item) {
            return;
        }

        if (item.kind === 'copy') {
            copyText(item.content || '', '已复制到剪贴板');
            return;
        }

        if (item.kind === 'prompt') {
            $root.find('[data-role="prompt"]').val(item.prompt || '').focus();
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
        var $wrap = $root.find('[data-role="task-state-wrap"]');
        if (!$wrap.length) {
            return;
        }

        if (!task || !task.id) {
            $wrap.hide();
            return;
        }

        $root.find('[data-role="task-state-badge"]').text(task.status_text || '后台中');
        $root.find('[data-role="task-state-title"]').text(task.quick_mode ? 'AI 后台快速任务' : 'AI 后台深度任务');
        $root.find('[data-role="task-state-text"]').text(task.status_message || '当前分析会在后台继续执行，你可以继续浏览其他页面。');
        $wrap.css('display', 'flex');
    };

    var buildPendingPromptText = function (prompt, presetKey) {
        var preset = presetKey ? findPreset(presetKey) : null;
        if (preset && prompt) {
            return (preset.label || '常用分析') + '\n\n补充说明：\n' + prompt;
        }
        if (preset) {
            return preset.label || preset.prompt || '常用分析';
        }
        return prompt || '已提交问题';
    };

    renderTaskState = function (task) {
        var $wrap = $root.find('[data-role="task-state-wrap"]');
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

        $root.find('[data-role="task-state-badge"]').text(task.status_text || '后台中');
        $root.find('[data-role="task-state-title"]').text(task.quick_mode ? 'AI 后台快速任务' : 'AI 后台深度任务');
        $root.find('[data-role="task-state-text"]').text(taskText);
        $wrap.css('display', 'flex');
    };

    getSendLabel = function () {
        if (state.sending) {
            return state.quickMode ? '<i class="fa fa-spinner fa-spin"></i> 后台快速分析中' : '<i class="fa fa-spinner fa-spin"></i> 后台深度分析中';
        }

        return state.quickMode ? '<i class="fa fa-bolt"></i> 快速发送' : '<i class="fa fa-search"></i> 深度分析';
    };

    updateSendState = function () {
        var disabled = state.sending || !state.currentSetting || !state.currentSetting.configured;
        $root.find('[data-action="send"]').prop('disabled', disabled).html(getSendLabel());
        $root.find('[data-action="clear"]').prop('disabled', state.sending);
        $root.find('[data-role="prompt"]').prop('disabled', !state.currentSetting || !state.currentSetting.configured);
    };

    var cloneMessages = function (messages) {
        return $.map(messages || [], function (item) {
            return $.extend({}, item);
        });
    };

    var stopMessageStream = function () {
        if (state.messageStreamTimer) {
            window.clearTimeout(state.messageStreamTimer);
            state.messageStreamTimer = null;
        }
        state.messageStreamNonce += 1;
    };

    var getStreamChunkSize = function (remaining) {
        if (remaining > 1000) {
            return 42;
        }
        if (remaining > 560) {
            return 28;
        }
        if (remaining > 220) {
            return 18;
        }
        return 10;
    };

    var getStreamDelay = function (remaining) {
        if (remaining > 1000) {
            return 12;
        }
        if (remaining > 560) {
            return 16;
        }
        if (remaining > 220) {
            return 22;
        }
        return 30;
    };

    roleLabel = function (role) {
        if (role === 'user') {
            return '我';
        }
        if (role === 'assistant') {
            return 'AI 助手';
        }
        return '系统';
    };

    renderMessages = function (messages) {
        state.messages = messages || [];
        var $wrap = $root.find('[data-role="messages"]');

        if (!state.messages.length) {
            $wrap.html(
                '<div class="ai-empty">' +
                '<i class="fa fa-comments-o"></i>' +
                '<div class="ai-title" style="font-size:18px;">先从一个问题开始</div>' +
                '<div class="ai-empty-hint">你可以直接点一个常用分析，或者自己输入问题开始。</div>' +
                '</div>'
            );
            return;
        }

        var html = [];
        $.each(state.messages, function (index, item) {
            var extraClass = item.pending ? ' is-pending' : '';
            var tools = '';
            if (item.role === 'assistant' && !item.pending) {
                tools = '<div class="ai-message-tools"><button type="button" class="btn btn-link btn-xs" data-copy-message="' + index + '">复制回答</button></div>';
            }

            html.push(
                '<div class="ai-message is-' + escapeHtml(item.role) + extraClass + '">' +
                '<div class="ai-message-bubble">' +
                '<div class="ai-message-role"><i class="' + roleIcon(item.role) + '"></i>' + roleLabel(item.role) + '</div>' +
                '<div class="ai-message-content">' + formatMessageContent(item.content || '') + '</div>' +
                tools +
                '<div class="ai-message-time">' + escapeHtml(item.message_at || '') + '</div>' +
                '</div>' +
                '</div>'
            );
        });

        $wrap.html(html.join(''));
        if ($wrap.length && $wrap[0]) {
            $wrap.scrollTop($wrap[0].scrollHeight);
        }
    };

    var renderChatMessages = function (messages) {
        state.messages = messages || [];
        var $wrap = $root.find('[data-role="messages"]');

        if (!state.messages.length) {
            $wrap.html(
                '<div class="ai-empty">' +
                '<i class="fa fa-comments-o"></i>' +
                '<div class="ai-title" style="font-size:18px;">先从一个问题开始</div>' +
                '<div class="ai-empty-hint">你可以直接点一个常用分析，或者自己输入问题开始。</div>' +
                '</div>'
            );
            return;
        }

        var html = [];
        $.each(state.messages, function (index, item) {
            var extraClass = item.pending ? ' is-pending' : '';
            var bubbleClass = 'ai-message-bubble' + (item.pending ? ' is-pending' : '') + (item.streaming ? ' is-streaming' : '');
            var tools = '';

            if (item.role === 'assistant' && !item.pending && !item.streaming) {
                tools = '<div class="ai-message-tools"><button type="button" class="btn btn-link btn-xs" data-copy-message="' + index + '">复制回答</button></div>';
            }

            var contentHtml = item.pending
                ? '<div class="ai-typing"><span class="ai-typing-dots"><span></span><span></span><span></span></span><span>' + escapeHtml(item.content || 'AI 正在整理结果，请稍候...') + '</span></div>'
                : formatMessageContent(item.content || '') + (item.streaming ? '<span class="ai-stream-cursor"></span>' : '');

            html.push(
                '<div class="ai-message is-' + escapeHtml(item.role) + extraClass + '">' +
                '<div class="ai-message-avatar"><i class="' + roleIcon(item.role) + '"></i></div>' +
                '<div class="' + bubbleClass + '">' +
                '<div class="ai-message-role"><i class="' + roleIcon(item.role) + '"></i>' + roleLabel(item.role) + '</div>' +
                '<div class="ai-message-content">' + contentHtml + '</div>' +
                tools +
                '<div class="ai-message-time">' + escapeHtml(item.message_at || '') + '</div>' +
                '</div>' +
                '</div>'
            );
        });

        $wrap.html(html.join(''));
        if ($wrap.length && $wrap[0]) {
            $wrap.scrollTop($wrap[0].scrollHeight);
        }
    };

    var streamMessages = function (messages) {
        stopMessageStream();

        var finalMessages = cloneMessages(messages || []);
        var targetIndex = -1;

        for (var i = finalMessages.length - 1; i >= 0; i -= 1) {
            if (finalMessages[i] && finalMessages[i].role === 'assistant' && finalMessages[i].content) {
                targetIndex = i;
                break;
            }
        }

        if (targetIndex === -1) {
            renderChatMessages(finalMessages);
            return;
        }

        var fullContent = String(finalMessages[targetIndex].content || '');
        if (fullContent.length < 80) {
            renderChatMessages(finalMessages);
            return;
        }

        var liveMessages = cloneMessages(finalMessages);
        liveMessages[targetIndex].content = '';
        liveMessages[targetIndex].streaming = true;

        renderChatMessages(liveMessages);

        var nonce = ++state.messageStreamNonce;
        var cursor = 0;

        var tick = function () {
            if (nonce !== state.messageStreamNonce) {
                return;
            }

            cursor = Math.min(fullContent.length, cursor + getStreamChunkSize(fullContent.length - cursor));
            liveMessages[targetIndex].content = fullContent.slice(0, cursor);
            liveMessages[targetIndex].streaming = cursor < fullContent.length;
            renderChatMessages(liveMessages);

            if (cursor >= fullContent.length) {
                stopMessageStream();
                renderChatMessages(finalMessages);
                return;
            }

            state.messageStreamTimer = window.setTimeout(tick, getStreamDelay(fullContent.length - cursor));
        };

        state.messageStreamTimer = window.setTimeout(tick, 36);
    };

    renderMessages = renderChatMessages;

    var renderPendingTask = function (task, displayPrompt, keepExistingUser) {
        var baseMessages = $.grep(state.messages || [], function (item) {
            return !item.pending;
        });

        if (!keepExistingUser && displayPrompt) {
            baseMessages.push({
                role: 'user',
                content: displayPrompt,
                message_at: task.created_at || ''
            });
        }

        baseMessages.push({
            role: 'assistant',
            content: task.status_message || 'AI 正在后台分析，你可以继续浏览其他页面。',
            message_at: task.status_text || '分析中',
            pending: true
        });

        stopMessageStream();
        state.suggestions = [];
        renderMessages(baseMessages);
        renderActionSuggestions();
        renderAnswerMeta(task);
        renderTaskState(task);
        renderDiagnostic({
            type: 'info',
            title: task.status_text || '后台处理中',
            message: task.status_message || 'AI 正在后台分析，你可以继续浏览其他页面。'
        });
    };

    var applyTaskResult = function (payload, task) {
        var data = payload || {};
        if (data.focus && data.focus.key) {
            state.focus = data.focus.key;
        }
        if (data.workspace_actions) {
            state.workspaceActions = data.workspace_actions;
        }

        state.suggestions = data.suggestions || [];
        renderTaskState(null);
        renderAnswerMeta(task || null);
        renderDiagnostic(data.diagnostic || null);
        streamMessages(data.messages || []);
        renderSetting(data.setting || state.currentSetting);
        renderSummaryCards(data.summary_cards || []);
        renderFocuses();
        renderActionSuggestions();
    };

    var scheduleTaskPoll = function (taskId) {
        state.taskPollTimer = window.setTimeout(function () {
            request({
                url: Config.taskStatusUrl,
                type: 'GET',
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
                    renderPendingTask(task, '', true);
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

                renderMessages($.grep(state.messages || [], function (item) {
                    return !item.pending;
                }));
                renderDiagnostic({
                    type: 'danger',
                    title: 'AI 后台分析失败',
                    message: task.error_message || task.status_message || '请稍后重试。'
                });
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
        if (sendBeaconRequest(Config.runTaskUrl, {task_id: taskId})) {
            return;
        }

        request({
            url: Config.runTaskUrl,
            type: 'POST',
            timeout: 8000,
            data: {
                task_id: taskId
            }
        }).fail(function () {
        });
    };

    var baseFillWorkbench = fillWorkbench;
    fillWorkbench = function (data) {
        baseFillWorkbench(data);

        var pendingTask = data.pending_task || null;
        if (!pendingTask || !pendingTask.id) {
            renderTaskState(null);
            renderAnswerMeta(null);
            return;
        }

        if (pendingTask.status === 'queued' || pendingTask.status === 'processing') {
            state.sending = true;
            renderPendingTask(pendingTask, '', true);
            updateSendState();
            startTaskPolling(pendingTask.id);

            if (pendingTask.status === 'queued') {
                runTaskInBackground(pendingTask.id);
            }
        }
    };

    sendPrompt = function () {
        if (state.sending) {
            return;
        }

        if (!state.currentSetting || !state.currentSetting.configured) {
            Toastr.warning('当前模型还没有配置好，请先补齐 AI 配置。');
            return;
        }

        var prompt = $.trim($root.find('[data-role="prompt"]').val());
        if (!prompt && !state.presetKey) {
            Toastr.warning('请输入问题，或者先点一个常用分析。');
            return;
        }

        state.sending = true;
        updateSendState();

        request({
            url: Config.submitTaskUrl,
            type: 'POST',
            data: {
                prompt: prompt,
                focus: state.focus,
                preset_key: state.presetKey,
                quick_mode: state.quickMode ? 1 : 0
            }
        }).done(function (ret) {
            if (ret.code !== 1) {
                state.sending = false;
                updateSendState();
                if (ret.data) {
                    renderDiagnostic(ret.data.diagnostic || null);
                }
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

            renderSetting(data.setting || state.currentSetting);
            renderPendingTask(task, buildPendingPromptText(prompt, state.presetKey), false);
            $root.find('[data-role="prompt"]').val('');
            syncPromptHeight();
            state.presetKey = '';
            renderPresets();
            updateSendState();
            startTaskPolling(task.id);
            runTaskInBackground(task.id);
        }).fail(function () {
            state.sending = false;
            updateSendState();
            Toastr.error('AI 任务提交失败，请稍后重试');
        });
    };

    var baseClearConversation = clearConversation;
    clearConversation = function () {
        if (state.sending) {
            Toastr.warning('AI 正在后台分析，请稍后再清空会话。');
            return;
        }

        stopTaskPolling();
        stopMessageStream();
        renderTaskState(null);
        renderAnswerMeta(null);
        baseClearConversation();
    };

    var bindEvents = function () {
        $root.on('click', '[data-focus]', function () {
            state.focus = $(this).data('focus');
            renderFocuses();
        });

        $root.on('click', '[data-preset]', function () {
            state.presetKey = $(this).data('preset');
            renderPresets();

            var prompt = $.trim($root.find('[data-role="prompt"]').val());
            if (!prompt) {
                sendPrompt();
            } else {
                $root.find('[data-role="prompt"]').focus();
            }
        });

        $root.on('click', '[data-example]', function () {
            $root.find('[data-role="prompt"]').val($(this).data('example')).focus();
            syncPromptHeight();
        });

        $root.on('click', '[data-mode]', function () {
            state.quickMode = $(this).data('mode') === 'quick';
            renderModeState();
        });

        $root.on('click', '[data-action="send"]', sendPrompt);
        $root.on('click', '[data-action="clear"]', clearConversation);

        $root.on('click', '[data-action="reload"]', function () {
            loadBootstrap();
        });

        $root.on('click', '[data-copy-message]', function () {
            var index = parseInt($(this).data('copy-message'), 10);
            if (!isNaN(index) && state.messages[index]) {
                copyText(state.messages[index].content || '', '回答已复制');
            }
        });

        $root.on('click', '[data-suggestion-index]', function () {
            handleSuggestion(parseInt($(this).data('suggestion-index'), 10));
        });

        $root.on('click', '[data-open-url]', function () {
            openTab($(this).data('open-url'), $(this).data('open-title'), $(this).data('open-icon'));
        });

        $root.on('keydown', '[data-role="prompt"]', function (event) {
            if ((event.ctrlKey || event.metaKey) && event.keyCode === 13) {
                event.preventDefault();
                sendPrompt();
            }
        });

        $root.on('input', '[data-role="prompt"]', syncPromptHeight);

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

    var Controller = {
        index: function () {
            $root = $('#ai-workbench');
            bindEvents();
            syncPromptHeight();
            renderModeState();
            loadBootstrap();
        }
    };

    return Controller;
});
