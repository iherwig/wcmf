define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/request",
    "dijit/_WidgetBase",
    "dojomat/_AppAware",
    "dojomat/_StateAware",
    "../_include/_PageMixin",
    "../_include/_NotificationMixin",
    "../../Cookie",
    "../../locale/Dictionary"
], function (
    declare,
    lang,
    request,
    _WidgetBase,
    _AppAware,
    _StateAware,
    _Page,
    _Notification,
    Cookie,
    Dict
) {
    return declare([_WidgetBase, _AppAware, _StateAware, _Page, _Notification], {

        request: null,
        session: null,

        constructor: function(params) {
            this.request = params.request;
            this.session = params.session;
        },

        startup: function() {
            this.inherited(arguments);
            this._logout();
        },

        _logout: function() {
            var data = {};
            data.controller = "wcmf\\application\\controller\\LoginController";
            data.action = "logout";

            request.post("main.php", {
                data: data,
                headers: {
                    "Accept" : "application/json"
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
                this.showNotification({
                    type: "error",
                    message: Dict.translate("Backend error")
                });
            }));
        }
    });
});