define([
    "dojo/_base/declare",
    "./ActionBase",
    "../persistence/RelationStore"
], function (
    declare,
    ActionBase,
    RelationStore
) {
    return declare([ActionBase], {

        name: 'unlink',
        iconClass: 'icon-unlink',
        source: null,
        relation: null,

        /**
         * Execute the unlink action on the store
         * @param e The event that triggered execution, might be null
         * @param data Object to unlink from source
         * @return Deferred instance
         */
        execute: function(e, data) {
            if (this.init instanceof Function) {
                this.init(data);
            }
            var store = RelationStore.getStore(this.source.oid, this.relation.name);
            var deferred = store.remove(data.oid);
            return deferred;
        }
    });
});
