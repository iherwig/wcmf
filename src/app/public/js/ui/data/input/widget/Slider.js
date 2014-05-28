define( [
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/topic",
    "dijit/form/HorizontalSlider",
    "../Factory",
    "../../../_include/_HelpMixin",
    "./_AttributeWidgetMixin",
    "../../../../model/meta/Model",
    "../../../../locale/Dictionary"
],
function(
    declare,
    lang,
    topic,
    HorizontalSlider,
    ControlFactory,
    _HelpMixin,
    _AttributeWidgetMixin,
    Model,
    Dict
) {
    return declare([HorizontalSlider, _HelpMixin, _AttributeWidgetMixin], {

        intermediateChanges: true,
        showButtons: false,
        entity: {},
        attribute: {},
        original: {},

        dateFormat: {selector: 'date', datePattern: 'yyyy-MM-dd HH:mm:ss', locale: appConfig.uiLanguage},

        constructor: function(args) {
            declare.safeMixin(this, args);

            var typeClass = Model.getTypeFromOid(this.entity.oid);

            this.label = Dict.translate(this.attribute.name);
            this.disabled = typeClass ? !typeClass.isEditable(this.attribute, this.entity) : false;
            this.name = this.attribute.name;
            this.value = this.entity[this.attribute.name];
            this.helpText = Dict.translate(this.attribute.description);

            var options = ControlFactory.getOptions(this.attribute.inputType);
            this.minimum = options.min ? options.min : 0;
            this.maximum = options.max ? options.max : 100;
            this.discreteValues = options.step ? parseInt((this.maximum-this.minimum)/options.step) : (this.maximum-this.minimum);
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