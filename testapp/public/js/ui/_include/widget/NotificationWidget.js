define([
    "dojo/_base/declare",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dojo/dom-class",
    "dojo/text!./template/NotificationWidget.html"
], function (
    declare,
    _WidgetBase,
    _TemplatedMixin,
    domClass,
    template
) {
    return declare([_WidgetBase, _TemplatedMixin], {

        templateString: template,

        _setContentAttr: function (val) {
            this.contentNode.innerHTML = '<i class="icon-exclamation-sign"></i> '+val;
        },

        _setClassAttr: function (val) {
            domClass.add(this.domNode, val);
        }
    });
});