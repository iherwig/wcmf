define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/promise/all",
    "./ActionBase",
    "../ui/_include/widget/ObjectSelectDlgWidget",
    "../persistence/RelationStore",
    "../model/meta/Model",
    "../locale/Dictionary"
], function (
    declare,
    lang,
    all,
    ActionBase,
    ObjectSelectDlg,
    RelationStore,
    Model,
    Dict
) {
    return declare([ActionBase], {

        name: 'link',
        iconClass: 'fa fa-link',
        source: null,
        relation: null,

        /**
         * Show a object selection dialog and execute the link action for
         * the selected objects on the store
         * @param e The event that triggered execution, might be null
         * @return Deferred instance
         */
        execute: function(e) {
            if (this.init instanceof Function) {
                this.init();
            }
            return new ObjectSelectDlg({
                type: this.relation.type,
                title: Dict.translate("Choose Objects"),
                message: Dict.translate("Select '%0%' objects, you want to link to '%1%'",
                    [Dict.translate(this.relation.type), Model.getDisplayValue(this.source)]),
                okCallback: lang.hitch(this, function(dlg) {
                    var store = RelationStore.getStore(this.source.oid, this.relation.name);

                    var oids = dlg.getSelectedOids();
                    var deferredList = [];
                    for (var i=0, count=oids.length; i<count; i++) {
                        var entity = { oid:oids[i] };
                        deferredList.push(store.put(entity, {overwrite: true}));
                    }
                    all(deferredList).then(lang.hitch(this, function(results) {
                        // callback completes
                        if (this.callback instanceof Function) {
                            this.callback(results);
                        }
                    }), lang.hitch(this, function(error) {
                        // error
                        if (this.errback instanceof Function) {
                            this.errback(error);
                        }
                    }));
                    return all(deferredList);
                })
            }).show();
        }
    });
});
