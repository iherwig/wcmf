define([
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
    "../../Cookie",
    "dijit/form/TextBox",
    "dijit/Dialog",
    "dijit/form/Button",
    "dojo/dom-form",
    "dojo/query",
    "dojo/request",
    "dojo/text!./template/LoginPage.html"
], function (
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
    Cookie,
    TextBox,
    Dialog,
    Button,
    domForm,
    query,
    request,
    template
) {
    return declare([_WidgetBase, _TemplatedMixin, _WidgetsInTemplateMixin, _AppAware, _StateAware, _Page, _Notification], {

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

            var navi = new NavigationWidget({
                titleOnly: true
            }, this.navigationNode);
            navi.startup();
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

            var oldBtnLabel = this.loginBtn.get("label");
              this.loginBtn.set("label", oldBtnLabel+' <i class="icon-spinner icon-spin"></i>');
            this.loginBtn.set("disabled", true);

            this.hideNotification();
            request.post("main.php", {
                data: data,
                headers: {
                    "Accept" : "application/json"
                },
                handleAs: 'json'

            }).then(lang.hitch(this, function(response) {
                // callback completes
                this.loginBtn.set("label", oldBtnLabel);
                this.loginBtn.set("disabled", false);
                if (!response.success) {
                    // error
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
                this.loginBtn.set("label", oldBtnLabel);
                this.loginBtn.set("disabled", false);
                this.showNotification({
                    type: "error",
                    message: error.response.data.errorMessage || error.message || "Backend error"
                });
            }));
        }
    });
});