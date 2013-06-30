define([
    "require",
    "dojo/_base/lang",
    "dojo/_base/declare",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dijit/_WidgetsInTemplateMixin",
    "../_include/_PageMixin",
    "../_include/_NotificationMixin",
    "../_include/widget/NavigationWidget",
    "../../model/meta/Model",
    "../../locale/Dictionary",
    "dojo/text!./template/HomePage.html"
], function (
    require,
    lang,
    declare,
    _WidgetBase,
    _TemplatedMixin,
    _WidgetsInTemplateMixin,
    _Page,
    _Notification,
    NavigationWidget,
    Model,
    Dict,
    template
) {
    return declare([_WidgetBase, _TemplatedMixin, _WidgetsInTemplateMixin, _Page, _Notification], {

        templateString: lang.replace(template, Dict.tplTranslate),
        contextRequire: require,

        postCreate: function() {
            this.inherited(arguments);
            this.setTitle(appConfig.title+' - '+Dict.translate('Home'));

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