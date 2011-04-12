/**
 * @class RelationDialog This class displays a list of objects of the given type.
 */
dojo.provide("wcmf.ui");

dojo.require("dijit.Dialog");
dojo.require("dijit.layout.ContentPane");
dojo.require("dijit.layout.BorderContainer");

dojo.declare("wcmf.ui.RelationDialog", dijit.Dialog, {

  /**
   * The type of objects to show
   */
  modelClass: null,

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

    dojo.mixin(this, {
      // default options
      title: wcmf.Message.get("Associate %1%", [this.modelClass.name]),
      style: "width:500px; height:400px; overflow: auto;"
    }, options);
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

    var okBtn = new dijit.form.Button({
      label: wcmf.Message.get("OK"),
      onClick: dojo.hitch(this, function() {
        this.hide();
      })
    }, dojo.create('div'));
    var cancelBtn = new dijit.form.Button({
      label: wcmf.Message.get("Cancel"),
      onClick: dojo.hitch(this, function() {
        this.hide();
      })
    }, dojo.create('div'));
    this.buttonbar.domNode.appendChild(okBtn.domNode);
    this.buttonbar.domNode.appendChild(cancelBtn.domNode);
  },

  destroy: function() {
    this.destroyRecursive();
    this.inherited(arguments);
  }
});
