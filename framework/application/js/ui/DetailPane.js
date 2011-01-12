/**
 * @class DetailPane This class displays the detail view of an object.
 */
dojo.provide("wcmf.ui");

dojo.declare("wcmf.ui.DetailPane", dijit.layout.ContentPane, {

  modelClass: null,
  oid: null,

  /**
   * Constructor
   * @param options Parameter object:
   *    - modelClass The model class whose instances this grid contains
   *    - oid The object id of the displayed object (optional)
   *    + All other options defined for dijit.layout.ContentPane
   */
  constructor: function(options) {
    this.modelClass = options.modelClass;
    this.oid = options.oid;

    dojo.mixin(this, {
      // default options
    }, options);
    
    dojo.connect(this, 'onShow', this, this.createContent);    
  },

  createContent: function() {
	/*
    var relations = this.modelClass.relations;
    for (var i=0, count=relations.length; i<count; i++) {
      var grid = new wcmf.ui.Grid({
        modelClass: wcmf.model[relations[i].type],
        height: "100px",
        autoheight: false,
        rowsPerPage: 10
      });
      this.domNode.appendChild(grid.domNode);
      grid.startup();
    }
    */
  }
});
