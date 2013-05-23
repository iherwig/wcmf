define([
    "dojo/_base/lang",
    "dojo/_base/declare",
    "dojo/aspect",
    "dojo/topic",
    "dojo/store/JsonRest",
    "dojox/uuid/generateRandomUuid",
    "../model/meta/Model"
], function (
    lang,
    declare,
    aspect,
    topic,
    JsonRest,
    uuid,
    Model
) {
    return declare([JsonRest], {

        idProperty: 'oid',

        constructor: function(options) {
            options.headers = {
                Accept: 'application/javascript, application/json'
            };

            // set id property in order to have url like /{type}/{id}
            // instead of /{type}/{oid}
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
                    var results = original.call(this, objectTmp, optionsTmp);
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
        },

        createBackEndDummyId: function() {
            return 'wcmf'+uuid().replace(/-/g, '');
        }

        // TODO:
        // implement DojoNodeSerializer on server that uses refs
        // http://dojotoolkit.org/reference-guide/1.7/dojox/json/ref.html#dojox-json-ref
    });
});