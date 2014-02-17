define([
    "require",
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/request",
    "../_include/_PageMixin",
    "../_include/_NotificationMixin",
    "../_include/widget/NavigationWidget",
    "../../Cookie",
    "../../locale/Dictionary",
    "dojo/text!./template/LogoutPage.html"
], function (
    require,
    declare,
    lang,
    request,
    _Page,
    _Notification,
    NavigationWidget,
    Cookie,
    Dict,
    template
) {
    return declare([_Page, _Notification], {

        templateString: lang.replace(template, Dict.tplTranslate),
        contextRequire: require,
        title: Dict.translate('Logout'),

        startup: function() {
            this.inherited(arguments);
            this._logout();
        },

        _logout: function() {
            var data = {};
            data.action = "logout";

            request.post(appConfig.backendUrl, {
                data: data,
                headers: {
                    Accept: "application/json"
                },
                handleAs: "json"

            }).then(lang.hitch(this, function(response){
                // redirect to login
                Cookie.destroy();
                var route = this.router.getRoute("login");
                var url = route.assemble();
                window.document.location.href = url;
            }), lang.hitch(this, function(error){
                // error
                query(".btn").button("reset");
                this.showBackendError(error);
            }));
        }
    });
});