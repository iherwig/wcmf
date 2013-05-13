define([
    "dojo/_base/xhr",
    "dojo/_base/lang",
    "dojo/_base/declare",
    "dojo/_base/kernel",
    "dojo/aspect",
    "dojo/store/JsonRest",
    "dojo/store/Cache",
    "dojo/store/Memory",
    "dojo/store/Observable",
    "./Entity",
    "../model/meta/Model"
], function (
    xhr,
    lang,
    declare,
    kernel,
    aspect,
    JsonRest,
    Cache,
    Memory,
    Observable,
    Entity,
    Model
) {
    var Store = declare([JsonRest], {

      idProperty: 'oid',
      typeName: '',
      language: '',

      constructor: function(options) {
          options.headers = {
              Accept: 'application/javascript, application/json'
          };
          this.inherited(arguments);

          // replace oid by id in xhr calls (makes simpler urls)
          aspect.around(this, "get", function(original) {
              return function(oid, options) {
                  var id = Model.getIdFromOid(oid);
                  return original.call(this, id, options);
              };
          });
          aspect.around(this, "put", function(original) {
              return function(object, options) {
                  options.id = Model.getIdFromOid(object.oid);
                  return original.call(this, object, options);
              };
          });
          aspect.around(this, "remove", function(original) {
              return function(oid, options) {
                  var id = Model.getIdFromOid(oid);
                  return original.call(this, id, options);
              };
          });
      },

      updateCache: function(object) {
          var memory = kernel.global.storeInstances[this.typeName][this.language].memory;
          memory.put(object);
      }

      // TODO:
      // implement DojoNodeSerializer on server that uses refs
      // http://dojotoolkit.org/reference-guide/1.7/dojox/json/ref.html#dojox-json-ref
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
                target: appConfig.pathPrefix+"/rest/"+language+"/"+fqTypeName+"/"/*+"/?XDEBUG_SESSION_START=netbeans-xdebug"*/
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