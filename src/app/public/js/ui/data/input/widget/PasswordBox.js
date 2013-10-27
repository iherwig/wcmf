define( [
    "dojo/_base/declare",
    "./TextBox"
],
function(
    declare,
    TextBox
) {
    return declare([TextBox], {

        type: "password",

        postCreate: function() {
            this.inherited(arguments);
        },

        _setValueAttr: function(value) {
            this.value = "";
        }
    });
});