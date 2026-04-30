define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var providerPresets = {
        aliyun: {
            provider_name: '阿里云百炼',
            base_url: 'https://dashscope.aliyuncs.com/compatible-mode/v1',
            model: 'qwen-plus',
            model_hint: '阿里云百炼常见模型：qwen-plus、qwen-max、qwen-turbo。'
        },
        openai: {
            provider_name: 'OpenAI',
            base_url: 'https://api.openai.com/v1',
            model: 'gpt-5.4',
            model_hint: 'OpenAI 兼容模型示例：gpt-5.4、gpt-4o、gpt-4.1-mini。'
        },
        deepseek: {
            provider_name: 'DeepSeek',
            base_url: 'https://api.deepseek.com/v1',
            model: 'deepseek-chat',
            model_hint: 'DeepSeek 常见模型：deepseek-chat、deepseek-reasoner。'
        },
        gateway: {
            provider_name: '通用网关',
            base_url: '',
            model: '',
            model_hint: '填写自有网关地址和 API Key 后，点击识别协议并加载模型。'
        }
    };

    var escapeHtml = function (value) {
        return $('<div />').text(value || '').html();
    };

    var parseWorkspaceMeta = function (raw) {
        if (!raw) {
            return {};
        }
        try {
            var parsed = JSON.parse(raw);
            return parsed && !Array.isArray(parsed) && typeof parsed === 'object' ? parsed : {};
        } catch (err) {
            return {};
        }
    };

    var syncWorkspaceMeta = function ($form) {
        var $hidden = $form.find('#c-workspace_json');
        if (!$hidden.length) {
            return;
        }

        var meta = parseWorkspaceMeta($hidden.val());
        meta.skip_ssl_verify = $form.find('#c-skip_ssl_verify').is(':checked');
        $hidden.val(JSON.stringify(meta));
    };

    var renderModelOptions = function ($form, modelIds, currentModel) {
        var $select = $form.find('#c-detected-models');
        if (!$select.length) {
            return;
        }

        var html = ['<option value=\"\">请选择模型</option>'];
        $.each(modelIds || [], function (_, id) {
            html.push('<option value=\"' + escapeHtml(id) + '\">' + escapeHtml(id) + '</option>');
        });

        $select.html(html.join(''));
        if (currentModel) {
            $select.val(currentModel);
        }
        $select.closest('.form-group').toggle((modelIds || []).length > 0);
    };

    var updateBaseUrlHint = function ($form, value) {
        var $hint = $form.find('#ai-base-url-warning');
        if (!$hint.length) {
            return;
        }

        if (String(value || '').indexOf('coding.dashscope.aliyuncs.com') >= 0) {
            $hint.removeClass('text-muted').addClass('text-danger').text('检测到 Coding Plan 接口，当前页面并不兼容。请使用 OpenAI 兼容接口，例如 https://dashscope.aliyuncs.com/compatible-mode/v1。');
            return;
        }

        $hint.removeClass('text-danger').addClass('text-muted').text('常见接口： https://api.openai.com/v1、https://dashscope.aliyuncs.com/compatible-mode/v1、https://api.deepseek.com/v1');
    };

    var renderDiscoveryResult = function ($form, result) {
        var $result = $form.find('#ai-discovery-result');
        if (!$result.length) {
            return;
        }

        var currentModelText = '';
        if (result.current_model_found === true) {
            currentModelText = '<div>当前模型：在候选模型中已找到</div>';
        } else if (result.current_model_found === false) {
            currentModelText = '<div>当前模型：在候选模型中未找到，请更新后重试</div>';
        }

        var notes = result.notes || [];
        var noteHtml = notes.length
            ? '<div style=\"margin-top:8px;color:#666;line-height:1.8;\">' + escapeHtml(notes.join(' ')) + '</div>'
            : '';
        var recommendationHtml = '';
        if (result.recommended_model) {
            recommendationHtml =
                '<div style=\"margin-top:10px;\">推荐模型：<strong>' + escapeHtml(result.recommended_model) + '</strong></div>' +
                '<div style=\"margin-top:4px;color:#666;line-height:1.8;\">' + escapeHtml(result.recommendation_reason || '建议优先使用更快且更稳定的模型。') + '</div>' +
                '<div style=\"margin-top:10px;\">' +
                '<button type=\"button\" class=\"btn btn-success btn-xs\" data-action=\"apply-recommended-model\" data-model=\"' + escapeHtml(result.recommended_model) + '\">' +
                '<i class=\"fa fa-bolt\"></i> 使用推荐模型' +
                '</button>' +
                '</div>';
        }
        var candidateHtml = '';
        if (result.faster_model_candidates && result.faster_model_candidates.length) {
            candidateHtml = '<div style=\"margin-top:8px;color:#666;line-height:1.8;\">候选模型：' + escapeHtml(result.faster_model_candidates.join('，')) + '</div>';
        }

        $result
            .removeClass('alert-danger alert-info')
            .addClass('alert-success')
            .html(
                '<strong>检测成功</strong>' +
                '<div style=\"margin-top:6px;\">协议：' + escapeHtml(result.protocol || '-') + '</div>' +
                '<div>提供商：' + escapeHtml(result.provider_name || '-') + '</div>' +
                '<div>标准地址：' + escapeHtml(result.normalized_base_url || '-') + '</div>' +
                '<div>模型数量：' + escapeHtml(String((result.model_ids || []).length)) + ' 个</div>' +
                currentModelText +
                recommendationHtml +
                candidateHtml +
                noteHtml
            )
            .show();
    };

    var renderDiscoveryError = function ($form, message) {
        var $result = $form.find('#ai-discovery-result');
        if (!$result.length) {
            return;
        }

        $result
            .removeClass('alert-success alert-info')
            .addClass('alert-danger')
            .html('<strong>检测失败</strong><div style=\"margin-top:6px;line-height:1.8;\">' + escapeHtml(message || '模型连接失败') + '</div>')
            .show();
    };

    var discoverDebounceTimer = null;

    var triggerAutoDiscovery = function ($form) {
        if (discoverDebounceTimer) {
            window.clearTimeout(discoverDebounceTimer);
        }

        discoverDebounceTimer = window.setTimeout(function () {
            var baseUrl = $.trim($form.find('#c-base_url').val());
            var apiKey = $.trim($form.find('#c-api_key').val());
            if (!baseUrl || !apiKey) {
                return;
            }
            discoverModels($form, true);
        }, 700);
    };

    var discoverModels = function ($form, silent) {
        var baseUrl = $.trim($form.find('#c-base_url').val());
        var apiKey = $.trim($form.find('#c-api_key').val());
        var currentModel = $.trim($form.find('#c-model').val());
        var $button = $form.find('[data-action=\"discover-models\"]');

        if (!baseUrl || !apiKey) {
            if (!silent) {
                Toastr.warning('请先填写 Base URL 和 API Key');
            }
            return;
        }

        $button.prop('disabled', true).text('检测中...');

        $.ajax({
            url: Config.discoverUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                base_url: baseUrl,
                api_key: apiKey,
                model: currentModel,
                skip_ssl_verify: $form.find('#c-skip_ssl_verify').is(':checked') ? 1 : 0
            }
        }).done(function (ret) {
            if (ret.code !== 1) {
                renderDiscoveryError($form, ret.msg || '模型连接失败');
                if (!silent) {
                    Toastr.error(ret.msg || '模型连接失败');
                }
                return;
            }

            var data = ret.data || {};
            renderDiscoveryResult($form, data);
            renderModelOptions($form, data.model_ids || [], currentModel);

            if (!$.trim($form.find('#c-provider_name').val()) && data.provider_name) {
                $form.find('#c-provider_name').val(data.provider_name);
            }
            if (data.normalized_base_url) {
                $form.find('#c-base_url').val(data.normalized_base_url);
                updateBaseUrlHint($form, data.normalized_base_url);
            }
            if (!currentModel && data.model_ids && data.model_ids.length) {
                $form.find('#c-model').val(data.model_ids[0]);
            }
            if (!silent) {
                Toastr.success('模型检测完成');
            }
        }).fail(function () {
            renderDiscoveryError($form, '模型检测失败，请检查 Base URL/API Key，或确认服务端代理地址可访问。');
            if (!silent) {
                Toastr.error('模型检测失败');
            }
        }).always(function () {
            $button.prop('disabled', false).text('识别协议并加载模型');
        });
    };

    var bindWorkspaceMetaForm = function () {
        var $form = $('form[role=form]');
        if (!$form.length) {
            return;
        }

        var $hidden = $form.find('#c-workspace_json');
        if ($hidden.length) {
            var meta = parseWorkspaceMeta($hidden.val());
            $form.find('#c-skip_ssl_verify').prop('checked', !!meta.skip_ssl_verify);
        }

        $form.on('change', '#c-skip_ssl_verify', function () {
            syncWorkspaceMeta($form);
        });

        $form.on('blur', '#c-base_url', function () {
            updateBaseUrlHint($form, $.trim($(this).val()));
            triggerAutoDiscovery($form);
        }).find('#c-base_url').trigger('blur');

        $form.on('click', '[data-provider-preset]', function () {
            var preset = providerPresets[$(this).data('provider-preset')];
            if (!preset) {
                return;
            }

            if (preset.provider_name) {
                $form.find('#c-provider_name').val(preset.provider_name);
            }
            $form.find('#c-base_url').val(preset.base_url).trigger('blur');
            if (!$.trim($form.find('#c-model').val())) {
                $form.find('#c-model').val(preset.model);
            }
            $form.find('#ai-model-hint').text(preset.model_hint);
            triggerAutoDiscovery($form);
        });

        $form.on('click', '[data-action=\"discover-models\"]', function () {
            discoverModels($form, false);
        });

        $form.on('change', '#c-detected-models', function () {
            var value = $.trim($(this).val());
            if (value) {
                $form.find('#c-model').val(value);
            }
        });

        $form.on('click', '[data-action=\"apply-recommended-model\"]', function () {
            var value = $.trim($(this).data('model'));
            if (!value) {
                return;
            }

            $form.find('#c-model').val(value);
            Toastr.success('推荐模型已应用到当前模型');
        });

        $form.on('blur', '#c-api_key', function () {
            triggerAutoDiscovery($form);
        });

        $form.on('input', '#c-base_url, #c-api_key', function () {
            triggerAutoDiscovery($form);
        });

        syncWorkspaceMeta($form);
    };

    var displayApiKey = function (value) {
        return value ? '<span class=\"label label-success\">已保存</span>' : '<span class=\"label label-warning\">未填写</span>';
    };

    var defaultFormatter = function (value, row) {
        return row.is_default
            ? '<span class=\"label label-success\">默认</span>'
            : '<span class=\"label label-default\">备用</span>';
    };

    var configuredFormatter = function (value, row) {
        return row.configured
            ? '<span class=\"label label-primary\">完整</span>'
            : '<span class=\"label label-warning\">待补</span>';
    };

    var sslFormatter = function (value, row) {
        return row.skip_ssl_verify
            ? '<span class=\"label label-info\">已跳过</span>'
            : '<span class=\"label label-default\">正常校验</span>';
    };

    var diagnosticFormatter = function (value) {
        if (!value || !value.message) {
            return '<span class=\"text-muted\">-</span>';
        }

        var labelClass = 'default';
        if (value.type === 'success') {
            labelClass = 'success';
        } else if (value.type === 'warning') {
            labelClass = 'warning';
        } else if (value.type === 'danger') {
            labelClass = 'danger';
        } else if (value.type === 'info') {
            labelClass = 'info';
        }

        return [
            '<div class=\"text-left\">',
            '<span class=\"label label-' + labelClass + '\">' + escapeHtml(value.title || '提示') + '</span>',
            '<div style=\"margin-top:6px;color:#666;line-height:1.7;\">' + escapeHtml(value.message || '') + '</div>',
            '</div>'
        ].join('');
    };

    var Controller = {
        index: function () {
            Table.api.init({
                extend: {
                    index_url: 'ai/setting/index' + location.search,
                    add_url: 'ai/setting/add',
                    edit_url: 'ai/setting/edit',
                    del_url: 'ai/setting/del',
                    multi_url: 'ai/setting/multi',
                    import_url: 'ai/setting/import',
                    table: 'ai_setting'
                }
            });

            var table = $('#table');
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                fixedColumns: true,
                fixedRightNumber: 1,
                columns: [[
                    {checkbox: true},
                    {field: 'provider_name', title: '供应商', operate: 'LIKE'},
                    {field: 'model', title: '模型', operate: 'LIKE'},
                    {field: 'configured', title: '配置状态', operate: false, formatter: configuredFormatter},
                    {field: 'workspace_json', title: '默认状态', operate: false, formatter: defaultFormatter},
                    {field: 'diagnostic', title: '可用提示', operate: false, formatter: diagnosticFormatter},
                    {field: 'updatetime', title: '更新时间', operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime},
                    {
                        field: 'operate',
                        title: __('Operate'),
                        table: table,
                        events: Table.api.events.operate,
                        buttons: [
                            {
                                name: 'setdefault',
                                text: '设默认',
                                title: '设为默认模型',
                                classname: 'btn btn-info btn-xs btn-ajax',
                                icon: 'fa fa-check-circle',
                                url: 'ai/setting/setdefault/ids/{ids}',
                                visible: function (row) {
                                    return !row.is_default;
                                },
                                confirm: '确认后 AI 工作台将默认使用该模型，是否继续？',
                                refresh: true
                            },
                            {
                                name: 'ping',
                                text: '测试',
                                title: '测试连接',
                                classname: 'btn btn-success btn-xs btn-ajax',
                                icon: 'fa fa-plug',
                                url: 'ai/setting/ping/ids/{ids}',
                                refresh: false
                            },
                            {
                                name: 'applyrecommended',
                                text: '用推荐',
                                title: '应用推荐模型',
                                classname: 'btn btn-warning btn-xs btn-ajax',
                                icon: 'fa fa-bolt',
                                url: 'ai/setting/applyrecommended/ids/{ids}',
                                visible: function (row) {
                                    return !!row.configured;
                                },
                                confirm: '确认后将当前配置应用为推荐模型，系统将尝试切换到更稳定可用模型。',
                                refresh: true
                            }
                        ],
                        formatter: Table.api.formatter.operate
                    }
                ]]
            });

            Table.api.bindevent(table);
        },
        add: function () {
            bindWorkspaceMetaForm();
            Controller.api.bindevent();
        },
        edit: function () {
            bindWorkspaceMetaForm();
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


