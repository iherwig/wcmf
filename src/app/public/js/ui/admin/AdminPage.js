define([
    "require",
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/dom-construct",
    "../_include/_PageMixin",
    "../_include/_NotificationMixin",
    "../_include/widget/NavigationWidget",
    "../_include/FormLayout",
    "../_include/widget/Button",
    "../../action/Process",
    "../../locale/Dictionary",
    "dojo/text!./template/AdminPage.html"
], function (
    require,
    declare,
    lang,
    domConstruct,
    _Page,
    _Notification,
    NavigationWidget,
    FormLayout,
    Button,
    Process,
    Dict,
    template
) {
    return declare([_Page, _Notification], {

        templateString: lang.replace(template, Dict.tplTranslate),
        contextRequire: require,
        title: Dict.translate('Settings'),

        postCreate: function() {
            this.inherited(arguments);
        },

        _index: function(e) {
            // prevent the page from navigating after submit
            e.preventDefault();

            this.indexBtn.setProcessing();
            this.hideNotification();

            var process = new Process({
                callback: lang.hitch(this, this.successHandler),
                errback: lang.hitch(this, this.errorHandler),
                progback: lang.hitch(this, this.progressHandler)
            });
            process.run("indexAll");
        },

        successHandler: function(response) {
            this.indexBtn.reset();
            this.showNotification({
                type: "ok",
                message: Dict.translate("The search index was successfully updated."),
                fadeOut: true,
                onHide: lang.hitch(this, function () {
                    domConstruct.empty(this.statusNode);
                })
            });
        },

        errorHandler: function(error) {
            this.indexBtn.reset();
            this.showBackendError(error);
        },

        progressHandler: function(stepName, stepNumber, numberOfSteps, response) {
            var text = domConstruct.toDom("<p>"+stepName+"</p>");
            domConstruct.place(text, this.statusNode, "only");
        }
    });
});