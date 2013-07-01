define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/when",
    "dojo/topic",
    "dijit/registry",
    "dijit/layout/TabContainer",
    "dijit/layout/ContentPane",
    "../../../Cookie",
    "../../../model/meta/Model",
    "../../../persistence/Store"
], function (
    declare,
    lang,
    when,
    topic,
    registry,
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
     *     page: this,
     *     selectedTab: {
     *         oid: 'Author'
     *     },
     *     selectedPanel: gridWidget
     * }, this.tabNode);
     * @endcode
     */
    var EntityTabWidget = declare([TabContainer], {

        page: null,
        selectedTab: {},
        selectedPanel: {},
        lastTab: {},

        constructor: function(params) {
            declare.safeMixin(this, params);

            this.doLayout = false;
            this.lastTab =  EntityTabWidget.lastTabDef;
            this.isListeningToSelect = true;
            Cookie.set("lastTab", this.lastTab);
        },

        postCreate: function() {
            this.inherited(arguments);
            this.buildTabs();

            // event handlers
            this.own(
                // subscribe to entity change events to change tab links
                topic.subscribe("entity-datachange", lang.hitch(this, function(data) {
                    var tab = this.getTabByOid(data.entity.oid);
                    if (tab !== null) {
                        this.setInstanceTabName(data.entity, tab);
                    }
                })),
                // allow to close tabs by sending tab-closed event
                // data is expected to have the following properties:
                // - oid: the oid of the tab to close
                // - nextOid: optional, the oid of the next tab to open
                topic.subscribe("tab-closed", lang.hitch(this, function(data) {
                    this.unpersistTab({ oid:data.oid });
                    if (data.nextOid) {
                        // prevent selecting the previous tab
                        this.isListeningToSelect = false;
                    }
                    this.closeTab(this.getTabByOid(data.oid));
                    this.selectTab(data.nextOid);
                    this.isListeningToSelect = true;
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

        /**
         * Overwritten from StackContainer to detect tab changes
         */
        selectChild: function(page, animate) {
            var oid = this.getOidFromTabId(page.id);
            this.selectTab(oid);
        },

        selectTab: function(oid) {
            if (oid !== undefined) {
                var routDef = this.getRouteForTab(oid);
                var route = this.page.getRoute(routDef.route);
                var url = route.assemble(routDef.routeParams);
                if (this.getTabByOid(this.selectedTab.oid)) {
                    // if the tab for the current oid is opened, we need to confirm
                    this.page.pushConfirmed(url);
                }
                else {
                    this.page.push(url);
                }
            }
        },

        closeTab: function(tab) {
            try {
                this.removeChild(tab);
            }
            catch(e) {
                // tab container tries to set a style on a not existing node
            }
            finally {
                registry.byId(tab.get("id")).destroy();
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
                            this.closeTab(tabItem);
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
                    // close by ourselves (return false)
                    container.unpersistTab({ oid:container.getOidFromTabId(this.get("id")) });
                    when(container.page.confirmLeave(null), function(result) {
                        if (result === true) {
                            container.closeTab(tabItem);
                        }
                    });
                    return false;
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
                if(tabs[i].id === tabId) {
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