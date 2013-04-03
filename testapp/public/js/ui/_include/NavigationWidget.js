/*jshint strict:false */

define([
    "dojo/_base/declare",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "bootstrap/Dropdown",
    "bootstrap/Collapse",
    "dojo/query",
    "dojo/dom-class",
    "dojo/NodeList-dom",
    "../../Session",
    "dojo/text!./template/NavigationWidget.html"
], function (
    declare,
    _WidgetBase,
    _TemplatedMixin,
    dropdown,
    collapse,
    query,
    domClass,
    nodeListDom,
    Session,
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
                query("a.btn-navbar").style("display", "none");
            }

            // set first root type on dataIndex route
            var firstRootType = appConfig.rootTypes[0];
            dojo.query("[data-dojorama-route='dataIndex']").
                     attr("data-dojorama-pathparams", "type: '"+firstRootType+"'");

            // set app title
            dojo.query(".brand").attr("innerHTML", appConfig.title);

            // set user name
            dojo.query(".user").attr("innerHTML", Session.get("user"));
        }
    });
});