define([
    "dojo/_base/declare",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dojomat/_AppAware",
    "dojomat/_StateAware",
    "../_include/_PageMixin",
    "../_include/_NotificationMixin",
    "../_include/NavigationWidget",
    "../_include/FooterWidget",
    "bootstrap/Tab",
    "dojo/dom-construct",
    "dojo/query",
    "dojo/text!./template/ListPage.html"
], function (
    declare,
    _WidgetBase,
    _TemplatedMixin,
    _AppAware,
    _StateAware,
    _Page,
    _Notification,
    NavigationWidget,
    FooterWidget,
    Tab,
    domConstruct,
    query,
    template
) {
    return declare([_WidgetBase, _TemplatedMixin, _AppAware, _StateAware, _Page, _Notification], {

        request: null,
        session: null,
        templateString: template,

        tabContainer: null,

        constructor: function(params) {
            this.request = params.request;
            this.session = params.session;
        },

        postCreate: function() {
            this.inherited(arguments);
            this.setTitle(appConfig.title+' - List');
            new NavigationWidget({activeRoute: "dataIndex"}, this.navigationNode);
            new FooterWidget({}, this.footerNode);

            var tcTabs = query('#typesTabContainer .nav-tabs')[0];
            var tcContent = query('#typesTabContainer .tab-content')[0];
            for (var i=0, count=appConfig.rootTypes.length; i<count; i++) {
                var typeName = appConfig.rootTypes[i];
                var typeCssId = typeName+"Tab";
                var tab = domConstruct.create("li", {
                  innerHTML: '<a href="#'+typeCssId+'" data-toggle="tab">'+typeName+'</a>'
                }, tcTabs);
                var content = domConstruct.create("div", {
                    id: typeCssId,
                    class: 'tab-pane fade',
                    innerHTML: '<p>Raw denim you probably haven\'t heard of them jean shorts Austin. Nesciunt tofu stumptown aliqua, retro synth master cleanse. Mustache cliche tempor, williamsburg carles vegan helvetica. Reprehenderit butcher retro keffiyeh dreamcatcher synth. Cosby sweater eu banh mi, qui irure terry richardson ex squid. Aliquip placeat salvia cillum iphone. Seitan aliquip quis cardigan american apparel, butcher voluptate nisi qui.</p>'
                }, tcContent);
            }
            /*
            //look for divs with a tab attr inside my container node!
            dojo.query("> div[tab]", this.containerNode).forEach(dojo.hitch(this, function(thisNode){
                //start building up our tab object
                var tabObj = {  "paneNode" : thisNode,
                                "label" : dojo.attr(thisNode, "label") ? dojo.attr(thisNode, "label") : "Undefined" };
                //get the tab name, we'll need this!
                var tabName = dojo.attr(thisNode, "tab");

                //add a list item and an anchor tag to the list
                var tabItem = dojo.create("li", {"class":"tab"}, this._listNode);
                var tabLink = dojo.create("a", {"innerHTML": tabObj.label, "href":"javascript:;", "tab":tabName}, tabItem);
                tabObj.tabNode = tabItem;

                //connect the onclick event of our new link
                dojo.connect(tabLink, "onclick", this, "_onTabLinkClick");

                //now add our tabObj to the dijit level tabList so we can
                //get to it later
                this._tabList[tabName] = tabObj;

                //get hold of the selected attr, to see if it should
                //be initially visible - last one found will win this battle
                var selected = dojo.attr(thisNode, "selected");

                if (!selected) {
                    //not selected, so adding hidden class
                    //(hidden class defined in global css as display:none)
                    dojo.addClass(thisNode, "hidden");
                }
                else {
                    //this ones selected, so put the name into our selectedTab var
                    this._selectedTab = tabName;
                    //add a 'selected' class to our tab list item
                    dojo.addClass(tabItem, "selected");
                }
            }));
            */

            this.setupRoutes();
        },

        startup: function() {
            this.inherited(arguments);
        }
    });
});