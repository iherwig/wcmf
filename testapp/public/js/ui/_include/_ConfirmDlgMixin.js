define([
    "dojo/_base/declare",
    "dojo/dom-construct",
    "./widget/ConfirmDlgWidget"
], function (
    declare,
    domConstruct,
    Confirm
) {
    "use strict";

    return declare([], {
        node: null,
        widget: null,

        showConfirm: function (options) {
            this.hideConfirm();

            if (this.node) {
                domConstruct.destroy(this.node);
            }

            this.node = domConstruct.create('div', {}, dojo.body());

            this.widget = new Confirm({
                id: 'confirmDlg',
                title: options.title,
                content: options.message,
                callback: options.callback
            }, this.node);

            this.widget.startup();
        },

        hideConfirm: function () {
            if (this.widget) {
                this.widget.destroyRecursive();
            }
        }
    });
});