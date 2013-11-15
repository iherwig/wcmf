define( [
    "dojo/_base/declare",
    "dojo/dom-construct",
    "dijit/form/CheckBox",
    "./_BinaryItemsControl",
    "../../../_include/_HelpMixin"
],
function(
    declare,
    domConstruct,
    CheckBox,
    _BinaryItemsControl,
    HelpIcon
) {
    return declare([_BinaryItemsControl, HelpIcon], {

        multiValued: true,

        buildItemWidget: function(item) {
            var itemId = this.store.getIdentity(item);
            var itemLabel = item.displayText;

            // create checkbox
            var widget = new CheckBox({
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