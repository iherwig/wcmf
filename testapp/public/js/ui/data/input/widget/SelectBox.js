define( [
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/topic",
    "dijit/form/FilteringSelect",
    "../../../../persistence/Store",
    "dojo/text!./template/SelectBox.html"
],
function(
    declare,
    lang,
    topic,
    FilteringSelect,
    Store,
    template
) {
    return declare([FilteringSelect], {

        templateString: template,
        intermediateChanges: true,
        entity: {},
        attribute: {},
        original: {},

        constructor: function(args) {
            declare.safeMixin(this, args);

            //https://github.com/thesociable/dbootstrap

            this.label = this.attribute.name;
            this.disabled = !this.attribute.isEditable;
            this.name = this.attribute.name;
            this.value = this.entity[this.attribute.name];
            this.store = Store.getStore('Author');

            this.options = [
                { label: "TN", value: "Tennessee" },
                { label: "VA", value: "Virginia", selected: true },
                { label: "WA", value: "Washington" },
                { label: "FL", value: "Florida" },
                { label: "CA", value: "California" }
            ];
        },

        postCreate: function() {
            this.inherited(arguments);

            this.helpNode.innerHTML = this.original[this.attribute.name] || "";

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