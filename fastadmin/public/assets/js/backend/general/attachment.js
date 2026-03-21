define(['jquery', 'bootstrap', 'backend', 'form', 'table'], function ($, undefined, Backend, Form, Table) {

    var parseJson = function (value, fallback) {
        if (!value) {
            return fallback;
        }
        try {
            return JSON.parse(value);
        } catch (e) {
            return fallback;
        }
    };

    var buildPresetQuery = function () {
        var query = new URLSearchParams(window.location.search);
        var preset = {filter: {}, op: {}};

        if (query.get('ids')) {
            preset.filter.id = query.get('ids');
            preset.op.id = 'IN';
        }
        if (query.get('filename')) {
            preset.filter.filename = query.get('filename');
            preset.op.filename = 'LIKE';
        }
        if (query.get('category')) {
            preset.filter.category = query.get('category');
            preset.op.category = '=';
        }

        return preset;
    };

    var presetQuery = buildPresetQuery();

    var Controller = {
        index: function () {
            Table.api.init({
                extend: {
                    index_url: 'general/attachment/index',
                    add_url: 'general/attachment/add',
                    edit_url: 'general/attachment/edit',
                    del_url: 'general/attachment/del',
                    multi_url: 'general/attachment/multi',
                    table: 'attachment'
                }
            });

            var table = $("#table");

            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                sortName: 'id',
                queryParams: function (params) {
                    var filter = parseJson(params.filter, {});
                    var op = parseJson(params.op, {});
                    $.extend(filter, presetQuery.filter);
                    $.extend(op, presetQuery.op);
                    params.filter = JSON.stringify(filter);
                    params.op = JSON.stringify(op);
                    return params;
                },
                columns: [
                    [
                        {field: 'state', checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'category', title: __('Category'), operate: 'in', formatter: Table.api.formatter.label, searchList: Config.categoryList},
                        {field: 'admin_id', title: __('Admin_id'), visible: false, addClass: "selectpage", extend: "data-source='auth/admin/index' data-field='nickname'"},
                        {field: 'user_id', title: __('User_id'), visible: false, addClass: "selectpage", extend: "data-source='user/user/index' data-field='nickname'"},
                        {field: 'preview', title: __('Preview'), formatter: Controller.api.formatter.thumb, operate: false},
                        {field: 'url', title: __('Url'), formatter: Controller.api.formatter.url, visible: false},
                        {field: 'filename', title: __('Filename'), sortable: true, formatter: Controller.api.formatter.filename, operate: 'like'},
                        {
                            field: 'filesize', title: __('Filesize'), operate: 'BETWEEN', sortable: true, formatter: function (value) {
                                var size = parseFloat(value);
                                if (!size || size <= 0) {
                                    return '0 B';
                                }
                                var i = Math.floor(Math.log(size) / Math.log(1024));
                                return (size / Math.pow(1024, i)).toFixed(i < 2 ? 0 : 2) * 1 + ' ' + ['B', 'KB', 'MB', 'GB', 'TB'][i];
                            }
                        },
                        {field: 'imagewidth', title: __('Imagewidth'), sortable: true},
                        {field: 'imageheight', title: __('Imageheight'), sortable: true},
                        {field: 'imagetype', title: __('Imagetype'), sortable: true, formatter: Table.api.formatter.search, operate: 'like'},
                        {field: 'storage', title: __('Storage'), formatter: Table.api.formatter.search, operate: 'like'},
                        {field: 'mimetype', title: __('Mimetype'), formatter: Controller.api.formatter.mimetype},
                        {
                            field: 'createtime',
                            title: __('Createtime'),
                            formatter: Table.api.formatter.datetime,
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            sortable: true,
                            width: 150
                        },
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            $('.filter-type ul li a', table.closest(".panel-intro")).on('click', function () {
                $(this).closest("ul").find("li").removeClass("active");
                $(this).closest("li").addClass("active");
                var field = 'mimetype';
                var value = $(this).data("value") || '';
                var object = $("[name='" + field + "']", table.closest(".bootstrap-table").find(".commonsearch-table"));
                if (object.prop('tagName') === "SELECT") {
                    $("option[value='" + value + "']", object).prop("selected", true);
                } else {
                    object.val(value);
                }
                table.trigger("uncheckbox");
                table.bootstrapTable('refresh', {pageNumber: 1});
            });

            Table.api.bindevent(table);

            $(document).on('click', '.btn-classify', function () {
                var ids = Table.api.selectedids(table);
                Layer.open({
                    title: __('Classify'),
                    content: Template("typetpl", {}),
                    btn: [__('OK')],
                    yes: function (index, layero) {
                        var category = $("select[name='category']", layero).val();
                        Fast.api.ajax({
                            url: "general/attachment/classify",
                            type: "post",
                            data: {category: category, ids: ids.join(',')},
                        }, function () {
                            table.bootstrapTable('refresh', {});
                            Layer.close(index);
                        });
                    }
                });
            });

        },
        select: function () {
            Table.api.init({
                extend: {
                    index_url: 'general/attachment/select',
                }
            });
            var urlArr = [];
            var idArr = [];
            var multiple = Backend.api.query('multiple');
            multiple = multiple === 'true';

            var table = $("#table");

            table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function (e, row) {
                if (e.type === 'check' || e.type === 'uncheck') {
                    row = [row];
                } else {
                    urlArr = [];
                    idArr = [];
                }
                $.each(row, function (i, j) {
                    if (e.type.indexOf("uncheck") > -1) {
                        var index = urlArr.indexOf(j.url);
                        var idIndex = idArr.indexOf(String(j.id));
                        if (index > -1) {
                            urlArr.splice(index, 1);
                        }
                        if (idIndex > -1) {
                            idArr.splice(idIndex, 1);
                        }
                    } else {
                        if (urlArr.indexOf(j.url) === -1) {
                            urlArr.push(j.url);
                        }
                        if (idArr.indexOf(String(j.id)) === -1) {
                            idArr.push(String(j.id));
                        }
                    }
                });
            });

            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                sortName: 'id',
                showToggle: false,
                showExport: false,
                maintainSelected: true,
                fixedColumns: true,
                fixedRightNumber: 1,
                queryParams: function (params) {
                    var filter = parseJson(params.filter, {});
                    var op = parseJson(params.op, {});
                    $.extend(filter, presetQuery.filter);
                    $.extend(op, presetQuery.op);
                    params.filter = JSON.stringify(filter);
                    params.op = JSON.stringify(op);
                    return params;
                },
                columns: [
                    [
                        {field: 'state', checkbox: multiple, visible: multiple, operate: false},
                        {field: 'id', title: __('Id')},
                        {field: 'category', title: __('Category'), operate: 'in', formatter: Table.api.formatter.label, searchList: Config.categoryList},
                        {field: 'admin_id', title: __('Admin_id'), formatter: Table.api.formatter.search, visible: false},
                        {field: 'user_id', title: __('User_id'), formatter: Table.api.formatter.search, visible: false},
                        {field: 'url', title: __('Preview'), formatter: Controller.api.formatter.thumb, operate: false},
                        {field: 'filename', title: __('Filename'), sortable: true, formatter: Controller.api.formatter.filename, operate: 'like'},
                        {field: 'imagewidth', title: __('Imagewidth'), operate: false, sortable: true},
                        {field: 'imageheight', title: __('Imageheight'), operate: false, sortable: true},
                        {
                            field: 'mimetype', title: __('Mimetype'), sortable: true, operate: 'LIKE %...%',
                            process: function (value) {
                                return value.replace(/\*/g, '%');
                            },
                            formatter: Controller.api.formatter.mimetype
                        },
                        {field: 'createtime', title: __('Createtime'), width: 120, formatter: Table.api.formatter.datetime, datetimeFormat: 'YYYY-MM-DD', operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {
                            field: 'operate', title: __('Operate'), width: 85, events: {
                                'click .btn-chooseone': function (e, value, row) {
                                    Fast.api.close($.extend({multiple: multiple, ids: [String(row.id)]}, row));
                                },
                            }, formatter: function () {
                                return '<a href="javascript:;" class="btn btn-danger btn-chooseone btn-xs"><i class="fa fa-check"></i> ' + __('Choose') + '</a>';
                            }
                        }
                    ]
                ]
            });

            $('.filter-type ul li a', table.closest(".panel-intro")).on('click', function () {
                $(this).closest("ul").find("li").removeClass("active");
                $(this).closest("li").addClass("active");
                var field = 'mimetype';
                var value = $(this).data("value") || '';
                var object = $("[name='" + field + "']", table.closest(".bootstrap-table").find(".commonsearch-table"));
                if (object.prop('tagName') === "SELECT") {
                    $("option[value='" + value + "']", object).prop("selected", true);
                } else {
                    object.val(value);
                }
                table.trigger("uncheckbox");
                table.bootstrapTable('refresh', {pageNumber: 1});
            });

            $(document).on("click", ".btn-choose-multi", function () {
                Fast.api.close({url: urlArr.join(","), ids: idArr, multiple: multiple});
            });

            Table.api.bindevent(table);
            require(['upload'], function (Upload) {
                $("#toolbar .faupload").data("category", function () {
                    return $("ul.nav-tabs[data-field='category'] li.active a").data("value");
                });
                Upload.api.upload($("#toolbar .faupload"), function () {
                    $(".btn-refresh").trigger("click");
                });
            });
        },
        add: function () {
            $(".faupload").data("upload-complete", function () {
                setTimeout(function () {
                    window.parent.$(".btn-refresh").trigger("click");
                }, 100);
            });
            $("#faupload-third,#faupload-third-chunking").data("category", function () {
                return $("#category-third").val();
            });
            $("#faupload-local,#faupload-local-chunking").data("category", function () {
                return $("#category-local").val();
            });
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            formatter: {
                thumb: function (value, row) {
                    var html = '';
                    if (row.mimetype.indexOf("image") > -1) {
                        html = '<a href="' + row.fullurl + '" target="_blank"><img src="' + row.fullurl + row.thumb_style + '" alt="" style="max-height:60px;max-width:120px"></a>';
                    } else {
                        html = '<a href="' + row.fullurl + '" target="_blank"><img src="' + Fast.api.fixurl("ajax/icon") + "?suffix=" + row.imagetype + '" alt="" style="max-height:90px;max-width:120px"></a>';
                    }
                    return '<div style="width:120px;margin:0 auto;text-align:center;overflow:hidden;white-space: nowrap;text-overflow: ellipsis;">' + html + '</div>';
                },
                url: function (value, row) {
                    return '<a href="' + row.fullurl + '" target="_blank" class="label bg-green">' + row.url + '</a>';
                },
                filename: function (value, row, index) {
                    return '<div style="width:150px;margin:0 auto;text-align:center;overflow:hidden;white-space: nowrap;text-overflow: ellipsis;">' + Table.api.formatter.search.call(this, value, row, index) + '</div>';
                },
                mimetype: function (value, row, index) {
                    return '<div style="width:80px;margin:0 auto;text-align:center;overflow:hidden;white-space: nowrap;text-overflow: ellipsis;">' + Table.api.formatter.search.call(this, value, row, index) + '</div>';
                }
            }
        }

    };
    return Controller;
});
