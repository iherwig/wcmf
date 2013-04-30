define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/on",
    "dojo/query",
    "dojo/promise/Promise",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "bootstrap/Modal",
    "dojo/text!./template/ConfirmDlgWidget.html"
], function (
    declare,
    lang,
    on,
    query,
    Promise,
    _WidgetBase,
    _TemplatedMixin,
    Confirm,
    template
) {
    return declare([_WidgetBase, _TemplatedMixin], {

        templateString: template,
        modal: null,
        callback: null,

        constructor: function(params) {
            this.callback = params.callback;
        },

        _setTitleAttr: function (val) {
            this.titleNode.innerHTML = val;
        },

        _setContentAttr: function (val) {
            this.contentNode.innerHTML = '<i class="icon-question-sign icon-3x pull-left"></i> &nbsp;'+val;
        },

        postCreate: function () {
            this.inherited(arguments);

            this.hideSpinner();
            query('#confirmDlg').modal({});
            query('#confirmDlg').on('hidden', lang.hitch(this, function () {
                this.destroyRecursive();
            }));
            this.own(
                on(this.okBtn, "click", lang.hitch(this, function(e) {
                    this.doCallback(e);
                })),
                on(dojo.body(), 'keyup', lang.hitch(this, function (e) {
                    if (e.which === 13) {
                        this.doCallback(e);
                    }
                }))
            );
        },

        doCallback: function(e) {
            if (this.callback instanceof Function) {
                e.stopPropagation();
                e.preventDefault();
                this.showSpinner();
                var result = this.callback();
                if (result instanceof Promise) {
                  result.then(function() {
                    query('#confirmDlg').modal('hide');
                  });
                }
            }
        },

        hideSpinner: function() {
            query(this.spinnerNode).style("display", "none");
        },

        showSpinner: function() {
            query(this.spinnerNode).style("display", "block");
        }
    });
});