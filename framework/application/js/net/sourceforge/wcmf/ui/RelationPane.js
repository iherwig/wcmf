dojo.provide("wcmf.ui.RelationPane");

dojo.require("dojox.layout.ContentPane");
dojo.require("dijit.layout.BorderContainer");

dojo.require("wcmf.ui.Grid");

/**
 * @class RelationPane This class displays the objects that are in a
 * specific relation to another object.
 */
dojo.declare("wcmf.ui.RelationPane", dojox.layout.ContentPane, {

  /**
   * The type of object on this side of the relation
   */
  modelClass: null,
  /**
   * The object id of the object on this side of the relation
   */
  oid: null,
  /**
   * The type of the objects on the other side of the relation
   */
  otherClass: null,
  /**
   * The role of the objects on the other side of the relation
   */
  otherRole: null,
  /**
   * The query to find objects on the other side of the relation
   */
  relationQuery: null,
  /**
   * An object with the following properties:
   * - attribute: the name of the attribute to sort by
   * - descending: boolean
   */
  sortInfo: null,

  /**
   * UI elements
   */
  createBtn: null,
  associateBtn: null,
  relationsGrid: null,

  /**
   * Constructor
   * @param options Parameter object:
   *    - oid The object id of the object on this side of the relation
   *    - otherRole The role of the objects on the other side of the relation
   *    - relationQuery The query to find objects on the other side of the relation
   *    - sortInfo An object with the following properties:
   *      - attribute: the name of the attribute to sort by
   *      - descending: boolean
   *    + All other options defined for dijit.layout.ContentPane
   */
  constructor: function(options) {
    this.oid = options.oid;
    this.otherRole = options.otherRole;
    this.relationQuery = options.relationQuery;
    this.sortInfo = options.sortInfo;

    this.modelClass = wcmf.model.meta.Model.getTypeFromOid(this.oid);
    this.otherClass = this.modelClass.getTypeForRole(this.otherRole);
    this.title = this.otherRole;

    dojo.mixin(this, {
      // default options
      closable: false
    }, options);
  },

  /**
   * Reload the content
   */
  reload: function() {
    this.relationsGrid.setQuery({
      query: this.relationQuery
    });
  },

  buildRendering: function() {
    this.inherited(arguments);

    var self = this;
    var layoutContainer = new dijit.layout.BorderContainer({
      gutters: false
    });

    // create the toolbar
    var toolbar = new dijit.Toolbar({
      region: "top"
    });
    this.createBtn = new dijit.form.Button({
      label: wcmf.Message.get("New %1%", [this.otherRole]),
      iconClass: "wcmfToolbarIcon wcmfToolbarIconCreate",
      onClick: function() {
        var pane = wcmf.Action.create(self.otherClass);
        var eventHandle = self.connect(pane, "onSaved", function(pane, item, oldOid, newOid) {
          // associate (only on first time)
          wcmf.Action.associate(self.oid, newOid, self.otherRole).then(function() {
            // show the original node to which the new one is connected
            //wcmf.ui.TypeTabContainer.getInstance().displayNode(self.oid, false);
            // disconnect the save event handler
            self.disconnect(eventHandle);
          });
        });
      }
    });
    this.associateBtn = new dijit.form.Button({
      label: wcmf.Message.get("Associate %1%", [this.otherRole]),
      iconClass: "wcmfToolbarIcon wcmfToolbarIconAssociate",
      onClick: function() {
        var dialog = new wcmf.ui.ObjectSelectDialog({
          modelClass: self.otherClass
        });
        dialog.show().then(function(selectedObjects) {
          // associate the selected objects
          for (var i=0, count=selectedObjects.length; i<count; i++) {
            wcmf.Action.associate(self.oid, selectedObjects[i].oid, self.otherRole);
          }
        });
      }
    });
    toolbar.addChild(this.createBtn);
    toolbar.addChild(this.associateBtn);

    // create relations grid
    var gridOptions = {
      modelClass: this.otherClass,
      query: {
        query: this.relationQuery
      },
      actions: [
        new wcmf.ui.GridActionEdit(),
        new wcmf.ui.GridActionDisassociate({
          sourceOid: this.oid,
          role: this.otherRole
        }),
        new wcmf.ui.GridActionDelete()
      ],
      masterOid: this.oid,
      role: this.otherRole,
      region: "center"
    };
    if (this.sortInfo) {
      gridOptions["sortFields"] = [this.sortInfo];
    }
    this.relationsGrid = new wcmf.ui.Grid(gridOptions);

    layoutContainer.addChild(toolbar);
    layoutContainer.addChild(this.relationsGrid);
    layoutContainer.startup();
    this.set('content', layoutContainer);
  },

  destroy: function() {
    this.destroyDescendants();
    this.inherited(arguments);
  }
});
