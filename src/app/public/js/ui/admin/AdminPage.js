define([
    "require",
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/request",
    "../_include/_PageMixin",
    "../_include/_NotificationMixin",
    "../_include/widget/NavigationWidget",
    "../_include/FormLayout",
    "../_include/widget/Button",
    "../../locale/Dictionary",
    "dojo/text!./template/AdminPage.html"
], function (
    require,
    declare,
    lang,
    request,
    _Page,
    _Notification,
    NavigationWidget,
    FormLayout,
    Button,
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
            this.doCall("", "indexAll");

            this.hideNotification();
        },

        handleResponse: function(response) {
            var stepNumber = parseInt(response['stepNumber']);
            var numberOfSteps = parseInt(response['numberOfSteps']);
            var stepName = response['displayText'];
            var controller = response['controller'];

            if (response.action === "done") {
                // call the success handler if the task is finished
                if (this.successHandler instanceof Function) {
                    this.successHandler(response);
                }
            }
            else {
                if (this.processHandler instanceof Function) {
                    this.processHandler(stepName, stepNumber, numberOfSteps, response);
                }

                // do the proceeding calls
                this.doCall(controller, "continue");
            }
        },

        processHandler: function(stepName, stepNumber, numberOfSteps, response) {
            console.log(stepName+" "+stepNumber+"/"+numberOfSteps);
        },

        successHandler: function(response) {
            this.indexBtn.reset();
        },

        doCall: function(controller, action) {
            request.post(appConfig.backendUrl, {
                data: {
                    controller: controller,
                    action: action
                },
                headers: {
                    "Accept" : "application/json"
                },
                handleAs: 'json'

            }).then(lang.hitch(this, function(response) {
                // callback completes
                if (!response.success) {
                    // error
                    this.showBackendError(response);
                }
                else {
                    // success
                    this.handleResponse(response);
                }
            }), lang.hitch(this, function(error) {
                // error
                this.indexBtn.reset();
                this.showBackendError(error);
            }));
        }
    });
});