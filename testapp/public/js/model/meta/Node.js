define([
    "dojo/_base/declare",
    "dojo/_base/array",
    "./Model"
], function(
    declare,
    array,
    Model
) {
    var Node = declare(null, {

        typeName: '',
        isSortable: false,
        displayValues: [],
        attributes: [],
        relations: [],

        /**
         * Get all relation definitions
         * @return Array
         */
        getRelations: function() {
            return this.relations;
        },

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
        },

        /**
         * Get the Node attributes
         * @param tag Optional tag that the attributes should have
         * @return Array
         */
        getAttributes: function(tag) {
            var result = [];
            for (var i=0, count=this.attributes.length; i<count; i++) {
                var attribute = this.attributes[i];
                if (!tag || array.indexOf(attribute.tags, tag) !== -1) {
                    result.push(attribute);
                }
            }
            return result;
        }
    });

    return Node;
});
