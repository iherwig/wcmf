define([
    "dojo/_base/declare",
    "./Model"
], function(
    declare,
    Model
) {
    var Node = declare(null, {

        typeName: '',
        isSortable: false,
        displayValues: [],
        attributes: [],
        relations: [],

        /**
         * Get a relation definition for a given role name
         * @param roleName The name of the role
         * @return Object
         */
        getRelation: function(roleName) {
            for (var i=0, count=this.relations.length; i<count; i++) {
                if (this.relations[i].name === roleName) {
                    return this.relations[i];
                }
            }
            return null;
        },

        /**
         * Get the Node for a given role name
         * @param roleName The name of the role
         * @return Node
         */
        getTypeForRole: function(roleName) {
            var relation = this.getRelation(roleName);
            if (relation !== null) {
                return Model.getType(relation.type);
            }
            return null;
        }
    });

    /**
     * Get the type parameter from an object id. Object ids have
     * the format type:id1:id2..
     * @param oid The object id
     * @return String
     */
    Node.getTypeFromOid = function(oid) {
        var pos = oid.indexOf(':');
        if (pos !== -1) {
            return oid.substring(0, pos);
        }
        return oid;
    };

    /**
     * Get the id parameter from an object id. Object ids have
     * the format type:id1:id2..
     * @param oid The object id
     * @return String
     */
    Node.getIdFromOid = function(oid) {
        var pos = oid.indexOf(':');
        if (pos !== -1) {
            return oid.substring(pos+1);
        }
        return oid;
    };

    /**
     * Get the display value of an object.
     * @param object The object
     * @return String
     */
    Node.getDisplayValue = function(object) {
        var result = '';
        var type = Model.getTypeFromOid(object.oid);
        if (type) {
            for (var i=0; i<type.displayValues.length; i++) {
                result += object[type.displayValues[i]]+" | ";
            }
            result = result.substring(0, result.length-3);
        }
        else {
            result = object.oid || "unknown";
        }
        return result;
    };

    /**
     * Get a random unique object id for a given type
     * @param type The type
     * @return String
     */
    Node.createRandomOid = function(type) {
        var oid = type+":"+dojox.uuid.generateRandomUuid();
        return oid;
    };

    return Node;
});
