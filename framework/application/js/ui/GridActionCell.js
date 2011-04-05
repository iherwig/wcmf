/**
 * @class ActionCell
 *
 * ActionCell and it's subclasses are used in dojox.grid.DataGrid instances to
 * perform actions on a row item. Each cell renders as an image
 * and performs the given action on the item represented by the row.
 */
dojo.require("dojox.grid.cells._base");

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
  iconClass: "wcmfToolbarIconEdit",
  action: function(item) {
    wcmf.Action.edit(item.oid);
  }
});

/**
 * This class calls the wcmf.Action.remove method on the current item
 */
dojo.declare("wcmf.ui.GridActionDelete", wcmf.ui.GridActionCell, {
  iconClass: "wcmfToolbarIconDelete",
  action: function(item) {
    wcmf.Action.remove(item.oid);
  }
});
