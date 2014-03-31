define( [
    "dojo/_base/declare",
    "dijit/form/CheckBox",
    "../../../../locale/Dictionary",
    "../../../_include/_HelpMixin",
    "./_AttributeWidgetMixin"
],
function(
    declare,
    CheckBox,
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

            this.label = Dict.translate(this.attribute.name);
            this.disabled = !this.attribute.isEditable;
            this.name = this.attribute.name;
            this.value = this.entity[this.attribute.name];
            this.checked = this.value === 1; // value may be string or number
            this.helpText = Dict.translate(this.attribute.description);
        },

        _getValueAttr: function() {
            return this.get("checked") ? "1" : "0";
        }
    });
});