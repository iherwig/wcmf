define([
    "dojo/_base/declare",
    "dojomat/_StateAware"
], function (
    declare,
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
        },

        /**
         * Execute the action. Before start, call this.init. When done, call
         * this.callback, this.errback, this.progback
         * depending on the execution result.
         */
        execute: function() {
            throw("Method execute() must be implemented by concrete action.");
        }
    });
});
