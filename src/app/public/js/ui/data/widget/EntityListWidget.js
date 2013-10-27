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
    "../../../locale/Dictionary",
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
    Dict,
    template
) {
    return declare([_WidgetBase, _TemplatedMixin, _WidgetsInTemplateMixin, _Notification], {

        templateString: lang.replace(template, Dict.tplTranslate),
        contextRequire: require,

        type: null,
        page: null,
        route: '',
        onCreated: null, // function to be called after the widget is created

        constructor: function(args) {
            declare.safeMixin(this, args);

            this.typeName = Dict.translate(this.type);
        },

        postCreate: function() {
            this.inherited(arguments);

            var typeClass = Model.getType(this.type);
            var enabledFeatures = [];
            if (typeClass.isSortable) {
                enabledFeatures.push('DnD');
            }

            new GridWidget({
                type: this.type,
                store: Store.getStore(this.type, appConfig.defaultLanguage),
                actions: this.getGridActions(),
                enabledFeatures: enabledFeatures/*,
                autoReload: false*/
            }, this.gridNode);

            if (this.onCreated instanceof Function) {
                this.onCreated(this);
            }
        },

        getGridActions: function() {

            var editAction = new Edit({
                page: this.page,
                route: this.route
            });

            var duplicateAction = {
                name: 'duplicate',
                iconClass:  'icon-copy',
                execute: function(data) {
                    console.log('duplicate '+data.oid);
                }
            };

            var deleteAction = new Delete({
                page: this.page,
                init: lang.hitch(this, function(data) {
                    this.hideNotification();
                }),
                callback: lang.hitch(this, function(data, result) {
                    // success
                    this.showNotification({
                        type: "ok",
                        message: Dict.translate("'%0%' was successfully deleted", [Model.getDisplayValue(data)]),
                        fadeOut: true
                    });
                }),
                errback: lang.hitch(this, function(data, result) {
                    // error
                    this.showNotification({
                        type: "error",
                        message: Dict.translate("Backend error")
                    });
                })
            });

            return [editAction, duplicateAction, deleteAction];
        },

        _create: function(e) {
            // prevent the page from navigating after submit
            e.preventDefault();

            new Create({
                page: this.page,
                route: this.route
            }).execute(e, this.type);
        }
    });
});