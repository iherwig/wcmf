define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/on",
    "dojo/query",
    "dojo/dom-construct",
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
    domConstruct,
    Promise,
    _WidgetBase,
    _TemplatedMixin,
    Modal,
    template
) {
    /**
     * Modal popup dialog. Usage:
     * @code
     * PopupDlg.show({
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
     * });
     * @endcode
     */
    var PopupDlg = declare([_WidgetBase, _TemplatedMixin], {

        templateString: template,
        modal: null,
        okCallback: null,
        cancelCallback: null,

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

            this.hideSpinner();

            var dlgNode = this.getDlgNode();
            dlgNode.modal({});
            dlgNode.on('hidden', lang.hitch(this, function () {
                this.destroyRecursive();
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
            var dlgNode = this.getDlgNode();
            if (callback instanceof Function) {
                e.preventDefault();
                var result = callback(this);
                if (result instanceof Promise) {
                    this.showSpinner();
                    result.always(function() {
                        dlgNode.modal('hide');
                    });
                }
                else {
                    dlgNode.modal('hide');
                }
            }
        },

        getDlgNode: function() {
          return query('#'+PopupDlg._dlgId);
        },

        hideSpinner: function() {
            query(this.spinnerNode).style("display", "none");
        },

        showSpinner: function() {
            query(this.spinnerNode).style("display", "block");
        }
    });

    PopupDlg._node = null;
    PopupDlg._dlgId = "popupDlg";
    PopupDlg._widgetClass = PopupDlg;

    /**
     * Show the dialog
     */
    PopupDlg.show = function(options) {
        PopupDlg.hide();

        if (PopupDlg._node) {
            domConstruct.destroy(PopupDlg._node);
        }

        PopupDlg._node = domConstruct.create('div', {}, dojo.body());

        var params = declare.safeMixin({id: PopupDlg._dlgId}, options);
        var widget = new PopupDlg.widgetClass(params, PopupDlg._node);
        widget.startup();
    };

    /**
     * Hide the dialog
     */
    PopupDlg.hide = function () {
        query('#'+PopupDlg._dlgId).modal('hide');
    };

    /**
     * Use this method to create a subclass that inherits the static
     * methods and uses the given subclass as actual widget
     * @param subclass PopupDlg subclass
     * @returns Enhanced class
     */
    PopupDlg.extend = function (subclass) {
        PopupDlg.widgetClass = subclass;
        subclass.show = PopupDlg.show;
        subclass.hide = PopupDlg.hide;
        return subclass;
    };

    return PopupDlg;
});