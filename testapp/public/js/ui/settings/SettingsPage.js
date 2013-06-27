define([
    "require",
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dijit/_WidgetsInTemplateMixin",
    "dojomat/_AppAware",
    "dojomat/_StateAware",
    "../_include/_PageMixin",
    "../_include/_NotificationMixin",
    "../_include/widget/NavigationWidget",
    "../_include/FormLayout",
    "../_include/widget/Button",
    "../../locale/Dictionary",
    "dojo/text!./template/SettingsPage.html"
], function (
    require,
    declare,
    lang,
    _WidgetBase,
    _TemplatedMixin,
    _WidgetsInTemplateMixin,
    _AppAware,
    _StateAware,
    _Page,
    _Notification,
    NavigationWidget,
    FormLayout,
    Button,
    Dictionary,
    template
) {
    return declare([_WidgetBase, _TemplatedMixin, _WidgetsInTemplateMixin, _AppAware, _StateAware, _Page, _Notification], {

        request: null,
        session: null,
        templateString: lang.replace(template, Dictionary),
        contextRequire: require,

        constructor: function(params) {
            this.request = params.request;
            this.session = params.session;
        },

        postCreate: function() {
            this.inherited(arguments);
            this.setTitle(appConfig.title+' - Settings');

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