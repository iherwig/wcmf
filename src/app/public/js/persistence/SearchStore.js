define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/_base/xhr",
    "dojo/Deferred",
    "dojo/store/util/QueryResults",
    "./BaseStore"
], function (
    declare,
    lang,
    xhr,
    Deferred,
    QueryResults,
    BaseStore
) {
    var Store = declare([BaseStore], {

        idProperty: "oid",

        query: function(query, options) {
           return QueryResults(this.retrieve());
        },

        retrieve: function() {
            var deferred = new Deferred();
            xhr("GET", {
                url: this.target,
                handleAs: "json",
                headers: {
                    Accept: "application/json"
                }
            }).then(lang.hitch(this, function(data) {
                deferred.resolve(data.list);
            }), function(error) {
                deferred.reject(error);
            });
            return deferred;
        }
    });

    /**
     * Get the store for a given language
     * @param searchterm The searchterm
     * @return Store instance
     */
    Store.getStore = function(searchterm) {
        return new Store({
            target: appConfig.backendUrl+"?action=search&query="+searchterm
        });
    };

    return Store;
});