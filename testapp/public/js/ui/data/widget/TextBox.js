define( [
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/topic",
    "dijit/form/TextBox",
    "dojo/text!./template/TextBox.html"
],
function(
    declare,
    lang,
    topic,
    TextBox,
    template
) {
    return declare([TextBox], {

        templateString: template,
        intermediateChanges: true,
        nodeData: {},
        attribute: {},

        constructor: function(args) {
            declare.safeMixin(this, args);

            this.label = this.attribute.name;
            this.disabled = !this.attribute.isEditable;
            this.name = this.attribute.name;
            this.value = this.nodeData[this.attribute.name];
        },

        postCreate: function() {
            this.inherited(arguments);

            // subscribe to node change events to change tab links
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