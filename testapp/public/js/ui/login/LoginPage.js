define([
    "require",
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/request",
    "dojo/dom-form",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dijit/_WidgetsInTemplateMixin",
    "dijit/form/TextBox",
    "dojomat/_AppAware",
    "dojomat/_StateAware",
    "../_include/_PageMixin",
    "../_include/_NotificationMixin",
    "../_include/widget/NavigationWidget",
    "../_include/widget/Button",
    "../../Cookie",
    "../../locale/Dictionary",
    "dojo/text!./template/LoginPage.html"
], function (
    require,
    declare,
    lang,
    request,
    domForm,
    _WidgetBase,
    _TemplatedMixin,
    _WidgetsInTemplateMixin,
    TextBox,
    _AppAware,
    _StateAware,
    _Page,
    _Notification,
    NavigationWidget,
    Button,
    Cookie,
    Dict,
    template
) {
    return declare([_WidgetBase, _TemplatedMixin, _WidgetsInTemplateMixin, _AppAware, _StateAware, _Page, _Notification], {

        request: null,
        session: null,
        templateString: lang.replace(template, Dict.tplTranslate),
        contextRequire: require,

        constructor: function(params) {
            this.request = params.request;
            this.session = params.session;
        },

        postCreate: function() {
            this.inherited(arguments);
            this.setTitle(appConfig.title+' - '+Dict.translate('Login'));

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

            this.loginBtn.setProcessing();

            this.hideNotification();
            request.post("main.php", {
                data: data,
                headers: {
                    "Accept" : "application/json"
                },
                handleAs: 'json'

            }).then(lang.hitch(this, function(response) {
                // callback completes
                this.loginBtn.reset();
                if (!response.success) {
                    // error
                    this.showNotification({
                        type: "error",
                        message: response.errorMessage || Dict.translate("Backend error")
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
                this.loginBtn.reset();
                this.showNotification({
                    type: "error",
                    message: error.response.data.errorMessage || error.message || Dict.translate("Backend error")
                });
            }));
        }
    });
});