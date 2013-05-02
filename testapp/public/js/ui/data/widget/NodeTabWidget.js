define([
    "dojo/_base/declare",
    "dojo/dom-construct",
    "dojo/query",
    "bootstrap/Tab",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "../../../model/meta/Model",
    "dojo/text!./template/NodeTabWidget.html"
], function (
    declare,
    domConstruct,
    query,
    Tab,
    _WidgetBase,
    _TemplatedMixin,
    Model,
    template
) {
    var NodeTabWidget = declare([_WidgetBase, _TemplatedMixin], {

        templateString: template,
        selectedTab: {},

        constructor: function (params) {
            declare.safeMixin(this, params);
        },

        postCreate: function () {
            this.inherited(arguments);
            this.buildTabs();
        },

        buildTabs: function () {
            // push selected tab to the end, if it does not exist already
            if (NodeTabWidget.tabDefs[this.selectedTab.oid] === undefined) {
                NodeTabWidget.tabDefs[this.selectedTab.oid] = this.selectedTab;
            }

            // iterate over all panels
            for (var oid in NodeTabWidget.tabDefs) {
                var isSelected = (oid === this.selectedTab.oid);

                var tabItem = domConstruct.create("li", {
                    class: isSelected ? "active" : ""
                }, this.tabNode);

                var tabName = this.getNameForTab(oid);
                var tabRoute = this.getRouteForTab(oid);
                var tabLink = domConstruct.create("a", {
                    href: "#"+tabName,
                    'data-dojorama-route': tabRoute.route,
                    'data-dojorama-pathparams': tabRoute.routeParams,
                    'data-toggle': "tab",
                    class: "push",
                    innerHTML: tabName
                }, tabItem);

                if (isSelected) {
                  var content = domConstruct.create("div", {
                      class: isSelected ? "tab-pane fade in active" : "tab-pane fade"
                  }, this.panelNode);

                  content.appendChild(this.selectedTab.widget.domNode);
                  var selectedTab = tabLink;
                }
            }
            query(selectedTab).tab('show');
        },

        getRouteForTab: function (oid) {
            var typeName = Model.getTypeNameFromOid(oid);
            var id = Model.getIdFromOid(oid);
            if (id === typeName) {
                return {
                    route: "dataIndex",
                    routeParams: "type: '"+typeName+"'"
                }
            }

        },

        getNameForTab: function (oid) {
            var typeName = Model.getTypeNameFromOid(oid);
            var id = Model.getIdFromOid(oid);
            if (id === typeName) {
                return typeName;
            }
        }
    });

    NodeTabWidget.tabDefs = {};
    // initially add all root types
    for (var i=0, count=appConfig.rootTypes.length; i<count; i++) {
        var typeName = appConfig.rootTypes[i];
        NodeTabWidget.tabDefs[typeName] = {
            oid: typeName
        };
    }

    return NodeTabWidget;
});