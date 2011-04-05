/**
 * @class DetailPane This class displays the detail view of an object.
 */
dojo.provide("wcmf.ui");

dojo.require("dijit.layout.ContentPane");
dojo.require("dijit.layout.TabContainer");

/**
 * NodeTabContainer is a TabContainer that contains tab panels for objects
 * of it's type.
 */
dojo.declare("wcmf.ui.NodeTabContainer", dijit.layout.ContentPane, {

  /**
   * The wcmf.model.meta.Node instance which defines the type of this tab
   */
  modelClass: null,

  /**
   * The object tabs, key is the object id
   */
  nodeTabs: null,

  /**
   * Ui elements
   */
  createBtn: null,
  saveBtn: null,
  deleteBtn: null,
  tabContainer: null,

  /**
   * Constructor
   * @param options Parameter object
   *    - modelClass An instance of wcmf.model.meta.Node which defines the type of this tab
   *    + All options defined for dijit.layout.TabContainer
   */
  constructor: function(options) {
    this.modelClass = options.modelClass;
    this.nodeTabs = {};

    dojo.mixin(this, {
      // default options
      title: this.modelClass.name,
      useMenu: true
    }, options);
  },

  /**
   * Create a new wcmf.ui.DetailPane for the node with the given oid and show it.
   * If there is already a DetailPane for the node no new will be created.
   * @param oid The object id
   * @param isNewNode True if the node does not exist yet, false else
   * @return wcmf.ui.DetailPane
   */
  displayNode: function(oid, isNewNode) {
    var type = this.modelClass.name;

    // check if the oid type fits to this container
    type = wcmf.model.meta.Node.getTypeFromOid(oid);
    if (type != this.modelClass.name) {
      return null;
    }

    // check if there is already a DetailPane for the given oid
    var pane = this.nodeTabs[oid];
    if (pane == undefined) {
      // create a new DetailPane if not
      pane = new wcmf.ui.DetailPane({
        oid: oid,
        modelClass: this.modelClass,
        isNewNode: isNewNode
      });
      this.connect(pane, "onClose", function() {
        delete this.nodeTabs[oid];
        return true;
      });
      this.connect(pane, "onChange", function() {
        this.saveBtn.set('disabled', false);
      });
      this.connect(pane, "onSaved", function(pane, item, oldOid, newOid) {
        // update the tab registry if the oid changed
        if (oldOid != newOid) {
          this.nodeTabs[newOid] = pane;
          delete this.nodeTabs[oldOid];
        }
        this.updateButtonStates();
      });

      this.tabContainer.addChild(pane);
      this.nodeTabs[oid] = pane;
    }
    this.tabContainer.selectChild(pane);
    return pane;
  },

  /**
   * Delete the node with the given oid and close the related panel.
   * @param oid The object id
   */
  deleteNode: function(oid) {
    var store = wcmf.persistence.Store.getStore(this.modelClass);
    store.fetchItemByIdentity({
      scope: this,
      identity: oid,
      onItem: function(item) {
        if (item) {
          store.deleteItem(item);
          store.save({
            scope: this,
            onComplete: function() {
              var pane = this.nodeTabs[oid];
              if (pane) {
                this.tabContainer.removeChild(pane);
                delete this.nodeTabs[oid];
              }
            },
            onError: function(errorData) {
              wcmf.Error.show(wcmf.Message.get("The object could not be deleted: %1%", [errorData]));
            }
          });
        }
      }
    });
  },

  /**
   * Show the DetailPane containing the node with the given object id
   * @param oid The object id
   */
  showNode: function(oid) {
    var pane = this.nodeTabs[oid];
    if (pane != undefined) {
      this.tabContainer.selectChild(pane);
    }
  },

  /**
   * Get the selected DetailPane instance
   * @return wcmf.ui.DetailPane
   */
  getSelectedDetailPane: function() {
    var pane = this.tabContainer.selectedChildWidget;
    if (pane instanceof wcmf.ui.DetailPane) {
      return pane;
    }
    return null;
  },

  /**
   * Get the selected DetailPane instance
   * @return wcmf.ui.DetailPane
   */
  getDetailPane: function(oid) {
    var pane = this.nodeTabs[oid];
    if (pane != undefined) {
      return pane;
    }
    return null;
  },

  handleSelectEvent: function() {
    this.updateButtonStates();
  },

  updateButtonStates: function() {
    // disable all buttons per default
    this.saveBtn.set('disabled', true);
    this.deleteBtn.set('disabled', true);

    // enable selected buttons
    var pane = this.getSelectedDetailPane();
    if (pane != null) {
      if (pane.getIsDirty()) {
        this.saveBtn.set('disabled', false);
      }
      if (!pane.getIsNewNode()) {
        this.deleteBtn.set('disabled', false);
      }
    }
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
      label: wcmf.Message.get("New"),
      iconClass: "wcmfToolbarIcon wcmfToolbarIconCreate",
      onClick: function() {
        wcmf.Action.create(self.modelClass);
      }
    });
    this.saveBtn = new dijit.form.Button({
      label: wcmf.Message.get("Save"),
      iconClass: "wcmfToolbarIcon wcmfToolbarIconSave",
      disabled: true,
      onClick: function() {
        var pane = self.getSelectedDetailPane();
        if (pane) {
          wcmf.Action.save(pane.getOid());
        }
      }
    });
    this.deleteBtn = new dijit.form.Button({
      label: wcmf.Message.get("Delete"),
      iconClass: "wcmfToolbarIcon wcmfToolbarIconDelete",
      disabled: true,
      onClick: function() {
        var pane = self.getSelectedDetailPane();
        if (pane) {
          wcmf.Action.remove(pane.getOid());
        }
      }
    });
    toolbar.addChild(this.createBtn);
    toolbar.addChild(this.saveBtn);
    toolbar.addChild(this.deleteBtn);

    // create the tab container
    this.tabContainer = new dijit.layout.TabContainer({
      tabStrips: true,
      useMenu: true,
      region: "center"
    });
    this.connect(this.tabContainer, "selectChild", this.handleSelectEvent);

    // create the all objects tab
    var mainPane = new dijit.layout.ContentPane({
      title: wcmf.Message.get("All")
    });
    var mainGrid = new wcmf.ui.Grid({
      modelClass: this.modelClass,
      actions: [
        new wcmf.ui.GridActionEdit(),
        new wcmf.ui.GridActionDelete()
      ],
      region: "center"
    });

    mainPane.set('content', mainGrid);
    this.tabContainer.addChild(mainPane);
    layoutContainer.addChild(toolbar);
    layoutContainer.addChild(this.tabContainer);
    this.set('content', layoutContainer);
  },

  destroy: function() {
    this.destroyRecursive();
    this.inherited(arguments);
  }
});
