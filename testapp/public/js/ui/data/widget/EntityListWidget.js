define( [
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dojomat/_StateAware",
    "../../_include/_NotificationMixin",
    "../../_include/widget/GridWidget",
    "bootstrap/Button",
    "../../../model/meta/Model",
    "../../../action/Delete",
    "dojo/text!./template/EntityListWidget.html"
],
function(
    declare,
    lang,
    _WidgetBase,
    _TemplatedMixin,
    _StateAware,
    _Notification,
    GridWidget,
    Button,
    Model,
    Delete,
    template
) {
    return declare([_WidgetBase, _TemplatedMixin, _StateAware, _Notification], {

        templateString: template,
        type: null,
        router: null,

        constructor: function(args) {
            declare.safeMixin(this, args);
        },

        postCreate: function() {
            this.inherited(arguments);

            new GridWidget({
                type: this.type,
                actions: this.getGridActions()
            }, this.gridNode);
        },

        getGridActions: function() {

            var editAction = {
                name: 'edit',
                iconClass: 'icon-pencil',
                execute: lang.hitch(this, function(data) {
                    var route = this.router.getRoute("entity");
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
        },

        _create: function(e) {
            // prevent the page from navigating after submit
            e.preventDefault();
            console.log("create "+this.type);
        }
    });
});