define([
    "require",
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dijit/_WidgetsInTemplateMixin",
    "../_include/_PageMixin",
    "../_include/_NotificationMixin",
    "../_include/widget/NavigationWidget",
    "../_include/FormLayout",
    "../_include/widget/Button",
    "../../locale/Dictionary",
    "dojo/text!./template/AdminPage.html"
], function (
    require,
    declare,
    lang,
    _WidgetBase,
    _TemplatedMixin,
    _WidgetsInTemplateMixin,
    _Page,
    _Notification,
    NavigationWidget,
    FormLayout,
    Button,
    Dict,
    template
) {
    return declare([_WidgetBase, _TemplatedMixin, _WidgetsInTemplateMixin, _Page, _Notification], {

        templateString: lang.replace(template, Dict.tplTranslate),
        contextRequire: require,

        postCreate: function() {
            this.inherited(arguments);
            this.setTitle(appConfig.title+' - '+Dict.translate('Settings'));

            var navi = new NavigationWidget({
            }, this.navigationNode);
            navi.setActiveRoute("settings");
            navi.startup();

            dojo.query("#title").attr("innerHTML", appConfig.title);
        },

        _save: function() {

        }
    });
});