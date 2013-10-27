define( [
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/_base/array",
    "dojo/query",
    "dojo/dom-construct",
    "dojo/topic",
    "dojo/when",
    "dojo/on",
    "dijit/layout/ContentPane",
    "../Factory",
    "../../../../locale/Dictionary"
],
function(
    declare,
    lang,
    array,
    query,
    domConstruct,
    topic,
    when,
    on,
    ContentPane,
    ControlFactory,
    Dict
) {
    return declare([ContentPane], {

        entity: {},
        attribute: {},
        original: {},

        multiValued: true,

        spinnerNode: null,

        constructor: function(args) {
            declare.safeMixin(this, args);

            this.label = Dict.translate(this.attribute.name);
            this.disabled = !this.attribute.isEditable;
            this.name = this.attribute.name;
            this.value = this.entity[this.attribute.name];

            this.store = ControlFactory.getListStore(this.attribute.inputType);
        },

        postCreate: function() {
            this.inherited(arguments);
            this.spinnerNode = domConstruct.create("p", {
                innerHTML: '<i class="icon-spinner icon-spin"></i>'
            }, this.domNode, "first");
            this.showSpinner();

            var _control = this;
            when(this.store.query(), lang.hitch(this, function(list) {
                this.hideSpinner();
                for (var i=0, c=list.length; i<c; i++) {
                    var itemWidget = this.buildItemWidget(list[i]);
                    this.own(
                        on(itemWidget, "change", function(isSelected) {
                            _control.updateValue(this.value, isSelected);
                        })
                    );
                }
            }));

            // subscribe to entity change events to change tab links
            this.own(
                topic.subscribe("entity-datachange", lang.hitch(this, function(data) {
                    if (data.name === this.attribute.name) {
                        this.set("value", data.newValue);
                    }
                }))
            );
        },

        buildItemWidget: function(item) {
            throw "must be implemented by subclass";
        },

        showSpinner: function() {
            query(this.spinnerNode).style("display", "block");
        },

        hideSpinner: function() {
            query(this.spinnerNode).style("display", "none");
        },

        updateValue: function(value, isSelected) {
            if (this.multiValued) {
                var values = this.get("value").split(",");
                if (isSelected) {
                    // add value
                    if (array.indexOf(values, value) === -1) {
                        values.push(value);
                    }
                }
                else {
                    // remove value
                    values = array.filter(values, function(item){
                        return (item != value); // value may be string or number
                    });
                }
                this.set("value", values.join(","));
                // send change event
                this.emit("change", this);
            }
            else {
                if (isSelected) {
                    this.set("value", value);
                    // send change event
                    this.emit("change", this);
                }
            }
        }
    });
});
