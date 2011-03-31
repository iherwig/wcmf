/**
 * @class RelationPane This class displays the objects that are in a
 * specific relation to another object.
 */
dojo.provide("wcmf.ui");

dojo.require("dojox.layout.ContentPane");

dojo.declare("wcmf.ui.RelationPane", dojox.layout.ContentPane, {

  /**
   * The type of object on this side of the relation
   */
  modelClass: null,
  /**
   * The object id of the object on this side of the relation
   */
  oid: null,
  /**
   * The type of the objects on the other side of the relation
   */
  otherClass: null,
  /**
   * The role of the objects on the other side of the relation
   */
  otherRole: null,
  /**
   * The query to find objects on the other side of the relation
   */
  relationQuery: null,

  /**
   * UI elements
   */
  createBtn: null,

  /**
   * Constructor
   * @param options Parameter object:
   *    - modelClass The type of object on this side of the relation
   *    - oid The object id of the object on this side of the relation
   *    - otherRole The role of the objects on the other side of the relation
   *    - relationQuery The query to find objects on the other side of the relation
   *    + All other options defined for dijit.layout.ContentPane
   */
  constructor: function(options) {
    this.modelClass = options.modelClass;
    this.oid = options.oid;
    this.otherRole = options.otherRole;
    this.relationQuery = options.relationQuery;

    this.otherClass = this.modelClass.getTypeForRole(this.otherRole);
    this.title = this.otherRole;

    dojo.mixin(this, {
      // default options
      closable: false
    }, options);
  },

  buildRendering: function() {
    this.inherited(arguments);

    var self = this;
    var layoutContainer = new dijit.layout.BorderContainer({
      gutters: false
    });

    // create the toolbar
    var toolbar = new dijit.Toolbar({
      region: "top"
    });
    this.createBtn = new dijit.form.Button({
      label: wcmf.Message.get("New"),
      iconClass: "wcmfToolbarIcon wcmfToolbarIconCreate",
      onClick: function() {
        wcmf.Action.create(self.otherClass);
      }
    });
    toolbar.addChild(this.createBtn);
    toolbar.startup();

    // create relations grid
    var relationsGrid = new wcmf.ui.Grid({
      modelClass: this.otherClass,
      query: {
        query: this.relationQuery
      },
      region: "center"
    });
    relationsGrid.initEvents();

    layoutContainer.addChild(toolbar);
    layoutContainer.addChild(relationsGrid);
    layoutContainer.startup();
    this.set('content', layoutContainer);
  }
});
