define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/_base/fx",
    "dojo/dom-construct",
    "../../locale/Dictionary",
    "./widget/NotificationWidget"
], function (
    declare,
    lang,
    fx,
    domConstruct,
    Dict,
    Notification
) {
    /**
     * Notification mixin. Expects a data-dojo-attach-point="notificationNode" in
     * the template. Usage:
     * @code
     * showNotification({
     *      type: "error",
     *      message: "Backend error",
     *      fadeOut: false,
     *      onHide: function () {}
     * });
     * @endcode
     */
    return declare([], {
        node: null,
        widget: null,

        showNotification: function (options) {
            var alertClass = 'alert-info';

            if (options.type === 'ok') {
                alertClass = 'alert-success';
            } else if (options.type === 'error') {
                alertClass = 'alert-error';
            }

            this.hideNotification();

            if (this.node) {
                domConstruct.destroy(this.node);
            }

            this.node = domConstruct.create('div', {}, this.notificationNode, 'first');

            this.widget = new Notification({
                'class': alertClass,
                content: options.message
            }, this.node);

            this.widget.startup();

            if (options.fadeOut) {
                fx.fadeOut({
                    node: this.widget.domNode,
                    delay: 1000,
                    duration: 1000,
                    onEnd: lang.hitch(this, function() {
                        this.hideNotification();
                    })
                }).play();
            }

            this.onHide = undefined;
            if (options.onHide instanceof Function) {
                this.onHide = options.onHide;
            }
        },

        hideNotification: function () {
            if (this.widget) {
                this.widget.destroyRecursive();
            }
            if (this.onHide instanceof Function) {
                this.onHide();
            }
        },

        showBackendError: function (errorData) {
            var message = Dict.translate("Backend error");

            // check for most specific (message is in response data)
            if (errorData.response && errorData.response.data && errorData.response.data.errorMessage) {
                message = errorData.response.data.errorMessage;
            }
            else if (errorData.errorMessage) {
                message = errorData.errorMessage;
            }
            else if (errorData.message) {
                message = errorData.message;
            }

            this.showNotification({
                type: "error",
                message: message
            })
        }
    });
});