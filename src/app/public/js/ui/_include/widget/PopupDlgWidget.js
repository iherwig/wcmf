define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/on",
    "dojo/dom-construct",
    "dojo/Deferred",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dijit/_WidgetsInTemplateMixin",
    "../_NotificationMixin",
    "dijit/Dialog",
    "./Button",
    "../../../locale/Dictionary",
    "dojo/text!./template/PopupDlgWidget.html"
], function (
    declare,
    lang,
    on,
    domConstruct,
    Deferred,
    _WidgetBase,
    _TemplatedMixin,
    _WidgetsInTemplateMixin,
    _Notification,
    Dialog,
    Button,
    Dict,
    template
) {
    /**
     * Modal popup dialog. Usage:
     * @code
     * new PopupDlg({
     *      title: "Confirm Object Deletion",
     *      message: "Do you really want to delete '"+Model.getTypeFromOid(data.oid).getDisplayValue(data)+"'?",
     *      contentWidget: myTextBox, // optional, will be set below message
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
            var contentWidget = new (declare([_WidgetBase, _TemplatedMixin, _WidgetsInTemplateMixin, _Notification], {
                templateString: lang.replace(this.getTemplate(), Dict.tplTranslate)
            }));
            if (contentWidget.contentNode) {
              contentWidget.contentNode.innerHTML = message;
            }
            if (this.contentWidget) {
              domConstruct.place(this.contentWidget.domNode, contentWidget.contentNode, "after");
            }
            contentWidget.startup();
            this.content = contentWidget;
        },

        postCreate: function () {
            this.inherited(arguments);

            this.own(
                on(this.content.okBtn, "click", lang.hitch(this, function(e) {
                    this.content.okBtn.setProcessing();
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
                        this.content.okBtn.setProcessing();
                        this.doCallback(e, this.okCallback);
                    }
                    if (e.which === 27) {
                        this.doCallback(e, this.cancelCallback);
                    }
                }))
            );
        },

        /**
         * Override this method in subclasses to provide your custom template
         * @returns String
         */
        getTemplate: function() {
          return template;
        },

        doCallback: function(e, callback) {
            this.content.hideNotification();
            if (callback instanceof Function) {
                e.preventDefault();
                var result = callback(this);
                if (result && result.then instanceof Function) {
                    result.then(lang.hitch(this, function() {
                        // success
                        this.hide();
                    }), lang.hitch(this, function(error) {
                        // error
                        this.content.okBtn.reset();
                        this.content.showBackendError(error);
                    }))
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
        }
    });

    return PopupDlg;
});