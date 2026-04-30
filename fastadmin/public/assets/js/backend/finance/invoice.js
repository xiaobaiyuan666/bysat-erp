define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var parseAttachmentIds = function (value) {
        if (!value) {
            return [];
        }
        if ($.isArray(value)) {
            return value;
        }
        try {
            var parsed = JSON.parse(value);
            return $.isArray(parsed) ? parsed : [];
        } catch (e) {
            return [];
        }
    };

    var attachmentUrl = function (row) {
        var ids = parseAttachmentIds(row.attachment_ids_json);
        return 'general/attachment/index?ids=' + ids.join(',');
    };

    var printUrl = function (row) {
        return 'finance/invoice/printview?ids=' + row.id;
    };

    var bindAttachmentField = function () {
        var $input = $('#c-attachment_ids_json');
        var $summary = $('#c-attachment_summary');
        var $view = $('#btn-view-attachments');
        var openApi = window.parent && window.parent.Fast ? window.parent.Fast.api : Fast.api;

        var refresh = function () {
            var ids = parseAttachmentIds($input.val());
            $summary.val(ids.length > 0 ? ('已关联 ' + ids.length + ' 个附件') : '未选择附件');
            $view.prop('disabled', ids.length === 0);
        };

        $('#btn-choose-attachments').on('click', function () {
            openApi.open('general/attachment/select?multiple=true', '选择附件', {
                area: ['90%', '88%'],
                callback: function (data) {
                    var ids = data && data.ids ? data.ids : [];
                    $input.val(JSON.stringify(ids));
                    refresh();
                }
            });
            return false;
        });

        $view.on('click', function () {
            var ids = parseAttachmentIds($input.val());
            if (!ids.length) {
                return false;
            }
            openApi.open('general/attachment/index?ids=' + ids.join(','), '查看附件', {
                area: ['90%', '88%']
            });
            return false;
        });

        refresh();
    };

    var Controller = {
        index: function () {
            Table.api.init({
                extend: {
                    index_url: 'finance/invoice/index' + location.search,
                    add_url: 'finance/invoice/add',
                    edit_url: 'finance/invoice/edit',
                    del_url: 'finance/invoice/del',
                    multi_url: 'finance/invoice/multi',
                    import_url: 'finance/invoice/import',
                    table: 'finance_invoice'
                }
            });

            var table = $("#table");

            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'due_date',
                fixedColumns: true,
                fixedRightNumber: 1,
                columns: [[
                    {checkbox: true},
                    {field: 'kind', title: __('Kind'), searchList: {receivable: __('Kind receivable'), payable: __('Kind payable')}, formatter: Table.api.formatter.normal},
                    {field: 'title', title: __('Title'), operate: 'LIKE'},
                    {field: 'counterparty', title: __('Counterparty'), operate: 'LIKE'},
                    {field: 'amount', title: __('Amount'), operate: 'BETWEEN'},
                    {field: 'due_date', title: __('Due_date'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false},
                    {
                        field: 'status',
                        title: __('Status'),
                        searchList: {
                            pending: __('Status pending'),
                            partial: __('Status partial'),
                            paid: __('Status paid'),
                            overdue: __('Status overdue'),
                            cancelled: __('Status cancelled')
                        },
                        formatter: Table.api.formatter.status
                    },
                    {field: 'notes', title: __('Notes'), operate: 'LIKE'},
                    {
                        field: 'operate',
                        title: __('Operate'),
                        table: table,
                        events: Table.api.events.operate,
                        buttons: [
                            {
                                name: 'print',
                                text: '打印预览',
                                title: '打印预览',
                                icon: 'fa fa-print',
                                classname: 'btn btn-success btn-xs btn-dialog',
                                url: printUrl,
                                extend: 'data-area=["88%","92%"]'
                            },
                            {
                                name: 'attachments',
                                text: '查看附件',
                                title: '查看附件',
                                icon: 'fa fa-paperclip',
                                classname: 'btn btn-info btn-xs btn-dialog',
                                url: attachmentUrl,
                                visible: function (row) {
                                    return parseAttachmentIds(row.attachment_ids_json).length > 0;
                                },
                                extend: 'data-area=["90%","88%"]'
                            }
                        ],
                        formatter: Table.api.formatter.operate
                    }
                ]]
            });

            Table.api.bindevent(table);
        },
        add: function () {
            bindAttachmentField();
            Controller.api.bindevent();
        },
        edit: function () {
            bindAttachmentField();
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
