define([
    "dojo/_base/declare",
    "../types/_TypeList",
    "../../locale/Dictionary",
    "../../ui/data/display/Renderer"
], function(
    declare,
    TypeList,
    Dict,
    Renderer
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
     * Get the type parameter from an object id. Object ids have
     * the format type:id1:id2..
     * @param oid The object id
     * @return String
     */
    Model.getTypeNameFromOid = function(oid) {
        var pos = oid.indexOf(':');
        if (pos !== -1) {
            return oid.substring(0, pos);
        }
        return oid;
    };

    /**
     * Get the id parameter from an object id. Object ids have
     * the format type:id1:id2.. Returns type name, if no id is contained
     * @param oid The object id
     * @return String
     */
    Model.getIdFromOid = function(oid) {
        var pos = oid.indexOf(':');
        if (pos !== -1) {
            return oid.substring(pos+1);
        }
        return oid;
    };

    /**
     * Assemble an (fully qualified) object id from the given parameters.
     * @param type The object's type
     * @param id The object's id
     * @return String
     */
    Model.getOid = function(type, id) {
        return Model.getFullyQualifiedTypeName(type)+":"+id;
    };

    /**
     * Get the display value of an object.
     * @param object The object
     * @return String
     */
    Model.getDisplayValue = function(object) {
        var result = '';
        var type = Model.getTypeFromOid(object.oid);
        if (type) {
            if (Model.isDummyOid(object.oid)) {
                result = Dict.translate("New %0%",
                    [Dict.translate(Model.getSimpleTypeName(type.typeName))]);
            }
            else {
                for (var i=0; i<type.displayValues.length; i++) {
                    var curValue = type.displayValues[i];
                    var curAttribute = type.getAttribute(curValue);
                    result += Renderer.render(object[curValue], curAttribute.displayType)+" | ";
                }
                result = result.substring(0, result.length-3);
            }
        }
        else {
            result = object.oid || "unknown";
        }
        return result;
    };

    /**
     * Get a dummy object id for a given type
     * @param type The type
     * @return String
     */
    Model.createDummyOid = function(type) {
        var oid = type+":~";
        return oid;
    };

    /**
     * Get if the given oid is a dummy id
     * @param oid The object id
     * @return Boolean
     */
    Model.isDummyOid = function(oid) {
        return oid.match(/:~$/) !== null;
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
        var typeName = Model.getTypeNameFromOid(oid);
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
