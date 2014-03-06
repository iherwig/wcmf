define([
    "dojo/_base/declare",
    "dijit/form/Button"
], function (
    declare,
    Button
) {
    return declare([Button], {

        initialLabel: "",

        setProcessing: function() {
            this.initialLabel = this.get("label");
            this.set("label", this.initialLabel+' <i class="fa fa-spinner fa-spin"></i>');
            this.set("disabled", true);
        },

        reset: function() {
            this.set("label", this.initialLabel);
            this.set("disabled", false);
        }
    });
});