define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/on",
    "dojo/query",
    "dojo/dom-construct",
    "dojo/Deferred",
    "bootstrap/Modal",
    "../../../locale/Dictionary"
], function (
    declare,
    lang,
    on,
    query,
    domConstruct,
    Deferred,
    Modal,
    Dict
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
    var PopupDlg = declare([Modal], {

        spinner: null,
        okBtn: null,
        cancelBtn: null,
        okCallback: null,
        cancelCallback: null,
        deferred: null,

        constructor: function(args) {
            lang.mixin(this, args);

            this.spinner = domConstruct.toDom("<i></i>");
            query(this.spinner).addClass("fa fa-spinner fa-spin fa-2x pull-left");

            this.okBtn = domConstruct.toDom("<button>"+Dict.translate("OK")+"</button>");
            query(this.okBtn).addClass("btn btn-primary");

            this.cancelBtn = domConstruct.toDom("<button class='btn'>"+Dict.translate("Cancel")+"</button>");

            var buttonContainer = domConstruct.toDom("<div></div>");
            domConstruct.place(this.cancelBtn, buttonContainer);
            domConstruct.place(this.okBtn, buttonContainer);
            domConstruct.place(this.spinner, buttonContainer);

            this.header = this.title;
            this.footer = buttonContainer;
            this.content = this.message || '';
        },

        postCreate: function () {
            this.inherited(arguments);
            this.hideSpinner();

            this.own(
                on(this.okBtn, "click", lang.hitch(this, function(e) {
                    this.doCallback(e, this.okCallback);
                })),
                on(this.cancelBtn, "click", lang.hitch(this, function(e) {
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
            query(this.spinner).style("display", "block");
        },

        hideSpinner: function() {
            query(this.spinner).style("display", "none");
        }
    });

    return PopupDlg;
});