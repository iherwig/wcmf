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
     */
    return declare([Stateful], {

        postscript: function(params) {
            this.inherited(arguments);

            // watch after initial set
            this.watch(function(name, oldValue, newValue) {
                topic.publish("entity-datachange", {
                    node: this,
                    name: name,
                    oldValue: oldValue,
                    newValue: newValue
                });
            });
        }
    });
});
