define( [
    "dojo/_base/declare",
    "./TextBox"
],
function(
    declare,
    TextBox
) {
    return declare([TextBox], {

        type: "password"
    });
});