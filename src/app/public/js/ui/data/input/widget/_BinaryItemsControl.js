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
    "../../../_include/_HelpMixin",
    "./_AttributeWidgetMixin",
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
    _HelpMixin,
    _AttributeWidgetMixin,
    Dict
) {
    return declare([ContentPane, _HelpMixin, _AttributeWidgetMixin], {

        entity: {},
        attribute: {},
        original: {},

        multiValued: true,

        spinnerNode: null,
        itemWidgets: {},
        listenToWidgetChanges: true,

        constructor: function(args) {
            declare.safeMixin(this, args);

            this.label = Dict.translate(this.attribute.name);
            this.disabled = !this.attribute.isEditable;
            this.name = this.attribute.name;
            this.value = this.entity[this.attribute.name];
            this.helpText = Dict.translate(this.attribute.description);

            this.store = ControlFactory.getListStore(this.attribute.inputType);
        },

        postCreate: function() {
            this.inherited(arguments);
            this.spinnerNode = domConstruct.create("p", {
                innerHTML: '<i class="fa fa-spinner fa-spin"></i>'
            }, this.domNode, "first");
            this.showSpinner();

            var _control = this;
            when(this.store.query(), lang.hitch(this, function(list) {
                this.hideSpinner();
                for (var i=0, c=list.length; i<c; i++) {
                    var item = list[i];
                    var itemId = this.store.getIdentity(item);
                    var itemWidget = this.buildItemWidget(item);
                    this.own(
                        on(itemWidget, "change", function(isSelected) {
                            if (_control.listenToWidgetChanges) {
                                _control.updateValue(this.value, isSelected);
                            }
                        })
                    );
                    this.itemWidgets[itemId] = itemWidget;
                }
            }));

            // subscribe to entity change events to change tab links
            this.own(
                topic.subscribe("entity-datachange", lang.hitch(this, function(data) {
                    if (data.name === this.attribute.name) {
                        this.set("value", data.newValue);
                    }
                })),
                on(this, "attrmodified-value", lang.hitch(this, function(e){
                    // update item widgets
                    var oldListenValue = this.listenToWidgetChanges;
                    this.listenToWidgetChanges = false;
                    var value = e.detail.newValue;
                    var values = value.split(",");
                    for (var itemId in this.itemWidgets) {
                        var widget = this.itemWidgets[itemId];
                        var isChecked = array.indexOf(values, itemId) !== -1;
                        widget.set("checked", isChecked);
                    }
                    this.listenToWidgetChanges = oldListenValue;
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
