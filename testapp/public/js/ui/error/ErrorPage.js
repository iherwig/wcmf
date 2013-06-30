define([
    "require",
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dijit/_WidgetsInTemplateMixin",
    "../_include/_PageMixin",
    "../../locale/Dictionary",
    "dojo/text!./template/ErrorPage.html"
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

        error: null,
        templateString: lang.replace(template, Dict.tplTranslate),
        contextRequire: require,

        constructor: function (params) {
            this.error = params.error;
        },

        postCreate: function () {
            this.inherited(arguments);
            this.setTitle(appConfig.title+' - '+Dict.translate('Error'));
            this.messageNode.innerHTML = this.error.message;
        },

        startup: function () {
            this.inherited(arguments);
        }
    });
});