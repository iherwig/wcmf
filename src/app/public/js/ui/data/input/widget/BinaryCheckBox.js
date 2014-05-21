define( [
    "dojo/_base/declare",
    "dijit/form/CheckBox",
    "../../../../model/meta/Model",
    "../../../../locale/Dictionary",
    "../../../_include/_HelpMixin",
    "./_AttributeWidgetMixin"
],
function(
    declare,
    CheckBox,
    Model,
    Dict,
    _HelpMixin,
    _AttributeWidgetMixin
) {
    return declare([CheckBox, _HelpMixin, _AttributeWidgetMixin], {

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
            this.checked = this.value == 1; // value may be string or number
            this.helpText = Dict.translate(this.attribute.description);
        },

        _getValueAttr: function() {
            return this.get("checked") ? "1" : "0";
        }
    });
});