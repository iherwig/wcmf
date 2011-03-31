/**
 * @class Store This class represents the client side object repository.
 * There is one store for each type. Store uses wcmf.DionysosService to exchange
 * objects and their modifications with the server.
 */
dojo.provide("wcmf.persistence");
dojo.require("dojox.data.JsonRestStore");

dojo.declare("wcmf.persistence.Store", dojox.data.JsonRestStore, {
  // we call the base class constructor manually
  "-chains-": {
    constructor: "manual"
  },

  /**
   * The wcmf.mode.meta.Node instance that describes objects
   * of this store
   */
  modelClass: null,

  /**
   * The identifier attribute of the contained objects
   */
  idAttribute: "oid",

  /**
   * The Service implementation
   */
  serviceImpl: null,

  /**
   * Constructor
   * @param options Parameter object:
   *    - modelClass The class definition of the entities this store contains
   *    + All other options defined for dojox.data.JsonRestStore
   */
  constructor: function(options) {
    this.modelClass = options.modelClass;
    this.serviceImpl = new wcmf.persistence.DionysosService(this.modelClass);

    dojo.mixin(this, {
      target: this.serviceImpl.getServiceUrl(),
      service: this.serviceImpl.getServiceFunction(),
      cacheByDefault: true
    }, options);

    this.inherited(arguments);

    // autosave
    dojo.connect(this, "onSet", this, function(item, attribute) {
      this.save();
    });
  },

  getLabel: function(item) {
    var label = '';
    var displayValues = this.getLabelAttributes(item);
    for (var i=0, count=displayValues.length; i<count; i++) {
      label += item[displayValues[i]] + " ";
    }
    label = dojo.trim(label);
    if (label.length == 0) {
      label = item.oid;
    }
    return label;
  },

  getLabelAttributes: function(item) {
    return this.modelClass.displayValues;
  }
});

/**
 * Get the store for a given model class. If the store is
 * not created already, it will be created.
 * @param modelClass The model class to get the store for (subclass of wcmf.model.meta.Node)
 * @return An instance of wcmf.Store
 */
wcmf.persistence.Store.getStore = function(modelClass ) {
  if (modelClass instanceof wcmf.model.meta.Node) {
    var store = wcmf.persistence.Store.stores[modelClass.name];
    if (store == undefined) {
      // create stores only for known model classes
      store = new wcmf.persistence.Store({
        modelClass: modelClass
      });
      wcmf.persistence.Store.stores[modelClass.name] = store;
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
