define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/promise/all",
    "dojo/topic",
    "dojo/Deferred",
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
    "dojo/text!./template/EntityPage.html"
], function (
    declare,
    lang,
    all,
    topic,
    Deferred,
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
    template
) {
    return declare([_WidgetBase, _TemplatedMixin, _AppAware, _StateAware, _Page, _Notification], {

        request: null,
        session: null,
        templateString: template,
        type: null,
        oid: null, // object id of the object to edit
        isNew: false, // boolean weather the object exists or not

        sourceOid: null, // object id of the source object of a relation
                         // (ignored if isNew == false)
        relation: null, // the relation in which the object should be created
                        // related to sourceOid (ignored if isNew == false)
        entity: null, // entity to edit

        language: appConfig.defaultLanguage,
        isTranslation: false,
        original: null, // untranslated entity

        constructor: function(params) {
            this.request = params.request;
            this.session = params.session;
            this.type = this.request.getPathParam("type");

            var idParam = this.request.getPathParam("id");
            this.oid = Model.getOid(this.type, idParam);
            this.isNew = Model.isDummyOid(this.oid);

            this.sourceOid = this.request.getQueryParam("oid");
            this.relation = this.request.getQueryParam("relation");

            this.language = this.request.getQueryParam("lang") || appConfig.defaultLanguage;
            this.isTranslation = this.language !== appConfig.defaultLanguage;
        },

        postCreate: function() {
            this.inherited(arguments);

            var id = Model.getIdFromOid(this.oid);

            var navi = new NavigationWidget({
            }, this.navigationNode);
            navi.setContentRoute(this.type, id);
            navi.setActiveRoute("entity");
            navi.startup();

            if (!this.isNew) {
                this.setTitle(appConfig.title+' - '+this.oid);

                // create widget when entity is loaded
                var loadPromises = [];
                var oid = Model.getOid(this.type, id);
                var store = Store.getStore(this.type, this.language);
                loadPromises.push(store.get(oid));
                if (this.isTranslation) {
                  // provide original entity for reference
                  var storeOrig = Store.getStore(this.type, appConfig.defaultLanguage);
                  loadPromises.push(storeOrig.get(oid));
                }
                all(loadPromises).then(lang.hitch(this, function(loadResults) {
                    // allow to watch for changes of the object data
                    this.entity = new Entity(loadResults[0]);
                    this.original = this.isTranslation ? loadResults[1] : {};
                    this.buildForm();
                    this.setTitle(appConfig.title+' - '+Model.getDisplayValue(this.entity));
                }), lang.hitch(this, function(error) {
                    // error
                    this.showNotification({
                        type: "error",
                        message: error.message || "Backend error"
                    });
                }));
            }
            else {
                // create a new entity instance
                this.entity = new Entity({
                    oid: this.oid
                });
                this.entity.setState("new");
                this.buildForm();
                this.setTitle(appConfig.title+' - New '+this.type);
            }

            this.own(
                topic.subscribe("entity-datachange", lang.hitch(this, function(data) {
                    this.setTitle(appConfig.title+' - '+Model.getDisplayValue(data.entity));
                }))
            );
        },

        confirmLeave: function(url) {
            if (this.entity && this.entity.getState() === 'dirty') {
                var deferred = new Deferred();
                new ConfirmDlg({
                    title: "Confirm Leave Page",
                    content: "'"+Model.getDisplayValue(this.entity)+"' has unsaved changes. Leaving the page will discard these. Do you want to proceed?",
                    okCallback: lang.hitch(this, function(dlg) {
                        deferred.resolve(true);
                    }),
                    cancelCallback: lang.hitch(this, function(dlg) {
                        deferred.resolve(false);
                    })
                }).show();
                return deferred.promise;
            }
            return this.inherited(arguments);
        },

        buildForm: function() {
            var typeClass = Model.getType(this.type);
            require([typeClass.detailView || 'js/ui/data/widget/EntityFormWidget'], lang.hitch(this, function(View) {
                if (View instanceof Function) {
                    // create the tab panel
                    var panel = new View({
                        entity: this.entity,
                        original: this.original,
                        sourceOid: this.isNew ? this.sourceOid : undefined,
                        relation: this.isNew ? this.relation : undefined,
                        language: this.language,
                        router: this.router
                    });

                    // create the tab container
                    new EntityTabWidget({
                        router: this.router,
                        selectedTab: {
                            oid: this.oid
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
                        message: "Detail view class for type '"+this.type+"' not found."
                    });
                }
            }));
        }
    });
});