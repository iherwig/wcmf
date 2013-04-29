define([
    "dojo/_base/declare",
    "dojo/dom-construct",
    "./widget/NotificationWidget"
], function (
    declare,
    domConstruct,
    Notification
) {
    "use strict";

    return declare([], {
        node: null,
        widget: null,

        showNotification: function (notification) {
            var alertClass = 'alert-info';

            if (notification.type === 'ok') {
                alertClass = 'alert-success';
            } else if (notification.type === 'error') {
                alertClass = 'alert-error';
            }

            this.hideNotification();

            if (this.node) {
                domConstruct.destroy(this.node);
            }

            this.node = domConstruct.create('div', {}, this.notificationNode, 'first');

            this.widget = new Notification({
                'class': alertClass,
                content: notification.message,
                closable: true
            }, this.node);

            this.widget.startup();
        },

        hideNotification: function () {
            if (this.widget) {
                this.widget.destroyRecursive();
            }
        }
    });
});