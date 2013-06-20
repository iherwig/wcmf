define( [
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/topic",
    "dijit/form/FilteringSelect",
    "../Factory"
],
function(
    declare,
    lang,
    topic,
    FilteringSelect,
    ControlFactory
) {
    return declare([FilteringSelect], {

        intermediateChanges: true,
        searchAttr: "value",
        entity: {},
        attribute: {},
        original: {},

        constructor: function(args) {
            declare.safeMixin(this, args);

            this.label = this.attribute.name;
            this.disabled = !this.attribute.isEditable;
            this.name = this.attribute.name;
            this.value = this.entity[this.attribute.name];

            this.store = ControlFactory.getListStore(this.attribute.inputType);
        },

        postCreate: function() {
            this.inherited(arguments);

            // subscribe to entity change events to change tab links
            this.own(
                topic.subscribe("entity-datachange", lang.hitch(this, function(data) {
                    if (data.name === this.attribute.name) {
                        this.set("value", data.newValue);
                    }
                }))
            );
        }
    });
});