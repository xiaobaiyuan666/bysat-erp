define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var projectTypeMeta = {
        app: {
            subjectLabel: 'APP名称',
            subjectPlaceholder: '例如：猿创 CRM',
            versionLabel: '当前版本',
            versionPlaceholder: '例如：v2.3.0',
            help: '适合常规 APP 版本运营、增长、留存和发版协同。'
        },
        miniprogram: {
            subjectLabel: '小程序名称',
            subjectPlaceholder: '例如：客服服务助手',
            versionLabel: '当前版本',
            versionPlaceholder: '例如：1.8.2',
            help: '适合微信、支付宝等小程序项目，重点看渠道和转化。'
        },
        website: {
            subjectLabel: '站点名称',
            subjectPlaceholder: '例如：企业官网',
            versionLabel: '上线版本',
            versionPlaceholder: '例如：2026-Q1',
            help: '适合官网、落地页、专题站和内容站的运营迭代。'
        },
        campaign: {
            subjectLabel: '活动名称',
            subjectPlaceholder: '例如：春季拉新活动',
            versionLabel: '投放批次',
            versionPlaceholder: '例如：第 1 波',
            help: '适合活动投放、渠道拉新、促销转化等短周期项目。'
        },
        private_domain: {
            subjectLabel: '运营阵地',
            subjectPlaceholder: '例如：企业微信私域',
            versionLabel: '阶段标记',
            versionPlaceholder: '例如：增长期 S2',
            help: '适合私域社群、用户运营、客服承接和复购场景。'
        },
        other: {
            subjectLabel: '项目主体',
            subjectPlaceholder: '例如：新媒体矩阵',
            versionLabel: '版本/阶段号',
            versionPlaceholder: '例如：阶段 01',
            help: '用于其他互联网运营项目，后续可继续细分。'
        }
    };

    function syncProjectTypeForm() {
        var type = $('#c-project_type').val() || 'app';
        var meta = projectTypeMeta[type] || projectTypeMeta.other;
        $('[data-label="project-subject"]').text(meta.subjectLabel + ':');
        $('[data-label="project-version"]').text(meta.versionLabel + ':');
        $('#c-app_name').attr('placeholder', meta.subjectPlaceholder);
        $('#c-app_version').attr('placeholder', meta.versionPlaceholder);
        $('#project-type-help').text(meta.help);
    }

    var Controller = {
        index: function () {
            Table.api.init({
                extend: {
                    index_url: 'app/project/index' + location.search,
                    add_url: 'app/project/add',
                    edit_url: 'app/project/edit',
                    del_url: 'app/project/del',
                    multi_url: 'app/project/multi',
                    import_url: 'app/project/import',
                    table: 'app_project'
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
                    {
                        field: 'project_type',
                        title: '项目类型',
                        searchList: {
                            app: 'APP',
                            miniprogram: '小程序',
                            website: '官网/网站',
                            campaign: '活动投放',
                            private_domain: '私域运营',
                            other: '其他'
                        },
                        formatter: function (value, row) {
                            return row.project_type_text || value || 'APP';
                        }
                    },
                    {field: 'app_name', title: '项目主体', operate: 'LIKE'},
                    {field: 'name', title: '运营项目', operate: 'LIKE'},
                    {field: 'manager', title: '负责人', operate: 'LIKE'},
                    {
                        field: 'lifecycle_stage',
                        title: '生命周期',
                        searchList: {
                            idea: '想法期',
                            validation: '验证期',
                            launch: '上线期',
                            growth: '增长期',
                            retention: '留存期',
                            mature: '成熟期',
                            sunset: '退场期'
                        },
                        formatter: function (value, row) {
                            return row.lifecycle_stage_text || value;
                        }
                    },
                    {
                        field: 'status',
                        title: '项目状态',
                        searchList: {
                            planning: '筹备中',
                            running: '进行中',
                            paused: '已暂停',
                            completed: '已完成',
                            archived: '已归档'
                        },
                        formatter: Table.api.formatter.status
                    },
                    {
                        field: 'priority',
                        title: '优先级',
                        searchList: {
                            low: '低',
                            medium: '中',
                            high: '高',
                            urgent: '紧急'
                        },
                        formatter: function (value, row) {
                            return row.priority_text || value;
                        }
                    },
                    {field: 'app_version', title: '版本/阶段号', operate: 'LIKE'},
                    {field: 'end_date', title: '结束日期', operate: 'RANGE', addclass: 'datetimerange', autocomplete: false},
                    {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                ]]
            });

            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindProjectTypeForm();
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindProjectTypeForm();
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($('form[role=form]'));
            },
            bindProjectTypeForm: function () {
                $('#c-project_type').on('change', syncProjectTypeForm);
                syncProjectTypeForm();
            }
        }
    };

    return Controller;
});
