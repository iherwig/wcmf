define([
    "dojo/_base/declare",
    "dojo/store/Cache",
    "dojo/store/Memory",
    "dojo/store/Observable",
    "./BaseStore"
], function (
    declare,
    Cache,
    Memory,
    Observable,
    BaseStore
) {
    var Store = declare([BaseStore], {
        language: ''
    });

    /**
     * Get the store for a given language
     * @param language The language
     * @return Store instance
     */
    Store.getStore = function(language) {
        var memory = new Memory({
            idProperty: 'oid'
        });
        var jsonRest = new Store({
            language: language,
            target: appConfig.backendUrl+"?action=search"
        });
        var cache = new Observable(new Cache(
            jsonRest,
            memory
        ));
        return cache;
    };

    return Store;
});