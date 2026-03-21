define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var expenseIndexUrl = function (row) {
        return 'business/expense_request/index?supplier_id=' + row.id;
    };

    var expenseAddUrl = function (row) {
        return 'business/expense_request/add?supplier_id=' + row.id;
    };

    var Controller = {
        index: function () {
            Table.api.init({
                extend: {
                    index_url: 'business/supplier/index' + location.search,
                    add_url: 'business/supplier/add',
                    edit_url: 'business/supplier/edit',
                    del_url: 'business/supplier/del',
                    multi_url: 'business/supplier/multi',
                    table: 'business_supplier'
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
                    {field: 'supplier_name', title: '供应商名称', operate: 'LIKE'},
                    {field: 'short_name', title: '简称', operate: 'LIKE'},
                    {
                        field: 'category',
                        title: '供应商类型',
                        searchList: {
                            software: '软件服务',
                            cloud: '云资源',
                            service: '专业服务',
                            marketing: '投放渠道',
                            outsourcing: '外包合作',
                            hardware: '硬件设备',
                            other: '其他'
                        },
                        formatter: Table.api.formatter.normal
                    },
                    {
                        field: 'level',
                        title: '供应商等级',
                        searchList: {
                            strategic: '战略供应商',
                            core: '核心供应商',
                            normal: '常规供应商',
                            backup: '备选供应商'
                        },
                        formatter: Table.api.formatter.normal
                    },
                    {
                        field: 'status',
                        title: '合作状态',
                        searchList: {
                            active: '合作中',
                            paused: '暂停合作',
                            blacklist: '黑名单'
                        },
                        formatter: Table.api.formatter.status
                    },
                    {
                        field: 'settlement_cycle',
                        title: '结算周期',
                        searchList: {
                            advance: '预付款',
                            monthly: '月结',
                            quarterly: '季结',
                            on_delivery: '交付后结算',
                            other: '其他'
                        },
                        formatter: Table.api.formatter.normal
                    },
                    {field: 'contact_name', title: '联系人', operate: 'LIKE'},
                    {field: 'contact_phone', title: '联系电话', operate: 'LIKE'},
                    {field: 'owner', title: '负责人', operate: 'LIKE'},
                    {field: 'city', title: '城市', operate: 'LIKE'},
                    {
                        field: 'operate',
                        title: __('Operate'),
                        table: table,
                        events: Table.api.events.operate,
                        buttons: [
                            {
                                name: 'expense_index',
                                text: '费用记录',
                                title: '查看费用记录',
                                icon: 'fa fa-list-alt',
                                classname: 'btn btn-info btn-xs btn-addtabs',
                                url: expenseIndexUrl
                            },
                            {
                                name: 'expense_add',
                                text: '新增费用',
                                title: '新增费用申请',
                                icon: 'fa fa-plus',
                                classname: 'btn btn-primary btn-xs btn-addtabs',
                                url: expenseAddUrl
                            }
                        ],
                        formatter: Table.api.formatter.operate
                    }
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
