dojo.provide("wcmf.ui.Grid");

dojo.require("dojox.grid.EnhancedGrid");
dojo.require("dojox.grid.enhanced.plugins.DnD");
dojo.require("dojox.grid.enhanced.plugins.NestedSorting");
dojo.require("dojox.grid.enhanced.plugins.IndirectSelection");
dojo.require("dojox.grid.enhanced.plugins.Filter");

/**
 * @class Grid This class displays a list of objects in a table.
 */
dojo.declare("wcmf.ui.Grid", dojox.grid.EnhancedGrid, {

  modelClass: null,
  actions: [],

  plugins: {
      nestedSorting: true,
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
  //singleClickEdit: true,

  /**
   * Constructor
   * @param options Parameter object:
   *    - modelClass The model class whose instances this grid contains
   *    - actions Array of wcmf.ui.GridActionCell instances
   *    + All other options defined for dojox.grid.EnhancedGrid
   */
  constructor: function(options) {
    this.modelClass = options.modelClass;
    this.actions = options.actions || [];

    dojo.mixin(this, {
      // default options
      store: wcmf.persistence.Store.getStore(this.modelClass),
      structure: this.getDefaultLayout()
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
  },

  resizeGrid: function() {
    this.resize();
    this.update();
  },

  getDefaultLayout: function() {
    var self = this;
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
  }
});