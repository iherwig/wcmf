define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/dom-construct",
    "dojo/dom-attr",
    "dojo/query",
    "dojo/on",
    "dojo/promise/Promise",
    "bootstrap/Tab",
    "dojomat/_StateAware",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "../../../Cookie",
    "../../../model/meta/Model",
    "../../../persistence/Store",
    "dojo/text!./template/NodeTabWidget.html"
], function (
    declare,
    lang,
    domConstruct,
    domAttr,
    query,
    on,
    Promise,
    Tab,
    _StateAware,
    _WidgetBase,
    _TemplatedMixin,
    Cookie,
    Model,
    Store,
    template
) {
    /**
     * Tab panel for types and nodes. Tabs are identified by oid. Type
     * tabs are not closable, node tabs are. Opened node tabs are stored
     * in a cookie. Switching tabs is done by calling the appropriate route
     * for either the type or node.
     * That implies that the tab panel is expected to be newly instantiated
     * on each view and while all tab links are created only the currently
     * displayed tab panel (given in the selectedPanel property) is added.
     *
     * Example usage to instantiate the tab panel to display nodes of
     * the type 'Author':
     *
     * @code
     * new NodeTabWidget({
     *     router: this.router,
     *     selectedTab: {
     *         oid: 'Author'
     *     },
     *     selectedPanel: gridWidget
     * }, this.tabNode);
     * @endcode
     */
    var NodeTabWidget = declare([_WidgetBase, _TemplatedMixin, _StateAware], {

        router: null,
        selectedTab: {},
        selectedPanel: {},
        lastTab: {},
        templateString: template,

        constructor: function(params) {
            declare.safeMixin(this, params);

            this.lastTab =  NodeTabWidget.lastTabDef;
            Cookie.set("lastTab", this.lastTab);
        },

        postCreate: function() {
            this.inherited(arguments);
            this.buildTabs();
        },

        buildTabs: function() {
            // add selected tab
            this.persistTab(this.selectedTab);

            // iterate over all panels
            for (var oid in NodeTabWidget.tabDefs) {
                var isSelected = (oid === this.selectedTab.oid);
                this.createTabLink(oid, isSelected);

                if (isSelected) {
                    // create tab content for selected tab only
                    if (this.selectedPanel && this.selectedPanel.domNode) {
                        var content = domConstruct.create("div", {
                            class: isSelected ? "tab-pane fade in active" : "tab-pane fade"
                        }, this.panelNode);
                        content.appendChild(this.selectedPanel.domNode);
                    }
                }
            }
            NodeTabWidget.lastTabDef = this.selectedTab;
       },

        closeTab: function(oid) {
            this.unpersistTab({ oid:oid });
            domConstruct.destroy("tab-"+oid);
            if (this.isSelected(oid)) {
                if (this.lastTab.oid && this.lastTab.oid !== oid) {
                    this.selectTab(this.lastTab.oid);
                }
                else {
                    this.selectFirstTab();
                }
            }
        },

        selectTab: function(oid) {
            if (oid !== undefined) {
                var routDef = this.getRouteForTab(oid);
                var route = this.router.getRoute(routDef.route);
                var url = route.assemble(routDef.routeParams);
                this.push(url);
            }
        },

        selectFirstTab: function() {
            for (var key in NodeTabWidget.tabDefs) {
                this.selectTab(NodeTabWidget.tabDefs[key].oid);
                break;
            }
        },

        isSelected: function(oid) {
            return (oid === this.selectedTab.oid);
        },

        isPersisted: function(tabDef) {
            return NodeTabWidget.tabDefs[tabDef.oid] !== undefined;
        },

        persistTab: function(tabDef) {
            if (!this.isPersisted(tabDef)) {
                NodeTabWidget.tabDefs[tabDef.oid] = tabDef;
                var openNodeTabs = Cookie.get("openNodeTabs", {});
                openNodeTabs[tabDef.oid] = tabDef;
                Cookie.set("openNodeTabs", openNodeTabs);
            }
        },

        unpersistTab: function(tabDef) {
            if (this.isPersisted(tabDef)) {
                delete NodeTabWidget.tabDefs[tabDef.oid];
                var openNodeTabs = Cookie.get("openNodeTabs", {});
                delete openNodeTabs[tabDef.oid];
                Cookie.set("openNodeTabs", openNodeTabs);
            }
        },

        getRouteForTab: function(oid) {
            var typeName = Model.getSimpleTypeName(Model.getTypeNameFromOid(oid));
            var id = Model.getIdFromOid(oid);
            var isTypeTab = (id === typeName);
            var route = {
                route: isTypeTab ? "dataIndex" : "node",
                routeParams: isTypeTab ? { type:typeName } : { type:typeName, id: id }
            };
            return route;
        },

        setTabName: function(oid, tabLink) {
            var typeName = Model.getTypeNameFromOid(oid);
            var id = Model.getIdFromOid(oid);
            var isTypeTab = (id === typeName);
            if (isTypeTab) {
                // type tab
                tabLink.innerHTML = '<i class="icon-reorder"></i> '+typeName;
            }
            else {
                // node tab
                var store = Store.getStore(typeName, 'en');
                var result = store.get(Model.getOid(typeName, id));
                if (result instanceof Promise) {
                    return result.then(lang.hitch(this, function(node) {
                        this.setNodeTabName(node, tabLink);
                    }), lang.hitch(this, function(error) {
                        closeTab(oid);
                    }));
                }
                else {
                    this.setNodeTabName(result, tabLink);
                }
            }
        },

        setNodeTabName: function(node, tabLink) {
            tabLink.innerHTML = '<i class="icon-file"></i> '+Model.getDisplayValue(node)+' ';
            var closeLink = domConstruct.create("span", {
                class: "close-tab",
                innerHTML: '&times;'
            }, tabLink);

            this.own(on(closeLink, 'click', lang.hitch(this, function(e) {
                e.preventDefault();
                e.stopPropagation();
                var tabItems = query(e.target).closest("li");
                if (tabItems.length > 0) {
                    var tabItem = tabItems[0];
                    var oid = domAttr.get(tabItem, "id").replace(/^tab-/, '');
                    this.closeTab(oid);
                }
            })));
        },

        createTabLink: function(oid, isSelected) {
            var tabItem = domConstruct.create("li", {
                id: "tab-"+oid,
                class: isSelected ? "active" : ""
            }, this.tabNode);

            var tabRoute = this.getRouteForTab(oid);
            var routeParamsStr = '';
            for (var key in tabRoute.routeParams) {
                routeParamsStr += key+": '"+tabRoute.routeParams[key]+"', ";
            }
            if (routeParamsStr.length > 0) {
                routeParamsStr = routeParamsStr.substring(0, routeParamsStr.length-2);
            }
            var tabLink = domConstruct.create("a", {
                href: "#",
                'data-dojorama-route': tabRoute.route,
                'data-dojorama-pathparams': routeParamsStr,
                'data-toggle': "tab",
                class: "push",
                innerHTML: '<i class="icon-spinner icon-spin"></i>'
            }, tabItem);
            this.setTabName(oid, tabLink);

            if (isSelected) {
                query(tabLink).tab('show');
            }
            return tabLink;
        }
    });

    NodeTabWidget.lastTabDef = {};
    NodeTabWidget.tabDefs = null;
    NodeTabWidget.initializeTabs = function() {
        if (NodeTabWidget.tabDefs === null) {
            NodeTabWidget.tabDefs = {};
            // initially add all root types
            for (var i=0, count=appConfig.rootTypes.length; i<count; i++) {
                var typeName = appConfig.rootTypes[i];
                NodeTabWidget.tabDefs[typeName] = {
                    oid: typeName
                };
            }
            // add tabs opened by the user (stored in cookie)
            var openNodeTabs = Cookie.get("openNodeTabs", {});
            for (var key in openNodeTabs) {
                var tabDef = openNodeTabs[key];
                NodeTabWidget.tabDefs[tabDef.oid] = tabDef;
            }
            var lastTab = Cookie.get("lastTab", {});
            NodeTabWidget.lastTabDef = lastTab;
        }
    };
    NodeTabWidget.initializeTabs();

    return NodeTabWidget;
});