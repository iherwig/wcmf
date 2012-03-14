define(["dojo/_base/declare", "dojo/on", "dgrid/OnDemandGrid", "dgrid/Editor", "dgrid/Selection", "dgrid/Keyboard", "dgrid/extensions/DnD", "dgrid/extensions/ColumnResizer", "../persistence/Store", "dojo/domReady!"
], function(declare, on, Grid, Editor, Selection, Keyboard, DnD, ColumnResizer, Store) {

 /**
  * @class Grid This class displays a list of objects of the given type in a table.
  * The grid is configured with a number of GridActionCell instances that
  * define actions that may be executed on the contained objects.
  * If the grid is used in a master-detail scenario, the masterOid parameter
  * defines the object to which the contained objects are related.
  */
  return declare([Grid, Selection, Keyboard, DnD, ColumnResizer], {

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
        store: Store.getStore(this.modelClass, wcmf.defaultLanguage),
        columns: this.getColumns(),
        showHeader: true,
//        minRowsPerPage: 50,
//        maxRowsPerPage: 100,
        selectionMode: "multiple"
      }, options);
    },

    /**
    * Reload the grid content
    */
    reload: function() {
      var store = Store.getStore(this.modelClass, wcmf.defaultLanguage);
      this.setStore(store);
    },

    postCreate: function() {
      this.inherited(arguments);
      var grid = this;
      on(grid, "click", function(event) {
        var target = event.target;
        if (dojo.hasClass(target, "actionCell")) {
          var cell = grid.cell(target.parentNode);
          var actionCell = cell.column.actionCell;
          if (actionCell instanceof wcmf.ui.GridActionCell) {
            actionCell.action.call(actionCell, cell.row.data);
          }
        }
      });
  //    this.on(this, "onShow", this.resizeGrid);
  //
  //    // dnd
  //    this.on(this.pluginMgr.getPlugin("dnd"), "_startDnd", this.onRowMoveStart);
  //    this.on(this.pluginMgr.getPlugin("rearrange"), "moveRows", this.onRowMoved);
  //    dojo.subscribe("dojox/grid/rearrange/move/" + this.id, this, this.onRowMovedEnd);
  //
  //    var dndPlugin = this.plugin('dnd');
  //    if (dndPlugin) {
  //      var isSortable = this.sortField && this.sortField.isSortkey;
  //      dndPlugin.setupConfig(this.getDnDConfig(isSortable));
  //    }
    },

    resizeGrid: function() {
      this.resize();
      this.update();
    },

    getColumns: function() {
      var columns = [];
      dojo.forEach(this.modelClass.attributes, function(item) {
        if (dojo.some(item.tags, "return item == 'DATATYPE_ATTRIBUTE';")) {
          var columnDef = {
            field: item.name,
            label: item.name,
            sortable: true,
            formatter: wcmf.ui.Format.text
          };
          if (item.isEditable) {
            columnDef = Editor(columnDef, "text", "dblclick");
          }
          columns.push(columnDef);
        }
      });
      // add cells for actions
      for(var i=0, count=this.actions.length; i<count; i++) {
        var curActionCell = this.actions[i];
        columns.push({
          formatter: function(data) {
            // data is the return value of get
            return '<i class="actionCell '+data.iconClass+'"></i>';
          },
          actionCell: curActionCell,
          className: '_action_cell',
          get: function() {
            return this.actionCell;
          }
        });
      }
      return columns;
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
});
