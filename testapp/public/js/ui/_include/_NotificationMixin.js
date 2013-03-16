define([
    "dojo/_base/declare",
    "dojo/dom-construct",
    "./NotificationWidget"
], function (
    declare,
    domConstruct,
    Notification
) {
    "use strict";

    return declare([], {
        alertNode: null,
        alertWidget: null,

        showNotification: function (notification) {
            var alertClass = 'alert-info';

            if (notification.type === 'ok') {
                alertClass = 'alert-success';
            } else if (notification.type === 'error') {
                alertClass = 'alert-error';
            }

            this.hideNotification();

            if (this.alertNode) {
                domConstruct.destroy(this.alertNode);
            }

            this.alertNode = domConstruct.create('div', {}, this.notificationNode, 'first');

            this.alertWidget = new Notification({
                'class': alertClass,
                content: notification.message,
                closable: true
            }, this.alertNode);

            this.alertWidget.startup();
        },

        hideNotification: function () {
            if (this.alertWidget) {
                this.alertWidget.destroyRecursive();
            }
        }
    });
});