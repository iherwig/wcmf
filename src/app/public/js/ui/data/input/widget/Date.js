define( [
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/topic",
    "dijit/form/DateTextBox",
    "dojo/date/locale",
    "../../../_include/_HelpMixin",
    "./_AttributeWidgetMixin",
    "../../../../locale/Dictionary"
],
function(
    declare,
    lang,
    topic,
    DateTextBox,
    locale,
    _HelpMixin,
    _AttributeWidgetMixin,
    Dict
) {
    return declare([DateTextBox, _HelpMixin, _AttributeWidgetMixin], {

        intermediateChanges: true,
        hasDownArrow: false,
        entity: {},
        attribute: {},
        original: {},

        dateFormat: {selector: 'date', datePattern: 'yyyy-MM-dd HH:mm:ss', locale: appConfig.uiLanguage},

        constructor: function(args) {
            declare.safeMixin(this, args);

            this.label = Dict.translate(this.attribute.name);
            this.disabled = !this.attribute.isEditable;
            this.name = this.attribute.name;
            // add time, if missing
            var value = this.entity[this.attribute.name];
            if (value && value.length === 10) {
              value = value+" 00:00:00";
            }
            this.value = locale.parse(value, this.dateFormat);
            this.helpText = Dict.translate(this.attribute.description);
        },

        postCreate: function() {
            this.inherited(arguments);

            // subscribe to entity change events to change tab links
            this.own(
                topic.subscribe("entity-datachange", lang.hitch(this, function(data) {
                    if (data.name === this.attribute.name) {
                        this.set("value", locale.parse(data.newValue, this.dateFormat));
                    }
                }))
            );
        },

        _getValueAttr: function() {
            var value = this.inherited(arguments);
            if (value) {
                var dateFormat = this.dateFormat;
                value.toJSON = function() {
                    return locale.format(this, dateFormat);
                };
            }
            return value;
        }
    });
});