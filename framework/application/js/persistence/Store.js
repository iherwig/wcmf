/**
 * @class Store This class represents the client side object repository.
 * There is one store for each type. Store uses wcmf.Service to exchange
 * objects and their modifications with the server.
 */
dojo.provide("wcmf.persistence");
dojo.require("dojox.data.JsonRestStore");

dojo.declare("wcmf.persistence.Store", dojox.data.JsonRestStore, {
  // we call the base class constructor manually
  "-chains-": {
    constructor: "manual"
  },

  modelClass: null,
  idAttribute: "oid",
  
  /**
   * Constructor
   * @param options Parameter object:
   *    - modelClass The class definition of the entities this store contains
   *    + All other options defined for dojox.data.JsonRestStore
   */
  constructor: function(options) {
    this.modelClass = options.modelClass;
    
    dojo.mixin(this, {
      target: "rest/"+this.modelClass.type+"/",
      service: new wcmf.persistence.Service(this.modelClass),
      cacheByDefault: true
    }, options);
    
    this.inherited(arguments);

    // autosave
    dojo.connect(this, "onSet", this, function(item, attribute) {
      this.save();
    });
  }
});

/**
 * Get the store for a given model class. If the store is
 * not created already, it will be created.
 * @param modelClass The model class to get the store for (subclass of wcmf.model.base.Class)
 * @return An instance of wcmf.Store
 */ 
wcmf.persistence.Store.getStore = function(modelClass ) {
  if (modelClass instanceof wcmf.model.base.Class) {
    var store = wcmf.persistence.Store.stores[modelClass.type];
    if (store == undefined) {
      // create stores only for known model classes
      store = new wcmf.persistence.Store({
        modelClass: modelClass
      });
      wcmf.persistence.Store.stores[modelClass.type] = store;
    }
    return store;
  }
  else {
    throw ("Unknown modelClass: "+dojo.toJson(modelClass));
  }
}

/**
 * Store registry.
 */ 
wcmf.persistence.Store.stores = {};
