define([
    "dojo/_base/declare",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "bootstrap/Alert",
    "dojo/dom-class",
    "dojo/text!./template/ConfirmDlgWidget.html"
], function (
    declare,
    _WidgetBase,
    _TemplatedMixin,
    Alert,
    domClass,
    template
) {
    return declare([_WidgetBase, _TemplatedMixin], {

        templateString: template,

        _setHeaderAttr: function (val) {
            this.headerNode.innerHTML = val;
        },

        _setContentAttr: function (val) {
            this.contentNode.innerHTML = val;
        }
    });
});