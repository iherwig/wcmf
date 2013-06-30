define([
    "require",
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dijit/_WidgetsInTemplateMixin",
    "../_include/_PageMixin",
    "../../locale/Dictionary",
    "dojo/text!./template/NotFoundPage.html"
], function (
    require,
    declare,
    lang,
    _WidgetBase,
    _TemplatedMixin,
    _WidgetsInTemplateMixin,
    _Page,
    Dict,
    template
) {
    return declare([_WidgetBase, _TemplatedMixin, _WidgetsInTemplateMixin, _Page], {

        templateString: lang.replace(template, Dict.tplTranslate),
        contextRequire: require,

        postCreate: function () {
            this.inherited(arguments);
            this.setTitle(Dict.translate('Page not found'));
            this.messageNode.innerHTML = Dict.translate('Page not found');
        },

        startup: function () {
            this.inherited(arguments);
        }
    });
});