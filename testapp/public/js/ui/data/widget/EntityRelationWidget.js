define( [
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "../../_include/_NotificationMixin",
    "../../_include/widget/GridWidget",
    "../../../model/meta/Model",
    "../../../persistence/RelationStore",
    "../../../action/Edit",
    "../../../action/Link",
    "dojo/text!./template/EntityRelationWidget.html"
],
function(
    declare,
    lang,
    _WidgetBase,
    _TemplatedMixin,
    _NotificationMixin,
    GridWidget,
    Model,
    RelationStore,
    Edit,
    Link,
    template
) {
    return declare([_WidgetBase, _TemplatedMixin, _NotificationMixin], {

        templateString: template,
        entity: {},
        relation: {},
        router: null,

        constructor: function(args) {
            declare.safeMixin(this, args);

            this.relationName = this.relation.name;
        },

        postCreate: function() {
            this.inherited(arguments);

            new GridWidget({
                type: Model.getTypeNameFromOid(this.entity.oid),
                store: RelationStore.getStore(this.entity.oid, this.relation.name, 'en'),
                actions: this.getGridActions(),
                height: 198
            }, this.gridNode);
        },

        getGridActions: function() {

            var editAction = new Edit({
                router: this.router
            });

            var unlinkAction = {
                name: 'unlink',
                iconClass:  'icon-unlink',
                execute: function(data) {
                    console.log('unlink '+data.oid);
                }
            };

            return [editAction, unlinkAction];
        },

        _create: function(e) {
            // prevent the page from navigating after submit
            e.preventDefault();

        },

        _link: function(e) {
            // prevent the page from navigating after submit
            e.preventDefault();

            new Link({
                router: this.router,
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
            }).execute(this.entity, this.relation);
        }
    });
});