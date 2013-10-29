define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/aspect",
    "dojo/_base/window",
    "dojo/dom-attr",
    "dojo/query",
    "dojo/on",
    "dojo/when",
    "dojomat/_AppAware",
    "dojomat/_StateAware",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dijit/_WidgetsInTemplateMixin"
], function (
    declare,
    lang,
    aspect,
    win,
    domAttr,
    query,
    on,
    when,
    _AppAware,
    _StateAware,
    _WidgetBase,
    _TemplatedMixin,
    _WidgetsInTemplateMixin
) {
    return declare([_WidgetBase, _TemplatedMixin, _WidgetsInTemplateMixin, _AppAware, _StateAware], {

        request: null,
        router: null,
        session: null,
        inConfirmLeave: false,

        // attributes to be overridden by subclasses
        title: appConfig.title,

        constructor: function(params) {
            this.request = params.request;
            this.router = params.router;
            this.session = params.session;

            // setup navigation routes even if an error occurs
            aspect.around(this, "startup", function(original) {
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
                        this.setTitle(this.title);
                        this.setupRoutes();
                    }
                };
            });
        },

        setTitle: function(title) {
            this.inherited(arguments, [appConfig.title+' - '+title]);
        },

        /**
         * Set up routing for links with class push.
         * The route name is defined in the link's data-dojorama-route attribute,
         * optional path parameters in data-dojorama-pathparams (e.g. "type:'Page', id:12")
         */
        setupRoutes: function() {
            query('.push', win.body()).forEach(lang.hitch(this, function(node) {
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
                    this.pushConfirmed(url);
                })));
            }));
        },

        getRoute: function(path) {
            return this.router.getRoute(path);
        },

        /**
         * Push with asking for confimation
         */
        pushConfirmed: function (url) {
            if (!this.inConfirmLeave) {
                this.inConfirmLeave = true;
                when(this.confirmLeave(url), lang.hitch(this, function(result) {
                    this.inConfirmLeave = false;
                    if (result === true) {
                        this.pushState(url);
                    }
                }));
            }
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