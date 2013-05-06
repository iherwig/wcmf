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
        label: ""
    });
});