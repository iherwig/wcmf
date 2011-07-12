dojo.provide("wcmf.ui.ObjectTree");

dojo.require("dijit.Tree");

/**
 * @class ObjectTree
 *
 * ObjectTree displays all objects in the model.
 */
dojo.declare("wcmf.ui.ObjectTree", dijit.Tree, {

  /**
   * Constructor
   * @param options Parameter object:
   */
  constructor: function(options) {
    this.store = new dojox.data.JsonRestStore({
      target:"?controller=TreeViewController&action=loadChilren&oid=",
      labelAttribute:"oid"
    });
    this.model = new dijit.tree.ForestStoreModel({
      store: this.store,
      deferItemLoadingUntilExpand: true,
      query: "root",
      childrenAttrs: ["children"]
    });

    dojo.mixin(this, {
      // default options
      model: this.model,
      openOnClick: true,
      showRoot: true
    }, options);
  }
});
