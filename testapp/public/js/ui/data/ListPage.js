define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dojomat/_AppAware",
    "dojomat/_StateAware",
    "../_include/_PageMixin",
    "../_include/_NotificationMixin",
    "../_include/NavigationWidget",
    "../_include/FooterWidget",
    "./widget/GridWidget",
    "dojo/store/JsonRest",
    "bootstrap/Tab",
    "dojo/dom-construct",
    "dojo/query",
    "dojo/on",
    "dojo/text!./template/ListPage.html"
], function (
    declare,
    lang,
    _WidgetBase,
    _TemplatedMixin,
    _AppAware,
    _StateAware,
    _Page,
    _Notification,
    NavigationWidget,
    FooterWidget,
    GridWidget,
    JsonRest,
    Tab,
    domConstruct,
    query,
    on,
    template
) {
    return declare([_WidgetBase, _TemplatedMixin, _AppAware, _StateAware, _Page, _Notification], {

        request: null,
        session: null,
        templateString: template,
        selectedType: null,

        tabContainer: null,

        gridWidget: null,

        constructor: function(params) {
            this.request = params.request;
            this.session = params.session;
            this.selectedType = this.request.getPathParam("type");
        },

        postCreate: function() {
            this.inherited(arguments);
            this.setTitle(appConfig.title+' - '+this.selectedType);
            new NavigationWidget({activeRoute: "dataIndex"}, this.navigationNode);
            new FooterWidget({}, this.footerNode);

            // create type tab panes
            var tcTabs = query("#typesTabContainer .nav-tabs")[0];
            var tcContent = query("#typesTabContainer .tab-content")[0];
            for (var i=0, count=appConfig.rootTypes.length; i<count; i++) {
                var typeName = appConfig.rootTypes[i];
                var isSelected = typeName === this.selectedType;

                var tabItem = domConstruct.create("li", {
                    class: isSelected ? "active" : ""
                }, tcTabs);

                var tabLink = domConstruct.create("a", {
                    href: "#"+typeName,
                    'data-dojorama-route': "dataIndex",
                    'data-dojorama-pathparams': "type: '"+typeName+"'",
                    'data-toggle': "tab",
                    class: "push",
                    innerHTML: typeName
                }, tabItem);

                if (isSelected) {
                  var content = domConstruct.create("div", {
                      id: typeName+"Tab",
                      class: isSelected ? "tab-pane fade in active" : "tab-pane fade",
                      innerHTML: '<div id="typeGrid"></div>'
                  }, tcContent);
                  var store = new JsonRest({
                      target: appConfig.pathPrefix+"/rest/en/"+typeName+"/"
                  });
                  this.gridWidget = new GridWidget({
                      request: this.request,
                      router: this.router,
                      store: store
                  }, "typeGrid");
                }
            }
            query('#typesTabContainer a[href="#'+this.selectedType+'"]').tab('show');

            this.setupRoutes();
        },

        startup: function() {
            this.inherited(arguments);
            this.gridWidget.startup();
        }
    });
});