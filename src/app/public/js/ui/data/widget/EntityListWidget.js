define( [
    "require",
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/topic",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dijit/_WidgetsInTemplateMixin",
    "../../_include/_NotificationMixin",
    "../../_include/widget/GridWidget",
    "../../_include/widget/Button",
    "../../../model/meta/Model",
    "../../../persistence/Store",
    "../../../persistence/Entity",
    "../../../action/Create",
    "../../../action/Edit",
    "../../../action/Copy",
    "../../../action/Delete",
    "../../../locale/Dictionary",
    "dojo/text!./template/EntityListWidget.html"
],
function(
    require,
    declare,
    lang,
    topic,
    _WidgetBase,
    _TemplatedMixin,
    _WidgetsInTemplateMixin,
    _Notification,
    GridWidget,
    Button,
    Model,
    Store,
    Entity,
    Create,
    Edit,
    Copy,
    Delete,
    Dict,
    template
) {
    return declare([_WidgetBase, _TemplatedMixin, _WidgetsInTemplateMixin, _Notification], {

        templateString: lang.replace(template, Dict.tplTranslate),
        contextRequire: require,

        type: null,
        typeClass: null,
        page: null,
        route: '',
        onCreated: null, // function to be called after the widget is created
        gridWidget: null,

        constructor: function(args) {
            declare.safeMixin(this, args);

            this.typeName = Dict.translate(this.type);
            this.headline = this.typeName;
            this.typeClass = Model.getType(this.type);
        },

        postCreate: function() {
            this.inherited(arguments);

            var enabledFeatures = [];
            if (this.typeClass.isSortable) {
                enabledFeatures.push('DnD');
            }

            this.gridWidget = new GridWidget({
                type: this.type,
                store: Store.getStore(this.type, appConfig.defaultLanguage),
                actions: this.getGridActions(),
                enabledFeatures: enabledFeatures
            }, this.gridNode);

            if (this.onCreated instanceof Function) {
                this.onCreated(this);
            }

            this.own(
                topic.subscribe('ui/_include/widget/GridWidget/error', lang.hitch(this, function(error) {
                    this.showBackendError(error);
                }))
            );
        },

        getGridActions: function() {

            var editAction = new Edit({
                page: this.page,
                route: this.route
            });

            var copyAction = new Copy({
                page: this.page,
                init: lang.hitch(this, function(data) {
                    this.showNotification({
                        type: "ok",
                        message: Dict.translate("Copying")+' <i class="fa fa-spinner fa-spin"></i>'
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

            var deleteAction = new Delete({
                page: this.page,
                init: lang.hitch(this, function(data) {
                    this.hideNotification();
                }),
                callback: lang.hitch(this, function(data, result) {
                    // success
                    this.showNotification({
                        type: "ok",
                        message: Dict.translate("'%0%' was successfully deleted", [this.typeClass.getDisplayValue(data)]),
                        fadeOut: true
                    });
                }),
                errback: lang.hitch(this, function(data, result) {
                    // error
                    this.showBackendError(result);
                })
            });

            return [editAction, copyAction, deleteAction];
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