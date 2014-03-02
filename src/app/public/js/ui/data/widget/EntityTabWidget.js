define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/when",
    "dojo/topic",
    "dojo/dom-construct",
    "dojo/dom-class",
    "dojo/dom-attr",
    "dojo/query",
    "dojo/on",
    "dojo/NodeList-manipulate",
    "dijit/registry",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dijit/_WidgetsInTemplateMixin",
    "dijit/layout/ContentPane",
    "bootstrap/Tab",
    "../../../Cookie",
    "../../../model/meta/Model",
    "../../../persistence/Store",
    "../../../locale/Dictionary",
    "dojo/text!./template/EntityTabWidget.html"
], function (
    declare,
    lang,
    when,
    topic,
    domConstruct,
    domClass,
    domAttr,
    query,
    on,
    manipulate,
    registry,
    _WidgetBase,
    _TemplatedMixin,
    _WidgetsInTemplateMixin,
    ContentPane,
    Tab,
    Cookie,
    Model,
    Store,
    Dict,
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
     * The route name is derived from the route property:
     * - type route: route+'List'
     * - instance route: route
     *
     * Tab panel subscribes to the 'tab-closed' event, which may be emitted in order
     * to close a specific tab programatically.
     *
     * Example usage to instantiate the tab panel to display entities of
     * the type 'Author':
     *
     * @code
     * new EntityTabWidget({
     *     route: 'entity',
     *     types: appConfig.rootTypes,
     *     page: this,
     *     selectedTab: {
     *         oid: 'Author'
     *     },
     *     selectedPanel: gridWidget
     * }, this.tabNode);
     * @endcode
     */
    var EntityTabWidget = declare([_WidgetBase, _TemplatedMixin, _WidgetsInTemplateMixin], {

        templateString: lang.replace(template, Dict.tplTranslate),
        contextRequire: require,

        route: '',
        types: [],
        page: null,
        selectedTab: {},
        selectedPanel: {},

        // data persisted in cookie
        tabDefs: {},

        constructor: function(params) {
            declare.safeMixin(this, params);

            this.restoreFromCookie();

            this.doLayout = false;
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
                    this.closeTab(data.oid);
                    this.selectTab(data.nextOid);
                })),
                topic.subscribe(this.id+"-selectChild", lang.hitch(this, function(page) {
                    var oid = this.getOidFromTabId(page.id);
                    this.selectTab(oid);
                }))
            );

            this.startup();
        },

        buildTabs: function() {
            // add selected tab
            this.persistTab(this.selectedTab);

            // create all panels
            for (var oid in this.tabDefs) {
                var isSelected = (oid === this.selectedTab.oid);
                this.createTab(oid, isSelected ? this.selectedPanel : null);
            }
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
                    this.page.pushState(url);
                }
            }
            else {
                // select last tab
                for (var key in this.tabDefs) {
                    this.selectTab(this.tabDefs[key].oid);
                }
            }
        },

        closeTab: function(oid) {
            this.unpersistTab(oid);
            registry.byId(this.getTabIdFromOid(oid)).destroy();
            domConstruct.destroy(this.getNavTabIdFromOid(oid));
        },

        isSelected: function(oid) {
            return (oid === this.selectedTab.oid);
        },

        isPersisted: function(oid) {
            return this.tabDefs[oid] !== undefined;
        },

        isInstanceTab: function(oid) {
            var typeName = Model.getTypeNameFromOid(oid);
            var id = Model.getIdFromOid(oid);
            return (id !== typeName);
        },

        persistTab: function(tabDef) {
            var oid = tabDef.oid;
            if (!this.isPersisted(oid)) {
                this.tabDefs[oid] = tabDef;
                var openInstanceTabs = this.getCookieValue("openInstanceTabs");
                openInstanceTabs[oid] = tabDef;
                this.setCookieValue("openInstanceTabs", openInstanceTabs);
            }
        },

        unpersistTab: function(oid) {
            if (this.isPersisted(oid)) {
                delete this.tabDefs[oid];
                var openInstanceTabs = this.getCookieValue("openInstanceTabs");
                delete openInstanceTabs[oid];
                this.setCookieValue("openInstanceTabs", openInstanceTabs);
            }
        },

        getRouteForTab: function(oid) {
            var typeName = Model.getSimpleTypeName(Model.getTypeNameFromOid(oid));
            var id = Model.getIdFromOid(oid);
            var isTypeTab = (id === typeName);
            var route = {
                route: isTypeTab ? this.route+"List" : this.route,
                routeParams: isTypeTab ? { type:typeName } : { type:typeName, id:id }
            };
            return route;
        },

        createTab: function(oid, content) {
            var isSelected = content !== null;

            // create panel with spinner icon as title, since the
            // real title may be resolved async
            var title = '<i class="fa fa-spinner fa-spin"></i>';

            // get the tab url
            var routDef = this.getRouteForTab(oid);
            var route = this.page.getRoute(routDef.route);
            var url = route.assemble(routDef.routeParams);

            // create nav tab
            var tabNavNode = domConstruct.create("li", null, this.navTabNode);
            var linkNode = domConstruct.create("a", {
                id: this.getNavTabIdFromOid(oid),
                innerHTML: title
            }, tabNavNode);
            if (!isSelected) {
                domAttr.set(linkNode, "href", url);
            }

            // create the content for the selected tab
            if (isSelected) {
                var tabItem = new ContentPane({
                    id: this.getTabIdFromOid(oid),
                    content: content
                });
                domConstruct.place(tabItem.domNode, this.tabContentNode);
                domClass.add(tabItem.domNode, "tab-pane");

                // mark as active
                domClass.add(tabItem.domNode, "active");
                domClass.add(tabNavNode, "active");
            }

            this.setTabName(oid);
        },

        setTabName: function(oid) {
            var typeName = Model.getTypeNameFromOid(oid);
            if (!this.isInstanceTab(oid)) {
                // type tab
                this.setTypeTabName(typeName, oid);
            }
            else {
                // instance tab
                var isNew = Model.isDummyOid(oid);
                if (isNew) {
                    this.setInstanceTabName({ oid:oid }, oid);
                }
                else {
                    var store = Store.getStore(typeName, appConfig.defaultLanguage);
                    when(store.get(oid), lang.hitch(this, function(entity) {
                            this.setInstanceTabName(entity, oid);
                        }), lang.hitch(this, function(error) {
                            this.closeTab(oid);
                            this.unpersistTab(oid);
                        })
                    );
                }
            }
        },

        setTypeTabName: function(typeName, oid) {
            var id = this.getNavTabIdFromOid(oid);
            query("#"+id).innerHTML('<i class="fa fa-list"></i> '+Dict.translate(typeName));
        },

        setInstanceTabName: function(entity) {
            var oid = entity.oid;
            var id = this.getNavTabIdFromOid(oid);
            var queryId = id.replace(/\./g, '\\.');
            query("#"+queryId).innerHTML('<i class="fa fa-file"></i> '+Model.getDisplayValue(entity)+
                    ' <span class="close" id="close-'+id+'">×</span>');
            on(query("#close-"+queryId), "click", lang.hitch(this, function(e) {
                e.preventDefault();
                when(this.page.confirmLeave(null), lang.hitch(this, function(result) {
                    if (result === true) {
                        this.closeTab(oid);
                        this.selectTab();
                    }
                }));
            }));
        },

        getNavTabIdFromOid: function(oid) {
            return "nav-"+this.getTabIdFromOid(oid);
        },

        getTabIdFromOid: function(oid) {
            return "tab-"+oid.replace(/:/, '-');
        },

        getOidFromTabId: function(tabId) {
            return tabId.replace(/^tab-/, '').replace(/-/, ':');
        },

        getTabByOid: function(oid) {
            return registry.byId(this.getTabIdFromOid(oid));
        },

        restoreFromCookie: function() {
            this.tabDefs = {};
            // initially add all configured types
            for (var i=0, count=this.types.length; i<count; i++) {
                var typeName = this.types[i];
                this.tabDefs[typeName] = {
                    oid: typeName
                };
            }
            // add tabs opened by the user (stored in cookie)
            var openInstanceTabs = this.getCookieValue("openInstanceTabs");
            for (var key in openInstanceTabs) {
                var tabDef = openInstanceTabs[key];
                this.tabDefs[tabDef.oid] = tabDef;
            }
        },

        getCookieValue: function(name) {
            return Cookie.get(this.route+"_"+name, {});
        },

        setCookieValue: function(name, value) {
            Cookie.set(this.route+"_"+name, value);
        }
    });

    return EntityTabWidget;
});