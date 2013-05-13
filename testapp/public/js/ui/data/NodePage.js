define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/_base/Deferred",
    "dojo/dom-construct",
    "dojo/query",
    "dojo/promise/Promise",
    "dojo/on",
    "dojo/when",
    "dojo/topic",
    "bootstrap/Tab",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dojomat/_AppAware",
    "dojomat/_StateAware",
    "../_include/_PageMixin",
    "../_include/_NotificationMixin",
    "../_include/widget/NavigationWidget",
    "../_include/widget/GridWidget",
    "./widget/NodeTabWidget",
    "../../persistence/Store",
    "../../persistence/Entity",
    "../../model/meta/Model",
    "../../Loader",
    "dojo/text!./template/NodePage.html"
], function (
    declare,
    lang,
    Deferred,
    domConstruct,
    query,
    Promise,
    on,
    when,
    topic,
    Tab,
    _WidgetBase,
    _TemplatedMixin,
    _AppAware,
    _StateAware,
    _Page,
    _Notification,
    NavigationWidget,
    GridWidget,
    NodeTabWidget,
    Store,
    Entity,
    Model,
    Loader,
    template
) {
    return declare([_WidgetBase, _TemplatedMixin, _AppAware, _StateAware, _Page, _Notification], {

        request: null,
        session: null,
        templateString: template,
        type: null,
        oid: null,

        constructor: function(params) {
            this.request = params.request;
            this.session = params.session;
            this.type = this.request.getPathParam("type");
            this.oid = Model.getOid(this.type, this.request.getPathParam("id"));
        },

        postCreate: function() {
            this.inherited(arguments);
            this.setTitle(appConfig.title+' - '+this.oid);
            new NavigationWidget({
                activeRoute: "nodeList"
            }, this.navigationNode);

            // create tab panel widget
            var store = Store.getStore(this.type, 'en');
            var id = Model.getIdFromOid(this.oid);
            when(store.get(Model.getOid(this.type, id)), lang.hitch(this, function(node) {
                node = new Entity(node);
                this.buildForm(node);
            }));
            topic.subscribe("entity-datachange", lang.hitch(this, function(data) {
                this.setTitle(appConfig.title+' - '+Model.getDisplayValue(data.node));
            }));
        },

        buildForm: function(node) {
            this.setTitle(appConfig.title+' - '+Model.getDisplayValue(node));

            new Loader("js/ui/data/widget/NodeFormWidget").then(lang.hitch(this, function(Widget) {
                // create the node form
                var form = new Widget({
                    nodeData: node
                });

                // create type tab panel
                new NodeTabWidget({
                    router: this.router,
                    selectedTab: {
                        oid: this.oid
                    },
                    selectedPanel: form
                }, this.tabNode);

                this.setupRoutes();
            }));
        }
    });
});