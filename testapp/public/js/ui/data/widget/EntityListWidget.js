define( [
    "require",
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dijit/_WidgetsInTemplateMixin",
    "../../_include/_NotificationMixin",
    "../../_include/widget/GridWidget",
    "../../_include/widget/Button",
    "../../../model/meta/Model",
    "../../../persistence/Store",
    "../../../action/Create",
    "../../../action/Edit",
    "../../../action/Delete",
    "dojo/text!./template/EntityListWidget.html"
],
function(
    require,
    declare,
    lang,
    _WidgetBase,
    _TemplatedMixin,
    _WidgetsInTemplateMixin,
    _Notification,
    GridWidget,
    Button,
    Model,
    Store,
    Create,
    Edit,
    Delete,
    template
) {
    return declare([_WidgetBase, _TemplatedMixin, _WidgetsInTemplateMixin, _Notification], {

        templateString: template,
        contextRequire: require,

        type: null,
        router: null,
        onCreated: null, // function to be called after the widget is created

        constructor: function(args) {
            declare.safeMixin(this, args);
        },

        postCreate: function() {
            this.inherited(arguments);

            new GridWidget({
                type: this.type,
                store: Store.getStore(this.type, appConfig.defaultLanguage),
                actions: this.getGridActions(),
                autoReload: false
            }, this.gridNode);

            if (this.onCreated instanceof Function) {
                this.onCreated(this);
            }
        },

        getGridActions: function() {

            var editAction = new Edit({
                router: this.router
            });

            var duplicateAction = {
                name: 'duplicate',
                iconClass:  'icon-copy',
                execute: function(data) {
                    console.log('duplicate '+data.oid);
                }
            };

            var deleteAction = new Delete({
                router: this.router,
                init: lang.hitch(this, function(data) {
                    this.hideNotification();
                }),
                callback: lang.hitch(this, function(data, result) {
                    // success
                    this.showNotification({
                        type: "ok",
                        message: "'"+Model.getDisplayValue(data)+"' was successfully deleted",
                        fadeOut: true
                    });
                }),
                errback: lang.hitch(this, function(data, result) {
                    // error
                    this.showNotification({
                        type: "error",
                        message: "Backend error"
                    });
                })
            });

            return [editAction, duplicateAction, deleteAction];
        },

        _create: function(e) {
            // prevent the page from navigating after submit
            e.preventDefault();

            new Create({
                router: this.router
            }).execute(e, this.type);
        }
    });
});