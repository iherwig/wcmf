define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/when",
    "dojo/topic",
    "dojo/Deferred",
    "bootstrap/Tab",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dojomat/_AppAware",
    "dojomat/_StateAware",
    "../_include/_PageMixin",
    "../_include/_NotificationMixin",
    "../_include/widget/NavigationWidget",
    "../_include/widget/ConfirmDlgWidget",
    "./widget/EntityTabWidget",
    "../../persistence/Store",
    "../../persistence/Entity",
    "../../model/meta/Model",
    "../../Loader",
    "dojo/text!./template/EntityPage.html"
], function (
    declare,
    lang,
    when,
    topic,
    Deferred,
    Tab,
    _WidgetBase,
    _TemplatedMixin,
    _AppAware,
    _StateAware,
    _Page,
    _Notification,
    NavigationWidget,
    ConfirmDlg,
    EntityTabWidget,
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
        entity: null,

        constructor: function(params) {
            this.request = params.request;
            this.session = params.session;
            this.type = this.request.getPathParam("type");
            this.oid = Model.getOid(this.type, this.request.getPathParam("id"));
        },

        postCreate: function() {
            this.inherited(arguments);
            this.setTitle(appConfig.title+' - '+this.oid);

            var id = Model.getIdFromOid(this.oid);

            var navi = new NavigationWidget({
            }, this.navigationNode);
            navi.setContentRoute(this.type, id);
            navi.setActiveRoute("entity");

            // create tab panel widget
            var store = Store.getStore(this.type, 'en');
            when(store.get(Model.getOid(this.type, id)), lang.hitch(this, function(entity) {
                // allow to watch for changes of the object data
                this.entity = new Entity(entity);
                this.buildForm();
            }), lang.hitch(this, function(error) {
                // error
                this.showNotification({
                    type: "error",
                    message: error.message || "Backend error"
                });
            }));
            this.own(
                topic.subscribe("entity-datachange", lang.hitch(this, function(data) {
                    this.setTitle(appConfig.title+' - '+Model.getDisplayValue(data.entity));
                }))
            );
        },

        confirmLeave: function(url) {
            if (this.entity.getState() === 'dirty') {
                var deferred = new Deferred();
                ConfirmDlg.showConfirm({
                    title: "Confirm Leave Page",
                    content: "'"+Model.getDisplayValue(this.entity)+"' has unsaved changes. Leaving the page will discard these. Do you want to proceed?",
                    okCallback: lang.hitch(this, function() {
                        deferred.resolve(true);
                    }),
                    cancelCallback: lang.hitch(this, function() {
                        deferred.resolve(false);
                    })
                });
                return deferred.promise;
            }
            return this.inherited(arguments);
        },

        buildForm: function() {
            this.setTitle(appConfig.title+' - '+Model.getDisplayValue(this.entity));

            new Loader("js/ui/data/widget/EntityFormWidget").then(lang.hitch(this, function(Widget) {
                // create the entity form
                var form = new Widget({
                    entity: this.entity
                });

                // create type tab panel
                new EntityTabWidget({
                    router: this.router,
                    selectedTab: {
                        oid: this.oid
                    },
                    selectedPanel: form
                }, this.tabNode);

                // setup routes on tab container after loading
                this.setupRoutes();
            }));
        }
    });
});