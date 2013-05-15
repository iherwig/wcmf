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
    "./widget/EntityTabWidget",
    "../../Loader",
    "dojo/text!./template/EntityListPage.html"
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
    EntityTabWidget,
    Loader,
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

            // create widget
            this.buildForm();

            this.own(
                topic.subscribe('ui/_include/widget/GridWidget/unknown-error', lang.hitch(this, function(data) {
                    this.showNotification(data.notification);
                }))
            );
        },

        buildForm: function() {
            new Loader("js/ui/data/widget/EntityListWidget").then(lang.hitch(this, function(Widget) {
                // create the tab panel
                var panel = new Widget({
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
            }));
        }
    });
});