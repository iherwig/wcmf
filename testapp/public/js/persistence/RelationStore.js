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
    var RelationStore = declare([BaseStore], {
        oid: '',
        relationName: ''
    });

    /**
     * Get the store for a given object id, relation
     * @param oid The object id
     * @param relationName The name of the relation
     * @return RelationStore instance
     */
    RelationStore.getStore = function(oid, relationName) {
        var fqTypeName = Model.getFullyQualifiedTypeName(Model.getTypeNameFromOid(oid));
        var id = Model.getIdFromOid(oid);

        var memory = new Memory({
            idProperty: 'oid'
        });
        var jsonRest = new RelationStore({
            oid: oid,
            relationName: relationName,
            target: appConfig.pathPrefix+"/rest/"+appConfig.defaultLanguage+"/"+fqTypeName+"/"+id+"/"+relationName+"/"
        });
        var cache = new Observable(new Cache(
            jsonRest,
            memory
        ));
        return cache;
    };

    return RelationStore;
});