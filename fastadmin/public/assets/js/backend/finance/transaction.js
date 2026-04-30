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
        return 'finance/transaction/printview?ids=' + row.id;
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
                    index_url: 'finance/transaction/index' + location.search,
                    add_url: 'finance/transaction/add',
                    edit_url: 'finance/transaction/edit',
                    del_url: 'finance/transaction/del',
                    multi_url: 'finance/transaction/multi',
                    import_url: 'finance/transaction/import',
                    table: 'finance_transaction'
                }
            });

            var table = $("#table");

            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'transaction_date',
                fixedColumns: true,
                fixedRightNumber: 1,
                columns: [[
                    {checkbox: true},
                    {field: 'transaction_date', title: __('Transaction_date'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false},
                    {field: 'type', title: __('Type'), searchList: {income: __('Type income'), expense: __('Type expense')}, formatter: Table.api.formatter.normal},
                    {field: 'category', title: __('Category'), operate: 'LIKE'},
                    {field: 'counterparty', title: __('Counterparty'), operate: 'LIKE'},
                    {field: 'amount', title: __('Amount'), operate: 'BETWEEN'},
                    {
                        field: 'payment_method',
                        title: __('Payment_method'),
                        searchList: {
                            bank: __('Payment_method bank'),
                            wechat: __('Payment_method wechat'),
                            alipay: __('Payment_method alipay'),
                            cash: __('Payment_method cash'),
                            other: __('Payment_method other')
                        },
                        formatter: Table.api.formatter.normal
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
