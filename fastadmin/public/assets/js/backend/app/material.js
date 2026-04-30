define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var viewMaterialUrl = function (row) {
        return row.download_url || row.file_path || '';
    };

    var appProjectUrl = function (row) {
        return 'app/project/edit/ids/' + row.app_project_id;
    };

    var replacementMaterialUrl = function (row) {
        return 'app/material/edit/ids/' + row.replacement_material_id;
    };

    var bindFileField = function () {
        var $form = $('form[role=form]');
        var syncFilePath = function () {
            var url = $('#c-download_url').val() || '';
            $('#c-file_path').val(url);
        };

        Form.events.faupload($form);
        Form.events.faselect($form);
        $('#c-download_url').on('change keyup', syncFilePath);
        syncFilePath();
    };

    var Controller = {
        index: function () {
            Table.api.init({
                extend: {
                    index_url: 'app/material/index' + location.search,
                    add_url: 'app/material/add',
                    edit_url: 'app/material/edit',
                    del_url: 'app/material/del',
                    multi_url: 'app/material/multi',
                    import_url: 'app/material/import',
                    table: 'app_material'
                }
            });

            var table = $('#table');

            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'updated_on',
                sortOrder: 'desc',
                fixedColumns: true,
                fixedRightNumber: 1,
                columns: [[
                    {checkbox: true},
                    {field: 'title', title: '资料名称', operate: 'LIKE'},
                    {
                        field: 'category',
                        title: '资料分类',
                        searchList: {
                            manual: '操作手册',
                            faq: 'FAQ',
                            training: '培训资料',
                            script: '脚本',
                            report: '报告',
                            other: '其他'
                        },
                        formatter: Table.api.formatter.normal
                    },
                    {field: 'owner', title: '负责人', operate: 'LIKE'},
                    {field: 'applicable_versions', title: '适用范围/版本', operate: 'LIKE'},
                    {field: 'version_tag', title: '版本标签', operate: 'LIKE'},
                    {
                        field: 'archive_status',
                        title: '状态',
                        searchList: {active: '在用', archived: '已归档'},
                        formatter: Table.api.formatter.status
                    },
                    {field: 'updated_on', title: '更新日期', operate: 'RANGE', addclass: 'datetimerange', autocomplete: false},
                    {
                        field: 'operate',
                        title: __('Operate'),
                        table: table,
                        events: Table.api.events.operate,
                        buttons: [
                            {
                                name: 'app_project',
                                text: '所属项目',
                                title: '查看所属项目',
                                icon: 'fa fa-sitemap',
                                classname: 'btn btn-primary btn-xs btn-dialog',
                                url: appProjectUrl,
                                visible: function (row) {
                                    return parseInt(row.app_project_id, 10) > 0;
                                },
                                extend: 'data-area=["82%","88%"]'
                            },
                            {
                                name: 'view_material',
                                text: '打开附件',
                                title: '打开资料附件或下载地址',
                                icon: 'fa fa-file-text-o',
                                classname: 'btn btn-info btn-xs btn-addtabs',
                                url: viewMaterialUrl,
                                visible: function (row) {
                                    return !!viewMaterialUrl(row);
                                }
                            },
                            {
                                name: 'replacement_material',
                                text: '替代资料',
                                title: '查看替代资料',
                                icon: 'fa fa-files-o',
                                classname: 'btn btn-success btn-xs btn-dialog',
                                url: replacementMaterialUrl,
                                visible: function (row) {
                                    return parseInt(row.replacement_material_id, 10) > 0;
                                },
                                extend: 'data-area=["82%","88%"]'
                            }
                        ],
                        formatter: Table.api.formatter.operate
                    }
                ]]
            });

            Table.api.bindevent(table);
        },
        add: function () {
            bindFileField();
            Controller.api.bindevent();
        },
        edit: function () {
            bindFileField();
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
