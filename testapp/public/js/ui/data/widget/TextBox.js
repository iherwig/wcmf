define( [
    "dojo/_base/declare",
    "dijit/form/TextBox",
    "dojo/text!./template/TextBox.html"
],
function(
    declare,
    TextBox,
    template
) {
    return declare([TextBox], {

        templateString: template,
        nodeData: {},
        attribute: {},

        constructor: function(args) {
            declare.safeMixin(this, args);

            this.label = this.attribute.name;
            this.disabled = !this.attribute.isEditable;
            this.name = this.attribute.name;
            this.value = this.nodeData[this.attribute.name];
        }
    });
});