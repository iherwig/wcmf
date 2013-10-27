define([
    "require",
    "dojo/_base/declare",
    "dojo/_base/lang",
    "../_include/widget/NavigationWidget",
    "../_include/_PageMixin",
    "../../locale/Dictionary",
    "dojo/text!./template/ErrorPage.html"
], function (
    require,
    declare,
    lang,
    NavigationWidget,
    _Page,
    Dict,
    template
) {
    return declare([_Page], {

        error: null,
        templateString: lang.replace(template, Dict.tplTranslate),
        contextRequire: require,
        title: Dict.translate('Error'),

        constructor: function (params) {
            this.error = params.error;
        },

        postCreate: function () {
            this.inherited(arguments);
            this.messageNode.innerHTML = this.error.message;
        }
    });
});