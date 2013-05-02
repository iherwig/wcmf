define([
    "dojo/_base/declare"
], function (
    declare
) {
    return declare(null, {

        name: '',
        iconClass:  'icon-asterisk',
        callback: null,
        errback: null,
        progback: null,

        /**
         * Constructor
         * @param init Function to be before action is executed
         * @param callback Function to be called on success
         * @param errback Function to be called on error
         * @param progback Functoin to be called to signal a progress
         */
        constructor: function(init, callback, errback, progback) {
            this.init = init;
            this.callback = callback;
            this.errback = errback;
            this.progback = progback;
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
