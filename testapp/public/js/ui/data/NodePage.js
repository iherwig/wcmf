define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/topic",
    "dojo/dom-construct",
    "dojo/query",
    "dojo/on",
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
    "../../action/Delete",
    "../../model/meta/Model",
    "dojo/text!./template/NodePage.html"
], function (
    declare,
    lang,
    topic,
    domConstruct,
    query,
    on,
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
    Delete,
    Model,
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

            // create type tab panel
            new NodeTabWidget({
                router: this.router,
                selectedTab: {
                    oid: this.oid
                },
                selectedPanel: {}
            }, this.tabNode);

            this.setupRoutes();
        }
    });
});