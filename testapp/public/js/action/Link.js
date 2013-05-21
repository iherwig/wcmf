define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/promise/all",
    "./ActionBase",
    "../ui/_include/widget/ObjectSelectDlgWidget",
    "../persistence/RelationStore",
    "../model/meta/Model"
], function (
    declare,
    lang,
    all,
    ActionBase,
    ObjectSelectDlg,
    RelationStore,
    Model
) {
    return declare([ActionBase], {

        name: 'link',
        iconClass: 'icon-link',
        source: null,
        relation: null,

        /**
         * Execute the link action on the store
         * @param e The event that triggered execution, might be null
         */
        execute: function(e) {
            if (this.init instanceof Function) {
                this.init();
            }
            ObjectSelectDlg.show({
                type: this.relation.type,
                title: "Choose Objects",
                content: "Select '"+this.relation.type+"' objects, you want to link to '"+Model.getDisplayValue(this.source)+"'",
                okCallback: lang.hitch(this, function(dlg) {
                    var store = RelationStore.getStore(this.source.oid, this.relation.name, 'en');

                    var oids = dlg.getSelectedOids();
                    var deferredList = [];
                    for (var i=0, count=oids.length; i<count; i++) {
                        var entity = { oid:oids[i] };
                        deferredList.push(store.add(entity));
                    }
                    all(deferredList).then(lang.hitch(this, function(results) {
                        // callback completes
                        this.callback(results);
                    }), lang.hitch(this, function(error) {
                        // error
                        this.errback(error);
                    }));
                    return all(deferredList);
                })
            });
        }
    });
});
