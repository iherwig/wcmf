define( [
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/topic",
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
    _TemplatedMixin,
    Datepicker,
    HelpIcon,
    Dict,
    template
) {
    return declare([Datepicker, _TemplatedMixin, HelpIcon], {

        templateString: lang.replace(template, Dict.tplTranslate),
        intermediateChanges: true,
        format: Dict.translate("dd.M.yyyy"),
        trigger: "click",
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

            // subscribe to entity change events to change tab links
            this.own(
                topic.subscribe("entity-datachange", lang.hitch(this, function(data) {
                    if (data.name === this.attribute.name) {
                        this.set("value", data.newValue);
                    }
                })
            ));
        }
    });
});