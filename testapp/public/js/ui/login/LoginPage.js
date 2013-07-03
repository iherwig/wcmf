define([
    "require",
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/request",
    "dojo/dom-form",
    "dijit/form/TextBox",
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
    TextBox,
    _Page,
    _Notification,
    NavigationWidget,
    Button,
    Cookie,
    Dict,
    template
) {
    return declare([_Page, _Notification], {

        templateString: lang.replace(template, Dict.tplTranslate),
        contextRequire: require,
        title: Dict.translate('Login'),

        postCreate: function() {
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
            request.post(appConfig.backendUrl, {
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