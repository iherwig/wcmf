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
      service: new wcmf.persistence.Service(this.modelClass)
    }, options);
    
    this.inherited(arguments);

    // autosave
    dojo.connect(this, "onSet", this, function(item, attribute) {
      this.save();
    });
  }
});

/**
 * Create a store for a given model class
 * @param modelClass The model class to create the store for.
 */
wcmf.persistence.Store.create = function(modelClass) {
  var type = modelClass.type;
  var store = wcmf.persistence.Store.stores[type];
  if (store != undefined) {
    throw ("A store for '"+type+"' already exists.");
  }
  store = new wcmf.persistence.Store({
    modelClass: modelClass
  });
  wcmf.persistence.Store.stores[type] = store;
  return store;
}

/**
 * Get the store for a given model class
 * @param type The classname
 * @return An instance of wcmf.Store
 */ 
wcmf.persistence.Store.getStore = function(type) {
  var store = wcmf.persistence.Store.stores[type];
  if (store == undefined) {
    throw ("A store for '"+type+"' does not exist.");
  }
  return store;
}

/**
 * Store registry.
 */ 
wcmf.persistence.Store.stores = {};
