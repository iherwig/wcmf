define( [
    "require",
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/Deferred",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dijit/_WidgetsInTemplateMixin",
    "../../_include/_NotificationMixin",
    "../../_include/widget/GridWidget",
    "../../_include/widget/Button",
    "../../../model/meta/Model",
    "../../../persistence/RelationStore",
    "../../../action/Edit",
    "../../../action/Copy",
    "../../../action/Link",
    "../../../action/Unlink",
    "../../../action/Delete",
    "../../../action/CreateInRelation",
    "../../../locale/Dictionary",
    "dojo/text!./template/EntityRelationWidget.html"
],
function(
    require,
    declare,
    lang,
    Deferred,
    _WidgetBase,
    _TemplatedMixin,
    _WidgetsInTemplateMixin,
    _NotificationMixin,
    GridWidget,
    Button,
    Model,
    RelationStore,
    Edit,
    Copy,
    Link,
    Unlink,
    Delete,
    CreateInRelation,
    Dict,
    template
) {
    return declare([_WidgetBase, _TemplatedMixin, _WidgetsInTemplateMixin, _NotificationMixin], {

        templateString: lang.replace(template, Dict.tplTranslate),
        contextRequire: require,

        route: '',
        entity: {},
        relation: {},
        typeClass: null,
        page: null,
        gridWidget: null,

        constructor: function(args) {
            declare.safeMixin(this, args);

            this.relationName = Dict.translate(this.relation.name);
            this.multiplicity = this.relation.maxMultiplicity;
        },

        postCreate: function() {
            this.inherited(arguments);

            this.typeClass = Model.getType(this.relation.type);
            var enabledFeatures = [];
            if (this.typeClass.isSortable) {
                enabledFeatures.push('DnD');
            }

            this.gridWidget = new GridWidget({
                type: this.relation.type,
                store: RelationStore.getStore(this.entity.oid, this.relation.name),
                actions: this.getGridActions(),
                enabledFeatures: enabledFeatures,
                height: 211
            }, this.gridNode);

            this.createBtn.set("disabled", this.relation.aggregationKind === "none");
            this.linkBtn.set("disabled", this.relation.aggregationKind === "composite");
        },

        getGridActions: function() {

            var editAction = new Edit({
                page: this.page,
                route: this.route
            });

            var copyAction = new Copy({
                page: this.page,
                targetoid: this.entity.oid,
                init: lang.hitch(this, function(data) {
                    this.showNotification({
                        type: "process",
                        message: Dict.translate("Copying '%0%'", [this.typeClass.getDisplayValue(data)])
                    });
                }),
                callback: lang.hitch(this, function(data, result) {
                    // success
                    this.showNotification({
                        type: "ok",
                        message: Dict.translate("'%0%' was successfully copied", [this.typeClass.getDisplayValue(data)]),
                        fadeOut: true
                    });
                    this.gridWidget.refresh();
                }),
                errback: lang.hitch(this, function(data, result) {
                    // error
                    this.showBackendError(result);
                })
            });

            var unlinkAction = new Unlink({
                page: this.page,
                source: this.entity,
                relation: this.relation,
                callback: lang.hitch(this, function(data, result) {
                    // success
                    this.showNotification({
                        type: "ok",
                        message: Dict.translate("'%0%' was successfully unlinked", [this.typeClass.getDisplayValue(data)]),
                        fadeOut: true
                    });
                    this.gridWidget.refresh();
                }),
                errback: lang.hitch(this, function(data, result) {
                    // error
                    this.showBackendError(result);
                })
            });

            var deleteAction = new Delete({
                page: this.page,
                callback: lang.hitch(this, function(data, result) {
                    // success
                    this.showNotification({
                        type: "ok",
                        message: Dict.translate("'%0%' was successfully deleted", [this.typeClass.getDisplayValue(data)]),
                        fadeOut: true
                    });
                    this.gridWidget.refresh();
                }),
                errback: lang.hitch(this, function(data, result) {
                    // error
                    this.showBackendError(result);
                })
            });

            if (this.relation.aggregationKind === "composite") {
                return [editAction, copyAction, deleteAction];
            }
            else {
                return [editAction, copyAction, unlinkAction];
            }
        },

        _create: function(e) {
            // prevent the page from navigating after submit
            e.preventDefault();

            new CreateInRelation({
                page: this.page,
                route: this.route,
                source: this.entity,
                relation: this.relation,
                init: lang.hitch(this, function(data) {
                    this.hideNotification();
                }),
                callback: lang.hitch(this, function(data, result) {
                    // success
                }),
                errback: lang.hitch(this, function(data, result) {
                    // error
                    this.showBackendError(result);
                })
            }).execute(e, this.relation.type);
        },

        _link: function(e) {
            // prevent the page from navigating after submit
            e.preventDefault();

            new Link({
                page: this.page,
                source: this.entity,
                relation: this.relation,
                init: lang.hitch(this, function(data) {
                    this.hideNotification();
                }),
                callback: lang.hitch(this, function(data, result) {
                    // success
                    this.gridWidget.refresh();
                }),
                errback: lang.hitch(this, function(data, result) {
                    // error
                    this.showBackendError(result);
                    this.gridWidget.refresh();
                })
            }).execute(e);
        }
    });
});