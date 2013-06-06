define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/when",
    "dojo/aspect",
    "dojo/dom-attr",
    "dojo/query",
    "dojo/on"
], function (
    declare,
    lang,
    when,
    aspect,
    domAttr,
    query,
    on
) {
    return declare([], {

        router: null,

        constructor: function(params) {
            this.router = params.router;

            // setup navigation routes even if an error occurs
            aspect.around(this, "postCreate", function(original) {
                return function() {
                    try {
                        original.call(this);
                    }
                    catch (e) {
                        console.error(e.message);
                        if (this.showNotification) {
                            this.showNotification({
                                type: "error",
                                message: e
                            });
                        }
                    }
                    finally {
                        this.setupRoutes();
                    }
                };
            });
        },

        /**
         * Set up routing for links with class push.
         * The route name is defined in the link's data-dojorama-route attribute,
         * optional path parameters in data-dojorama-pathparams (e.g. "type: Page, id: 12")
         */
        setupRoutes: function() {
            query('.push', this.domNode).forEach(lang.hitch(this, function(node) {
                var routeName = domAttr.get(node, 'data-dojorama-route');
                var route = this.router.getRoute(routeName);
                if (!route) { return; }

                var pathParams, pathParamsStr = domAttr.get(node, 'data-dojorama-pathparams');
                if (pathParamsStr) {
                  pathParams = eval("({ "+pathParamsStr+" })");
                }
                var url = route.assemble(pathParams);
                node.href = url;

                var queryStr = domAttr.get(node, 'data-dojorama-queryparams');
                if (queryStr) {
                    url += queryStr;
                }

                this.own(on(node, 'click', lang.hitch(this, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    when(this.confirmLeave(url), lang.hitch(this, function(result) {
                        if (result === true) {
                            this.push(url);
                        }
                    }));
                })));
            }));
        },

        /**
         * Method to be called before the page is left.
         * Subclasses may override this in order to do some validation and veto
         * page leave. The default implementation returns true.
         * @param url The url to navigate to
         * @return Boolean or Promise that resolves to Boolean
         */
        confirmLeave: function(url) {
            return true;
        }
    });
});