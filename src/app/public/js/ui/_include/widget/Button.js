define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/on",
    "bootstrap/Button"
], function (
    declare,
    lang,
    on,
    Button
) {
    return declare([Button], {

        initialLabel: "",

        postCreate:function () {
            this.own(on(this.domNode, "click", lang.hitch(this, function(e) {
                // translate to dijit button event
                this.emit("onClick", e);
            })));
            this.initialLabel = this.domNode.innerHTML;
        },

        setProcessing: function() {
            this.set("text", this.initialLabel+' <i class="fa fa-spinner fa-spin"></i>');
            this.set("disabled", true);
        },

        reset: function() {
            this.set("text", this.initialLabel);
            this.set("disabled", false);
        }
    });
});