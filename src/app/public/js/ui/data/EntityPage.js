define([
    "require",
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/promise/all",
    "dojo/topic",
    "dojo/Deferred",
    "../_include/_PageMixin",
    "../_include/_NotificationMixin",
    "../_include/widget/NavigationWidget",
    "../_include/widget/ConfirmDlgWidget",
    "./widget/EntityTabWidget",
    "../../persistence/Store",
    "../../persistence/Entity",
    "../../model/meta/Model",
    "../../locale/Dictionary",
    "dojo/text!./template/EntityPage.html"
], function (
    require,
    declare,
    lang,
    all,
    topic,
    Deferred,
    _Page,
    _Notification,
    NavigationWidget,
    ConfirmDlg,
    EntityTabWidget,
    Store,
    Entity,
    Model,
    Dict,
    template
) {
    return declare([_Page, _Notification], {

        templateString: lang.replace(template, Dict.tplTranslate),
        contextRequire: require,
        title: Dict.translate('Content'),

        baseRoute: "entity",
        types: appConfig.rootTypes,
        type: null,
        typeClass: null,
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
            this.type = this.request.getPathParam("type");
            this.typeClass = Model.getType(this.type);

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

            if (!this.isNew) {
                this.setTitle(this.title+" - "+this.oid);

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
                    this.setTitle(this.title+" - "+this.typeClass.getDisplayValue(this.entity));
                }), lang.hitch(this, function(error) {
                    // error
                    this.showBackendError(error);
                }));
            }
            else {
                // create a new entity instance
                this.entity = new Entity({
                    oid: this.oid
                });
                this.entity.setDefaults();
                this.entity.setState("new");
                this.buildForm();
                this.setTitle(this.title+" - "+Dict.translate("New %0%",
                        [Dict.translate(this.type)]));
            }

            this.own(
                topic.subscribe("entity-datachange", lang.hitch(this, function(data) {
                    this.setTitle(this.title+" - "+this.typeClass.getDisplayValue(data.entity));
                }))
            );
        },

        confirmLeave: function(url) {
            if (this.entity && this.entity.getState() === 'dirty') {
                var deferred = new Deferred();
                new ConfirmDlg({
                    title: Dict.translate("Confirm Leave Page"),
                    message: Dict.translate("'%0%' has unsaved changes. Leaving the page will discard these. Do you want to proceed?",
                        [this.typeClass.getDisplayValue(this.entity)]),
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
            require([this.typeClass.detailView || './widget/EntityFormWidget'], lang.hitch(this, function(View) {
                if (View instanceof Function) {
                    // create the tab panel
                    var panel = new View({
                        entity: this.entity,
                        original: this.original,
                        sourceOid: this.isNew ? this.sourceOid : undefined,
                        relation: this.isNew ? this.relation : undefined,
                        language: this.language,
                        page: this,
                        onCreated: lang.hitch(this, function(panel) {
                            // create the tab container
                            var tabs = new EntityTabWidget({
                                route: this.baseRoute,
                                types: this.types,
                                page: this,
                                selectedTab: {
                                    oid: this.oid
                                },
                                selectedPanel: panel
                            }, this.tabNode);
                        })
                    });
                }
                else {
                    // error
                    this.showNotification({
                        type: "error",
                        message: Dict.translate("Detail view class for type '%0%' not found.", [this.type])
                    });
                }
            }));
        }
    });
});