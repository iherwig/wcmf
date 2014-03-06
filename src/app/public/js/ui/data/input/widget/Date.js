define( [
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/topic",
    "dojo/on",
    "dojo/dom-attr",
    "dijit/_TemplatedMixin",
    "bootstrap/Datepicker",
    "../../../_include/_HelpMixin",
    "../../../../locale/Dictionary",
    "dojo/text!./template/Date.html"
],
function(
    declare,
    lang,
    topic,
    on,
    domAttr,
    _TemplatedMixin,
    Datepicker,
    HelpIcon,
    Dict,
    template
) {
    return declare([Datepicker, _TemplatedMixin, HelpIcon], {

        templateString: lang.replace(template, Dict.tplTranslate),
        intermediateChanges: true,
        format: Dict.translate("dd.MM.yyyy"),
        trigger: "focus",
        entity: {},
        attribute: {},
        original: {},

        constructor: function(args) {
            declare.safeMixin(this, args);

            this.label = Dict.translate(this.attribute.name);
            this.disabled = !this.attribute.isEditable;
            this.name = this.attribute.name;
            this.value = this.entity[this.attribute.name];
            this.helpText = Dict.translate(this.attribute.description);
        },

        postCreate: function() {
            this.inherited(arguments);
            domAttr.set(this.inputNode, "value", this.value);

            // subscribe to entity change events to change tab links
            this.own(
                topic.subscribe("entity-datachange", lang.hitch(this, function(data) {
                    if (data.name === this.attribute.name) {
                        domAttr.set(this.inputNode, "value", data.newValue);
                    }
                }),
                this.on("change", lang.hitch(this, function(e) {
                    this.set("value", e.formattedDate);
                }))
            ));
        }
    });
});