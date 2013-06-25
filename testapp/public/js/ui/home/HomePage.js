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
    "../../model/meta/Model",
    "dojo/text!./template/HomePage.html"
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
    Model,
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
            this.setTitle(appConfig.title+' - Home');

            var navi = new NavigationWidget({
            }, this.navigationNode);
            navi.setActiveRoute("home");
            navi.startup();

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
        },

        _navigateMedia: function(e) {
            // prevent the page from navigating after submit
            e.preventDefault();

            var route = this.router.getRoute("media");
            var url = route.assemble();
            window.open(url, '_blank', 'width=800,height=700');
        }
    });
});