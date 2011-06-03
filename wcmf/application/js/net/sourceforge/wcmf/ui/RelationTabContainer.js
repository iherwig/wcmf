dojo.provide("wcmf.ui.RelationTabContainer");

dojo.require("dijit.layout.TabContainer");

dojo.require("wcmf.ui.RelationPane");

/**
 * @class RelationTabContainer
 *
 * RelationTabContainer contains one instance of RelationPane for each relation
 * of a source type to a target type. The instances in each RelationPane are
 * the objects at the target end of the relations of the given source object and
 * are selected using the provided queries.
 */
/**
 * TypeTabContainer is a TabContainer that consists of one NodeTabContainer per
 * model type.
 */
dojo.declare("wcmf.ui.RelationTabContainer", dijit.layout.TabContainer, {

  /**
   * The object id of the source object
   */
  oid: null,

  /**
   * An array of objects describing the relations. Each object has the following properties:
   * - role: the role name
   * - query: the obfuscated relation query condition
   */
  relations: [],

  /**
   * The RelationPane instances, key is the role name
   */
  relationPanes: null,

  /**
   * Constructor
   * @param options Parameter object
   *    - oid The object id of the source object
   *    - relations An array of objects describing the relations. Each object has
   *      the following properties:
   *      - role: the role name
   *      - query: the obfuscated relation query condition
   *    + All options defined for dijit.layout.TabContainer
   */
  constructor: function(options) {
    this.oid = options.oid;
    this.relations = options.relations || [];
    this.relationPanes = {};

    dojo.mixin(this, {
      // default options
      useMenu: true,
      tabPosition: "bottom"
    }, options);
  },

  /**
   * Reload the content of the RelationPane instance that
   * belongs to the given relation and display it
   * @param name The relation name
   */
  reloadRelation: function(name) {
    var pane = this.relationPanes[name];
    if (pane != undefined) {
      pane.reload();
      this.selectChild(pane);
    }
  },

  buildRendering: function() {
    this.inherited(arguments);

    // create the RelationPane instances
    for(var i=0, count=this.relations.length; i<count; i++) {
      var curRelation = this.relations[i];
      var pane = new wcmf.ui.RelationPane({
        oid: this.oid,
        otherRole: curRelation.role,
        relationQuery: curRelation.query
      });
      this.addChild(pane);
      this.relationPanes[curRelation.role] = pane;
    }
  },

  destroy: function() {
    this.destroyDescendants();
    this.inherited(arguments);
  }
});
