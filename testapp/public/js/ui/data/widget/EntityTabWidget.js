define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/dom",
    "dojo/dom-construct",
    "dojo/dom-attr",
    "dojo/query",
    "dojo/on",
    "dojo/when",
    "dojo/topic",
    "bootstrap/Tab",
    "dojomat/_StateAware",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "../../../Cookie",
    "../../../model/meta/Model",
    "../../../persistence/Store",
    "dojo/text!./template/EntityTabWidget.html"
], function (
    declare,
    lang,
    dom,
    domConstruct,
    domAttr,
    query,
    on,
    when,
    topic,
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
     * Tab panel for entity types and entity instances.
     * Tabs are identified by oid. Type tabs are not closable, instance tabs are.
     * Opened instance tabs are stored in a cookie.
     * Switching tabs is done by calling the appropriate route for either the type
     * or instance.
     * That implies that the tab panel is expected to be newly instantiated
     * on each view and while all tab links are created only the currently
     * displayed tab panel (given in the selectedPanel property) is added.
     *
     * Tab panel subscribes to the 'tab-closed' event, which may be emitted in order
     * to close a specific tab programatically.
     *
     * Example usage to instantiate the tab panel to display entities of
     * the type 'Author':
     *
     * @code
     * new EntityTabWidget({
     *     router: this.router,
     *     selectedTab: {
     *         oid: 'Author'
     *     },
     *     selectedPanel: gridWidget
     * }, this.tabNode);
     * @endcode
     */
    var EntityTabWidget = declare([_WidgetBase, _TemplatedMixin, _StateAware], {

        router: null,
        selectedTab: {},
        selectedPanel: {},
        lastTab: {},
        templateString: template,

        constructor: function(params) {
            declare.safeMixin(this, params);

            this.lastTab =  EntityTabWidget.lastTabDef;
            Cookie.set("lastTab", this.lastTab);
        },

        postCreate: function() {
            this.inherited(arguments);
            this.buildTabs();

            // subscribe to entity change events to change tab links
            this.own(
                topic.subscribe("entity-datachange", lang.hitch(this, function(data) {
                    var tablinks = query("a", dom.byId(this.getTabLinkIdFromOid(data.entity.oid)));
                    if (tablinks.length === 1) {
                        this.setInstanceTabName(data.entity, tablinks[0]);
                    }
                })),
                topic.subscribe("tab-closed", lang.hitch(this, function(data) {
                    this.closeTab(data.oid, data.selectLast);
                }))
            );
        },

        buildTabs: function() {
            // add selected tab
            this.persistTab(this.selectedTab);

            // iterate over all panels
            for (var oid in EntityTabWidget.tabDefs) {
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
            EntityTabWidget.lastTabDef = this.selectedTab;
        },

        closeTab: function(oid, selectLast) {
            this.unpersistTab({ oid:oid });
            domConstruct.destroy(this.getTabLinkIdFromOid(oid));
            if (selectLast && this.isSelected(oid)) {
                var lastTabOid = this.lastTab.oid;
                var selected = false;
                if (lastTabOid && lastTabOid !== oid && EntityTabWidget.tabDefs[lastTabOid]) {
                    this.selectTab(lastTabOid);
                    selected = true;
                }
                if (!selected) {
                    // fallback
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
            for (var key in EntityTabWidget.tabDefs) {
                this.selectTab(EntityTabWidget.tabDefs[key].oid);
                break;
            }
        },

        isSelected: function(oid) {
            return (oid === this.selectedTab.oid);
        },

        isPersisted: function(tabDef) {
            return EntityTabWidget.tabDefs[tabDef.oid] !== undefined;
        },

        persistTab: function(tabDef) {
            if (!this.isPersisted(tabDef)) {
                EntityTabWidget.tabDefs[tabDef.oid] = tabDef;
                var openInstanceTabs = Cookie.get("openInstanceTabs", {});
                openInstanceTabs[tabDef.oid] = tabDef;
                Cookie.set("openInstanceTabs", openInstanceTabs);
            }
        },

        unpersistTab: function(tabDef) {
            if (this.isPersisted(tabDef)) {
                delete EntityTabWidget.tabDefs[tabDef.oid];
                var openInstanceTabs = Cookie.get("openInstanceTabs", {});
                delete openInstanceTabs[tabDef.oid];
                Cookie.set("openInstanceTabs", openInstanceTabs);
            }
        },

        getRouteForTab: function(oid) {
            var typeName = Model.getSimpleTypeName(Model.getTypeNameFromOid(oid));
            var id = Model.getIdFromOid(oid);
            var isTypeTab = (id === typeName);
            var route = {
                route: isTypeTab ? "entityList" : "entity",
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
                this.setTypeTabName(typeName, tabLink);
            }
            else {
                // instance tab
                var isNew = Model.isDummyOid(oid);
                if (isNew) {
                    this.setInstanceTabName({ oid:oid }, tabLink);
                }
                else {
                    var store = Store.getStore(typeName, 'en');
                    when(store.get(Model.getOid(typeName, id)), lang.hitch(this, function(entity) {
                            this.setInstanceTabName(entity, tabLink);
                        }), lang.hitch(this, function(error) {
                            this.closeTab(oid, true);
                        })
                    );
                }
            }
        },

        setTypeTabName: function(typeName, tabLink) {
            tabLink.innerHTML = '<i class="icon-reorder"></i> '+typeName;
        },

        setInstanceTabName: function(entity, tabLink, create) {
            tabLink.innerHTML = '<i class="icon-file"></i> '+Model.getDisplayValue(entity)+' ';
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
                    var oid = this.getOidFromTabLinkId(domAttr.get(tabItem, "id"));
                    this.closeTab(oid, true);
                }
            })));
        },

        createTabLink: function(oid, isSelected) {
            var tabItem = domConstruct.create("li", {
                id: this.getTabLinkIdFromOid(oid),
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
        },

        getTabLinkIdFromOid: function(oid) {
            return "tab-"+oid.replace(':', '-');
        },

        getOidFromTabLinkId: function(tablinkId) {
            return tablinkId.replace(/^tab-/, '').replace(/-/, ':');
        }
    });

    EntityTabWidget.lastTabDef = {};
    EntityTabWidget.tabDefs = null;
    EntityTabWidget.initializeTabs = function() {
        if (EntityTabWidget.tabDefs === null) {
            EntityTabWidget.tabDefs = {};
            // initially add all root types
            for (var i=0, count=appConfig.rootTypes.length; i<count; i++) {
                var typeName = appConfig.rootTypes[i];
                EntityTabWidget.tabDefs[typeName] = {
                    oid: typeName
                };
            }
            // add tabs opened by the user (stored in cookie)
            var openInstanceTabs = Cookie.get("openInstanceTabs", {});
            for (var key in openInstanceTabs) {
                var tabDef = openInstanceTabs[key];
                EntityTabWidget.tabDefs[tabDef.oid] = tabDef;
            }
            var lastTab = Cookie.get("lastTab", {});
            EntityTabWidget.lastTabDef = lastTab;
        }
    };
    EntityTabWidget.initializeTabs();

    return EntityTabWidget;
});