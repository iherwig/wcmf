define([
    "require",
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/topic",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dijit/_WidgetsInTemplateMixin",
    "../_include/_PageMixin",
    "../_include/_NotificationMixin",
    "../_include/widget/NavigationWidget",
    "../_include/widget/EntityTabWidget",
    "../../model/meta/Model",
    "../../locale/Dictionary",
    "dojo/text!./template/EntityListPage.html"
], function (
    require,
    declare,
    lang,
    topic,
    _WidgetBase,
    _TemplatedMixin,
    _WidgetsInTemplateMixin,
    _Page,
    _Notification,
    NavigationWidget,
    EntityTabWidget,
    Model,
    Dict,
    template
) {
    return declare([_WidgetBase, _TemplatedMixin, _WidgetsInTemplateMixin, _Page, _Notification], {

        templateString: lang.replace(template, Dict.tplTranslate),
        contextRequire: require,

        type: null,

        constructor: function(params) {
            this.type = this.request.getPathParam("type");
        },

        postCreate: function() {
            this.inherited(arguments);
            this.setTitle(appConfig.title+' - '+this.type);

            var navi = new NavigationWidget({
            }, this.navigationNode);
            navi.setContentRoute(this.type);
            navi.setActiveRoute("entityList");
            navi.startup();

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
            require([typeClass.listView || 'js/ui/data/widget/EntityListWidget'], lang.hitch(this, function(View) {
                if (View instanceof Function) {
                    // create the tab panel
                    var panel = new View({
                        type: this.type,
                        page: this,
                        onCreated: lang.hitch(this, function(panel) {
                            // create the tab container
                            var tabs = new EntityTabWidget({
                                context: 'content',
                                types: appConfig.rootTypes,
                                page: this,
                                selectedTab: {
                                    oid: this.type
                                },
                                selectedPanel: panel
                            }, this.tabNode);

                            // setup routes on tab container after loading
                            this.setupRoutes();
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