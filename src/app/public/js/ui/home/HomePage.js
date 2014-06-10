define([
    "require",
    "dojo/_base/lang",
    "dojo/_base/declare",
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
    _Page,
    _Notification,
    NavigationWidget,
    Model,
    Dict,
    template
) {
    return declare([_Page, _Notification], {

        templateString: lang.replace(template, Dict.tplTranslate),
        contextRequire: require,
        title: Dict.translate('Home'),

        _navigateContent: function(e) {
            // prevent the page from navigating after submit
            e.preventDefault();

            var type = Model.getSimpleTypeName(appConfig.rootTypes[0]);
            var route = this.router.getRoute("entityList");
            var pathParams = { type:type };
            var url = route.assemble(pathParams);
            this.pushState(url);
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