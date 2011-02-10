/**
 * @class wcmf.grid.Action. Base class for grid actions
 */
wcmf.grid.Action = function(config) {
    Ext.apply(this, config);
    wcmf.grid.Action.superclass.constructor.call(this);
};

Ext.extend(wcmf.grid.Action, Ext.util.Observable, {
    header:'',
    width:25,
    sortable:false,
    fixed:true,
    dataIndex:'',

    init: function(grid) {
        this.grid = grid;

        // add click listener
        grid.on('render', function() {
            grid.getView().mainBody.on('mousedown', this.onMouseDown, this);
        }, this);
    },

    /**
     * Perform the action. The name is derived from the className of the clicked image.
     */
    onMouseDown: function(e, t) {
        var action = t.className;
        var regexp = /Action$/;
        if (action.search(regexp) > 0) {
          var actionName = action.replace(regexp, '');
          if (this.getSupportedActions().indexOf(actionName) != -1) {

            // get row
            var row = e.getTarget('.x-grid3-row');
            // get the row record
            var record = this.grid.getStore().getAt(row.rowIndex);
            // select the row
            this.grid.getSelectionModel().selectRow(row.rowIndex, false); 

            e.stopEvent();
            this.performAction(actionName, record);
          }
        }
    },

    /**
     * Get the names of actions that this class handles. Subclasses have to
     * specify this
     */
    getSupportedActions: function() {
      return [];
    },
    
    /**
     * Perform the given action
     */
    performAction: function(actionName, record) {
      // do nothing
    },
    
    /**
     * Render a link. Any parameter may be null.
     */
    renderAction: function(actionName, text, image) {
      var link = '';
        
      if (image != null)
        link += String.format('<img class="{0}" src="{1}" alt="{2}" title="{2}" border="0" style="cursor:pointer;">', actionName, image, text);
      else
        link += text;

      return link;
    }
});

/**
 * @class wcmf.grid.SortAction. Action for sorting row items
 */
wcmf.grid.SortAction = function(config) {
    Ext.apply(this, config);
    wcmf.grid.SortAction.superclass.constructor.call(this);
};
Ext.extend(wcmf.grid.SortAction, wcmf.grid.Action, {
    width:30,

    getSupportedActions: function() {
      return ['sortDown', 'sortUp'];
    },

    performAction: function(actionName, record) {
      // sort up action
      if (actionName == 'sortUp') {
        Action.perform('sortup', {sortoid:record['id'], prevoid:record.data['prevoid']}, this.grid.actionPerformed, this);
      }
      // sort down action
      else if (actionName == 'sortDown') {
        Action.perform('sortdown', {sortoid:record['id'], nextoid:record.data['nextoid']}, this.grid.actionPerformed, this);
      }    
    },
    
    renderer: function(v, p, record) {
      var actionNav = '';

      // sort up action
      if (record.data['hasSortUp'] && record.data['hasSortUp'])
        actionNav += wcmf.grid.Action.prototype.renderAction("sortUpAction", Message.get("Up"), "images/up.png");
      else
        actionNav += wcmf.grid.Action.prototype.renderAction("", Message.get("Up"), "images/up_grey.png");

      // sort down action
      if (record.data['hasSortDown'] && record.data['hasSortDown'])
        actionNav += wcmf.grid.Action.prototype.renderAction("sortDownAction", Message.get("Down"), "images/down.png");
      else
        actionNav += wcmf.grid.Action.prototype.renderAction("", Message.get("Down"), "images/down_grey.png");

      return '<span class="txtdefault">'+actionNav+'</span>';
    }
});

/**
 * @class wcmf.grid.EditAction. Action for editing row items
 */
wcmf.grid.EditAction = function(config) {
    Ext.apply(this, config);
    wcmf.grid.EditAction.superclass.constructor.call(this);
};
Ext.extend(wcmf.grid.EditAction, wcmf.grid.Action, {

    getSupportedActions: function() {
      return ['edit'];
    },

    performAction: function(actionName, record) {
      // see if we have a real subject to use instead of record
      var realSubject = record.data._properties.realSubject;
      // edit action
      if (realSubject) {
        setContext(realSubject['type']); doDisplay(realSubject['oid']); submitAction('display');
      }
      else {
        setContext(record.data._type); doDisplay(record['id']); submitAction('display');
      }
    },
    
    renderer: function(v, p, record) {
      var actionNav = '';
      // see if we have a real subject to use instead of record
      var realSubject = record.data._properties.realSubject;

      // edit action
      if (realSubject)
        actionNav += wcmf.grid.Action.prototype.renderAction("editAction", Message.get("Edit %1%", [realSubject['oid']]), "images/edit.png");
      else
        actionNav += wcmf.grid.Action.prototype.renderAction("editAction", Message.get("Edit %1%", [record['id']]), "images/edit.png");

      return '<span class="txtdefault">'+actionNav+'</span>';
    }
});

/**
 * @class wcmf.grid.DuplicateAction. Action for duplicating row items
 */
wcmf.grid.DuplicateAction = function(config) {
    Ext.apply(this, config);
    wcmf.grid.DuplicateAction.superclass.constructor.call(this);
};
Ext.extend(wcmf.grid.DuplicateAction, wcmf.grid.Action, {

    getSupportedActions: function() {
      return ['duplicate'];
    },

    performAction: function(actionName, record) {
      // duplicate action
      var params = {oid:record['id'], oneCall:true};
      if (this.grid.store.baseParams && this.grid.store.baseParams['poid'])
        params.targetoid = this.grid.store.baseParams['poid'];
      Action.perform('copy', params, this.grid.actionPerformed, this);
    },
    
    renderer: function(v, p, record) {
      var actionNav = '';

      // duplicate action
      actionNav += wcmf.grid.Action.prototype.renderAction("duplicateAction", Message.get("Duplicate %1%", [record['id']]), "images/duplicate.png");

      return '<span class="txtdefault">'+actionNav+'</span>';
    }
});

/**
 * @class wcmf.grid.DeleteAction. Action for deleting row items
 */
wcmf.grid.DeleteAction = function(config) {
    Ext.apply(this, config);
    wcmf.grid.DeleteAction.superclass.constructor.call(this);
};
Ext.extend(wcmf.grid.DeleteAction, wcmf.grid.Action, {

    getSupportedActions: function() {
      return ['delete', 'unlink'];
    },

    performAction: function(actionName, record) {
      // see if we have a real subject to use instead of record
      var realSubject = record.data._properties.realSubject;
      // delete action
      if (actionName == 'delete') {
        var _this = this;
        var _grid = this.grid;
        Ext.MessageBox.confirm(Message.get("Delete %1%", [record['id']]), Message.get("Really delete node %1%?", [record['id']]), 
          function(btn) {
            if (btn == "yes") {
              Action.perform('delete', {deleteoids:record['id']}, _grid.actionPerformed, _this);
            }
          });
      }
      // unlink action
      else if (actionName == 'unlink') {
        if (realSubject)
          associateoid = realSubject['oid'];
        else
          associateoid = record['id'];
        Action.perform('disassociate', {oid:record.data._properties.clientOID, associateoids:associateoid}, this.grid.actionPerformed, this);
      }
    },
    
    renderer: function(v, p, record) {
      var actionNav = '';
      // see if we have a real subject to use instead of record
      var realSubject = record.data._properties.realSubject;

      // delete/unlink action
      if (record.data._properties.composition == undefined || record.data._properties.composition == true)
        actionNav += wcmf.grid.Action.prototype.renderAction("deleteAction", Message.get("Delete %1%", [record['id']]), "images/delete.png");
      else {
        if (realSubject || record.data._properties.aggregation == true)
          actionNav += wcmf.grid.Action.prototype.renderAction("unlinkAction", Message.get("Disassociate %1%", [record['id']]), "images/unlink.png");
      }

      return '<span class="txtdefault">'+actionNav+'</span>';
    }
});