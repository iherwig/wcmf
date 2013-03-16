/*jshint strict:false */

define([
    "dojo/_base/declare",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "bootstrap/Dropdown",
    "dojo/query",
    "dojo/dom-class",
    "dojo/dom-style",
    "dojo/text!./template/NavigationWidget.html"
], function (
    declare,
    _WidgetBase,
    _TemplatedMixin,
    dropdown,
    query,
    domClass,
    domStyle,
    template
) {
    return declare([_WidgetBase, _TemplatedMixin], {

        activeRoute: null,
        titleOnly: false,
        templateString: template,

        constructor: function (params) {
            this.activeRoute = params.activeRoute;
            this.titleOnly = params.titleOnly;
        },

        postCreate: function() {
            // mark active route
            if (this.activeRoute !== null) {
              query("[data-dojorama-route='"+this.activeRoute+"']").parent().forEach(function(node){
                domClass.add(node, "active");
              });
            }
            // hide buttons, if titleOnly
            if (this.titleOnly) {
              query("ul.nav").style("display", "none");
            }
        }
    });
});