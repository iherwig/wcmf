dojo.provide("wcmf.ui.Grid");

dojo.require("dojox.grid.EnhancedGrid");
dojo.require("dojox.grid.enhanced.plugins.DnD");
dojo.require("dojox.grid.enhanced.plugins.IndirectSelection");
dojo.require("dojox.grid.enhanced.plugins.Filter");

/**
 * @class Grid This class displays a list of objects of the given type in a table.
 * The grid is configured with a number of GridActionCell instances that
 * define actions that may be executed on the contained objects.
 * If the grid is used in a master-detail scenario, the masterOid parameter
 * defines the object to which the contained objects are related.
 */
dojo.declare("wcmf.ui.Grid", dojox.grid.EnhancedGrid, {

  /**
   * The wcmf.model.meta.Node instance which defines the type of objects in this grid
   */
  modelClass: null,

  /**
   * Array of wcmf.ui.GridActionCell instances to be executed on the contained objects
   */
  actions: [],

  /**
   * Array of wcmf.ui.GridActionCell instances to be executed on the contained objects
   */
  masterOid: null,

  /**
   * The role of the cntained instances in relation to the master object
   */
  role: null,

  /**
   * Object with attributes 'attribute', 'descending' (Boolean) and 'isSortkey' (Boolean)
   * defining the attribute and direction to order the objects by. If isSortkey is true,
   * drag and drop sorting will be enabled
   */
  sortField: null,

  /**
   * Constructor
   * @param options Parameter object:
   *    - modelClass  The wcmf.model.meta.Node instance which defines the type of objects in this grid
   *    - actions Array of wcmf.ui.GridActionCell instances to be executed on the contained objects
   *    - masterOid The object id of the master object in a master-detail scenario (optional)
   *    - role The role of the cntained instances in relation to the master object (optional)
   *    - sortFields Array of objects with attributes 'attribute', 'descending' (Boolean) and 'isSortkey' (Boolean)
   *      defining the attribute and direction to order the objects by. If isSortkey is true,
   *      drag and drop sorting will be enabled (optional)
   *    + All other options defined for dojox.grid.EnhancedGrid
   */
  constructor: function(options) {
    this.modelClass = options.modelClass;
    this.actions = options.actions || [];
    this.masterOid = options.masterOid;
    this.role = options.role;
    if (options.sortFields) {
      // explicit sorting is only possible for one attribute
      this.sortField = options.sortFields[0];
    }

    dojo.mixin(this, {
      // default options
      store: wcmf.persistence.Store.getStore(this.modelClass),
      structure: this.getDefaultLayout(),
      plugins: {
          indirectSelection: {headerSelector: true},
          dnd: true,
          filter: true
      },
      delayScroll: true,
      //elasticView: "2",
      autoWidth: false,
      autoHeight: false,
      rowsPerPage: 25,
      rowCount: 25,
      selectionMode: "multiple",
      clientSort: false
    }, options);
  },

  /**
   * Reload the grid content
   */
  reload: function() {
    var store = wcmf.persistence.Store.getStore(this.modelClass);
    this.setStore(store);
  },

  postCreate: function() {
    this.inherited(arguments);
    this.connect(this, "onCellClick", function(event) {
      var item = this.getItem(event.rowIndex);
      var actionCell = event.cell.actionCell;
      if (actionCell instanceof wcmf.ui.GridActionCell) {
        actionCell.action.call(actionCell, item);
      }
    });
    this.connect(this, "onApplyCellEdit", function(value, rowIndex, fieldIndex) {
      var item = this.getItem(rowIndex);
      this.store.setValue(item, fieldIndex, value);
      this.store.save();
    });
    this.connect(this, "onShow", this.resizeGrid);

    // dnd
    this.connect(this.pluginMgr.getPlugin("dnd"), "_startDnd", this.onRowMoveStart);
    this.connect(this.pluginMgr.getPlugin("rearrange"), "moveRows", this.onRowMoved);
    dojo.subscribe("dojox/grid/rearrange/move/" + this.id, this, this.onRowMovedEnd);

    var dndPlugin = this.plugin('dnd');
    if (dndPlugin) {
      var isSortable = this.sortField && this.sortField.isSortkey;
      dndPlugin.setupConfig(this.getDnDConfig(isSortable));
    }
  },

  resizeGrid: function() {
    this.resize();
    this.update();
  },

  getDefaultLayout: function() {
    var layout = {};
    layout.defaultCell = {};
    layout.cells = [];
    dojo.forEach(this.modelClass.attributes, function(item) {
      if (dojo.some(item.tags, "return item == 'DATATYPE_ATTRIBUTE';")) {
        layout.cells.push({
          field: item.name,
          name: item.name,
          width: "auto",
          editable: item.isEditable,
          hidden: false // TODO: hide non-display values
        });
      }
    });
    // add cells for actions
    for(var i=0, count=this.actions.length; i<count; i++) {
      var curActionCell = this.actions[i];
      layout.cells.push({
        formatter: function(inDatum, inRowIndex, inItem) {
          return '<div class="wcmfToolbarIcon '+inItem.actionCell.iconClass+'"></div>';
        },
        width: "26px",
        styles: "text-align:center;vertical-align:middle;",
        actionCell: curActionCell
      });
    }
    return layout;
  },

  onRowMoveStart: function() {
    // make a snapshot of the index array, because the
    // rearrange plugin will clear it
    this.tmpIdx = this._by_idx;
  },

  onRowMoved: function(indexesOfRowsToMove, targetIndex) {
    // determine the object ids of the involved objects
    // from the snapshot made before dnd
    this.dndTargetOid = this.tmpIdx[targetIndex].idty;
    this.dndMovedOids = [];
    for (var i=0, count=indexesOfRowsToMove.length; i<count; i++) {
      this.dndMovedOids.push(this.tmpIdx[indexesOfRowsToMove[i]].idty);
    }
    this.tmpIdx = [];
  },

  onRowMovedEnd: function(map, colsToMove) {
    // perform the move action with the information collected before
    var self = this;
    if (this.masterOid != null) {
      wcmf.Action.move(this.dndMovedOids[0], this.dndTargetOid, this.masterOid, this.role).then(function() {
        self.sort();
      });
    }
    else {
      wcmf.Action.move(this.dndMovedOids[0], this.dndTargetOid).then(function() {
        self.sort();
      });
    }
    // clear the selection to prevent users moving rows from an old selection
    this.selection.clear();
  },

  getDnDConfig: function(isSortable) {
    var dndConfig = {
          within: {
            row: isSortable,
            col: false,
            cell: false
          },
          "in": false,
          out: false
        };
     return dndConfig;
  }
});