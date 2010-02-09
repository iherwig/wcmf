/**
 * @class AssociateDialog. Build on Ext.LayoutDialog
 */
AssociateDialog = function() {};

AssociateDialog.prototype = {
  // the Ext.LayoutDialog instance
  dialog: null,
  // the wcmf.grid.Grid instance
  grid: null,
  // the source Grid instance
  sourceGrid: null,
  // the oid of the node to associate to
  oid: null,
  // the role of the associated nodes
  associateAs: '',
  // need a complete refresh after associate action?
  needPageRefresh: false,
  
  /**
   * Show the dialog
   * @param id The html container id
   * @param type The type to display
   * @param sourceGrid The grid from which the dialog is opened
   * @param oid The oid of the node to associate to
   * @param associateAs The role of the associated nodes as seen from oid: Either 'parent' or 'child'
   * @param needPageRefresh True/False wether the whole page must be reloaded after the action or not
   */
  show: function(type, sourceGrid, oid, associateAs, needPageRefresh) {
    if (!this.dialog) {
      // initialize the grid
      this.grid = new wcmf.grid.Grid();
      // copy column defs 
      var columnDefs = [];
      for (var i=0; i<sourceGrid.columnDefs.length; i++)
      {
        var origCol = sourceGrid.columnDefs[i];
        columnDefs.push({id:origCol.id, dataIndex:origCol.dataIndex, header:origCol.header, width:origCol.width, 
          sortable:origCol.sortable, renderer:this.grid.renderColumnDefault.createDelegate(this.grid)});
      }
      this.grid.init('', type, '', columnDefs, {paging:true, singleSelect:false}, []);
      var extGrid = this.grid.getGridImpl();

      // add listeners
      var selModel = extGrid.getSelectionModel();
      selModel.on('selectionchange', this.selectionChanged.createDelegate(this));

      var dlg = new Ext.Window({
        applyTo:Ext.DomHelper.append(document.body, {tag:'div'}),
        layout:'fit',
        width:500,
        height:300,
        closeAction:'hide',
        plain:true,
        items:extGrid,
        buttons: [{
          text:'Submit',
          handler: this.submitDlg.createDelegate(this),
          disabled:true
        },{
          text: 'Close',
          handler: function() {
            dlg.hide();
          }
        }]
      });

      this.dialog = dlg;
      this.sourceGrid = sourceGrid;
      this.oid = oid;
      this.associateAs = associateAs;
      this.needPageRefresh = needPageRefresh;
      
      extGrid.un("celldblclick", this.grid.cellDblClicked, this.grid);
      extGrid.on("celldblclick", this.submitDlg, this);
    }
    this.grid.load();
    this.grid.getGridImpl().getSelectionModel().clearSelections();
    this.dialog.show();
  },
  
  selectionChanged: function(selModel) {
    var buttons = this.dialog.buttons;
    for (var i=0; i<buttons.length; i++) {
      if (buttons[i].getText() == 'Submit') {
        if (selModel.getCount() > 0)
          buttons[i].enable();
        else
          buttons[i].disable();
        break;
      }
    }
  },

  submitDlg: function() {
    document.body.style.cursor = "wait";
    var records = this.grid.getGridImpl().getSelectionModel().getSelections();
    var ids = '';
    if (records.length > 0) {
      for (var i=0; i<records.length-1; i++)
        ids += records[i].id+",";
      ids += records[records.length-1].id;
    }
    Action.perform('associate', {oid:this.oid, associateoids:ids, associateAs:this.associateAs}, this.actionPerformed, this);
  },
  
  /**
   * Callback
   */
  actionPerformed: function(record, arg, success) {
    document.body.style.cursor = "";
    if (this.needPageRefresh)
      submitAction('');
    else {
      this.sourceGrid.load();
      this.dialog.hide();
    }
  }
}
