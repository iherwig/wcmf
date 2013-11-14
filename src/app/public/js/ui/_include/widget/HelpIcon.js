define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dijit/TooltipDialog",
    "dijit/popup",
    "dojo/dom",
    "dojo/domReady!"
], function (
    declare,
    lang,
    _WidgetBase,
    _TemplatedMixin,
    TooltipDialog,
    popup,
    dom
) {
    return declare([_WidgetBase, _TemplatedMixin], {

        templateString: '<i class="icon-question-sign" data-dojo-attach-event="onMouseOver:_show"></i>',
        dialog: null,

        constructor: function(args) {
            declare.safeMixin(this, args);
        },

        postCreate: function() {
            this.inherited(arguments);

            this.dialog = new TooltipDialog({
                content: this.text,
                onMouseLeave: lang.hitch(this, function() {
                    popup.close(this.dialog);
                })
            });
        },

        _show: function() {
            var id = this.id;
            popup.open({
                popup: this.dialog,
                orient: ['before', 'after'],
                around: dom.byId(id)
            });
        }
    });
});