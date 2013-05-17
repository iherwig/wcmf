define([
    "dojo/_base/lang",
    "dojo/_base/declare",
    "dojo/aspect",
    "dojo/store/JsonRest",
    "../model/meta/Model"
], function (
    lang,
    declare,
    aspect,
    JsonRest,
    Model
) {
    var RelationStore = declare([JsonRest], {

        idProperty: 'oid',
        oid: '',
        relationName: '',
        language: '',

        constructor: function(options) {
            options.headers = {
                Accept: 'application/javascript, application/json'
            };
            this.inherited(arguments);

            // set id property in order to have url like /{type}/{id}/{relation}/{other_id}
            // instead of /{type}/{id}/{relation}/{other_oid}
            // NOTE: this has to be set on cloned options!
            aspect.around(this, "get", function(original) {
                return function(oid, options) {
                    var id = Model.getIdFromOid(oid);
                    return original.call(this, id, options);
                };
            });
            aspect.around(this, "put", function(original) {
                return function(object, options) {
                    var isUpdate = options.overwrite;
                    var objectTmp = object.getCleanCopy ? object.getCleanCopy() : object;
                    var optionsTmp = lang.clone(options);

                    // set real id only if an existing object is updated
                    // otherwise set to undefined
                    optionsTmp.id = isUpdate ? Model.getIdFromOid(object.oid) : undefined;
                    if (!isUpdate) {
                        objectTmp.oid = Model.getOid(Model.getTypeNameFromOid(objectTmp.oid), this.createBackEndDummyId());
                    }
                    return original.call(this, objectTmp, optionsTmp);
                };
            });
            aspect.around(this, "remove", function(original) {
                return function(oid, options) {
                    var id = Model.getIdFromOid(oid);
                    return original.call(this, id, options);
                };
            });
        }
    });

    /**
     * Get the store for a given object id, relation and language
     * @param oid The object id
     * @param relationName The name of the relation
     * @param language The language
     * @return RelationStore instance
     */
    RelationStore.getStore = function(oid, relationName, language) {
        var fqTypeName = Model.getFullyQualifiedTypeName(Model.getTypeNameFromOid(oid));
        var id = Model.getIdFromOid(oid);

        var jsonRest = new RelationStore({
            oid: oid,
            relationName: relationName,
            language: language,
            target: appConfig.pathPrefix+"/rest/"+language+"/"+fqTypeName+"/"+id+"/"+relationName
        });
        return jsonRest;
    };

    return RelationStore;
});