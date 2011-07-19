dojo.provide("wcmf.ui.DetailPane");

dojo.require("dojox.layout.ContentPane");

/**
 * @class DetailPane
 *
 * DetailPane displays the detail view of an object. Widgets for displaying/
 * editing the object's attributes are supposed to be contained in
 * wcmf.ui.AttibutePane instances and the object's relations are supposed
 * to be displayed in wcmf.ui.RelationPane instances.
 * The concrete representation is defined in a backend template, which
 * is loaded on creation. This allows developers to defined the concrete
 * set of attributes/relations to be displayed.
 */
dojo.declare("wcmf.ui.DetailPane", dojox.layout.ContentPane, {

  /**
   * The model class of the object displayed object (wcmf.mode.meta.Node instance)
   */
  modelClass: null,
  /**
   * The object id of the object that is edited
   */
  oid: null,
  /**
   * Indicates if the displayed object is new
   */
  isNewNode: null,
  /**
   * Indicates if any changes happened to the object data
   */
  isDirty: false,
  /**
   * The list of wcmf.ui.AttributePane instances to listen to
   */
  attributePaneInstances: null,

  /**
   * Constructor
   * @param options Parameter object:
   *    - modelClass The model class of the object displayed object (wcmf.mode.meta.Node instance)
   *    - oid The object id of the object that is edited
   *    - isNewNode True if the displayed object does not exist yet, false else
   *    + All other options defined for dijit.layout.ContentPane
   */
  constructor: function(options) {
    this.modelClass = options.modelClass;
    this.oid = options.oid;
    this.isNewNode = options.isNewNode;

    this.title = this.isNewNode ? wcmf.Message.get("New %1%", [this.modelClass.name]) : this.oid;
    this.href = this.getHref();

    dojo.mixin(this, {
      // default options
      parseOnLoad: true,
      preload: true,
      closable: true
    }, options);

    // mark dirty if the oid is null
    if (this.isNewNode) {
      this.setDirty();
    }
  },

  postCreate: function() {
    this.inherited(arguments);
    this.connect(this, 'onLoad', this.handleLoadEvent);
    this.connect(this, 'onClose', this.handleCloseEvent);

    var store = this.getStore();
    this.connect(store, 'onSet', this.handleItemChangeEvent);
  },

  /**
   * Set the title of the pane
   * @param title The title
   */
  setTitle: function(title) {
    var re = /<[^>]*?>/gi;
    this.set("title", dojo.trim(title.replace(re, "")));
  },

  /**
   * Get the object id of the contained object
   * @return String
   */
  getOid: function() {
    return this.oid;
  },

  /**
   * Check if the contained object is new
   * @return Boolean
   */
  getIsNewNode: function() {
    return this.isNewNode;
  },

  /**
   * Check if the contained object was changed
   * @return Boolean
   */
  getIsDirty: function() {
    return this.isDirty;
  },

  /**
   * Save the contained object data
   * @return dojo.Deferred promise (The only parameter is the saved item)
   */
  save: function() {
    var deferred = new dojo.Deferred();
    var self = this;

    // call save on each attribute panel
    var deferredArray = [];
    dojo.forEach(this.getAttributePaneInstances(), function(widget) {
      deferredArray.push(widget.save());
    }, this);

    // create a deferred list to wait for all panels to save
    var deferredList = new dojo.DeferredList(deferredArray);
    deferredList.addCallback(function(result) {
      // NOTE: the callback parameter is an array of results which contains
      // localized items, but since we are only interested in the oid,
      // this doesn't matter
      var item = result[0][1];
      self.afterSave(item);
      deferred.callback(item);
    });
    deferredList.addErrback(function(errorData) {
      deferred.errback(errorData);
    });

    return deferred.promise;
  },

  /**
   * Called, after a property of the contained object changed
   * @param pane The wcmf.ui.DetailPane which containes the changed object
   */
  onChange: function(pane) {
    // only defined for other widgets to connect to
  },

  /**
   * Called, after the pane content was saved. The two oid parameters may
   * differ, if the contained object was not contained in the store before.
   * @param pane The wcmf.ui.DetailPane that containes the saved object
   * @param item The saved item (in the default language)
   * @param oldOid The object id of the contained object before saving
   * @param newOid The object id of the contained object after saving
   */
  onSaved: function(pane, item, oldOid, newOid) {
    // only defined for other widgets to connect to
  },

  /**
   * Get the store that handles item persistence
   * @return wcmf.persistence.Store
   */
  getStore: function() {
    return wcmf.persistence.Store.getStore(this.modelClass, wcmf.defaultLanguage);
  },

  /**
   * Update the list of objects in the given relation
   * @param name The relation name
   */
  reloadRelation: function(name) {
    dojo.forEach(this.getDescendants(), function(widget) {
      if (widget instanceof wcmf.ui.RelationTabContainer) {
        widget.reloadRelation(name);
      }
    }, this);
  },

  /**
   * Update the DetailPane after the contained item was saved
   * and notify onSaved listeners
   * @param item The contained persistent item (in the default language)
   */
  afterSave: function(item) {
    var oldOid = this.oid;
    var wasNewNode = this.isNewNode;
    // set the oid
    var store = this.getStore();
    this.oid = store.getValue(item, "oid");
    this.unsetDirty();
    this.isNewNode = false;

    // reload the content if the node was new
    if (wasNewNode) {
      var self = this;
      // don't use set() to avoid automatic loading
      this.href = this.getHref();
      this.refresh().then(function() {
        // notify listeners
        self.onSaved(self, item, oldOid, self.oid);
      });
    }
    else {
      // notify listeners
      this.onSaved(this, item, oldOid, this.oid);
    }
  },

  setDirty: function() {
    if (!this.isDirty) {
      this.setTitle("*"+this.get("title"));
      this.isDirty = true;
      // notify listeners
      this.onChange(this);
    }
  },

  unsetDirty: function() {
    if (this.isDirty) {
      this.setTitle(this.get("title").replace(/^\*/, ''));
      this.isDirty = false;
    }
  },

  getAttributePaneInstances: function() {
    if (this.attributePaneInstances == null) {
      this.attributePaneInstances = [];
      dojo.forEach(this.getDescendants(), function(widget) {
        if (widget instanceof wcmf.ui.AttributePane) {
          this.attributePaneInstances.push(widget);
        }
      }, this);
    }
    return this.attributePaneInstances;
  },

  /**
   * Get the url for loading the content
   * @return String
   */
  getHref: function() {
    if (this.isNewNode) {
      return '?action=detail&type='+this.modelClass.name;
    }
    else {
      return '?action=detail&oid='+this.oid;
    }
  },

  handleItemChangeEvent: function(item, attribute, oldValue, newValue) {
    var store = this.getStore();
    if (store.getValue(item, "oid") == this.oid) {
      // update title
      this.setTitle(store.getLabel(item));
    }
  },

  handleCloseEvent: function(e) {
    if (this.isDirty) {
      return confirm(wcmf.Message.get("Do you really want to close this panel and lose all changes?"))
    }
    return true;
  },

  handleLoadEvent: function(e) {
    // listen to changes in attribute panel instances
    this.attributePaneInstances = null;
    dojo.forEach(this.getAttributePaneInstances(), function(widget) {
      this.connect(widget, "onChange", this.setDirty);
    }, this);

    // set the title, if the object existed already
    var self = this;
    if (!this.isNewNode) {
      wcmf.persistence.Store.fetch(self.oid, wcmf.defaultLanguage).then(function(item) {
        var store = self.getStore();
        self.setTitle(store.getLabel(item));
      });
    }
  },

  destroy: function() {
    this.destroyDescendants();
    this.inherited(arguments);
  }
});

/**
 * Get the DetailPane instance for a given object id
 * @param oid The object id
 * @return wcmf.ui.DetailPane or null, if not opened
 */
wcmf.ui.DetailPane.get = function(oid) {
  var nodeTabContainer = wcmf.ui.NodeTabContainer.get(oid);
  return nodeTabContainer.getDetailPane(oid);
}

/**
 * Get the enclosing DetailPane instance for a given div id. This method is
 * especially useful for controls that are embedded in a DetailPane instance.
 * @param divId The detail div id
 * @return wcmf.ui.DetailPane or null
 */
wcmf.ui.DetailPane.getFromContainedDiv = function(divId) {
  var maxDepth = 20;
  var widget = dijit.getEnclosingWidget(dojo.byId(divId));
  while (maxDepth > 0 && !(widget instanceof wcmf.ui.DetailPane)) {
    widget = dijit.getEnclosingWidget(widget.domNode.parentNode);
    maxDepth--;
  }
  return widget;
}
