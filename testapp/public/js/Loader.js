define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/_base/Deferred"
], function(
    declare,
    lang,
    Deferred
) {
    return declare(null, {

        loader: null,

        constructor: function(requirements) {
            this.loader = new Deferred();
            require([requirements], lang.hitch(this, function(requirements) {
                this.loader.resolve(requirements);
            }));
        },

        then: function(callback, errback) {
            this.loader.then(callback, errback);
        }
    });
});
