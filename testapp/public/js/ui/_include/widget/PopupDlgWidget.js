define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/on",
    "dojo/query",
    "dojo/dom-style",
    "dojo/Deferred",
    "dojo/promise/Promise",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "bootstrap/Modal",
    "dojo/text!./template/PopupDlgWidget.html"
], function (
    declare,
    lang,
    on,
    query,
    domStyle,
    Deferred,
    Promise,
    _WidgetBase,
    _TemplatedMixin,
    Modal,
    template
) {
    /**
     * Modal popup dialog. Usage:
     * @code
     * new PopupDlg({
     *      title: "Confirm Object Deletion",
     *      content: "Do you really want to delete '"+Model.getDisplayValue(data)+"'?",
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
    var PopupDlg = declare([_WidgetBase, _TemplatedMixin], {

        templateString: template,
        modal: null,
        okCallback: null,
        cancelCallback: null,
        deferred: null,

        constructor: function(args) {
            declare.safeMixin(this, args);
        },

        _setTitleAttr: function (val) {
            this.titleNode.innerHTML = val;
        },

        _setContentAttr: function (val) {
            this.contentNode.innerHTML = val;
        },

        postCreate: function () {
            this.inherited(arguments);

            this.placeAt(dojo.body());
            domStyle.set(this.domNode, {
                display: "none"
            });
            this.hideSpinner();

            query(this.domNode).modal({});
            query(this.domNode).on('hidden', lang.hitch(this, function () {
                this.destroyRecursive();
                if (this.deferred) {
                    this.deferred.resolve();
                }
            }));
            this.own(
                on(this.okBtn, "click", lang.hitch(this, function(e) {
                    this.doCallback(e, this.okCallback);
                })),
                on(this.cancelBtn, "click", lang.hitch(this, function(e) {
                    this.doCallback(e, this.cancelCallback);
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
                if (result instanceof Promise) {
                    this.showSpinner();
                    result.always(function() {
                        this.hide();
                    });
                }
                else {
                    this.hide();
                }
            }
        },

        /**
         * Show the dialog
         * @return Deferred instance that will resolve, when the dialog is
         * closed.
         */
        show: function() {
            domStyle.set(this.domNode, {
                display: "block"
            });
            this.deferred = new Deferred();
            return this.deferred;
        },

        /**
         * Hide the dialog
         */
        hide: function () {
            query(this.domNode).modal('hide');
        },

        showSpinner: function() {
            query(this.spinnerNode).style("display", "block");
        },

        hideSpinner: function() {
            query(this.spinnerNode).style("display", "none");
        }
    });

    return PopupDlg;
});