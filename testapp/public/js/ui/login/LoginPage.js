define([
    "dojo/_base/declare",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dojomat/_AppAware",
    "dojomat/_StateAware",
    "../_include/_PageMixin",
    "../_include/_NotificationMixin",
    "../_include/widget/NavigationWidget",
    "../../Cookie",
    "bootstrap/Button",
    "dojo/_base/lang",
    "dojo/dom-form",
    "dojo/query",
    "dojo/request",
    "dojo/text!./template/LoginPage.html",
], function (
    declare,
    _WidgetBase,
    _TemplatedMixin,
    _AppAware,
    _StateAware,
    _Page,
    _Notification,
    NavigationWidget,
    Cookie,
    button,
    lang,
    domForm,
    query,
    request,
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
            this.setTitle(appConfig.title+' - Login');
            new NavigationWidget({
                titleOnly: true
            }, this.navigationNode);

            this.setupRoutes();
        },

        startup: function() {
            this.inherited(arguments);
        },

        _login: function(e) {
            // prevent the page from navigating after submit
            e.preventDefault();

            var data = domForm.toObject("loginForm");
            data.controller = "wcmf\\application\\controller\\LoginController";
            data.action = "login";

            query(".btn").button("loading");

            this.hideNotification();
            request.post("main.php", {
                data: data,
                headers: {
                    "Accept" : "application/json"
                },
                handleAs: 'json'

            }).then(lang.hitch(this, function(response) {
                // callback completes
                if (!response.success) {
                    // error
                    query(".btn").button("reset");
                    this.showNotification({
                        type: "error",
                        message: response.errorMessage || "Backend error"
                    });
                }
                else {
                    // success
                    Cookie.set("user", data.user);

                    // redirect to initially requested route if given
                    var redirectRoute = this.request.getQueryParam("route");
                    if (redirectRoute) {
                        window.location.href = this.request.getPathname()+redirectRoute;
                    }
                    else {
                        // redirect to default route
                        var route = this.router.getRoute("home");
                        var url = route.assemble();
                        this.push(url);
                    }
                }
            }), lang.hitch(this, function(error) {
                // error
                query(".btn").button("reset");
                this.showNotification({
                    type: "error",
                    message: "Backend error"
                });
            }));
        }
    });
});