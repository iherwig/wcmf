define([
    "require",
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/request",
    "dojo/json",
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
    request,
    json,
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

            this.startProcess("indexAll", this.indexBtn,
                Dict.translate("The search index was successfully updated."));
        },

        _export: function(e) {
            // prevent the page from navigating after submit
            e.preventDefault();

            this.startProcess("exportAll", this.exportBtn,
                Dict.translate("The content was successfully exported."));
        },

        startProcess: function(action, btn, message) {
            btn.setProcessing();
            this.hideNotification();
            var process = new Process({
                callback: lang.hitch(this, lang.partial(this.finishProcess, btn, message)),
                errback: lang.hitch(this, this.errorHandler),
                progback: lang.hitch(this, this.progressHandler)
            });
            process.run(action);
        },

        finishProcess: function(btn, message) {
            btn.reset();
            this.showNotification({
                type: "ok",
                message: message,
                fadeOut: true,
                onHide: lang.hitch(this, function () {
                    domConstruct.empty(this.statusNode);
                })
            });
        },

        errorHandler: function(error) {
            this.indexBtn.reset();
            this.exportBtn.reset();
            this.showBackendError(error);
        },

        progressHandler: function(stepName, stepNumber, numberOfSteps, response) {
            var text = domConstruct.toDom("<p>"+stepName+"</p>");
            domConstruct.place(text, this.statusNode, "only");
        }
    });
});