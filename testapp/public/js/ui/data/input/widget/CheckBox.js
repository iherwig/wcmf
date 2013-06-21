define( [
    "dojo/_base/declare",
    "dojo/dom-construct",
    "dijit/form/CheckBox",
    "./_BinaryItemsControl"
],
function(
    declare,
    domConstruct,
    CheckBox,
    _BinaryItemsControl
) {
    return declare([_BinaryItemsControl], {

        multiValued: false,

        buildItemWidget: function(item) {
            // create checkbox
            var widget = new CheckBox({
                name: this.name,
                value: ""+item.id,
                checked: (this.value == item.id) // value may be string or number
            });
            widget.startup();
            this.addChild(widget);

            // create label
            domConstruct.create("span", {
                innerHTML: item.name,
                class: "checkBoxLabel"
            }, widget.domNode, "after");

            return widget;
        }
    });
});