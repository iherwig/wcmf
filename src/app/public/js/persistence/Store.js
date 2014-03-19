define([
    "dojo/_base/declare",
    "dojo/store/Cache",
    "dojo/store/Memory",
    "dojo/store/Observable",
    "./BaseStore",
    "../model/meta/Model"
], function (
    declare,
    Cache,
    Memory,
    Observable,
    BaseStore,
    Model
) {
    var Store = declare([BaseStore], {
        typeName: '',
        language: '',

        updateCache: function(object) {
            var memory = Store.storeInstances[this.typeName][this.language].memory;
            memory.put(object);
        }
    });

    /**
     * Registry for shared instances
     */
    Store.storeInstances = {};

    /**
     * Get the store for a given type and language
     * @param typeName The name of the type
     * @param language The language
     * @return Store instance
     */
    Store.getStore = function(typeName, language) {
        // register store under the fully qualified type name
        var fqTypeName = Model.getFullyQualifiedTypeName(typeName);

        if (!Store.storeInstances[fqTypeName]) {
            Store.storeInstances[fqTypeName] = {};
        }
        if (!Store.storeInstances[fqTypeName][language]) {
            var memory = new Memory({
                idProperty: 'oid'
            });
            var jsonRest = new Store({
                typeName: fqTypeName,
                language: language,
                target: appConfig.pathPrefix+"/rest/"+language+"/"+fqTypeName+"/"
            });
            var cache = new Observable(new Cache(
                jsonRest,
                memory
            ));
            Store.storeInstances[fqTypeName][language] = {
                cache: cache,
                jsonRest: jsonRest,
                memory: memory
            };
        }
        return Store.storeInstances[fqTypeName][language].cache;
    };

    return Store;
});