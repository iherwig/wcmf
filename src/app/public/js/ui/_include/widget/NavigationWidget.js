define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dijit/registry",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dijit/_WidgetsInTemplateMixin",
    "dojo/query",
    "dojo/on",
    "dojo/topic",
    "dojo/keys",
    "dojo/NodeList-dom",
    "dojo/dom-class",
    "dojo/dom-style",
    "bootstrap/Dropdown",
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
    query,
    on,
    topic,
    keys,
    nodeListDom,
    domClass,
    domStyle,
    dropdown,
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
            // search field
            this.own(
                on(this.searchField, "keydown", lang.hitch(this, function(event) {
                    if (event.keyCode === keys.ENTER) {
                        event.preventDefault();
                        topic.publish('navigate', 'search', null, {q: this.searchField.get("value")});
                    }
                })),
                on(this.collapseToggleBtn, "click", lang.hitch(this, function(event) {
                    var height = domStyle.get(this.menuCollapse, "height");
                    domStyle.set(this.menuCollapse, "height", height == 0 ? "auto" : 0);
                }))
            );
        },

        startup: function() {
            this.inherited(arguments);

            // hide buttons, if titleOnly
            if (this.titleOnly) {
                query(".main-menu").style("display", "none");
            }
            else {
                // set selected menu
                query(".main-menu").removeClass("active");
                if (this.selected) {
                    query("#"+this.selected).addClass("active");
                }
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