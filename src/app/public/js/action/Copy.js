define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/request",
    "dojo/topic",
    "dojo/Deferred",
    "./Process",
    "./ActionBase"
], function (
    declare,
    lang,
    request,
    topic,
    Deferred,
    Process,
    ActionBase
) {
    return declare([ActionBase], {

        name: 'copy',
        iconClass: 'icon-copy',
        action: "copy",
        targetOid: null,

        data: null,
        deferred: null,

        /**
         * Copy the given object
         * @param e The event that triggered execution, might be null
         * @param data Object to lock
         */
        execute: function(e, data) {
            if (this.init instanceof Function) {
                this.init(data);
            }
            this.data = data;
            this.deferred = new Deferred();
            var process = new Process({
                callback: lang.hitch(this, this.successHandler),
                errback: lang.hitch(this, this.errorHandler),
                progback: lang.hitch(this, this.progressHandler)
            });
            process.run("copy", {
                oid: data.oid,
                targetoid: this.targetOid
            });
            return this.deferred;
        },

        successHandler: function(response) {
            topic.publish("store-datachange", {
                store: this,
                oid: this.data.oid,
                action: "add"
            });
            this.deferred.resolve();
            if (this.callback instanceof Function) {
                this.callback(this.data, response);
            }
        },

        errorHandler: function(error) {
            if (this.errback instanceof Function) {
                this.errback(this.data, error);
            }
        },

        progressHandler: function(stepName, stepNumber, numberOfSteps, response) {
            if (this.progback instanceof Function) {
                this.progback(this.data, response);
            }
        }
    });
});
