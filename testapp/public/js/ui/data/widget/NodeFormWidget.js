define( [
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "../../../model/meta/Model",
    "../../../Loader",
    "dojo/text!./template/NodeFormWidget.html"
],
function(
    declare,
    lang,
    _WidgetBase,
    _TemplatedMixin,
    Model,
    Loader,
    template
) {
    return declare([_WidgetBase, _TemplatedMixin], {

        templateString: template,
        node: null,
        headline: "",

        constructor: function(args) {
            declare.safeMixin(this, args);

            this.headline = Model.getDisplayValue(this.node);
        },

        postCreate: function() {
            this.inherited(arguments);

            new Loader("js/ui/data/widget/TextBox").then(lang.hitch(this, function(TextBox) {
                for (var i=0; i<3; i++) {
                    var textBox = new TextBox({
                        label: "Name "+(i+1)
                    });
                    this.fieldsNode.appendChild(textBox.domNode);
                }
            }));
        }
    });
});