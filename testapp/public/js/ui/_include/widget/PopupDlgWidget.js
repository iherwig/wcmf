define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/on",
    "dojo/query",
    "dojo/dom-style",
    "dojo/Deferred",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dijit/_WidgetsInTemplateMixin",
    "dijit/Dialog",
    "dijit/form/Button",
    "dojo/text!./template/PopupDlgWidget.html"
], function (
    declare,
    lang,
    on,
    query,
    domStyle,
    Deferred,
    _WidgetBase,
    _TemplatedMixin,
    _WidgetsInTemplateMixin,
    Dialog,
    Button,
    template
) {
    /**
     * Modal popup dialog. Usage:
     * @code
     * new PopupDlg({
     *      title: "Confirm Object Deletion",
     *      message: "Do you really want to delete '"+Model.getDisplayValue(data)+"'?",
     *      okCallback: function() {
     *          // will be called when OK button is clicked
     *          var deferred = new Deferred();
     *          // do something
     *          return deferred;
     *      },
     *      cancelCallback: function() {
     *          // will be called when Cancel button is clicked
     *          ....
     *      }
     * }).show();
     * @endcode
     */
    var PopupDlg = declare([Dialog], {

        okCallback: null,
        cancelCallback: null,
        deferred: null,

        constructor: function(args) {
            lang.mixin(this, args);

            var message = this.message || '';
            var contentWidget = new (declare([_WidgetBase, _TemplatedMixin, _WidgetsInTemplateMixin], {
                templateString: template, //get template via dojo loader or so
                message: message
            }));
            contentWidget.startup();
            this.content = contentWidget;
        },

        postCreate: function () {
            this.inherited(arguments);
            this.hideSpinner();

            this.own(
                on(this.content.okBtn, "click", lang.hitch(this, function(e) {
                    this.doCallback(e, this.okCallback);
                })),
                on(this.content.cancelBtn, "click", lang.hitch(this, function(e) {
                    this.doCallback(e, this.cancelCallback);
                })),
                on(this, "hide", lang.hitch(this, function(e) {
                    this.deferred.resolve();
                })),
                on(dojo.body(), 'keyup', lang.hitch(this, function (e) {
                    if (e.which === 13) {
                        this.doCallback(e, this.okCallback);
                    }
                    if (e.which === 27) {
                        this.doCallback(e, this.cancelCallback);
                    }
                }))
            );
        },

        doCallback: function(e, callback) {
            if (callback instanceof Function) {
                e.preventDefault();
                var result = callback(this);
                if (result && result.always instanceof Function) {
                    this.showSpinner();
                    result.always(lang.hitch(this, function() {
                        this.hide();
                    }));
                }
                else {
                    this.hide();
                }
            }
            else {
                this.hide();
            }
        },

        /**
         * Show the dialog
         * @return Deferred instance that will resolve, when the dialog is
         * closed.
         */
        show: function() {
            this.inherited(arguments);
            this.deferred = new Deferred();
            return this.deferred;
        },

        showSpinner: function() {
            query(this.content.spinnerNode).style("display", "block");
        },

        hideSpinner: function() {
            query(this.content.spinnerNode).style("display", "none");
        }
    });

    return PopupDlg;
});