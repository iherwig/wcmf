define(["dojo/_base/declare", "dojo/store/Observable", "./DionysosServiceAdapter"
], function(declare, Observable, ServiceAdapter) {

/**
 * @class Store This class represents the client side object repository,
 * that uses a wcmf.persistence.ServiceAdapter instance to communicate with a server.
 * There is one store for each type.
 */
declare("wcmf.persistence.Store", null, {

  /**
   * The wcmf.mode.meta.Node instance that describes objects of this store
   */
  modelClass: null,

  /**
   * The language of the objects of this store
   */
  language: null,

  /**
   * The Service implementation
   */
  serviceAdapter: null,

  /**
   * @see dojo.store.api.Store.idProperty
   */
  idProperty: "oid",

  /**
   * Constructor
   * @param options Parameter object:
   *  - modelClass The class definition of the entities this store contains
   *  - language The language of the objects of this store
   */
  constructor: function(options) {
    this.modelClass = options.modelClass;
    this.language = options.language;
    this.serviceAdapter = new ServiceAdapter(
      this.modelClass,
      this.language
    );
  },

  /**
   * @see dojo.store.api.Store.get
   */
  get: function(id) {
    return this.serviceAdapter.get(id);
  },

  /**
   * @see dojo.store.api.Store.getIdentity
   */
  getIdentity: function(object) {
    return object[this.idProperty];
  },

  /**
   * @see dojo.store.api.Store.put
   */
  put: function(object, directives){
    directives = directives || {};
    directives.id = ("id" in directives) ? directives.id : this.getIdentity(object);
    return this.serviceAdapter.addOrUpdate(object, directives);
  },

  /**
   * @see dojo.store.api.Store.add
   */
  add: function(object, directives){
    directives = directives || {};
    directives.overwrite = false;
    return this.put(object, directives);
  },

  /**
   * @see dojo.store.api.Store.remove
   */
  remove: function(id){
    return this.serviceAdapter.remove(id);
  },

  /**
   * @see dojo.store.api.Store.query
   */
  query: function(query, options){
    return this.serviceAdapter.query(query, options);
  }
});

/**
 * Get the store for a given model class. If the store is
 * not created already, it will be created.
 * @param modelClass The model class to get the store for (subclass of wcmf.model.meta.Node)
 * @param language The language to get the store for
 * @return An instance of wcmf.Store
 */
wcmf.persistence.Store.getStore = function(modelClass, language) {
  if (modelClass instanceof wcmf.model.meta.Node) {
    if (language != undefined) {
      var store = null;
      if (wcmf.persistence.Store.stores[modelClass.name]) {
        store = wcmf.persistence.Store.stores[modelClass.name][language];
      }
      if (store == undefined) {
        // create stores only for known model classes
        // our store is a dojo.data.store
        store = new wcmf.persistence.Store({
          modelClass: modelClass,
          language: language
        });
        // wrap dojo.data.store for usage with object store consumers
        //store = new dojo.store.DataStore({store: store});
        // add observable interface
        store = Observable(store);
        if (wcmf.persistence.Store.stores[modelClass.name] == undefined) {
          wcmf.persistence.Store.stores[modelClass.name] = {};
        }
        wcmf.persistence.Store.stores[modelClass.name][language] = store;
      }
      return store;
    }
    else {
      throw ("Language parameter is undefined");
    }
  }
  else {
    throw ("Unknown modelClass: "+dojo.toJson(modelClass));
  }
}

/**
 * Fetch an object (translation) with a given object id and language
 * @param oid The object id of the object
 * @param language The language of the object
   * @return dojo.Deferred promise (The only parameter is the fetched item)
 */
wcmf.persistence.Store.fetch = function(oid, language) {
  var deferred = new dojo.Deferred();

  // load the object
  var modelClass = wcmf.model.meta.Model.getTypeFromOid(oid);
  var store = this.getStore(modelClass, language);
  store.fetchItemByIdentity({
    identity: oid,
    onItem: function(item) {
      if (item) {
        deferred.callback(item);
      }
    }
  });
  return deferred.promise;
}

/**
 * Store registry.
 */
wcmf.persistence.Store.stores = {};

return wcmf.persistence.Store;
});
