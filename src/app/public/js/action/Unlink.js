define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "./ActionBase",
    "../persistence/RelationStore"
], function (
    declare,
    lang,
    ActionBase,
    RelationStore
) {
    return declare([ActionBase], {

        name: 'unlink',
        iconClass: 'fa fa-unlink',

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
            var deferred = store.remove(data.oid).then(lang.hitch(this, function(results) {
                // callback completes
                if (this.callback instanceof Function) {
                    this.callback(data, results);
                }
            }), lang.hitch(this, function(error) {
                // error
                if (this.errback instanceof Function) {
                    this.errback(data, error);
                }
            }));
            return deferred;
        }
    });
});
