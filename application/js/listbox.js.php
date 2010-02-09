/**
 * @class Listbox. Build on Ext.form.ComboBox
 */
Listbox = function() {};

Listbox.prototype = {
  // the displayed type
  type:null,
  // the custom parameters
  customParams:null,
  // the Ext.data.Store instance
  ds:null,
  
  /**
   * Initialize a grid that displays entities
   * @param id The html container id
   * @param name The name submitted to the server
   * @param type The entity type to display
   * @param value The selected value (aka 'key')
   * @param display The displayed value (aka 'value')
   * @param filter A filter string to be used to filter the entities (see StringQuery), maybe obfuscated using Obfuscator
   * @param config An associative array with the following keys: none defined yet
   * @param customParams An assoziative array of additional values passed to the controller [optional]
   */
  init: function(id, name, type, value, display, filter, config, customParams) {
    this.type = type;
    this.customParams = customParams;

    // create the Data Store
    this.ds = new Ext.data.Store({
        proxy:new Ext.data.HttpProxy({
            url:'<?php echo $APP_URL; ?>'
        }),
        
        baseParams:{type:type, controller:'<?php echo $controller; ?>', context:'<?php echo $context; ?>', usr_action:'listbox', response_format:'JSON', sid:'<?php echo session_id() ?>', filter:filter},

        reader:new Ext.data.JsonReader({
            root:'objects',
            totalProperty:'totalCount',
            id:'key'
        }, [{name:'key'}, {name:'val'}]),

        // turn on remote sorting
        remoteSort:true
    });
    
    // add custom parameters to baseParams
    if (customParams)
      for (var i in customParams)
        this.ds.baseParams[i] = customParams[i];

    // create the combo box
    var self = this;
    var combo = new Ext.form.ComboBox({
        store:this.ds,
        applyTo:id,
        displayField:'val',
        valueField:'key',
        hiddenName:name,
        mode:'remote',
        triggerAction:'all',
        emptyText:'',
        editable:true,
        forceSelection:false,
        typeAhead:false,
        selectOnFocus:true,
        listeners: {
          beforequery: function(queryEvent) {
            self.ds.baseParams.displayFilter = "^"+queryEvent.query;
          }
        }
    });
    if (value && display) {
      // add the given record and set it
      RecType = Ext.data.Record.create([{name:'key'}, {name:'val'}]);
      var record = new RecType({key:value, val:display});
      this.ds.add(record);
      combo.setValue(value);
    }
  }
};
