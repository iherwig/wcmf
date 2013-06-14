define( [
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
    "../../../action/Link",
    "../../../action/Unlink",
    "../../../action/CreateInRelation",
    "dojo/text!./template/EntityRelationWidget.html"
],
function(
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
    Link,
    Unlink,
    CreateInRelation,
    template
) {
    return declare([_WidgetBase, _TemplatedMixin, _WidgetsInTemplateMixin, _NotificationMixin], {

        templateString: template,
        entity: {},
        relation: {},
        router: null,
        gridWidget: null,

        constructor: function(args) {
            declare.safeMixin(this, args);

            this.relationName = this.relation.name;
        },

        postCreate: function() {
            this.inherited(arguments);

            var typeClass = Model.getType(this.relation.type);
            var enabledFeatures = [];
            if (typeClass.isSortable) {
                enabledFeatures.push('DnD');
            }

            this.gridWidget = new GridWidget({
                type: this.relation.type,
                store: RelationStore.getStore(this.entity.oid, this.relation.name),
                actions: this.getGridActions(),
                enabledFeatures: enabledFeatures,
                height: 211
            }, this.gridNode);
        },

        getGridActions: function() {

            var editAction = new Edit({
                router: this.router
            });

            var unlinkAction = new Unlink({
                router: this.router,
                source: this.entity,
                relation: this.relation
            });

            return [editAction, unlinkAction];
        },

        _create: function(e) {
            // prevent the page from navigating after submit
            e.preventDefault();

            new CreateInRelation({
                router: this.router,
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
                    this.showNotification({
                        type: "error",
                        message: "Backend error"
                    });
                })
            }).execute(e, this.relation.type);
        },

        _link: function(e) {
            // prevent the page from navigating after submit
            e.preventDefault();

            var gridRefresh = new Deferred();
            new Link({
                router: this.router,
                source: this.entity,
                relation: this.relation,
                init: lang.hitch(this, function(data) {
                    this.hideNotification();
                    this.gridWidget.postponeRefresh(gridRefresh);
                }),
                callback: lang.hitch(this, function(data, result) {
                    // success
                    gridRefresh.resolve();
                }),
                errback: lang.hitch(this, function(data, result) {
                    // error
                    this.showNotification({
                        type: "error",
                        message: "Backend error"
                    });
                    gridRefresh.resolve();
                })
            }).execute(e);
        }
    });
});