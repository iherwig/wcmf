define([
    "dojo/_base/declare",
    "dojo/_base/lang",
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
    "../../../User",
    "../../../model/meta/Model",
    "../../../locale/Dictionary",
    "dojo/text!./template/NavigationWidget.html",
    "dojo/domReady!"
], function (
    declare,
    lang,
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
                on(this.searchField, "keydown", lang.hitch(this, function(e) {
                    if (e.keyCode === keys.ENTER) {
                        e.preventDefault();
                        topic.publish('navigate', 'search', null, {q: this.searchField.get("value")});
                    }
                })),
                on(this.collapseToggleBtn, "click", lang.hitch(this, function(e) {
                    var height = domStyle.get(this.menuCollapse, "height");
                    domStyle.set(this.menuCollapse, "height", height == 0 ? "auto" : 0);
                }))
            );
        },

        startup: function() {
            this.inherited(arguments);

            // initialize dropdowns
            query(".dropdown-toggle").on("click", lang.hitch(this, function(e) {
                e.preventDefault();
                var menu = query(e.target).closest(".main-menu.dropdown")[0];
                if (menu) {
                    if (domClass.contains(menu, "open")) {
                        domClass.remove(menu, "open");
                    }
                    else {
                        domClass.add(menu, "open");
                    }
                }
            }));

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