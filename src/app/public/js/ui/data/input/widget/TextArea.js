define( [
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/topic",
    "dijit/form/Textarea",
    "../../../_include/_HelpMixin",
    "./_AttributeWidgetMixin",
    "../../../../model/meta/Model",
    "../../../../locale/Dictionary"
],
function(
    declare,
    lang,
    topic,
    TextArea,
    _HelpMixin,
    _AttributeWidgetMixin,
    Model,
    Dict
) {
    return declare([TextArea, _HelpMixin, _AttributeWidgetMixin], {

        intermediateChanges: true,
        entity: {},
        attribute: {},
        original: {},

        constructor: function(args) {
            declare.safeMixin(this, args);

            var typeClass = Model.getTypeFromOid(this.entity.oid);

            this.label = Dict.translate(this.attribute.name);
            this.disabled = typeClass ? !typeClass.isEditable(this.attribute, this.entity) : false;
            this.name = this.attribute.name;
            this.value = this.entity[this.attribute.name];
            this.helpText = Dict.translate(this.attribute.description);
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