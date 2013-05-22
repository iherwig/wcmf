define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "./ActionBase",
    "../ui/_include/widget/ConfirmDlgWidget",
    "../persistence/Store",
    "../model/meta/Model"
], function (
    declare,
    lang,
    ActionBase,
    ConfirmDlg,
    Store,
    Model
) {
    return declare([ActionBase], {

        name: 'delete',
        iconClass: 'icon-trash',

        /**
         * Shows confirm dialog and executes the delete action on the store
         * @param e The event that triggered execution, might be null
         * @param data Object to delete
         * @return Deferred instance
         */
        execute: function(e, data) {
            if (this.init instanceof Function) {
                this.init(data);
            }
            return new ConfirmDlg({
                title: "Confirm Object Deletion",
                content: "Do you really want to delete '"+Model.getDisplayValue(data)+"'?",
                okCallback: lang.hitch(this, function(dlg) {
                    var typeName = Model.getTypeNameFromOid(data.oid);
                    var store = Store.getStore(typeName, 'en');
                    var deferred = store.remove(data.oid).then(lang.hitch(this, function(results) {
                        // callback completes
                        this.callback(data, results);
                    }), lang.hitch(this, function(error) {
                        // error
                        this.errback(data, error);
                    }));
                    return deferred;
                })
            }).show();
        }
    });
});
