define([
    "dojo/_base/declare",
    "./_TypeList",
    "./Node"
], function(
    declare,
    TypeList,
    Node
) {
    var Model = declare(null, {
    });

    /**
     * Register a type
     * @param type A Node subclass
     */
    Model.registerType = function(type) {
        var typeName = type.typeName;
        Model.types[typeName] = type;
        // also register simple type name
        var pos = typeName.lastIndexOf('.');
        if (pos !== -1) {
            var simpleTypeName = typeName.substring(pos+1);
            if (Model.types[simpleTypeName] === undefined) {
                Model.types[simpleTypeName] = type;
            }
        }
    };

    /**
     * Get a type from it's name
     * @param typeName The name of the type
     * @return Node instance
     */
    Model.getType = function(typeName) {
        return Model.types[typeName];
    };

    /**
     * Get a type from an object id
     * @param oid The object id
     * @return Node instance
     */
    Model.getTypeFromOid = function(oid) {
        var typeName = Node.getTypeFromOid(oid);
        return Model.types[typeName];
    };

    /**
     * Get all types that are defined in the meta model
     * @return An array of Node instances
     */
    Model.getAllTypes = function() {
        var types = [];
        for (var typeName in Model.types) {
            types.push(Model.types[typeName]);
        }
        return types;
    };

    // register types
    Model.types = {};
    for (var i=0, count=TypeList.length; i<count; i++) {
        Model.registerType(TypeList[i]);
    }

    return Model;
});
