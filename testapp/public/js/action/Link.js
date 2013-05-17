define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "./ActionBase",
    "../ui/_include/widget/ObjectSelectDlgWidget",
    "../persistence/RelationStore",
    "../model/meta/Model"
], function (
    declare,
    lang,
    ActionBase,
    ObjectSelectDlg,
    RelationStore,
    Model
) {
    return declare([ActionBase], {

        name: 'delete',
        iconClass: 'icon-trash',

        /**
         * Execute the link action on the store
         * @param data Object to link to
         * @param relation Relation to link to
         */
        execute: function(data, relation) {
            if (this.init instanceof Function) {
                this.init(data, relation);
            }
            ObjectSelectDlg.showConfirm({
                type: Model.getTypeNameFromOid(data.oid),
                title: "Choose Objects",
                content: "Select objects, you want to link to '"+Model.getDisplayValue(data)+"'",
                okCallback: lang.hitch(this, function(oids) {
                    var typeName = Model.getTypeNameFromOid(data.oid);
                    var store = RelationStore.getStore(typeName, 'en', relation.name);

                    // TODO add oids to relation
                    /*
                    var deferred = store.add(data.oid).then(lang.hitch(this, function(results) {
                        // callback completes
                        this.callback(data, results);
                    }), lang.hitch(this, function(error) {
                        // error
                        this.errback(data, error);
                    }));
                    return deferred;
                    */
                })
            });
        }
    });
});
