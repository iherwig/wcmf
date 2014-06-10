define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/_base/fx",
    "dojo/query",
    "dojo/dom-construct",
    "dojo/on",
    "./widget/NotificationWidget",
    "../../persistence/BackendError"
], function (
    declare,
    lang,
    fx,
    query,
    domConstruct,
    on,
    Notification,
    BackendError
) {
    /**
     * Notification mixin. Expects an element with id "notification" in
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
            } else if (options.type === 'process') {
                alertClass = 'alert-info';
            }

            this.hideNotification();

            if (this.node) {
                domConstruct.destroy(this.node);
            }

            var nodes = query('#notification');
            if (nodes.length > 0) {
                this.node = domConstruct.create('div', {}, nodes[0], 'first');
            }
            else {
                console.warn('No node with id "notification" found.');
                return;
            }

            var content = options.message;
            if (options.type === 'process') {
              content += ' <i class="fa fa-spinner fa-spin"></i>';
            }

            this.widget = new Notification({
                'class': alertClass,
                content: content
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