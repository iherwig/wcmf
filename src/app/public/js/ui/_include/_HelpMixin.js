define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dijit/TooltipDialog",
    "dijit/popup",
    "dojo/on",
    "../../locale/Dictionary",
    "dojo/domReady!"
], function (
    declare,
    lang,
    TooltipDialog,
    popup,
    on,
    Dict
) {
    return declare([], {

        dialog: null,

        postCreate: function() {
            this.inherited(arguments);

            var text = Dict.translate(this.attribute.description);
            if (text.length > 0) {
                this.own(
                    on(this.domNode, 'mouseover', lang.hitch(this, function() {
                        popup.open({
                            popup: this.dialog,
                            orient: ["below", "below-alt", "above", "above-alt"],
                            around: this.domNode
                        });
                    })),
                    on(this.domNode, 'mouseleave', lang.hitch(this, function() {
                        popup.close(this.dialog);
                    }))
                );
                this.dialog = new TooltipDialog({
                    content: text,
                    onMouseLeave: lang.hitch(this, function() {
                        popup.close(this.dialog);
                    })
                });
            }
        }
    });
});