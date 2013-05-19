define([
    "dojo/_base/lang",
    "dojo/_base/declare",
    "dojo/aspect",
    "dojo/topic",
    "dojo/store/JsonRest",
    "../model/meta/Model"
], function (
    lang,
    declare,
    aspect,
    topic,
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
                    var optionsTmp = lang.clone(options);
                    optionsTmp.id = undefined;
                    var results = original.call(this, object, optionsTmp);
                    results.then(lang.hitch(this, function() {
                        topic.publish("store-datachange", {
                            store: this,
                            action: options.overwrite ? "put" : "add"
                        });
                    }));
                    return results;
                };
            });
            aspect.around(this, "remove", function(original) {
                return function(oid, options) {
                    var id = Model.getIdFromOid(oid);
                    var results = original.call(this, id, options);
                    results.then(lang.hitch(this, function() {
                        topic.publish("store-datachange", {
                            store: this,
                            action: "remove"
                        });
                    }));
                    return results;
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
            target: appConfig.pathPrefix+"/rest/"+language+"/"+fqTypeName+"/"+id+"/"+relationName+"/"
        });
        return jsonRest;
    };

    return RelationStore;
});