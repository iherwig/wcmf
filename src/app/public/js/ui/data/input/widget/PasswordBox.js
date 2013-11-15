define( [
    "dojo/_base/declare",
    "./TextBox",
    "../../../_include/_HelpMixin",
],
function(
    declare,
    TextBox,
    HelpIcon
) {
    return declare([TextBox, HelpIcon], {

        type: "password",

        postCreate: function() {
            this.inherited(arguments);
        },

        _setValueAttr: function(value) {
            this.value = "";
        }
    });
});