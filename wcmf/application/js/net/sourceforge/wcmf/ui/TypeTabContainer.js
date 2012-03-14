dojo.provide("wcmf.ui.TypeTabContainer");

dojo.require("dijit.layout.TabContainer");
dojo.require("dijit.Toolbar");

/**
 * @class TypeTabContainer
 *
 * TypeTabContainer contains one instance of NodeTabContainer per
 * domain object type.
 */
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
   * Array of root type names
   */
  rootTypes: null,

  /**
   * Constructor
   * @param options Parameter object
   *    - rootTypes: Array of root type names
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
   * Create a new wcmf.ui.DetailPane for the node with the given oid and show it.
   * If there is already a DetailPane for the node no new will be created.
   * @param oid The object id
   * @param isNewNode True if the node does not exist yet, false else
   * @return wcmf.ui.DetailPane
   */
  displayNode: function(oid, isNewNode) {
    var className = wcmf.model.meta.Node.getTypeFromOid(oid);
    var modelClass = wcmf.model.meta.Model.getType(className);
    var nodeTabContainer = this.getNodeTabContainer(modelClass);
    this.selectChild(nodeTabContainer);
    return nodeTabContainer.displayNode(oid, isNewNode);
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
      this.connect(tabContainer, "onClose", function() {
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
    for (var i=0, count=this.rootTypes.length; i<count; i++) {
      var curType = wcmf.model.meta.Model.getType(this.rootTypes[i]);
      this.getNodeTabContainer(curType);
    }
  },

  destroy: function() {
    this.destroyDescendants();
    this.inherited(arguments);
  }
});

/**
 * Get the only instance of TypeTabContainer
 * @return wcmf.ui.TypeTabContainer
 */
wcmf.ui.TypeTabContainer.getInstance = function() {
  var typeTabContainer = dijit.byId("typeTabContainerDiv");
  if (typeTabContainer instanceof wcmf.ui.TypeTabContainer) {
    return typeTabContainer;
  }
  throw "The application expects a wcmf.ui.TypeTabContainer attached to "+
    "a div with id 'typeTabContainerDiv'";
}
