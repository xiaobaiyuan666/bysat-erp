define(['jquery', 'bootstrap', 'backend'], function ($, undefined, Backend) {
    var state = {
        overview: null,
        lastCheck: null,
        loading: false,
        checking: false,
        saving: false,
        updating: false
    };

    var $root = null;

    var escapeHtml = function (value) {
        return $('<div />').text(value || '').html();
    };

    var parseBootstrap = function () {
        var raw = $.trim($('#erp-upgrade-bootstrap').text());
        if (!raw) {
            return {};
        }

        try {
            var binary = window.atob(raw);
            var bytes = new Uint8Array(binary.length);
            for (var index = 0; index < binary.length; index++) {
                bytes[index] = binary.charCodeAt(index);
            }
            return JSON.parse(new TextDecoder('utf-8').decode(bytes));
        } catch (err) {
            return {};
        }
    };

    var request = function (options) {
        return $.ajax($.extend({dataType: 'json'}, options));
    };

    var formatTime = function (value) {
        return value ? value : '-';
    };

    var formatSize = function (value) {
        var size = parseInt(value || 0, 10);
        if (!size) {
            return '-';
        }
        if (size < 1024) {
            return size + ' B';
        }
        if (size < 1024 * 1024) {
            return (size / 1024).toFixed(1) + ' KB';
        }
        return (size / (1024 * 1024)).toFixed(1) + ' MB';
    };

    var updateActionState = function () {
        $root.find('[data-action="reload-overview"]').prop('disabled', state.loading);
        $root.find('[data-action="check-update"]').prop('disabled', state.loading || state.checking || state.updating);
        $root.find('[data-action="save-config"]').prop('disabled', state.loading || state.saving || state.updating);
        $root.find('[data-action="fill-default"]').prop('disabled', state.loading || state.saving || state.updating);
        $root.find('[data-action="start-update"]').prop('disabled', state.loading || state.checking || state.saving || state.updating);
    };

    var renderLocalCards = function (local, database) {
        var lastDbBackup = database && database.last_backup ? database.last_backup.name : '';
        var html = [
            '<div class="upgrade-item"><strong>当前版本</strong><div class="upgrade-code">' + escapeHtml(local.version || '未识别') + '</div></div>',
            '<div class="upgrade-item"><strong>当前引用</strong><div class="upgrade-code">' + escapeHtml(local.current_ref_short || '-') + '</div></div>',
            '<div class="upgrade-item"><strong>最近代码更新</strong><div>' + escapeHtml(formatTime(local.last_update_at)) + '</div></div>',
            '<div class="upgrade-item"><strong>最近文件备份</strong><div class="upgrade-code">' + escapeHtml(local.last_backup || '-') + '</div></div>',
            '<div class="upgrade-item"><strong>数据库迁移</strong><div>' + escapeHtml((database && database.applied_count ? database.applied_count : 0) + ' 条已执行') + '</div></div>',
            '<div class="upgrade-item"><strong>最近数据库备份</strong><div class="upgrade-code">' + escapeHtml(lastDbBackup || '-') + '</div></div>'
        ];

        $root.find('[data-role="local-cards"]').html(html.join(''));
    };

    var renderConfig = function (config) {
        var $form = $('#upgrade-config-form');
        $form.find('[name="source_mode"]').val(config.source_mode || 'branch');
        $form.find('[name="owner"]').val(config.owner || '');
        $form.find('[name="repo"]').val(config.repo || '');
        $form.find('[name="branch"]').val(config.branch || '');
        $form.find('[name="release_tag"]').val(config.release_tag || 'latest');
        $form.find('[name="release_asset_pattern"]').val(config.release_asset_pattern || '');
        $form.find('[name="package_subdir"]').val(config.package_subdir || 'fastadmin');
        $form.find('[name="skip_ssl_verify"]').prop('checked', !!config.skip_ssl_verify);
        $form.find('[name="github_token"]').val('');
        $root.find('[data-role="token-hint"]').text(config.token_hint || '');
    };

    var renderWarnings = function (warnings, database) {
        var items = [].concat(warnings || []);
        if (database && database.error) {
            items.push('数据库检查失败：' + database.error);
        }

        if (!items.length) {
            $root.find('[data-role="warning-list"]').html('<li class="upgrade-empty">当前没有额外提示。</li>');
            return;
        }

        var html = [];
        $.each(items, function (_, item) {
            html.push('<li>' + escapeHtml(item) + '</li>');
        });
        $root.find('[data-role="warning-list"]').html(html.join(''));
    };

    var renderEnvironment = function (items) {
        var html = [];
        $.each(items || [], function (_, item) {
            html.push(
                '<li><strong class="' + (item.ok ? 'upgrade-status-ok' : 'upgrade-status-bad') + '">' +
                (item.ok ? '通过' : '失败') +
                '</strong> ' + escapeHtml(item.label || '') + '</li>'
            );
        });
        $root.find('[data-role="environment-list"]').html(html.join('') || '<li class="upgrade-empty">暂无环境数据。</li>');
    };

    var renderBackups = function (items) {
        if (!items || !items.length) {
            $root.find('[data-role="backup-list"]').html('<li class="upgrade-empty">暂时还没有代码备份包。</li>');
            return;
        }

        var html = [];
        $.each(items, function (_, item) {
            html.push(
                '<li><strong>' + escapeHtml(item.name || '-') + '</strong>' +
                '<div>' + escapeHtml(formatSize(item.size)) + ' / ' + escapeHtml(formatTime(item.time)) + '</div>' +
                '<div class="upgrade-code">' + escapeHtml(item.path || '') + '</div></li>'
            );
        });
        $root.find('[data-role="backup-list"]').html(html.join(''));
    };

    var renderHistory = function (items) {
        if (!items || !items.length) {
            $root.find('[data-role="history-list"]').html('<li class="upgrade-empty">暂时还没有更新记录。</li>');
            return;
        }

        var html = [];
        $.each(items, function (index, item) {
            var extra = [];
            if (item.backup) {
                extra.push('代码备份：' + item.backup);
            }
            if (item.database_backup) {
                extra.push('数据库备份：' + item.database_backup);
            }
            if (item.migrations && item.migrations.length) {
                extra.push('数据库迁移：' + item.migrations.length + ' 条');
            }
            if (item.cleanup) {
                extra.push('清理旧文件：' + item.cleanup + ' 个');
            }

            var canRollback = item.type === 'update' && item.status === '成功' && item.backup;
            var actionHtml = canRollback
                ? '<div class="upgrade-actions" style="margin-top:10px;"><button type="button" class="btn btn-xs btn-warning" data-action="rollback-history" data-history-index="' + index + '"><i class="fa fa-history"></i> 回滚到这里</button></div>'
                : '';

            html.push(
                '<li><strong>' + escapeHtml(item.status || '-') + '</strong>' +
                '<div>' + escapeHtml(item.label || '-') + ' / ' + escapeHtml(item.ref || '-') + '</div>' +
                '<div>' + escapeHtml(formatTime(item.time)) + '</div>' +
                (extra.length ? '<div>' + escapeHtml(extra.join(' / ')) + '</div>' : '') +
                (item.message ? '<div>' + escapeHtml(item.message) + '</div>' : '') +
                actionHtml +
                '</li>'
            );
        });
        $root.find('[data-role="history-list"]').html(html.join(''));
    };

    var renderRemote = function (result) {
        if (!result || !result.remote) {
            $root.find('[data-role="remote-cards"]').html('<div class="upgrade-item"><strong>远端版本</strong><div class="upgrade-empty">请先点击“检查更新”。</div></div>');
            $root.find('[data-role="remote-message"]').empty();
            return;
        }

        var remote = result.remote || {};
        var cards = [
            '<div class="upgrade-item"><strong>更新来源</strong><div>' + escapeHtml(remote.label || '-') + '</div></div>',
            '<div class="upgrade-item"><strong>远端引用</strong><div class="upgrade-code">' + escapeHtml(remote.ref_short || remote.ref || '-') + '</div></div>',
            '<div class="upgrade-item"><strong>发布时间</strong><div>' + escapeHtml(formatTime(remote.published_at)) + '</div></div>',
            '<div class="upgrade-item"><strong>状态</strong><div class="' + (result.update_available ? 'upgrade-status-ok' : '') + '">' + escapeHtml(result.update_available ? '发现新版本' : '当前已是最新') + '</div></div>'
        ];

        $root.find('[data-role="remote-cards"]').html(cards.join(''));
        $root.find('[data-role="remote-message"]').html(
            '<div class="upgrade-item"><strong>远端说明</strong><div class="upgrade-remote-message">' + escapeHtml(remote.message || '远端暂未提供更新说明。') + '</div></div>'
        );
    };

    var renderOverview = function (overview) {
        state.overview = overview || {};
        renderLocalCards(state.overview.local || {}, state.overview.database || {});
        renderConfig(state.overview.config || {});
        renderWarnings(state.overview.warnings || [], state.overview.database || {});
        renderEnvironment(state.overview.environment || []);
        renderBackups(state.overview.backups || []);
        renderHistory(state.overview.history || []);
        if (!state.lastCheck) {
            renderRemote(null);
        }
    };

    var collectConfigForm = function () {
        var values = {};
        $.each($('#upgrade-config-form').serializeArray(), function (_, item) {
            values[item.name] = $.trim(item.value || '');
        });
        values.skip_ssl_verify = $('#upgrade-config-form').find('[name="skip_ssl_verify"]').is(':checked') ? 1 : 0;
        return values;
    };

    var fillDefaultRepo = function () {
        var repo = (state.overview && state.overview.local && state.overview.local.default_repo) || {};
        $('#upgrade-config-form').find('[name="owner"]').val(repo.owner || '');
        $('#upgrade-config-form').find('[name="repo"]').val(repo.repo || '');
        $('#upgrade-config-form').find('[name="branch"]').val(repo.branch || 'master');
        $('#upgrade-config-form').find('[name="package_subdir"]').val('fastadmin');
        $('#upgrade-config-form').find('[name="source_mode"]').val('branch');
        $('#upgrade-config-form').find('[name="skip_ssl_verify"]').prop('checked', false);
    };

    var reloadOverview = function () {
        state.loading = true;
        updateActionState();

        request({
            url: Config.upgradeOverviewUrl,
            type: 'GET'
        }).done(function (ret) {
            if (ret.code !== 1) {
                Toastr.error(ret.msg || '加载更新中心失败');
                return;
            }
            renderOverview(ret.data || {});
        }).fail(function () {
            Toastr.error('加载更新中心失败');
        }).always(function () {
            state.loading = false;
            updateActionState();
        });
    };

    var saveConfig = function () {
        state.saving = true;
        updateActionState();

        request({
            url: Config.upgradeSaveConfigUrl,
            type: 'POST',
            data: collectConfigForm()
        }).done(function (ret) {
            if (ret.code !== 1) {
                Toastr.error(ret.msg || '保存更新源失败');
                return;
            }

            if (state.overview) {
                state.overview.config = ret.data ? ret.data.config || {} : {};
                state.overview.warnings = ret.data ? ret.data.warnings || [] : [];
                renderOverview(state.overview);
            }
            Toastr.success(ret.msg || '更新源已保存');
        }).fail(function () {
            Toastr.error('保存更新源失败');
        }).always(function () {
            state.saving = false;
            updateActionState();
        });
    };

    var checkUpdate = function () {
        state.checking = true;
        updateActionState();

        request({
            url: Config.upgradeCheckUrl,
            type: 'POST'
        }).done(function (ret) {
            if (ret.code !== 1) {
                Toastr.error(ret.msg || '检查更新失败');
                return;
            }

            state.lastCheck = ret.data || null;
            renderRemote(state.lastCheck);
            if (state.lastCheck && state.lastCheck.warnings) {
                renderWarnings(state.lastCheck.warnings, state.overview ? state.overview.database : {});
            }
            Toastr.success(ret.msg || '更新检查完成');
        }).fail(function () {
            Toastr.error('检查更新失败');
        }).always(function () {
            state.checking = false;
            updateActionState();
        });
    };

    var startUpdate = function () {
        Layer.confirm('确定现在执行在线更新吗？系统会先创建代码备份；如果本次版本包含数据库迁移，还会先导出数据库 SQL 备份。', {
            icon: 3,
            title: '确认更新'
        }, function (index) {
            Layer.close(index);

            state.updating = true;
            updateActionState();

            request({
                url: Config.upgradeStartUrl,
                type: 'POST'
            }).done(function (ret) {
                if (ret.code !== 1) {
                    Toastr.error(ret.msg || '在线更新失败');
                    return;
                }

                var data = ret.data || {};
                var migrationResult = data.migration_result || {};
                var cleanupResult = data.cleanup_result || {};
                var appliedCount = migrationResult.applied_ids ? migrationResult.applied_ids.length : 0;
                var skippedCount = migrationResult.skipped_ids ? migrationResult.skipped_ids.length : 0;
                var databaseBackup = data.database_backup && data.database_backup.created
                    ? (data.database_backup.name || '-')
                    : '本次未创建';

                var message = [
                    '更新已完成。',
                    '代码备份：' + (data.backup ? data.backup.name || '-' : '-'),
                    '数据库备份：' + databaseBackup,
                    '数据库迁移：执行 ' + appliedCount + ' 条，跳过 ' + skippedCount + ' 条',
                    '清理旧文件：' + (cleanupResult.removed_files || 0) + ' 个',
                    '后台入口：' + (data.admin_url || './')
                ].join('\n');

                state.lastCheck = null;
                Layer.alert(message, {title: '更新完成'});
                reloadOverview();
            }).fail(function () {
                Toastr.error('在线更新失败');
            }).always(function () {
                state.updating = false;
                updateActionState();
            });
        });
    };

    var rollbackHistory = function (historyIndex) {
        Layer.confirm('确定回滚到这次更新前的版本吗？系统会先再做一次当前代码和数据库的安全备份，然后开始回滚。', {
            icon: 3,
            title: '确认回滚'
        }, function (index) {
            Layer.close(index);

            state.updating = true;
            updateActionState();

            request({
                url: Config.upgradeRollbackUrl,
                type: 'POST',
                data: {
                    history_index: historyIndex
                }
            }).done(function (ret) {
                if (ret.code !== 1) {
                    Toastr.error(ret.msg || '执行回滚失败');
                    return;
                }

                var data = ret.data || {};
                var message = [
                    '回滚已完成。',
                    '安全代码备份：' + (data.backup ? data.backup.name || '-' : '-'),
                    '安全数据库备份：' + (data.database_backup ? data.database_backup.name || '-' : '-'),
                    '后台入口：' + (data.admin_url || './')
                ].join('\n');

                state.lastCheck = null;
                Layer.alert(message, {title: '回滚完成'});
                reloadOverview();
            }).fail(function () {
                Toastr.error('执行回滚失败');
            }).always(function () {
                state.updating = false;
                updateActionState();
            });
        });
    };

    var bindEvents = function () {
        $root.on('click', '[data-action="reload-overview"]', reloadOverview);
        $root.on('click', '[data-action="save-config"]', saveConfig);
        $root.on('click', '[data-action="check-update"]', checkUpdate);
        $root.on('click', '[data-action="start-update"]', startUpdate);
        $root.on('click', '[data-action="fill-default"]', fillDefaultRepo);
        $root.on('click', '[data-action="rollback-history"]', function () {
            var historyIndex = parseInt($(this).data('history-index'), 10) || 0;
            rollbackHistory(historyIndex);
        });
    };

    var Controller = {
        index: function () {
            $root = $('#erp-upgrade-page');
            renderOverview(parseBootstrap());
            bindEvents();
            updateActionState();
        }
    };

    return Controller;
});
