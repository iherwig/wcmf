define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/dom-construct",
    "dojo/_base/fx",
    "./widget/NotificationWidget"
], function (
    declare,
    lang,
    domConstruct,
    fx,
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
                content: options.message,
                closable: true
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
        }
    });
});