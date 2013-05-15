define([
    "dojo/_base/declare",
    "dojo/topic",
    "dojo/Stateful"
], function(
    declare,
    topic,
    Stateful
) {
    /**
     * Entity inherits observer capabilities from Stateful
     * and emits entity-datachange event, if properties change.
     * Entities may exist in different states (clean, dirty, new, deleted).
     * If the state changes a entity-staechange event is emitted.
     */
    return declare([Stateful], {

        _state: 'clean', /* clean, dirty, new, deleted */

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
            this._state = state;
        },

        getState: function() {
            return this._state;
        }
    });
});
