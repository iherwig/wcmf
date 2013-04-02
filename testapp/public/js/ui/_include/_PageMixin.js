/*jshint strict:false */

define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/dom-attr",
    "dojo/query",
    "dojo/on"
], function (
    declare,
    lang,
    domAttr,
    query,
    on
) {
    return declare([], {

        router: null,

        constructor: function(params) {
            this.router = params.router;
        },

        postCreate: function () {
            this.inherited(arguments);
        },

        /**
         * Set up routing for links with class push.
         * The route name is defined in the link's data-dojorama-route attribute,
         * optional path parameters in data-dojorama-pathparams (e.g. "type: Page, id: 12")
         */
        setupRoutes: function() {
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
        }
    });
});