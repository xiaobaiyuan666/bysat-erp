define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

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
                    {field: 'app_name', title: 'APP 名称', operate: 'LIKE'},
                    {field: 'app_version', title: '当前版本', operate: 'LIKE'},
                    {field: 'name', title: '运营项目', operate: 'LIKE'},
                    {
                        field: 'lifecycle_stage',
                        title: '生命周期',
                        searchList: {
                            idea: '构思',
                            validation: '验证',
                            launch: '上线',
                            growth: '增长',
                            retention: '留存',
                            mature: '成熟',
                            sunset: '下线'
                        },
                        formatter: Table.api.formatter.normal
                    },
                    {field: 'business_line', title: '业务线', operate: 'LIKE'},
                    {field: 'manager', title: '运营负责人', operate: 'LIKE'},
                    {field: 'core_metric', title: '核心指标', operate: 'LIKE'},
                    {
                        field: 'status',
                        title: '项目状态',
                        searchList: {
                            planning: '规划中',
                            running: '执行中',
                            paused: '暂停',
                            completed: '已完成',
                            archived: '已归档'
                        },
                        formatter: Table.api.formatter.status
                    },
                    {
                        field: 'priority',
                        title: '优先级',
                        searchList: {low: '低', medium: '中', high: '高', urgent: '紧急'},
                        formatter: Table.api.formatter.normal
                    },
                    {field: 'end_date', title: '结束日期', operate: 'RANGE', addclass: 'datetimerange', autocomplete: false},
                    {field: 'budget', title: '预算', operate: 'BETWEEN'},
                    {field: 'actual_cost', title: '实际成本', operate: 'BETWEEN'},
                    {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                ]]
            });

            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
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
