define([
    "dojo/_base/declare",
    "dojo/_base/kernel",
    "dojo/store/Observable",
    "dojo/store/JsonRest",
    "dojo/store/util/QueryResults"
], function (
    declare,
    kernel,
    Observable,
    JsonRest,
    QueryResults
) {
    var Store = declare([JsonRest], {

        idProperty: 'oid',

        constructor: function(options) {
            options.headers = {
                Accept: 'application/javascript, application/json'
            };
        },

      	get: function(id, options) {
            throw("Operation 'get' is not supported.");
        },

      	put: function(object, options) {
            throw("Operation 'put' is not supported.");
        },

      	add: function(object, options) {
            throw("Operation 'add' is not supported.");
        },

      	remove: function(id, options) {
            throw("Operation 'remove' is not supported.");
        },

        query: function(query, options) {
            if (query.oid === 'init') {
                return [{
                    oid: 'root',
                    displayText: 'ROOT'
                }]
            }
            else {
                return this.inherited(arguments).then(function(results) {
                    return QueryResults(results["list"]);
                });
            }
        },

        getChildren: function(object) {
            return this.query(object);
        }
    });

    /**
     * Registry for shared instances
     */
    kernel.global.treeStoreInstance = null;

    /**
     * Get the store
     * @return Store instance
     */
    Store.getStore = function() {
        if (!kernel.global.treeStoreInstance) {
            var jsonRest = new Store({
                target: appConfig.backendUrl+"?action=browseTree"
            });
            var observable = new Observable(jsonRest);
            kernel.global.treeStoreInstance = {
                observable: observable,
                jsonRest: jsonRest
            };
        }
        return kernel.global.treeStoreInstance.observable;
    };

    return Store;
});