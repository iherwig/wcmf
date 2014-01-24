define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dojo/dom-class",
    "../../../locale/Dictionary",
    "dojo/text!./template/NotificationWidget.html"
], function (
    declare,
    lang,
    _WidgetBase,
    _TemplatedMixin,
    domClass,
    Dict,
    template
) {
    return declare([_WidgetBase, _TemplatedMixin], {

        templateString: lang.replace(template, Dict.tplTranslate),

        _setContentAttr: function (val) {
            this.contentNode.innerHTML = '<i class="fa fa-exclamation-circle"></i> '+val;
        },

        _setClassAttr: function (val) {
            domClass.add(this.domNode, val);
        }
    });
});