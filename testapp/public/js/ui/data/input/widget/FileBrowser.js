define( [
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/dom-construct",
    "dojo/topic",
    "dijit/form/TextBox",
    "../../../_include/widget/Button",
    "dijit/layout/ContentPane"
],
function(
    declare,
    lang,
    domConstruct,
    topic,
    TextBox,
    Button,
    ContentPane
) {
    return declare([ContentPane], {

        intermediateChanges: true,
        entity: {},
        attribute: {},
        original: {},

        constructor: function(args) {
            declare.safeMixin(this, args);

            this.label = this.attribute.name;
            this.disabled = !this.attribute.isEditable;
            this.name = this.attribute.name;
            this.value = this.entity[this.attribute.name];
        },

        postCreate: function() {
            this.inherited(arguments);

            // create textbox
            var widget = new TextBox({
                name: this.name,
                value: this.value
            });
            widget.startup();
            this.addChild(widget);

            // create label
            var browseBtn = new Button({
                innerHTML: '<i class="icon-folder-open"></i>',
                class: "btn-mini",
                onClick: lang.hitch(this, function() {
                    window.open(appConfig.pathPrefix+'/media?fieldId='+widget.id, '_blank', 'width=800,height=700');
                })
            });
            this.addChild(browseBtn);

            // subscribe to entity change events to change tab links
            this.own(
                topic.subscribe("entity-datachange", lang.hitch(this, function(data) {
                    if (data.name === this.attribute.name) {
                        this.set("value", data.newValue);
                    }
                }))
            );
        }
    });
});