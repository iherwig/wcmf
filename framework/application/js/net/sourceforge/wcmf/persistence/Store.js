dojo.provide("wcmf.persistence.Store");

dojo.require("dojox.data.JsonRestStore");
dojo.require("dojo.DeferredList");

/**
 * @class Store This class represents the client side object repository.
 * There is one store for each type. Store uses wcmf.DionysosService to exchange
 * objects and their modifications with the server.
 */
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

/*
    // autosave
    dojo.connect(this, "onSet", this, function(item, attribute) {
      this.save();
    });
*/
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
  },

  /**
   * Overriden in order to set the item's oid after it has been committed.
   */
  save: function(kwArgs) {
    // remove the callbacks and re-add them later in order
    // to make sure that our callbacks are called first
    var onComplete = null;
    var onError = null;
    if (kwArgs) {
      onComplete = kwArgs.onComplete;
      onError = kwArgs.onError;
      delete kwArgs.onComplete;
      delete kwArgs.onError;
    }
    var defs = [];
    var actions = this.inherited(arguments);
    for(var i=0; i<actions.length; i++) {
      // need to update the item's oid after it has been committed
      (function(item, dfd) {
        dfd.then(function(result) {
          if(result) {
            item.oid = result.oid;
          }
          return result;
        }, function(error) {
          return error;
        });
      })(actions[i].content, actions[i].deferred);
      defs.push(actions[i].deferred);
    }
    // add the given callbacks to the callback of a deferred list
    var dl = new dojo.DeferredList(defs, false, true/* reject on first error */);
    dl.promise.then(function(result) {
        if (onComplete instanceof Function) {
          onComplete.call(kwArgs.scope, result[1]);
        }
      }, function(error) {
        if (onError instanceof Function) {
          onError.call(kwArgs.scope, error);
        }
      }
    );
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
