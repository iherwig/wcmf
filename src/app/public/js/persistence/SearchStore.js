define([
    "dojo/_base/declare",
    "./BaseStore"
], function (
    declare,
    BaseStore
) {
    var Store = declare([BaseStore], {
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