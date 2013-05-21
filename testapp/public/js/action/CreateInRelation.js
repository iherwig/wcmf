define([
    "dojo/_base/declare",
    "./ActionBase",
    "../model/meta/Model"
], function (
    declare,
    ActionBase,
    Model
) {
    return declare([ActionBase], {

        name: 'create',
        iconClass: 'icon-star',
        source: null,
        relation: null,

        /**
         * Navigate to create page for an object that is in relation to source
         * @param e The event that triggered execution, might be null
         * @param type Name of the type to create
         */
        execute: function(e, type) {
            if (this.init instanceof Function) {
                this.init(type);
            }
            var route = this.router.getRoute("entity");
            var oid = Model.createDummyOid(type);
            var pathParams = { type:type, id:Model.getIdFromOid(oid) };
            var url = route.assemble(pathParams);
            url += "?oid="+this.source.oid+"&relation="+this.relation.name;
            this.push(url);
        }
    });
});
