define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dijit/registry",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dijit/_WidgetsInTemplateMixin",
    "dijit/MenuBar",
    "dijit/MenuBarItem",
    "dijit/PopupMenuBarItem",
    "dijit/DropDownMenu",
    "dijit/MenuSeparator",
    "dijit/MenuItem",
    "dijit/form/TextBox",
    "dojo/query",
    "dojo/on",
    "dojo/topic",
    "dojo/keys",
    "dojo/NodeList-dom",
    "../../../User",
    "../../../model/meta/Model",
    "../../../locale/Dictionary",
    "dojo/text!./template/NavigationWidget.html",
    "dojo/domReady!"
], function (
    declare,
    lang,
    registry,
    _WidgetBase,
    _TemplatedMixin,
    _WidgetsInTemplateMixin,
    MenuBar,
    MenuBarItem,
    PopupMenuBarItem,
    Menu,
    MenuSeparator,
    MenuItem,
    TextBox,
    query,
    on,
    topic,
    keys,
    nodeListDom,
    User,
    Model,
    Dict,
    template
) {
    return declare([_WidgetBase, _TemplatedMixin, _WidgetsInTemplateMixin], {

        titleOnly: false,
        templateString: lang.replace(template, Dict.tplTranslate),
        selected: null,

        constructor: function (params) {
            this.titleOnly = params.titleOnly;

            // template variables
            this.title = appConfig.title;
            this.userName = User.getLogin();
            this.firstRootType = Model.getSimpleTypeName(appConfig.rootTypes[0]);
            this.userType = Model.getSimpleTypeName(appConfig.userType);
        },

        postCreate: function() {
            // set selected menu
            if (this.selected) {
                registry.byId(this.selected)._setSelected(true);
            }

            // search field
            this.own(
                on(this.searchField, "keydown", lang.hitch(this, function(event) {
                    if (event.keyCode === keys.ENTER) {
                        event.preventDefault();
                        topic.publish('navigate', 'search', null, {q: this.searchField.get("value")});
                    }
                }))
            );
        },

        startup: function() {
            this.inherited(arguments);

            // hide buttons, if titleOnly
            if (this.titleOnly) {
                query(".main-menu").style("display", "none");
            }
        },

        setContentRoute: function(type, id) {
            var contentNavNode = query("#navContent");
            contentNavNode.attr("data-wcmf-route", id !== undefined ? "entity" : "entityList");
            var routeParamStr = "type: '"+type+"'";
            if (id !== undefined) {
              routeParamStr += ", id: '"+id+"'";
            }
            contentNavNode.attr("data-wcmf-pathparams", routeParamStr);
        }
    });
});