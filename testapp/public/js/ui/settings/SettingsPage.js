define([
    "require",
    "dojo/_base/declare",
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
    "dojo/text!./template/SettingsPage.html"
], function (
    require,
    declare,
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
    template
) {
    return declare([_WidgetBase, _TemplatedMixin, _WidgetsInTemplateMixin, _AppAware, _StateAware, _Page, _Notification], {

        request: null,
        session: null,
        templateString: template,
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