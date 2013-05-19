define([
    "dojo/_base/declare",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dojomat/_AppAware",
    "dojomat/_StateAware",
    "../_include/_PageMixin",
    "../_include/_NotificationMixin",
    "../_include/widget/NavigationWidget",
    "../../model/meta/Model",
    "dojo/text!./template/HomePage.html"
], function (
    declare,
    _WidgetBase,
    _TemplatedMixin,
    _AppAware,
    _StateAware,
    _Page,
    _Notification,
    NavigationWidget,
    Model,
    template
) {
    return declare([_WidgetBase, _TemplatedMixin, _AppAware, _StateAware, _Page, _Notification], {

        request: null,
        session: null,
        templateString: template,

        constructor: function(params) {
            this.request = params.request;
            this.session = params.session;
        },

        postCreate: function() {
            this.inherited(arguments);
            this.setTitle(appConfig.title+' - Home');

            var navi = new NavigationWidget({
            }, this.navigationNode);
            navi.setActiveRoute("home");

            dojo.query("#title").attr("innerHTML", appConfig.title);
        },

        _navigateContent: function(e) {
            // prevent the page from navigating after submit
            e.preventDefault();

            var type = Model.getSimpleTypeName(appConfig.rootTypes[0]);
            var route = this.router.getRoute("entityList");
            var pathParams = { type:type };
            var url = route.assemble(pathParams);
            this.push(url);
        }
    });
});