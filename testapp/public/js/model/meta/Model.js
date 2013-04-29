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
        // register fully qualified type name
        var fqTypeName = type.typeName;
        Model.types[fqTypeName] = type;
        // also register simple type name
        var simpleTypeName = Model.getSimpleTypeName(fqTypeName);
        if (Model.types[simpleTypeName] === undefined) {
            Model.types[simpleTypeName] = type;
            Model.simpleToFqNames[simpleTypeName] = fqTypeName;
        }
    };

    /**
     * Get the known types
     * @return Array of simple type names
     */
    Model.getKnownTypes = function() {
        var result = [];
        for (var typeName in simpleToFqNames) {
            result.push(typeName);
        }
        return result;
    };

    /**
     * Check if a type is known
     * @param typeName Simple or fully qualified type name
     * @return Boolean
     */
    Model.isKnownType = function(typeName) {
        return Model.types[typeName] !== undefined;
    };

    /**
     * Get the fully qualified type name for a given type name
     * @param typeName Simple or fully qualified type name
     * @return String
     */
    Model.getFullyQualifiedTypeName = function(typeName) {
        if (Model.simpleToFqNames[typeName] !== undefined) {
            return Model.simpleToFqNames[typeName];
        }
        if (Model.isKnownType(typeName)) {
            return typeName;
        }
        return null;
    };

    /**
     * Get the simple type name for a given type name
     * @param typeName Simple or fully qualified type name
     * @return String
     */
    Model.getSimpleTypeName = function(typeName) {
        var pos = typeName.lastIndexOf('.');
        if (pos !== -1) {
            return typeName.substring(pos+1);
        }
        return typeName;
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
    Model.simpleToFqNames = {};
    for (var i=0, count=TypeList.length; i<count; i++) {
        Model.registerType(TypeList[i]);
    }

    return Model;
});
