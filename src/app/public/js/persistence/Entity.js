define([
    "dojo/_base/declare",
    "dojo/topic",
    "dojo/Stateful",
    "../model/meta/Model",
    "../locale/Dictionary",
    "../ui/data/display/Renderer"
], function(
    declare,
    topic,
    Stateful,
    Model,
    Dict,
    Renderer
) {
    /**
     * Entity inherits observer capabilities from Stateful
     * and emits entity-datachange event, if properties change.
     * Entities may exist in different states (clean, dirty, new, deleted).
     * If the state changes a entity-statechange event is emitted.
     */
    var Entity = declare([Stateful], {

        _state: "clean", /* clean, dirty, new, deleted */

        constructor: function(args) {
            this.inherited(arguments);

            this._state = "clean";
        },

        postscript: function(params) {
            this.inherited(arguments);

            // watch after initial set
            this.watch(function(name, oldValue, newValue) {
                if (name !== '_state') {
                    topic.publish("entity-datachange", {
                        entity: this,
                        name: name,
                        oldValue: oldValue,
                        newValue: newValue
                    });
                }
                else {
                    topic.publish("entity-statechange", {
                        entity: this,
                        oldValue: oldValue,
                        newValue: newValue
                    });
                }
            });
        },

        setState: function(state) {
            if (state !== this._state) {
                this.set("_state", state);
            }
        },

        getState: function() {
            return this.get("_state");
        },

        getDisplayValue: function() {
            return Entity.getDisplayValue(this);
        },

        setDefaults: function() {
            var typeClass = Model.getTypeFromOid(this.oid);
            var attributes = typeClass.getAttributes();
            for (var i=0, count=attributes.length; i<count; i++) {
                var attribute = attributes[i];
                this.set(attribute.name, attribute.defaultValue);
            }
        },

        getCleanCopy: function() {
            var typeClass = Model.getTypeFromOid(this.oid);
            var attributes = typeClass.getAttributes();
            var copy = {};
            for (var i=0, count=attributes.length; i<count; i++) {
                var attributeName = attributes[i].name;
                copy[attributeName] = this[attributeName] || "";
            }
            copy.oid = this.oid;
            return copy;
        }
  });

    /**
     * Get the display value of an object.
     * @param object The object
     * @return String
     */
    Entity.getDisplayValue = function(object) {
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
                    result += Renderer.render(object[curValue], curAttribute)+" | ";
                }
                result = result.substring(0, result.length-3);
            }
        }
        else {
            result = object.oid || "unknown";
        }
        return result;
    };

    return Entity;
});
