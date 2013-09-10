define([
    "require",
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/topic",
    "../_include/_PageMixin",
    "../_include/_NotificationMixin",
    "../_include/widget/NavigationWidget",
    "../data/widget/EntityTabWidget",
    "../../model/meta/Model",
    "../../locale/Dictionary",
    "dojo/text!./template/EntityListPage.html"
], function (
    require,
    declare,
    lang,
    topic,
    _Page,
    _Notification,
    NavigationWidget,
    EntityTabWidget,
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

        constructor: function(params) {
            this.type = this.request.getPathParam("type");
        },

        postCreate: function() {
            this.inherited(arguments);
            this.setTitle(this.title+" - "+Dict.translate(this.type));

            // create widget
            this.buildForm();

            this.own(
                topic.subscribe('ui/_include/widget/GridWidget/unknown-error', lang.hitch(this, function(data) {
                    this.showNotification(data.notification);
                }))
            );
        },

        buildForm: function() {
            var typeClass = Model.getType(this.type);
            require([typeClass.listView || 'app/ui/data/widget/EntityListWidget'], lang.hitch(this, function(View) {
                if (View instanceof Function) {
                    // create the tab panel
                    var panel = new View({
                        type: this.type,
                        page: this,
                        route: this.baseRoute,
                        onCreated: lang.hitch(this, function(panel) {
                            // create the tab container
                            var tabs = new EntityTabWidget({
                                route: this.baseRoute,
                                types: this.types,
                                page: this,
                                selectedTab: {
                                    oid: this.type
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
                        message: Dict.translate("List view class for type '%0%' not found.", [this.type])
                    });
                }
            }));
        }
    });
});