define([
    "dojo/_base/declare",
    "dojo/topic",
    "dojo/Stateful"
], function(
    declare,
    topic,
    Stateful
) {
    return declare([Stateful], {

        constructor: function() {
            this.inherited(arguments);

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
