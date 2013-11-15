define( [
    "dojo/_base/declare",
    "dojo/dom-construct",
    "dijit/form/RadioButton",
    "./_BinaryItemsControl",
    "../../../_include/_HelpMixin"
],
function(
    declare,
    domConstruct,
    RadioButton,
    _BinaryItemsControl,
    HelpIcon
) {
    return declare([_BinaryItemsControl, HelpIcon], {

        multiValued: false,

        buildItemWidget: function(item) {
            var itemId = this.store.getIdentity(item);
            var itemLabel = item.displayText;

            // create radio button
            var widget = new RadioButton({
                name: this.name,
                value: ""+itemId,
                checked: (this.value == itemId) // value may be string or number
            });
            widget.startup();
            this.addChild(widget);

            // create label
            domConstruct.create("span", {
                innerHTML: itemLabel,
                "class": "checkBoxLabel"
            }, widget.domNode, "after");

            return widget;
        }
    });
});