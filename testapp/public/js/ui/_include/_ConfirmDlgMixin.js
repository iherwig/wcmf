define([
    "dojo/_base/declare",
    "dojo/dom-construct",
    "dojo/query",
    "bootstrap/Modal",
    "./widget/ConfirmDlgWidget"
], function (
    declare,
    domConstruct,
    query,
    modal,
    Confirm
) {
    "use strict";

    return declare([], {
        node: null,
        widget: null,

        showConfirm: function (question) {
            this.hideConfirm();

            if (this.node) {
                domConstruct.destroy(this.node);
            }

            this.node = domConstruct.create('div', {}, dojo.body());

            this.widget = new Confirm({
                id: 'confirmDlg',
                content: question.message,
                closable: true
            }, this.node);

            this.widget.startup();
            query('#confirmDlg').modal({});
        },

        hideConfirm: function () {
            if (this.widget) {
                this.widget.destroyRecursive();
            }
        }
    });
});