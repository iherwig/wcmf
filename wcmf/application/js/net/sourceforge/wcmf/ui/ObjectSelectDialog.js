dojo.provide("wcmf.ui.ObjectSelectDialog");

dojo.require("dijit.Dialog");
dojo.require("dijit.layout.ContentPane");
dojo.require("dijit.layout.BorderContainer");

dojo.require("wcmf.ui.Grid");

/**
 * @class RelationDialog This class displays a list of objects of the given type.
 * 
 */
dojo.declare("wcmf.ui.ObjectSelectDialog", dijit.Dialog, {

  /**
   * The type of objects to show
   */
  modelClass: null,
  
  /**
   * The dojo.Deferred, that is resolved, when the ok button is clicked
   */
  selectDeferred: null,

  /**
   * UI elements
   */
  grid: null,
  buttonbar: null,

  /**
   * Constructor
   * @param options Parameter object:
   *    - modelClass The type of objects to show
   *    + All other options defined for dijit.layout.ContentPane
   */
  constructor: function(options) {
    this.modelClass = options.modelClass;
    
    this.selectDeferred = new dojo.Deferred();

    dojo.mixin(this, {
      // default options
      title: wcmf.Message.get("Associate %1%", [this.modelClass.name]),
      style: "width:500px; height:400px; overflow: auto;"
    }, options);
  },
  
  /**
   * Display the dialog. Returns a promise with the selected objects.
   * @return dojo.Deferred promise
   */
  show: function() {
    this.inherited(arguments);

    return this.selectDeferred.promise;
  },

  buildRendering: function() {
    this.inherited(arguments);

    var layoutContainer = new dijit.layout.BorderContainer({
      style: "width:480px; height:357px;",
      gutters: false
    });

    // create the toolbar
    this.buttonbar = new dijit.layout.ContentPane({
      style: "height:15px;",
      region: "bottom"
    });

    // create grid
    this.grid = new wcmf.ui.Grid({
      modelClass: this.modelClass,
      actions: [],
      region: "center"
    });

    layoutContainer.addChild(this.buttonbar);
    layoutContainer.addChild(this.grid);
    layoutContainer.startup();
    this.set('content', layoutContainer);
  },

  postCreate: function() {
    this.inherited(arguments);
    var self = this;
    
    var okBtn = new dijit.form.Button({
      label: wcmf.Message.get("OK"),
      onClick: dojo.hitch(this, function() {
        var selectedObjects = self.grid.selection.getSelected();
        self.selectDeferred.callback(selectedObjects);
        self.hide();
      })
    }, dojo.create('div'));
    var cancelBtn = new dijit.form.Button({
      label: wcmf.Message.get("Cancel"),
      onClick: dojo.hitch(this, function() {
        self.selectDeferred.cancel();
        self.hide();
      })
    }, dojo.create('div'));
    this.buttonbar.domNode.appendChild(okBtn.domNode);
    this.buttonbar.domNode.appendChild(cancelBtn.domNode);

    // destroy the dialog content on hide
    this.connect(this, 'onHide', function() {
      setTimeout(function() { self.destroy(); }, 0);
    });
  },

  destroy: function() {
    this.destroyDescendants();
    this.inherited(arguments);
  }
});
