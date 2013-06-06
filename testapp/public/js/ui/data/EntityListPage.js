define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/topic",
    "dojo/dom-construct",
    "dojo/query",
    "dojo/on",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dojomat/_AppAware",
    "dojomat/_StateAware",
    "../_include/_PageMixin",
    "../_include/_NotificationMixin",
    "../_include/widget/NavigationWidget",
    "./widget/EntityTabWidget",
    "../../model/meta/Model",
    "dojo/text!./template/EntityListPage.html"
], function (
    declare,
    lang,
    topic,
    domConstruct,
    query,
    on,
    _WidgetBase,
    _TemplatedMixin,
    _AppAware,
    _StateAware,
    _Page,
    _Notification,
    NavigationWidget,
    EntityTabWidget,
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
            navi.setActiveRoute("entityList");
            navi.startup();

            // create widget
            this.buildForm();

            this.own(
                topic.subscribe('ui/_include/widget/GridWidget/unknown-error', lang.hitch(this, function(data) {
                    this.showNotification(data.notification);
                }))
            );
        },

        buildForm: function() {
            var typeClass = Model.getType(this.type);
            require([typeClass.listView || 'js/ui/data/widget/EntityListWidget'], lang.hitch(this, function(View) {
                if (View instanceof Function) {
                    // create the tab panel
                    var panel = new View({
                        type: this.type,
                        router: this.router
                    });

                    // create the tab container
                    new EntityTabWidget({
                        router: this.router,
                        selectedTab: {
                            oid: this.type
                        },
                        selectedPanel: panel
                    }, this.tabNode);

                    // setup routes on tab container after loading
                    this.setupRoutes();
                }
                else {
                    // error
                    this.showNotification({
                        type: "error",
                        message: "List view class for type '"+this.type+"' not found."
                    });
                }
            }));
        }
    });
});