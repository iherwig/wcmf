define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/aspect",
    "dojo/_base/window",
    "dojo/dom-attr",
    "dojo/dom-style",
    "dojo/query",
    "dojo/on",
    "dojo/topic",
    "dojo/when",
    "dojomat/_AppAware",
    "dojomat/_StateAware",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dijit/_WidgetsInTemplateMixin",
    "../../User"
], function (
    declare,
    lang,
    aspect,
    win,
    domAttr,
    domStyle,
    query,
    on,
    topic,
    when,
    _AppAware,
    _StateAware,
    _WidgetBase,
    _TemplatedMixin,
    _WidgetsInTemplateMixin,
    User
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
                        this.removeRestricted();
                    }
                };
            });
        },

        postCreate: function() {
            this.inherited(arguments);
            this.own(
                // listen to navigate topic
                topic.subscribe("navigate", lang.hitch(this, function(routeName, pathParams, queryParams, windowParams) {
                    var route = this.router.getRoute(routeName);
                    if (!route) { return; }

                    var url = route.assemble(pathParams, queryParams);
                    if (windowParams) {
                        window.open(url, windowParams.name, windowParams.specs);
                    }
                    else {
                        this.pushConfirmed(url);
                    }
                }))
            );
        },

        setTitle: function(title) {
            this.inherited(arguments, [appConfig.title+' - '+title]);
        },

        /**
         * Set up routing for links with class push.
         * The route name is defined in the link's data-wcmf-route attribute,
         * optional path parameters in data-wcmf-pathparams (e.g. "type:'Page', id:12")
         */
        setupRoutes: function() {
            query('.push', win.body()).forEach(lang.hitch(this, function(node) {
                var routeName = domAttr.get(node, 'data-wcmf-route');
                var route = this.router.getRoute(routeName);
                if (!route) { return; }

                var pathParams, pathParamsStr = domAttr.get(node, 'data-wcmf-pathparams');
                if (pathParamsStr) {
                  pathParams = eval("({ "+pathParamsStr+" })");
                }
                var url = route.assemble(pathParams);
                node.href = url;

                var queryStr = domAttr.get(node, 'data-wcmf-queryparams');
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
        },

        /**
         * Remove elements that are restricted to certain roles.
         * The role names are defined in the elements data-wcmf-restrict-roles
         */
        removeRestricted: function() {
            query('[data-wcmf-restrict-roles]', win.body()).forEach(lang.hitch(this, function(node) {
                var roles = domAttr.get(node, 'data-wcmf-restrict-roles').split(",");
                for (var i=0, count=roles.length; i<count; i++) {
                    if (!User.hasRole(roles[i])) {
                        domStyle.set(node, "display", "none");
                    }
                }
            }));
        }
    });
});