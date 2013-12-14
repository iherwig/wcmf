define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/aspect",
    "dojo/query",
    "dojo/request",
    "dojo/dom-class"
], function (
    declare,
    lang,
    aspect,
    query,
    request,
    domClass
) {
    /**
     * Process wrapper class. A process is typically executed
     * in the backend by a subclass of BatchController.
     */
    return declare([], {

        callback: null,
        errback: null,
        progback: null,

        /**
         * Constructor
         * @param callback Function to be called on success (optional)
         * @param errback Function to be called on error (optional)
         * @param progback Function to be called to signal a progress (optional)
         */
        constructor: function(args) {
            declare.safeMixin(this, args);
        },

        /**
         * Initiate the process with the initial action.
         * @param action The action to be called on the backend
         */
        run: function(action) {
            this.doCall(action, "");
        },

        /**
         * Make a backend call.
         * @param action The action to be called on the backend
         * @param controller The controller that initiates the action
         */
        doCall: function(action, controller) {
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
                // success
                this.handleResponse(response);
            }), lang.hitch(this, function(error) {
                // error
                if (this.errback instanceof Function) {
                    this.errback(error);
                }
            }));
        },

        /**
         * Handle the response from the backend
         * @param response The response
         */
        handleResponse: function(response) {
            var stepNumber = parseInt(response['stepNumber']);
            var numberOfSteps = parseInt(response['numberOfSteps']);
            var stepName = response['displayText'];
            var controller = response['controller'];

            if (response.action === "done") {
                // call the success handler if the task is finished
                if (this.callback instanceof Function) {
                    this.callback(response);
                }
            }
            else {
                if (this.progback instanceof Function) {
                    this.progback(stepName, stepNumber, numberOfSteps, response);
                }

                // do the proceeding calls
                this.doCall("continue", controller);
            }
        }
    });
});
