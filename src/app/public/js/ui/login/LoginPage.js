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
    "../../User",
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
    User,
    Dict,
    template
) {
    return declare([_Page, _Notification], {

        templateString: lang.replace(template, Dict.tplTranslate),
        contextRequire: require,
        title: Dict.translate('Login'),

        constructor: function (params) {
            // template variables
            this.title = appConfig.title;
        },

        postCreate: function() {
            this.inherited(arguments);
        },

        _login: function(e) {
            // prevent the page from navigating after submit
            e.preventDefault();

            var data = domForm.toObject("loginForm");
            data.action = "login";

            this.loginBtn.setProcessing();

            this.hideNotification();
            request.post(appConfig.backendUrl, {
                data: data,
                headers: {
                    Accept: "application/json"
                },
                handleAs: 'json'

            }).then(lang.hitch(this, function(response) {
                // success
                this.loginBtn.reset();
                User.create(data.user, response.roles);

                // redirect to initially requested route if given
                var redirectRoute = this.request.getQueryParam("route");
                if (redirectRoute) {
                    window.location.href = this.request.getPathname()+redirectRoute;
                }
                else {
                    // redirect to default route
                    var route = this.router.getRoute("home");
                    var url = route.assemble();
                    this.pushState(url);
                }
            }), lang.hitch(this, function(error) {
                // error
                this.loginBtn.reset();
                this.showBackendError(error);
            }));
        }
    });
});