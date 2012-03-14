define(["dojo/_base/declare"
], function(declare) {

/**
 * @class ServiceAdapter This class defines the interface for
 * adapters used by the wcmf.persistence.PluggableStore class to
 * connect to a specific server.
 * The adapter is responsible to transform arguments and results of
 * server calls. The argument passed into the methods and the return
 * types are identically to what dojo.store.api.Store defines for the
 * appropriate methods.
 */
declare("wcmf.persistence.ServiceAdapter", null, {
  /**
   * @see dojo.store.api.Store.get
   */
  get: function(id) {
    throw new Error('wcmf.persistence.ServiceAdapter.get');
  },
  /**
   * @see dojo.store.api.Store.put
   * @see dojo.store.api.Store.add
   */
  addOrUpdate: function(object, directives) {
    throw new Error('wcmf.persistence.ServiceAdapter.addOrUpdate');
  },
  /**
   * @see dojo.store.api.Store.remove
   */
  remove: function(id) {
    throw new Error('wcmf.persistence.ServiceAdapter.remove');
  },
  /**
   * @see dojo.store.api.Store.query
   */
  query: function(query, options) {
    throw new Error('wcmf.persistence.ServiceAdapter.query');
  }
});

return wcmf.persistence.ServiceAdapter;
});
