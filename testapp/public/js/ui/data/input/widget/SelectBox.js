define( [
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/topic",
    "dijit/form/FilteringSelect",
    "dojo/store/Memory",
    "../../../../persistence/Store"
],
function(
    declare,
    lang,
    topic,
    FilteringSelect,
    Memory,
    Store
) {
    return declare([FilteringSelect], {

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

            var stateStore = new Memory({
                 data: [
                     {name:"Alabama", id:"AL"},
                     {name:"Alaska", id:"AK"},
                     {name:"American Samoa", id:"AS"},
                     {name:"Arizona", id:"AZ"},
                     {name:"Arkansas", id:"AR"},
                     {name:"Armed Forces Europe", id:"AE"},
                     {name:"Armed Forces Pacific", id:"AP"},
                     {name:"Armed Forces the Americas", id:"AA"},
                     {name:"California", id:"CA"},
                     {name:"Colorado", id:"CO"},
                     {name:"Connecticut", id:"CT"},
                     {name:"Delaware", id:"DE"}
                 ]
             });
            this.store = stateStore;
        },

        postCreate: function() {
            this.inherited(arguments);

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