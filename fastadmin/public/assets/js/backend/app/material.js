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
                fixedColumns: true,
                fixedRightNumber: 1,
                columns: [[
                    {checkbox: true},
                    {field: 'title', title: __('Title'), operate: 'LIKE'},
                    {field: 'category', title: __('Category'), searchList: {'manual': __('Category manual'), 'faq': __('Category faq'), 'training': __('Category training'), 'script': __('Category script'), 'report': __('Category report'), 'other': __('Category other')}, formatter: Table.api.formatter.normal},
                    {field: 'owner', title: __('Owner'), operate: 'LIKE'},
                    {field: 'version_tag', title: __('Version_tag'), operate: 'LIKE'},
                    {field: 'applicable_versions', title: __('Applicable_versions'), operate: 'LIKE'},
                    {field: 'archive_status', title: __('Archive_status'), searchList: {'active': __('Archive_status active'), 'archived': __('Archive_status archived')}, formatter: Table.api.formatter.status},
                    {field: 'expires_on', title: __('Expires_on'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false},
                    {field: 'updated_on', title: __('Updated_on'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false},
                    {
                        field: 'operate',
                        title: __('Operate'),
                        table: table,
                        events: Table.api.events.operate,
                        buttons: [
                            {
                                name: 'app_project',
                                text: '所属APP',
                                title: '查看所属APP项目',
                                icon: 'fa fa-mobile',
                                classname: 'btn btn-primary btn-xs btn-dialog',
                                url: appProjectUrl,
                                visible: function (row) {
                                    return parseInt(row.app_project_id, 10) > 0;
                                },
                                extend: 'data-area=["82%","88%"]'
                            },
                            {
                                name: 'view_material',
                                text: '查看文件',
                                title: '打开资料文件',
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
