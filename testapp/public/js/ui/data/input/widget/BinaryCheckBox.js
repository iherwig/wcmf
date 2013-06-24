define( [
    "dojo/_base/declare",
    "dijit/form/CheckBox"
],
function(
    declare,
    CheckBox
) {
    return declare([CheckBox], {

        entity: {},
        attribute: {},
        original: {},

        constructor: function(args) {
            declare.safeMixin(this, args);

            this.label = this.attribute.name;
            this.disabled = !this.attribute.isEditable;
            this.name = this.attribute.name;
            this.value = this.entity[this.attribute.name];
            this.checked = this.value == 1; // value may be string or number
        },

        _getValueAttr: function() {
            return this.get("checked") ? "1" : "0";
        }
    });
});