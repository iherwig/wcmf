define( [
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/topic",
    "dojo/on",
    "dijit/form/TextBox",
    "../../../_include/widget/Button",
    "dijit/layout/ContentPane",
    "../../../_include/_HelpMixin",
    "./_AttributeWidgetMixin",
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
    _HelpMixin,
    _AttributeWidgetMixin,
    Dict
) {
    return declare([ContentPane, _HelpMixin, _AttributeWidgetMixin], {

        entity: {},
        attribute: {},
        original: {},

        callbackName: null,
        browserUrl: null,
        textbox: null,
        listenToWidgetChanges: true,

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
            this.textbox = new TextBox({
                intermediateChanges: true,
                name: this.name,
                value: this.value
            });
            this.textbox.startup();
            this.addChild(this.textbox);

            // create callback
            this.callbackName = "field_cb_"+this.textbox.id;
            window[this.callbackName] = lang.hitch(this.textbox, function(value) {
                this.set("value", value);
            });

            // create button
            if (this.browserUrl) {
                var browseBtn = new Button({
                    innerHTML: '<i class="fa fa-folder-open"></i>',
                    "class": "btn-mini",
                    onClick: lang.hitch(this, function() {
                        window.open(this.browserUrl+'?callback='+this.callbackName+"&directory="+this.getDirectory(), '_blank', 'width=800,height=700');
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
                on(this.textbox, "change", lang.hitch(this, function(value) {
                    if (this.listenToWidgetChanges) {
                        this.set("value", value);
                        // send change event
                        this.emit("change", this);
                    }
                })),
                on(this, "attrmodified-value", lang.hitch(this, function(e) {
                    // update textbox
                    var oldListenValue = this.listenToWidgetChanges;
                    this.listenToWidgetChanges = false;
                    this.textbox.set("value", e.detail.newValue);
                    this.listenToWidgetChanges = oldListenValue;
                }))
            );
        },

        getDirectory: function() {
            var value = this.get("value");
            return value ? value.replace(/[^\/]*$/, '') : '';
        },

        destroy: function() {
            if (this.callbackName) {
                delete window[this.callbackName];
            }
            this.inherited(arguments);
        },

        focus: function() {
            // focus the widget, because otherwise focus loss
            // is not reported to grid editor resulting in empty grid value
            this.textbox.focus();
        }
    });
});