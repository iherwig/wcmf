define( [
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/query",
    "dojo/topic",
    "bootstrap/Datepicker",
    "dijit/form/TextBox",
    "dojo/text!./template/Date.html",
    "xstyle/css!bootstrap/assets/datepicker.css"
],
function(
    declare,
    lang,
    query,
    topic,
    Datepicker,
    TextBox,
    template
) {
    return declare([TextBox], {

        templateString: template,
        intermediateChanges: true,
        entity: {},
        attribute: {},
        original: {},

        constructor: function(args) {
            declare.safeMixin(this, args);

            this.label = this.attribute.name;
            this.disabled = !this.attribute.isEditable;
            this.name = this.attribute.name;
            this.value = this.entity[this.attribute.name];
        },

        postCreate: function() {
            this.inherited(arguments);

            var pickerNode = query(".date", this.domNode);
            pickerNode.datepicker({
                format: 'dd.mm.yyyy'
            });
            this.helpNode.innerHTML = this.original[this.attribute.name] || "";

            // subscribe to entity change events to change tab links
            this.own(
                topic.subscribe("entity-datachange", lang.hitch(this, function(data) {
                    if (data.name === this.attribute.name) {
                        this.set("value", data.newValue);
                    }
                })),
                pickerNode.datepicker().on('changeDate', lang.hitch(this, function(e) {
                    // notify listeners immediatly
                    this.setValue();
                }))
            );
        }
    });
});