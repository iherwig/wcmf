define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/_base/fx",
    "dojo/dom-construct",
    "dojo/on",
    "./widget/NotificationWidget",
    "../../persistence/BackendError"
], function (
    declare,
    lang,
    fx,
    domConstruct,
    on,
    Notification,
    BackendError
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
            on(this.widget.domNode, "click", lang.hitch(this, function() {
                    this.hideNotification();
                })
            );

            if (options.fadeOut) {
                fx.fadeOut({
                    node: this.widget.domNode,
                    delay: 1000,
                    duration: 1000,
                    onEnd: lang.hitch(this, function() {
                        fx.animateProperty({
                            node: this.widget.domNode,
                            duration: 100,
                            properties: {
                              height: 0
                            },
                            onEnd: lang.hitch(this, function() {
                                this.hideNotification();
                            })
                        }).play();
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
            var error = BackendError.parseResponse(errorData);
            if (error.code === 'SESSION_INVALID') {
                // prevent circular dependency
                require(["app/js/ui/_include/widget/LoginDlgWidget"], function(LoginDlg) {
                    new LoginDlg({}).show();
                });
            }
            else {
                this.showNotification({
                    type: "error",
                    message: error.message
                });
            }
        }
    });
});