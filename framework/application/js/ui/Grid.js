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
      indirectSelection: true,
      dnd: true
  },
  rowSelector: "0px",
  singleClickEdit: true,

  /**
   * Constructor
   * @param options Parameter object:
   *    - modelClass The model class whose instances this grid contains
   *    + All other options defined for dojox.grid.EnhancedGrid
   */
  constructor: function(options) {
    this.modelClass = options.modelClass;

    dojo.mixin(this, {
      store: wcmf.persistence.Store.getStore(this.modelClass.type),
      structure: this.getDefaultLayout()
    }, options);

    dojo.connect(this, "onApplyCellEdit", this, function(value, rowIndex, fieldIndex) {
      var item = this.getItem(rowIndex);
      this.store.setValue(item, fieldIndex, value);
    });
    dojo.connect(this, "onSelected", this, function(item, attribute, oldValue, newValue) {
      this.store.save();
    });
  },
  
  getDefaultLayout: function() {
    var layout = [];
    dojo.forEach(this.modelClass.attributes, function(item) {
      layout.push({
        field: item.name,
        name: item.name,
        width: "100px",
        editable:true
      });
    });
    layout.push({
      field: "_edit",
      name: " ",
      width: "26px",
      formatter: wcmf.ui.Grid.getEdit,
      styles: "text-align:center;vertical-align:middle;"
    });
    layout.push({
      field: "_delete",
      name: " ",
      width: "26px",
      formatter: wcmf.ui.Grid.getDelete,
      styles: "text-align:center;vertical-align:middle;"
    });
    return layout;
  }
});

/**
 * Static function used as Formatter for the edit column
 */
wcmf.ui.Grid.getEdit = function(item) {
  var url = "";
  return '<img onclick="'+url+'" src="images/edit.png" />';
}

/**
 * Static function used as Formatter for the delete column
 */
wcmf.ui.Grid.getDelete = function(item) {
//  var url = "if (confirm('Are you sure?')) { store{$type}.deleteItem(store{$type}.fetchItemByIdentity({identity: '"+item+"'})); *}";
  var url = "";
  return '<img onclick="'+url+'" src="images/delete.png" />';
}

