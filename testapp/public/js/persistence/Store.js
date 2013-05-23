define([
    "dojo/_base/declare",
    "dojo/_base/kernel",
    "dojo/store/Cache",
    "dojo/store/Memory",
    "dojo/store/Observable",
    "./BaseStore",
    "../model/meta/Model"
], function (
    declare,
    kernel,
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
            var memory = kernel.global.storeInstances[this.typeName][this.language].memory;
            memory.put(object);
        }
    });

    /**
     * Registry for shared instances
     */
    kernel.global.storeInstances = {};

    /**
     * Get the store for a given type and language
     * @param typeName The name of the type
     * @param language The language
     * @return Store instance
     */
    Store.getStore = function(typeName, language) {
        // register store under the fully qualified type name
        var fqTypeName = Model.getFullyQualifiedTypeName(typeName);

        if (!kernel.global.storeInstances[fqTypeName]) {
            kernel.global.storeInstances[fqTypeName] = {};
        }
        if (!kernel.global.storeInstances[fqTypeName][language]) {
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
            kernel.global.storeInstances[fqTypeName][language] = {
                cache: cache,
                jsonRest: jsonRest,
                memory: memory
            }
        }
        return kernel.global.storeInstances[fqTypeName][language].cache;
    };

    return Store;
});