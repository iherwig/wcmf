define([
    "dojo/_base/declare",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "bootstrap/Dropdown",
    "bootstrap/Collapse",
    "dojo/query",
    "dojo/dom-class",
    "dojo/NodeList-dom",
    "../../../Cookie",
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
    Cookie,
    template
) {
    return declare([_WidgetBase, _TemplatedMixin], {

        titleOnly: false,
        templateString: template,

        constructor: function (params) {
            this.titleOnly = params.titleOnly;
        },

        postCreate: function() {
            // hide buttons, if titleOnly
            if (this.titleOnly) {
                query("ul.nav").style("display", "none");
                query("a.btn-navbar").style("display", "none");
            }

            // set first root type on nodeList route
            var firstRootType = appConfig.rootTypes[0];
            this.setContentRoute(firstRootType);

            // set app title
            dojo.query(".brand").attr("innerHTML", appConfig.title);

            // set user name
            dojo.query(".user").attr("innerHTML", Cookie.get("user"));
        },

        setContentRoute: function(type, id) {
            var contentNavNode = dojo.query("#navContent");
            contentNavNode.attr("data-dojorama-route", id !== undefined ? "node" : "nodeList");
            var routeParamStr = "type: '"+type+"'";
            if (id !== undefined) {
              routeParamStr += ", id: '"+id+"'";
            }
            contentNavNode.attr("data-dojorama-pathparams", routeParamStr);
        },

        setActiveRoute: function(route) {
            query("[data-dojorama-route='"+route+"']").parent().forEach(function(node){
                domClass.add(node, "active");
            });
        }
    });
});