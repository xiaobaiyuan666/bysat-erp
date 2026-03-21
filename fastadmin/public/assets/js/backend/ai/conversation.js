define(['jquery', 'bootstrap', 'backend'], function ($, undefined, Backend) {

    var state = {
        focus: 'overview',
        presetKey: '',
        settingId: '',
        currentSetting: null,
        sending: false,
        suggestions: [],
        workspaceActions: [],
        focuses: [],
        presets: [],
        messages: [],
        initialApplied: false
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

    var updateSendState = function () {
        var disabled = state.sending || !state.currentSetting || !state.currentSetting.configured;
        $root.find('[data-action=\"send\"]').prop('disabled', disabled);
        $root.find('[data-role=\"prompt\"]').prop('disabled', state.sending);
    };

    var syncPromptHeight = function () {
        var $prompt = $root.find('[data-role=\"prompt\"]');
        if (!$prompt.length) {
            return;
        }

        var el = $prompt[0];
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 220) + 'px';
    };

    var renderDiagnostic = function (diagnostic) {
        var $box = $root.find('[data-role=\"diagnostic\"]');
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
            actionHtml = '<div style=\"margin-top:10px;\"><button type=\"button\" class=\"btn btn-default btn-xs\" data-open-url=\"' + escapeHtml(diagnostic.action_url) + '\" data-open-title=\"' + escapeHtml(diagnostic.action_label || '打开配置') + '\">' + escapeHtml(diagnostic.action_label || '打开配置') + '</button></div>';
        }

        $box
            .removeClass('alert-info alert-success alert-warning alert-danger')
            .addClass(panelClass)
            .html(
                '<strong>' + escapeHtml(diagnostic.title || '提示') + '</strong>' +
                '<div style=\"margin-top:6px;line-height:1.8;\">' + escapeHtml(diagnostic.message) + '</div>' +
                actionHtml
            )
            .show();
    };

    var renderSetting = function (setting) {
        var $name = $root.find('[data-role=\"setting-name\"]');
        var $meta = $root.find('[data-role=\"setting-meta\"]');
        var $badge = $root.find('[data-role=\"setting-badge\"]');
        var $hint = $root.find('[data-role=\"chat-hint\"]');

        state.currentSetting = setting || null;
        if (!setting) {
            $name.text('还没有可用模型');
            $meta.text('先到 AI 配置里新增一条 OpenAI 兼容模型。');
            $badge.removeClass().addClass('label label-warning').text('未配置');
            $hint.text('当前没有模型配置。先补齐 Base URL、API Key 和模型名称，再回来发问。');
            updateSendState();
            return;
        }

        $name.text(setting.provider_name + (setting.model ? ' / ' + setting.model : ''));
        $meta.text((setting.endpoint || setting.base_url || '未填写接口地址') + ' · 温度 ' + (setting.temperature || 0.2));

        if (setting.configured) {
            $badge.removeClass().addClass('label label-success').text(setting.is_default ? '默认模型' : '可用');
            $hint.text('可以直接问经营、财务、项目、APP 运营或合同审批问题，AI 会自动带上相关业务数据。');
        } else {
            $badge.removeClass().addClass('label label-warning').text('待补配置');
            $hint.text('当前模型记录还没配完整。补齐 Base URL、API Key 和模型名称后，这里就能直接分析。');
        }

        updateSendState();
    };

    var renderSettingOptions = function (items, current) {
        var html = ['<option value=\"\">默认模型</option>'];
        $.each(items || [], function (_, item) {
            var label = item.label || ('模型 #' + item.id);
            if (item.is_default) {
                label += '（默认）';
            }
            if (!item.configured) {
                label += '（待补配置）';
            }
            html.push('<option value=\"' + item.id + '\">' + escapeHtml(label) + '</option>');
        });

        var $select = $root.find('[data-role=\"setting-select\"]');
        $select.html(html.join(''));
        $select.val(current && current.id ? String(current.id) : '');
    };

    var renderContextSections = function (sections) {
        var html = [];
        $.each(sections || {}, function (key, text) {
            html.push('<li><strong>' + escapeHtml(key.toUpperCase()) + '</strong>：' + escapeHtml(text) + '</li>');
        });
        $root.find('[data-role=\"context-list\"]').html(html.join(''));
    };

    var renderFocusMeta = function () {
        var current = null;
        $.each(state.focuses || [], function (_, item) {
            if (item.key === state.focus) {
                current = item;
                return false;
            }
        });

        $root.find('[data-role=\"focus-desc\"]').text(current ? current.description : '');
    };

    var renderFocuses = function () {
        var html = [];
        $.each(state.focuses || [], function (_, item) {
            html.push(
                '<button type=\"button\" class=\"ai-chip ' + (state.focus === item.key ? 'is-active' : '') + '\" data-focus=\"' + escapeHtml(item.key) + '\">' +
                escapeHtml(item.label) +
                '</button>'
            );
        });
        $root.find('[data-role=\"focuses\"]').html(html.join(''));
        renderFocusMeta();
        renderWorkspaceActions();
    };

    var renderPresets = function () {
        var html = [];
        $.each(state.presets || [], function (_, item) {
            html.push(
                '<button type=\"button\" class=\"ai-chip ' + (state.presetKey === item.key ? 'is-active' : '') + '\" data-preset=\"' + escapeHtml(item.key) + '\" title=\"' + escapeHtml(item.description || '') + '\">' +
                escapeHtml(item.label) +
                '</button>'
            );
        });
        $root.find('[data-role=\"presets\"]').html(html.join(''));
    };

    var renderExamples = function (items) {
        var html = [];
        $.each(items || [], function (_, item) {
            html.push('<button type=\"button\" class=\"btn btn-default btn-xs\" data-example=\"' + escapeHtml(item) + '\">' + escapeHtml(item) + '</button>');
        });
        $root.find('[data-role=\"examples\"]').html(html.join(''));
    };

    var renderSummaryCards = function (cards) {
        var html = [];
        $.each(cards || [], function (_, item) {
            html.push(
                '<div class=\"ai-summary-card\">' +
                '<span>' + escapeHtml(item.label) + '</span>' +
                '<strong>' + escapeHtml(item.value) + '</strong>' +
                '<small class=\"ai-meta\">' + escapeHtml(item.hint || '') + '</small>' +
                '</div>'
            );
        });
        $root.find('[data-role=\"summary-cards\"]').html(html.join(''));
    };

    var renderWorkspaceActions = function () {
        var html = [];
        $.each(state.workspaceActions || [], function (_, item) {
            if (state.focus !== 'overview' && item.focuses && $.inArray(state.focus, item.focuses) === -1) {
                return;
            }
            html.push(
                '<button type=\"button\" class=\"btn btn-default btn-block text-left\" data-open-url=\"' + escapeHtml(item.url) + '\" data-open-title=\"' + escapeHtml(item.label) + '\" data-open-icon=\"' + escapeHtml(item.icon || '') + '\">' +
                '<i class=\"' + escapeHtml(item.icon || 'fa fa-link') + '\"></i> ' +
                escapeHtml(item.label) +
                '<div class=\"ai-meta\" style=\"margin-top:4px;\">' + escapeHtml(item.hint || '') + '</div>' +
                '</button>'
            );
        });
        $root.find('[data-role=\"workspace-actions\"]').html(html.join(''));
    };

    var renderActionSuggestions = function () {
        var $wrap = $root.find('[data-role=\"action-suggestions-wrap\"]');
        var $list = $root.find('[data-role=\"action-suggestions\"]');
        if (!state.suggestions.length) {
            $wrap.removeClass('is-visible').hide();
            $list.empty();
            return;
        }

        var html = [];
        $.each(state.suggestions, function (index, item) {
            if (item.kind === 'link') {
                html.push(
                    '<button type=\"button\" class=\"btn btn-default\" data-suggestion-index=\"' + index + '\">' +
                    (item.icon ? '<i class=\"' + escapeHtml(item.icon) + '\"></i> ' : '') +
                    escapeHtml(item.label) +
                    '</button>'
                );
            } else {
                html.push(
                    '<button type=\"button\" class=\"btn btn-default\" data-suggestion-index=\"' + index + '\">' +
                    escapeHtml(item.label) +
                    '</button>'
                );
            }
        });

        $list.html(html.join(''));
        $wrap.addClass('is-visible').show();
    };

    var renderMessages = function (messages) {
        state.messages = messages || [];
        var $wrap = $root.find('[data-role=\"messages\"]');
        if (!state.messages.length) {
            $wrap.html(
                '<div class=\"ai-empty\">' +
                '<i class=\"fa fa-comments-o\"></i>' +
                '<div class=\"ai-title\" style=\"font-size:18px;\">还没有会话</div>' +
                '<div class=\"ai-empty-hint\">你可以直接点一个常用分析，或者自己输入问题开始。</div>' +
                '</div>'
            );
            return;
        }

        var html = [];
        $.each(state.messages, function (index, item) {
            var tools = '';
            if (item.role === 'assistant') {
                tools = '<div class=\"ai-message-tools\"><button type=\"button\" class=\"btn btn-link btn-xs\" data-copy-message=\"' + index + '\">复制</button></div>';
            }

            html.push(
                '<div class=\"ai-message is-' + escapeHtml(item.role) + '\">' +
                '<div class=\"ai-message-bubble\">' +
                '<div class=\"ai-message-role\"><i class=\"' + roleIcon(item.role) + '\"></i>' + roleLabel(item.role) + '</div>' +
                '<div class=\"ai-message-content\">' + formatMessageContent(item.content || '') + '</div>' +
                tools +
                '<div class=\"ai-message-time\">' + escapeHtml(item.message_at || '') + '</div>' +
                '</div>' +
                '</div>'
            );
        });

        $wrap.html(html.join(''));
        if ($wrap.length && $wrap[0]) {
            $wrap.scrollTop($wrap[0].scrollHeight);
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
        state.settingId = data.setting && data.setting.id ? String(data.setting.id) : '';

        renderDiagnostic(data.diagnostic || (data.setting ? data.setting.diagnostic : null));
        renderSetting(data.setting || null);
        renderSettingOptions(data.settings || [], data.setting || null);
        renderContextSections(data.context_sections || {});
        renderFocuses();
        renderPresets();
        renderExamples(data.examples || []);
        renderSummaryCards(data.summary_cards || []);
        renderMessages(data.messages || []);
        renderActionSuggestions();
        applyInitialEntry();
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
            $root.find('[data-role=\"prompt\"]').val(initialEntry.prompt);
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

    var loadBootstrap = function (settingId) {
        var params = {};
        if (settingId) {
            params.setting_id = settingId;
        }

        request({
            url: Config.bootstrapUrl,
            type: 'GET',
            data: params
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
            Toastr.warning('当前模型还没有配好，请先补齐 AI 配置。');
            return;
        }

        var prompt = $.trim($root.find('[data-role=\"prompt\"]').val());
        if (!prompt && !state.presetKey) {
            Toastr.warning('请输入问题，或者先点一个常用分析。');
            return;
        }

        state.sending = true;
        updateSendState();
        $root.find('[data-action=\"send\"]').html('<i class=\"fa fa-spinner fa-spin\"></i> 发送中');

        request({
            url: Config.askUrl,
            type: 'POST',
            data: {
                prompt: prompt,
                focus: state.focus,
                preset_key: state.presetKey,
                setting_id: state.settingId || ''
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
            $root.find('[data-role=\"prompt\"]').val('');
            syncPromptHeight();
            state.presetKey = '';
            renderPresets();
        }).fail(function () {
            Toastr.error('发送失败，请稍后重试');
        }).always(function () {
            state.sending = false;
            updateSendState();
            $root.find('[data-action=\"send\"]').html('<i class=\"fa fa-send\"></i> 发送给 AI');
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
            $root.find('[data-role=\"prompt\"]').val(item.prompt || '').focus();
            Toastr.success('已带入输入框');
            return;
        }

        if (item.kind === 'link') {
            openTab(item.url, item.label, item.icon || '');
        }
    };

    var bindEvents = function () {
        $root.on('click', '[data-focus]', function () {
            state.focus = $(this).data('focus');
            renderFocuses();
        });

        $root.on('click', '[data-preset]', function () {
            state.presetKey = $(this).data('preset');
            renderPresets();
            var prompt = $.trim($root.find('[data-role=\"prompt\"]').val());
            if (!prompt) {
                sendPrompt();
            } else {
                $root.find('[data-role=\"prompt\"]').focus();
            }
        });

        $root.on('click', '[data-example]', function () {
            $root.find('[data-role=\"prompt\"]').val($(this).data('example')).focus();
        });

        $root.on('click', '[data-action=\"send\"]', sendPrompt);
        $root.on('click', '[data-action=\"clear\"]', clearConversation);

        $root.on('change', '[data-role=\"setting-select\"]', function () {
            state.settingId = $(this).val() || '';
            loadBootstrap(state.settingId);
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

        $root.on('keydown', '[data-role=\"prompt\"]', function (event) {
            if ((event.ctrlKey || event.metaKey) && event.keyCode === 13) {
                event.preventDefault();
                sendPrompt();
            }
        });

        $root.on('input', '[data-role=\"prompt\"]', syncPromptHeight);
    };

    var Controller = {
        index: function () {
            $root = $('#ai-workbench');
            bindEvents();
            syncPromptHeight();
            loadBootstrap(initialEntry.setting_id || '');
        }
    };

    return Controller;
});
