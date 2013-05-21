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

        name: 'edit',
        iconClass: 'icon-pencil',

        /**
         * Navigate to edit page
         * @param e The event that triggered execution, might be null
         * @param data Object to edit
         */
        execute: function(e, data) {
            if (this.init instanceof Function) {
                this.init(data);
            }
            var route = this.router.getRoute("entity");
            var type = Model.getSimpleTypeName(Model.getTypeNameFromOid(data.oid));
            var id = Model.getIdFromOid(data.oid);
            var pathParams = { type:type, id:id };
            var url = route.assemble(pathParams);
            this.push(url);
        }
    });
});
