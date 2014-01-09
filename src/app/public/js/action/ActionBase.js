define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/aspect",
    "dojo/query",
    "dojo/dom-class"
], function (
    declare,
    lang,
    aspect,
    query,
    domClass
) {
    return declare([], {

        name: '',
        iconClass:  'icon-asterisk',
        init: null,
        callback: null,
        errback: null,
        progback: null,

        _iconNode: null,
        _hasSpinner: false,

        page: null,

        /**
         * Constructor
         * @param page Instance of _PageMixin
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

                    if (deferred && deferred.then instanceof Function) {
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
         * Execute the action.
         * @param e The event that triggered execution, might be null
         *
         * Implementation hints: Before start, call this.init. When done, call
         * this.callback, this.errback, this.progback depending on the execution result.
         * Longer running actions should return a Deferred instance.
         */
        execute: function(e) {
            throw("Method execute() must be implemented by concrete action.");
        }
    });
});
