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
    "../_include/_PageMixin",
    "../_include/_NotificationMixin",
    "../_include/widget/NavigationWidget",
    "../_include/FormLayout",
    "../_include/widget/Button",
    "../../locale/Dictionary",
    "dojo/text!./template/SettingsPage.html"
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
    _Page,
    _Notification,
    NavigationWidget,
    FormLayout,
    Button,
    Dict,
    template
) {
    return declare([_WidgetBase, _TemplatedMixin, _WidgetsInTemplateMixin, _Page, _Notification], {

        templateString: lang.replace(template, Dict.tplTranslate),
        contextRequire: require,

        postCreate: function() {
            this.inherited(arguments);
            this.setTitle(appConfig.title+' - '+Dict.translate('Settings'));

            var navi = new NavigationWidget({
            }, this.navigationNode);
            navi.setActiveRoute("settings");
            navi.startup();

            dojo.query("#title").attr("innerHTML", appConfig.title);
        },

        _save: function(e) {
            // prevent the page from navigating after submit
            e.preventDefault();

            var data = domForm.toObject("settingsForm");
            data.controller = "wcmf\\application\\controller\\UserController";
            data.action = "save";

            this.saveBtn.setProcessing();

            this.hideNotification();
            request.post(appConfig.backendUrl, {
                data: data,
                headers: {
                    "Accept" : "application/json"
                },
                handleAs: 'json'

            }).then(lang.hitch(this, function(response) {
                // callback completes
                this.saveBtn.reset();
                if (!response.success) {
                    // error
                    this.showNotification({
                        type: "error",
                        message: response.errorMessage || Dict.translate("Backend error")
                    });
                }
                else {
                    // success
                    this.showNotification({
                        type: "ok",
                        message: Dict.translate("The password was successfully changed"),
                        fadeOut: true
                    });
                }
            }), lang.hitch(this, function(error) {
                // error
                this.saveBtn.reset();
                this.showNotification({
                    type: "error",
                    message: error.response.data.errorMessage || error.message || Dict.translate("Backend error")
                });
            }));
        }
    });
});