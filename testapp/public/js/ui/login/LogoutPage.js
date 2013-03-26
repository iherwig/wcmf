define([
    "dojo/_base/declare",
    "dijit/_WidgetBase",
    "dojomat/_AppAware",
    "dojomat/_StateAware",
    "../_include/_PageMixin",
    "../_include/_NotificationMixin",
    "dojo/_base/lang",
    "dojo/request"
], function (
    declare,
    _WidgetBase,
    _AppAware,
    _StateAware,
    _Page,
    _Notification,
    lang,
    request
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
                var route = this.router.getRoute("login");
                var url = route.assemble();
                window.document.location.href = url;
            }));
        }
    });
});