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
    "./GridWidget",
    "../../../persistence/Store",
    "dojo/text!./template/ObjectSelectDlgWidget.html"
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
    GridWidget,
    Store,
    template
) {
    /**
     * Modal link dialog. Usage:
     * @code
     * ObjectSelectDlg.showConfirm({
     *      type: "Author",
     *      title: "Choose Objects",
     *      content: "Select objects, you want to link to '"+Model.getDisplayValue(data)+"'",
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
    var ObjectSelectDlg = declare([_WidgetBase, _TemplatedMixin], {

        templateString: template,
        modal: null,
        type: "",
        okCallback: null,
        cancelCallback: null,

        constructor: function(args) {
            declare.safeMixin(this, args);
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

            new GridWidget({
                type: this.type,
                store: Store.getStore(this.type, 'en'),
                actions: [],
                height: 198
            }, this.gridNode);
        },

        doCallback: function(e, callback) {
            if (callback instanceof Function) {
                e.preventDefault();
                var result = callback();
                if (result instanceof Promise) {
                    this.showSpinner();
                    result.always(function() {
                        ObjectSelectDlg.hideConfirm();
                    });
                }
                else {
                    ObjectSelectDlg.hideConfirm();
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

    ObjectSelectDlg.node = null;
    ObjectSelectDlg.widget = null;

    ObjectSelectDlg.showConfirm = function(options) {
        ObjectSelectDlg.hideConfirm();

        if (ObjectSelectDlg.node) {
            domConstruct.destroy(ObjectSelectDlg.node);
        }

        ObjectSelectDlg.node = domConstruct.create('div', {}, dojo.body());

        var params = declare.safeMixin({id: 'confirmDlg'}, options);
        ObjectSelectDlg.widget = new ObjectSelectDlg(params, ObjectSelectDlg.node);

        this.widget.startup();
    };

    ObjectSelectDlg.hideConfirm = function () {
        query('#confirmDlg').modal('hide');
    };

    return ObjectSelectDlg;
});