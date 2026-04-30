define(['jquery', 'bootstrap', 'backend', 'form'], function ($, undefined, Backend, Form) {

    var Controller = {
        index: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($('form[role=form]'), function (data, ret) {
                    if (ret && ret.msg) {
                        Layer.msg(ret.msg);
                    }
                    setTimeout(function () {
                        location.reload();
                    }, 500);
                    return false;
                });
            }
        }
    };

    return Controller;
});
