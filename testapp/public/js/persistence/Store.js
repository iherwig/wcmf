define([
    "dojo/_base/xhr",
    "dojo/_base/lang",
    "dojo/_base/declare",
    "dojo/aspect",
    "dojo/store/JsonRest",
    "dojo/store/Cache",
    "dojo/store/Memory",
    "dojo/store/Observable",
    "../model/meta/Model"
], function (
    xhr,
    lang,
    declare,
    aspect,
    JsonRest,
    Cache,
    Memory,
    Observable,
    Model
) {
    var Store = declare([JsonRest], {

      idProperty: 'oid',

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
                  var object2 = lang.clone(object);
                  if (object2.oid !== undefined) {
                      object2.oid = Model.getIdFromOid(object2.oid);
                  }
                  var options2 = lang.clone(options);
                  if (options2.id !== undefined) {
                      options2.id = Model.getIdFromOid(options2.id);
                  }
                  return original.call(this, object2, options2);
              };
          });
          aspect.around(this, "remove", function(original) {
              return function(oid, options) {
                  var id = Model.getIdFromOid(oid);
                  return original.call(this, id, options);
              };
          });
      }

      // TODO:
      // implement DojoNodeSerializer on server that uses refs
      // http://dojotoolkit.org/reference-guide/1.7/dojox/json/ref.html#dojox-json-ref
    });

    /**
     * Registry for shared instances
     */
    Store.instances = {};

    /**
     * Get the store for a given type and language
     * @param typeName The name of the type
     * @param language The language
     * @return Store instance
     */
    Store.getStore = function(typeName, language) {
        // register store under the fully qualified type name
        var fqTypeName = Model.getFullyQualifiedTypeName(typeName);

        if (!Store.instances[fqTypeName]) {
            Store.instances[fqTypeName] = {};
        }
        if (!Store.instances[fqTypeName][language]) {
            var memory = new Memory({
                idProperty: 'oid'
            });
            var jsonRest = new Store({
                target: appConfig.pathPrefix+"/rest/"+language+"/"+fqTypeName+"/"/*+"/?XDEBUG_SESSION_START=netbeans-xdebug"*/
            });
            var cache = new Observable(new Cache(
                jsonRest,
                memory
            ));
            Store.instances[fqTypeName][language] = cache;
        }
        return Store.instances[fqTypeName][language];
    };

    return Store;
});