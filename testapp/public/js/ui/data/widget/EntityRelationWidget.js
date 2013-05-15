define( [
    "dojo/_base/declare",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dojo/text!./template/EntityRelationWidget.html"
],
function(
    declare,
    _WidgetBase,
    _TemplatedMixin,
    template
) {
    return declare([_WidgetBase, _TemplatedMixin], {

        templateString: template,
        entity: {},
        relation: {},
        headline: "",

        constructor: function(args) {
            declare.safeMixin(this, args);

            this.headline = this.relation.name;
        }
    });
});