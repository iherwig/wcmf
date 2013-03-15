/*jshint strict:false */

define([
    "dojo/_base/declare",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dojo/query",
    "dojo/dom-class",
    "dojo/text!./template/NavigationWidget.html"
], function (
    declare,
    _WidgetBase,
    _TemplatedMixin,
    query,
    domClass,
    template
) {
    return declare([_WidgetBase, _TemplatedMixin], {

        activeRoute: null,
        templateString: template,

        constructor: function (params) {
            this.activeRoute = params.activeRoute;
        },

        postCreate: function() {
            // mark active route
            query("[data-dojorama-route='"+this.activeRoute+"']").parent().forEach(function(node){
              domClass.add(node, "active");
            });
        }
    });
});