define([
    "dojo/_base/declare",
    "dojo/store/Observable",
    "dojo/store/JsonRest",
    "dojo/store/util/QueryResults"
], function (
    declare,
    Observable,
    JsonRest,
    QueryResults
) {
    var TreeStore = declare([JsonRest], {

        idProperty: 'oid',

        constructor: function(options) {
            options.headers = {
                Accept: "application/json"
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
    TreeStore.treeStoreInstance = null;

    /**
     * Get the store
     * @return Store instance
     */
    TreeStore.getStore = function() {
        if (!TreeStore.treeStoreInstance) {
            var jsonRest = new TreeStore({
                target: appConfig.backendUrl+"?action=browseTree"
            });
            var observable = new Observable(jsonRest);
            TreeStore.treeStoreInstance = {
                observable: observable,
                jsonRest: jsonRest
            };
        }
        return TreeStore.treeStoreInstance.observable;
    };

    return TreeStore;
});