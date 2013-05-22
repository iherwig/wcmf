define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/aspect",
    "dojo/query",
    "dojo/dom-class",
    "dojo/Deferred",
    "dojomat/_StateAware"
], function (
    declare,
    lang,
    aspect,
    query,
    domClass,
    Deferred,
    _StateAware
) {
    return declare([_StateAware], {

        name: '',
        iconClass:  'icon-asterisk',
        router: null,
        init: null,
        callback: null,
        errback: null,
        progback: null,

        _iconNode: null,
        _hasSpinner: false,

        /**
         * Constructor
         * @param router Instance of routed/Router
         * @param init Function to be before action is executed (optional)
         * @param callback Function to be called on success (optional)
         * @param errback Function to be called on error (optional)
         * @param progback Function to be called to signal a progress (optional)
         */
        constructor: function(args) {
            declare.safeMixin(this, args);

            // set spinner icon, if execution event target has iconClass
            aspect.around(this, "execute", function(original) {
                return function() {
                    var deferred = original.apply(this, arguments);

                    if (deferred instanceof Deferred) {
                        // set spinner icon
                        this._event = null;
                        this._hasSpinner = false;
                        if (arguments.length > 0) {
                            var e = arguments[0];
                            if (e.target) {
                                // icon is either target or a child
                                var iconNodes = query("."+this.iconClass, e.target.parentNode);
                                if (iconNodes.length > 0) {
                                    this._iconNode = iconNodes[0];
                                    this._hasSpinner = true;
                                    domClass.replace(this._iconNode, "icon-spinner icon-spin", this.iconClass);
                                }
                            }
                        }
                        deferred.then(lang.hitch(this, function() {
                            // reset icon
                            if (this._iconNode && this._hasSpinner) {
                                domClass.replace(this._iconNode, this.iconClass, "icon-spinner icon-spin");
                            }
                        }));
                    }
                    return deferred;
                };
            });
        },

        /**
         * Execute the action. Before start, call this.init. When done, call
         * this.callback, this.errback, this.progback. Longer running actions should
         * return a Deferred instance.
         * depending on the execution result.
         * @param e The event that triggered execution, might be null
         */
        execute: function(e) {
            throw("Method execute() must be implemented by concrete action.");
        }
    });
});
