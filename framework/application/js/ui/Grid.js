/**
 * @class Grid This class displays a list of objects in a table.
 */
dojo.provide("wcmf.ui");

dojo.require("dojox.grid.EnhancedGrid");
dojo.require("dojox.grid.enhanced.plugins.DnD");
dojo.require("dojox.grid.enhanced.plugins.NestedSorting");
dojo.require("dojox.grid.enhanced.plugins.IndirectSelection");

dojo.declare("wcmf.ui.Grid", dojox.grid.EnhancedGrid, {

  modelClass: null,
  plugins: {
      nestedSorting: true,
      //indirectSelection: true,
      dnd: true
  },
  delayScroll: true,
  //elasticView: "2",
  rowSelector: "0px",
  autoWidth: true,
  autoHeight: false,
  rowsPerPage: 25,
  rowCount: 25,
  //singleClickEdit: true,

  /**
   * Constructor
   * @param options Parameter object:
   *    - modelClass The model class whose instances this grid contains
   *    + All other options defined for dojox.grid.EnhancedGrid
   */
  constructor: function(options) {
    this.modelClass = options.modelClass;

    dojo.mixin(this, {
      // default options
      store: wcmf.persistence.Store.getStore(this.modelClass),
      structure: this.getDefaultLayout(),
      formatterScope: this
    }, options);

    dojo.connect(this, 'onShow', this, this.initListeners);
  },

  initEvents: function() {
    dojo.connect(this, "onCellClick", this, function(event) {
      var item = this.getItem(event.rowIndex);

      // edit action
      if (event.cell.field == '_edit') {
        wcmf.Action.edit(this.modelClass.type, item.oid);
      }
      // delete action
      if (event.cell.field == '_delete') {
    	if (confirm(wcmf.Message.get('Are you sure?'))) {
          this.store.deleteItem(this.store.fetchItemByIdentity({identity:item}));
    	}
      }
    });
    dojo.connect(this, "onApplyCellEdit", this, function(value, rowIndex, fieldIndex) {
      var item = this.getItem(rowIndex);
      this.store.setValue(item, fieldIndex, value);
    });
    dojo.connect(this, "onSelected", this, function(item, attribute, oldValue, newValue) {
      this.store.save();
    });
	dojo.connect(this, "onShow", this, this.resizeGrid);
  },

  resizeGrid: function() {
    // do whatever you need here, e.g.:
    this.resize();
    this.update();
  },

  getDefaultLayout: function() {
    var layout = {};
    layout.defaultCell = { width: "100px" };
    layout.cells = [];
    dojo.forEach(this.modelClass.attributes, function(item) {
    if (dojo.some(item.tags, "return item == 'DATATYPE_ATTRIBUTE';")) {
        layout.cells.push({
          field: item.name,
          name: item.name,
          //width: "10%",
          editable: item.isEditable
        });
      }
    });
    layout.cells.push({
      field: "_edit",
      name: " ",
      width: "26px",
      formatter: this.formatEdit,
      styles: "text-align:center;vertical-align:middle;"
    });
    layout.cells.push({
      field: "_delete",
      name: " ",
      width: "26px",
      formatter: this.formatDelete,
      styles: "text-align:center;vertical-align:middle;"
    });
    return layout;
  },

  /**
   * Formatter for the edit column
   */
  formatEdit: function(item) {
    return '<img src="images/edit.png" />';
  },

  /**
   * Formatter for the delete column
   */
  formatDelete: function(item) {
    return '<img src="images/delete.png" />';
  }
});