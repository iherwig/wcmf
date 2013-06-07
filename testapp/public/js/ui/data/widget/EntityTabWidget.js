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
    "dojomat/_StateAware",
    "dijit/layout/TabContainer",
    "dijit/layout/ContentPane",
    "../../../Cookie",
    "../../../model/meta/Model",
    "../../../persistence/Store"
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
    _StateAware,
    TabContainer,
    ContentPane,
    Cookie,
    Model,
    Store
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
    var EntityTabWidget = declare([TabContainer, _StateAware], {

        router: null,
        selectedTab: {},
        selectedPanel: {},
        lastTab: {},

        constructor: function(params) {
            declare.safeMixin(this, params);

            this.doLayout = false;
            this.lastTab =  EntityTabWidget.lastTabDef;
            Cookie.set("lastTab", this.lastTab);
        },

        postCreate: function() {
            this.inherited(arguments);
            this.buildTabs();

            // event handlers
            this.own(
                // subscribe to entity change events to change tab links
                topic.subscribe("entity-datachange", lang.hitch(this, function(data) {
                    var tablinks = query("a", dom.byId(this.getTabIdFromOid(data.entity.oid)));
                    if (tablinks.length === 1) {
                        this.setInstanceTabName(data.entity, tablinks[0]);
                    }
                })),
                // allow to close tabs by sending tab-closed event
                topic.subscribe("tab-closed", lang.hitch(this, function(data) {
                    this.removeChild(this.getTabByOid(data.oid));
                    //this.closeTab(data.oid, data.selectLast);
                })),
                // navigate to tab url instead of default behaviour
                this.watch("selectedChildWidget", lang.hitch(this, function(name, oval, nval) {
                    this.selectTab(this.getOidFromTabId(nval.get("id")));
                }))
            );

            this.startup();
        },

        buildTabs: function() {
            // add selected tab
            this.persistTab(this.selectedTab);

            // create all panels
            for (var oid in EntityTabWidget.tabDefs) {
                var isSelected = (oid === this.selectedTab.oid);
                this.createTab(oid, isSelected ? this.selectedPanel : null);
            }
            EntityTabWidget.lastTabDef = this.selectedTab;
        },
/*
        closeTab: function(oid, selectLast) {
            this.unpersistTab({ oid:oid });
            domConstruct.destroy(this.getTabIdFromOid(oid));
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
*/
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

        isInstanceTab: function(oid) {
            var typeName = Model.getTypeNameFromOid(oid);
            var id = Model.getIdFromOid(oid);
            return (id !== typeName);
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
                routeParams: isTypeTab ? { type:typeName } : { type:typeName, id:id }
            };
            return route;
        },

        setTabName: function(oid, tabItem) {
            var typeName = Model.getTypeNameFromOid(oid);
            if (!this.isInstanceTab(oid)) {
                // type tab
                this.setTypeTabName(typeName, tabItem);
            }
            else {
                // instance tab
                var isNew = Model.isDummyOid(oid);
                if (isNew) {
                    this.setInstanceTabName({ oid:oid }, tabItem);
                }
                else {
                    var store = Store.getStore(typeName, appConfig.defaultLanguage);
                    when(store.get(oid), lang.hitch(this, function(entity) {
                            this.setInstanceTabName(entity, tabItem);
                        }), lang.hitch(this, function(error) {
                            this.removeChild(tabItem);
                        })
                    );
                }
            }
        },

        setTypeTabName: function(typeName, tabItem) {
            tabItem.set("title", '<i class="icon-reorder"></i> '+typeName);
        },

        setInstanceTabName: function(entity, tabItem) {
            tabItem.set("title", '<i class="icon-file"></i> '+Model.getDisplayValue(entity)+' ');
        },

        createTab: function(oid, content) {
            // create panel with spinner icon as title, since the
            // real title may be resolved async
            var tabItem = new ContentPane({
                id: this.getTabIdFromOid(oid),
                title: '<i class="icon-spinner icon-spin"></i>'
            });
            // instance tabs are closable
            if (this.isInstanceTab(oid)) {
                tabItem.set("closable", true);
                tabItem.set("onClose", lang.hitch(tabItem, function(container) {
                    container.unpersistTab({ oid:container.getOidFromTabId(this.get("id")) });
                    return true;
                }));
            }

            // set the content for the selected tab
            if (content !== null) {
                tabItem.set("content", content);
                tabItem.set("selected", true);
            }
            this.addChild(tabItem);
            this.setTabName(oid, tabItem);
        },

        getTabIdFromOid: function(oid) {
            return "tab-"+oid.replace(':', '-');
        },

        getOidFromTabId: function(tabId) {
            return tabId.replace(/^tab-/, '').replace(/-/, ':');
        },

        getTabByOid: function(oid) {
            var tabId = this.getTabIdFromOid(oid);
            var tabs = this.getChildren();
            for (var i=0; i<tabs.length; i++) {
                if(tabs[i].id !== tabId) {
                    return tabs[i];
                }
            }
            return null;
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