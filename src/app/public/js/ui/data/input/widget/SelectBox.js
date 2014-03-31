define( [
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/aspect",
    "dojo/on",
    "dojo/query",
    "dojo/dom-construct",
    "dojo/topic",
    "dijit/form/FilteringSelect",
    "../Factory",
    "../../../_include/_HelpMixin",
    "./_AttributeWidgetMixin",
    "../../../../locale/Dictionary"
],
function(
    declare,
    lang,
    aspect,
    on,
    query,
    domConstruct,
    topic,
    FilteringSelect,
    ControlFactory,
    _HelpMixin,
    _AttributeWidgetMixin,
    Dict
) {
    return declare([FilteringSelect, _HelpMixin, _AttributeWidgetMixin], {

        intermediateChanges: true,
        entity: {},
        attribute: {},
        original: {},

        spinnerNode: null,

        searchAttr: "displayText",

        constructor: function(args) {
            declare.safeMixin(this, args);

            this.label = Dict.translate(this.attribute.name);
            this.disabled = !this.attribute.isEditable;
            this.name = this.attribute.name;
            this.value = this.entity[this.attribute.name];
            this.helpText = Dict.translate(this.attribute.description);

            this.store = ControlFactory.getListStore(this.attribute.inputType);

            aspect.before(this, "_startSearch", function(text) {
                // create spinner
                if (!this.spinnerNode) {
                    this.spinnerNode = domConstruct.create("p", {
                        innerHTML: '<i class="fa fa-spinner fa-spin"></i>'
                    }, this.domNode.parentNode, "last");
                }
                this.showSpinner();
                return text;
            });
        },

        postCreate: function() {
            this.inherited(arguments);

            // subscribe to entity change events to change tab links
            this.own(
                topic.subscribe("entity-datachange", lang.hitch(this, function(data) {
                    if (data.name === this.attribute.name) {
                        this.set("value", data.newValue);
                    }
                })),
                on(this, 'search', lang.hitch(this, function() {
                    this.hideSpinner();
                }))
            );
        },

        showSpinner: function() {
            query(this.spinnerNode).style("display", "block");
        },

        hideSpinner: function() {
            query(this.spinnerNode).style("display", "none");
        }
    });
});