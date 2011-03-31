/**
 * @class DetailPane This class displays the detail view of an object.
 */
dojo.provide("wcmf.ui");

dojo.require("dijit.layout.TabContainer");

/**
 * TypeTabContainer is a TabContainer that consists of one NodeTabContainer per
 * model type.
 */
dojo.declare("wcmf.ui.TypeTabContainer", dijit.layout.TabContainer, {

  /**
   * The contained NodeTabContainer instances, key is the type name
   */
  nodeTabContainer: null,

  /**
   * Constructor
   * @param options Parameter object
   *    + All options defined for dijit.layout.TabContainer
   */
  constructor: function(options) {
    this.nodeTabContainer = {};

    dojo.mixin(this, {
      // default options
      //tabPosition: "left-h",
      useMenu: true
    }, options);
  },

  /**
   * Get the NodeTabContainer for a given type
   * @param modelClass A wcmf.model.meta.Node instance
   * @return wcmf.ui.NodeTabContainer instance
   */
  getNodeTabContainer: function(modelClass) {
    var self = this;
    var tabContainer = this.nodeTabContainer[modelClass.name];
    if (tabContainer == undefined) {
      tabContainer = new wcmf.ui.NodeTabContainer({
        modelClass: modelClass,
        nested: true,
        closable: modelClass.isRootType ? false : true
      });
      dojo.connect(tabContainer, "onClose", this, function() {
        delete self.nodeTabContainer[modelClass.name];
        return true;
      });
      this.nodeTabContainer[modelClass.name] = tabContainer;
      this.addChild(tabContainer);
      tabContainer.startup();
    }
    return tabContainer;
  },

  buildRendering: function() {
    this.inherited(arguments);
    // create NodeTabContainer instances for all root types
    var allTypes = wcmf.model.meta.Model.getAllTypes();
    for (var i=0, count=allTypes.length; i<count; i++) {
      var curType = allTypes[i];
      if (curType.isRootType) {
        this.getNodeTabContainer(curType);
      }
    }
  }
});
