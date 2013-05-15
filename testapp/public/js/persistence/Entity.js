define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/topic",
    "dojo/Stateful"
], function(
    declare,
    lang,
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
        _initialData: {},

        constructor: function(args) {
            this.inherited(arguments);

            // record initial state
            this._initialData = lang.mixin({}, args);
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

        reset: function() {
            for (var key in this._initialData) {
                // notify listeners
                this.set(key, this._initialData[key]);
            }
            this.setState("clean");
        }
    });
});
