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
    "dojo/text!./template/ListPage.html"
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

        constructor: function(params) {
            this.request = params.request;
            this.session = params.session;
            this.type = this.request.getPathParam("type");
        },

        postCreate: function() {
            this.inherited(arguments);
            this.setTitle(appConfig.title+' - '+this.type);
            
            var navi = new NavigationWidget({
            }, this.navigationNode);
            navi.setContentRoute(this.type);
            navi.setActiveRoute("nodeList");

            // create tab panel widget
            var store = Store.getStore(this.type, 'en');
            var gridWidget = new GridWidget({
                request: this.request,
                router: this.router,
                store: store,
                type: Model.getType(this.type),
                actions: this.getGridActions()
            });

            // create type tab panel
            new NodeTabWidget({
                router: this.router,
                selectedTab: {
                    oid: this.type
                },
                selectedPanel: gridWidget
            }, this.tabNode);

            this.own(
                topic.subscribe('ui/_include/widget/GridWidget/unknown-error', lang.hitch(this, function(data) {
                    this.showNotification(data.notification);
                }))
            );
            this.setupRoutes();
        },

        getGridActions: function() {

            var editAction = {
                name: 'edit',
                iconClass: 'icon-pencil',
                execute: lang.hitch(this, function(data) {
                    console.log('edit '+data.oid);
                    var route = this.router.getRoute("node");
                    var type = Model.getSimpleTypeName(Model.getTypeNameFromOid(data.oid));
                    var id = Model.getIdFromOid(data.oid);
                    var pathParams = { type:type, id:id };
                    var url = route.assemble(pathParams);
                    this.push(url);
                })
            };

            var duplicateAction = {
                name: 'duplicate',
                iconClass:  'icon-copy',
                execute: function(data) {
                    console.log('duplicate '+data.oid);
                }
            };

            var deleteAction = new Delete(lang.hitch(this, function(data) {
                    this.hideNotification();
                }), lang.hitch(this, function(data, result) {
                    // success
                    this.showNotification({
                        type: "ok",
                        message: "'"+Model.getDisplayValue(data)+"' was successfully deleted",
                        fadeOut: true
                    });
                }), lang.hitch(this, function(data, result) {
                    // error
                    this.showNotification({
                        type: "error",
                        message: "Backend error"
                    });
                })
            );

            return [editAction, duplicateAction, deleteAction];
        }
    });
});