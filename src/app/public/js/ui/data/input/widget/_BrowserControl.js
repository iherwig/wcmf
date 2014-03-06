define( [
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/topic",
    "dojo/on",
    "dijit/form/TextBox",
    "../../../_include/widget/Button",
    "dijit/layout/ContentPane",
    "../../../_include/_HelpMixin",
    "../../../../locale/Dictionary"
],
function(
    declare,
    lang,
    topic,
    on,
    TextBox,
    Button,
    ContentPane,
    HelpIcon,
    Dict
) {
    return declare([ContentPane, HelpIcon], {

        entity: {},
        attribute: {},
        original: {},

        callbackName: null,
        browserUrl: null,

        constructor: function(args) {
            declare.safeMixin(this, args);

            this.label = Dict.translate(this.attribute.name);
            this.disabled = !this.attribute.isEditable;
            this.name = this.attribute.name;
            this.value = this.entity[this.attribute.name];
            this.helpText = Dict.translate(this.attribute.description);
        },

        postCreate: function() {
            this.inherited(arguments);

            // create textbox
            var widget = new TextBox({
                intermediateChanges: true,
                name: this.name,
                value: this.value
            });
            widget.startup();
            this.addChild(widget);

            // create callback
            this.callbackName = "field_cb_"+widget.id;
            window[this.callbackName] = lang.hitch(widget, function(value) {
                this.set("value", value);
            });

            // create button
            if (this.browserUrl) {
                var browseBtn = new Button({
                    innerHTML: '<i class="fa fa-folder-open"></i>',
                    "class": "btn-mini",
                    onClick: lang.hitch(this, function() {
                        window.open(this.browserUrl+'?callback='+this.callbackName, '_blank', 'width=800,height=700');
                    })
                });
                this.addChild(browseBtn);
            }

            // subscribe to entity change events to change tab links
            this.own(
                topic.subscribe("entity-datachange", lang.hitch(this, function(data) {
                    if (data.name === this.attribute.name) {
                        this.set("value", data.newValue);
                    }
                })),
                on(widget, "change", lang.hitch(this, function(value) {
                    this.set("value", value);
                    // send change event
                    this.emit("change", this);
                }))
            );
        },

        destroy: function() {
            if (this.callbackName) {
                delete window[this.callbackName];
            }
            this.inherited(arguments);
        }
    });
});