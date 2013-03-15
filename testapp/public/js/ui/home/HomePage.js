define([
    "dojo/_base/declare",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dojomat/_AppAware",
    "dojomat/_StateAware",
    "../_include/NavigationWidget",
    "../_include/FooterWidget",
    "../../Error",
    "bootstrap/Button",
    "bootstrap/Dropdown",
    "dojo/_base/lang",
    "dojo/dom-attr",
    "dojo/dom-form",
    "dojo/json",
    "dojo/query",
    "dojo/request",
    "dojo/on",
    "dojo/text!./template/HomePage.html"
], function (
    declare,
    _WidgetBase,
    _TemplatedMixin,
    _AppAware,
    _StateAware,
    NavigationWidget,
    FooterWidget,
    error,
    button,
    dropdown,
    lang,
    domAttr,
    domForm,
    json,
    query,
    request,
    on,
    template
) {
    return declare([_WidgetBase, _TemplatedMixin, _AppAware, _StateAware], {

        router: null,
        request: null,
        session: null,
        templateString: template,

        constructor: function(params) {
            this.router = params.router;
            this.request = params.request;
            this.session = params.session;
        },

        postCreate: function() {
            this.inherited(arguments);
            this.setTitle('Home');
            new NavigationWidget({activeRoute: "home"}, this.navigationNode);
            new FooterWidget({}, this.footerNode);

            query('a.push', this.domNode).forEach(lang.hitch(this, function(node) {
                var routeName = domAttr.get(node, 'data-dojorama-route');
                var route = this.router.getRoute(routeName);
                if (!route) { return; }

                var pathParams, pathParamsStr = domAttr.get(node, 'data-dojorama-pathparams');
                if (pathParamsStr) {
                  pathParams = eval("({ "+pathParamsStr+" })");
                }
                var url = route.assemble(pathParams);
                node.href = url;

                this.own(on(node, 'click', lang.hitch(this, function (ev) {
                    ev.preventDefault();
                    this.push(url);
                })));
            }));
        },

        startup: function() {
            this.inherited(arguments);
        }
    });
});