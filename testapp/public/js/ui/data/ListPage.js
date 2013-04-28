define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dojomat/_AppAware",
    "dojomat/_StateAware",
    "../_include/_PageMixin",
    "../_include/_NotificationMixin",
    "../_include/widget/NavigationWidget",
    "../_include/widget/GridWidget",
    "../../persistence/Store",
    "bootstrap/Tab",
    "dojo/dom-construct",
    "dojo/query",
    "dojo/on",
    "../../model/meta/Model",
    "../../model/meta/Node",
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
    GridWidget,
    Store,
    Tab,
    domConstruct,
    query,
    on,
    Model,
    Node,
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

                  var self = this;
                  var actions = [{
                      name: 'edit',
                      iconClass: 'icon-pencil',
                      execute: function(data) {
                          console.log('edit '+data.oid);
                      }
                  }, {
                      name: 'duplicate',
                      iconClass:  'icon-copy',
                      execute: function(data) {
                          console.log('duplicate '+data.oid);
                      }
                  }, {
                      name: 'delete',
                      iconClass:  'icon-trash',
                      execute: function(data) {
                          console.log('delete '+data.oid);
                          var typeName = Node.getTypeFromOid(data.oid);
                          var store = Store.getStore(typeName, 'en');
                          store.remove(data.oid).then(lang.hitch(self, function(results) {
                              // callback completes
                          }), lang.hitch(self, function(error) {
                              // error
                              this.showNotification({
                                  type: "error",
                                  message: "Backend error"
                              });
                          }));
                      }
                  }];

                  var store = Store.getStore(typeName, 'en');
                  this.gridWidget = new GridWidget({
                      request: this.request,
                      router: this.router,
                      store: store,
                      type: Model.getType(typeName),
                      actions: actions
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