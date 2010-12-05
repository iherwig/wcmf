/**
 * @class wcmf.grid.Grid. Build on Ext.grid.GridPanel
 */
wcmf.grid.Grid = function() {};

wcmf.grid.Grid.prototype = {
  // the displayed type
  type:null,
  // the Ext.grid.GridPanel instance
  grid:null,
  // the column definitions
  columnDefs:null,
  // the row actions
  actions:null,
  // the custom parameters
  customParams:null,
  // the Ext.data.Store instance
  ds:null,
  // the Ext.PagingToolbar instance
  pagingTb:null,
  // the page size
  pageSize:-1,
  
  /**
   * Initialize a grid that displays entities
   * @param title The title to display
   * @param type The entity type to display
   * @param filter A filter string to be used to filter the entities (see StringQuery), maybe obfuscated using Obfuscator
   * @param columnDefs An array of column definitions for Ext.grid.ColumnModel
   * @param config An associative array with the following keys: paging[true/false], pagesize[number, default=10], autoheight[true/false], singleSelect[true/false], ddRows[true/false], ddSortCol[columnname], groupBy[columnname]
   * @param actions An array of wcmf.grid.Action instances for each row (The first on is also used as double click action)
   * @param customBtns An array of additional button definitions for Ext.Toolbar [optional]
   * @param customParams An assoziative array of additional values passed to the controller [optional]
   */
  init: function(title, type, filter, columnDefs, config, actions, customBtns, customParams) {
    this.type = type;
    this.columnDefs = columnDefs;
    this.actions = actions;
    this.customParams = customParams;

    var _this = this;

    if (config.paging)
      this.pageSize = (config.pagesize != undefined) ? config.pagesize : 10;
    
    // define the mapping used by the JsonReader to deserialize the server response
    // node: the server uses ArrayOutputStrategy to serialize nodes
    jsonMapping = [];
    // store requested node attributes in record fields
    for (var i=0; i<columnDefs.length; i++)
      jsonMapping[i] = {name:columnDefs[i].id};
    // store generic node data in special record fields
    jsonMapping.push({name:"values", mapping:"values"});
    jsonMapping.push({name:"_properties", mapping:"properties"});
    jsonMapping.push({name:"_type", mapping:"type"});
  
    // create the Data Store
    var storeConfig = {
      proxy: new Ext.data.HttpProxy({
        url:'<?php echo $APP_URL; ?>'
      }),
      
      baseParams:{type:type, controller:'<?php echo $controller; ?>', context:'<?php echo $context; ?>', action:'list', response_format:'JSON', sid:'<?php echo session_id() ?>', filter:filter, renderValues:true},

      reader:new Ext.data.JsonReader({
        root:'objects',
        totalProperty:'totalCount',
        id:'oid'
      }, jsonMapping),

      // turn on remote sorting
      remoteSort:true
    };
    // add grouping information
    if (config.groupBy) {
      storeConfig['sortInfo'] = {field:'config.groupBy', direction:"ASC"};
      storeConfig['groupField'] = config.groupBy;
      this.ds = new Ext.data.GroupingStore(storeConfig);
    }
    else {
      this.ds = new Ext.data.Store(storeConfig);
    }
    
    // add custom parameters to baseParams
    if (customParams)
      for (var i in customParams)
        this.ds.baseParams[i] = customParams[i];

    // translate column headers
    for (var i=0; i<columnDefs.length; i++) {
      columnDefs[i].header = Message.get(columnDefs[i].header);
    }

    // add columns for row actions
    var allColumns = [];
    for (var i=0; i<columnDefs.length; i++)
      allColumns.push(columnDefs[i]);
    for (var i=0; i<this.actions.length; i++)
      allColumns.push(this.actions[i]);

    // the column model has information about grid columns
    // dataIndex maps the column to the specific data field in
    // the data store
    var cm = new Ext.grid.ColumnModel(allColumns);

    // create the page size toolbar with custom buttons
    var topTb = new Ext.Toolbar();
    if (title || config.paging || customBtns) {
      var items = ['->'];
      // add additional buttons
      if (customBtns)
        items.push(customBtns);

      // add page size buttons
      if (config.paging) {
        items.push('-');
        items.push([
          {text:'10', tooltip:Message.get('Display %1% items', [10]), handler:this.setPageSize.createDelegate(this, [10])},
          {text:'25', tooltip:Message.get('Display %1% items', [25]), handler:this.setPageSize.createDelegate(this, [25])},
          {text:'50', tooltip:Message.get('Display %1% items', [50]), handler:this.setPageSize.createDelegate(this, [50])},
          {text:'100', tooltip:Message.get('Display %1% items', [100]), handler:this.setPageSize.createDelegate(this, [100])}
        ]);
      }
        
      topTb = new Ext.Toolbar({
        items:items
      });
    }

    // create the paging toolbar
    this.pagingTb = new Ext.Toolbar();
    if (config.paging) {
      this.pagingTb = new Ext.PagingToolbar({
        store:this.ds,
        pageSize:this.pageSize,
        displayInfo:true,
        displayMsg:Message.get('Displaying {0} - {1} of {2}'),
        emptyMsg:Message.get('No objects to display')
      });
    }

    // create the grid
    var autoHeight = (config.autoheight) ? true : false;
    var ddRows = (config.ddRows) ? true : false;
    var gridConfig = {
      ds:this.ds,
      cm:cm,
      selModel:new Ext.grid.RowSelectionModel({singleSelect:config.singleSelect}),
      autoHeight:autoHeight,
      autoExpandColumn:allColumns[0].id,
      collapsible:true,
      animCollapse:false,
      titleCollapse:true,
      title:title,
      loadMask:true,
      enableDragDrop:ddRows,
      ddGroup:'DDGroup',
      ddText:Message.get('Drop {0} row(s) here'),
      tbar:topTb,
      bbar:this.pagingTb,
      plugins:this.actions
    }
    // choose view depending on grouping
    if (config.groupBy) {
      gridConfig['view'] = new Ext.grid.GroupingView({
        forceFit:true,
        groupTextTpl: '{gvalue} ({[values.rs.length]} {[values.rs.length > 1 ? "Items" : "Item"]})',
        hideGroupedColumn:true,
        startCollapsed:true
      });
    }
    else {
      gridConfig['viewConfig'] = {
        autoFill:true, 
        forceFit:true
      };
    }
    this.grid = new Ext.grid.GridPanel(gridConfig);
    
    // add callback method to grid
    this.grid.actionPerformed = function(record, arg, success) {
      _this.actionPerformed(record, arg, success);
    }
    
    // add click listener
    this.grid.on("celldblclick", this.cellDblClicked, this);
    this.grid.on("expand", this.onExpand, this);

    if (ddRows) {
      // drag'n'drop support
      this.grid.on("render", function(g) {
        var ddrow = new Ext.ux.dd.GridReorderDropTarget(g, {
          copy: false,
          listeners: {
            beforerowmove: function(objThis, oldIndex, newIndex, records) {
              // return false to cancel the move
            },
            afterrowmove: function(objThis, oldIndex, newIndex, records) {
              var dist = oldIndex - newIndex;
              var actionName = (dist > 0) ? 'sortup' : 'sortdown';
              Action.perform(actionName, {sortoid:records[0]['id'], dist:Math.abs(dist), filter:filter, sortcol:config.ddSortCol}, function(record, arg, success){}, _this);
            },
            beforerowcopy: function(objThis, oldIndex, newIndex, records) {
              // return false to cancel the copy
            },
            afterrowcopy: function(objThis, oldIndex, newIndex, records) {
            }
          }
        });
        Ext.dd.ScrollManager.register(g.getView().getEditorParent());
      });
      this.grid.on("beforedestroy", function(g) {
          Ext.dd.ScrollManager.unregister(g.getView().getEditorParent());
      });
    }
  },
  
  /**
   * Load the grid data
   */
  load: function() {
    var self = this;
    this.ds.reload({params:{start:0, limit:this.pageSize},
      callback: function() {
        self.grid.getView().refresh();
      }
    });
  },

  /**
   * Set the page size
   * @param newPageSize The new page size
   */
  setPageSize: function(newPageSize) {
    this.pageSize = newPageSize;
    this.pagingTb.pageSize = this.pageSize; // Make sure pagination follows suit
    this.load();
  },
  
  /**
   * Get the underlying grid
   * @return The Ext.grid.GridPanel instance
   */
  getGridImpl: function() {
    return this.grid;
  },
  
  /**
   * Handler for expand click
   */
  onExpand: function(grid) {
    this.load();
  },

  /**
   * Handler for cell double click
   */
  cellDblClicked: function(grid, rowIndex, colIndex, e) {
    // get the row record
    var record = this.grid.getStore().getAt(rowIndex);
    // perform the action
    if (this.actions.length > 0) {
      action = this.actions[0];
      actionNames = action.getSupportedActions();
      action.performAction(actionNames[0], record);
    }
  },
  
  /**
   * Callback
   */
  actionPerformed: function(record, arg, success) {
    this.load();
  },

  /**
   * Default column render function. Displays the id value of the column definition
   */
  renderColumnDefault: function(value, cellMeta, record, rowIndex, colIndex, store) {
    // try to get the value from the object
    var value = record.data[cellMeta.id];
    if (value) {
      return value;
    }
    else {
      // or from the realSubject if given
      var realSubject = record.data._properties.realSubject;
      if (realSubject)
        return realSubject.values[cellMeta.id];
    }
    return '-'
  }
};
