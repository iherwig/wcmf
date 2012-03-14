dojo.provide("wcmf.ui.GridActionCell");

dojo.require("dojox.grid.cells._base");

/**
 * @class ActionCell
 *
 * ActionCell and it's subclasses are used in dojox.grid.DataGrid instances to
 * perform actions on a row item. Each cell renders as an image
 * and performs the given action on the item represented by the row.
 */
dojo.declare("wcmf.ui.GridActionCell", null, {

  /**
   * The icon class
   */
  iconClass: "",

  /**
   * The action function (receives the row item as parameter)
   */
  action: null,

  /**
   * Constructor
   * @param options Parameter object:
   *    - iconClass The icon class
   *    - action The action function (receives the item as parameter)
   *    + All other options defined for dojox.grid.cells.Cell
   */
  constructor: function(options) {
    dojo.mixin(this, {
      // no defaults
    }, options);
  }
});

/**
 * This class calls the wcmf.Action.edit method on the current item
 */
dojo.declare("wcmf.ui.GridActionEdit", wcmf.ui.GridActionCell, {
  iconClass: "icon-pencil",
  action: function(item) {
    wcmf.Action.edit(item.oid);
  }
});

/**
 * This class calls the wcmf.Action.remove method on the current item
 */
dojo.declare("wcmf.ui.GridActionDelete", wcmf.ui.GridActionCell, {
  iconClass: "icon-remove-sign",
  action: function(item) {
    wcmf.Action.remove(item.oid);
  }
});

/**
 * This class calls the wcmf.Action.disassociate method on the current item
 */
dojo.declare("wcmf.ui.GridActionDisassociate", wcmf.ui.GridActionCell, {

  /**
   * The object id of the source object (not given as item action parameter)
   */
  sourceOid: null,

  /**
   * The role of the item action parameter in relation to the source object
   */
  role: null,

/**
   * Constructor
   * @param options Parameter object:
   *    - sourceOid The object id of the source object (not given as item action parameter)
   *    - role The role of the item action parameter in relation to the source object
   *    + All other options defined for dojox.grid.cells.Cell
   */
  constructor: function(options) {
    dojo.mixin(this, {
      // no defaults
    }, options);
  },

  iconClass: "wcmfToolbarIconDisassociate",
  action: function(item) {
    wcmf.Action.disassociate(this.sourceOid, item.oid, this.role);
  }
});
