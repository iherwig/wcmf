define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dijit/registry",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dijit/_WidgetsInTemplateMixin",
    "dijit/MenuBar",
    "dijit/MenuBarItem",
    "dijit/PopupMenuBarItem",
    "dijit/Menu",
    "dijit/MenuSeparator",
    "dijit/MenuItem",
    "dojo/query",
    "dojo/dom-class",
    "dojo/NodeList-dom",
    "../../../Cookie",
    "dojo/text!./template/NavigationWidget.html"
], function (
    declare,
    lang,
    registry,
    _WidgetBase,
    _TemplatedMixin,
    _WidgetsInTemplateMixin,
    MenuBar,
    MenuBarItem,
    PopupMenuBarItem,
    Menu,
    MenuSeparator,
    MenuItem,
    query,
    domClass,
    nodeListDom,
    Cookie,
    template
) {
    return declare([_WidgetBase, _TemplatedMixin, _WidgetsInTemplateMixin], {

        titleOnly: false,
        templateString: template,

        constructor: function (params) {
            this.titleOnly = params.titleOnly;
        },

        postCreate: function() {
            // hide buttons, if titleOnly
            if (this.titleOnly) {
                query(".main-menu").style("display", "none");
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
            contentNavNode.attr("data-dojorama-route", id !== undefined ? "entity" : "entityList");
            var routeParamStr = "type: '"+type+"'";
            if (id !== undefined) {
              routeParamStr += ", id: '"+id+"'";
            }
            contentNavNode.attr("data-dojorama-pathparams", routeParamStr);
        },

        setActiveRoute: function(route) {
            query("[data-dojorama-route='"+route+"']").forEach(lang.hitch(this, function(node) {
                this.menuBar.focusChild(registry.byId(node.id));
            }));
        }
    });
});