dojo.provide("wcmf.ui.DetailPane");

dojo.require("dojox.layout.ContentPane");

/**
 * @class DetailPane
 *
 * DetailPane displays the detail view of an object.
 * The concrete representation is defined in a backend template, which
 * is loaded on creation.
 */
dojo.declare("wcmf.ui.DetailPane", dojox.layout.ContentPane, {

  /**
   * The model class of the object displayed object
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
   * A list of connect handles for field change events
   */
  fieldChangeHandles: [],

  /**
   * Constructor
   * @param options Parameter object:
   *    - modelClass The model class of the object displayed object
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
    this.connect(this, 'onLoad', this.connectFieldChangeEvents);
    this.connect(this, 'onClose', this.handleCloseEvent);

    var store = this.getStore(this.modelClass);
    this.connect(store, 'onSet', this.handleItemChangeEvent);
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
    if (this.isDirty) {
      // get the store
      var store = this.getStore(this.modelClass);
      var self = this;

      if (!this.isNewNode) {
        // update the existing object in the store
        store.fetchItemByIdentity({
          scope: this,
          identity: this.oid,
          onItem: function(item) {
            if (item) {
              // use changing to issue one server request only
              store.changing(item);
              var values = this.getFieldValues();
              for (var attribute in values) {
                item[attribute] = values[attribute];
              }
              store.save({
                scope: item,
                onComplete: function() {
                  // 'this' is the saved object
                  self.afterSave(this);
                  deferred.callback(this);
                },
                onError: function(errorData) {
                  var msg = wcmf.Message.get("The object could not be saved: %1%", [errorData]);
                  deferred.errback(msg);
                }
              });
            }
          }
        });
      }
      else {
        // create a new object in the store
        var values = this.getFieldValues();
        var item = store.newItem(values);
        store.save({
          scope: item,
          alwaysPostNewItems: true,
          onComplete: function() {
            // 'this' is the saved object
            self.afterSave(this);
            deferred.callback(this);
          },
          onError: function(errorData) {
            var msg = wcmf.Message.get("The object could not be created: %1%", [errorData]);
            deferred.errback(msg);
          }
        });
      }
    }
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
   * @param item The saved item
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
    return wcmf.persistence.Store.getStore(this.modelClass);
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
   * @param item The contained persistent item
   */
  afterSave: function(item) {
    var oldOid = this.oid;
    var wasNewNode = this.isNewNode;
    // load the item from the store to get the current content
    var store = this.getStore(this.modelClass);
    // set the oid
    this.oid = store.getValue(item, "oid");
    // update title and fields
    this.set("title", store.getLabel(item));
    this.setFieldValues(item);
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
      this.set("title", "*"+this.get("title"));
      this.isDirty = true;
      // notify listeners
      this.onChange(this);
    }
  },

  unsetDirty: function() {
    if (this.isDirty) {
      this.set("title", this.get("title").replace(/^\*/, ''));
      this.isDirty = false;
    }
  },

  /**
   * Get all input field values
   * @return Name/Value pairs
   */
  getFieldValues: function() {
    var values = {};
    dojo.forEach(this.getDescendants(), function(widget) {
      if (widget.name) {
        var attribute = this.getAttributeNameFromFieldName(widget.name);
        if (attribute) {
          values[attribute] = widget.get('value');
        }
      }
    }, this);
    return values;
  },

  /**
   * Set the input field values according to the given item
   * @param item The contained persistent item
   */
  setFieldValues: function(item) {
    // disable handleValueChangeEvent temporarily
    this.disconnectFieldChangeEvents();
    var store = this.getStore(this.modelClass);
    dojo.forEach(this.getDescendants(), function(widget) {
      if (widget.name) {
        var attribute = this.getAttributeNameFromFieldName(widget.name);
        if (attribute) {
          widget.set('value', store.getValue(item, attribute));
        }
      }
    }, this);
    // enable handleValueChangeEvent again
    this.connectFieldChangeEvents();
  },

  /**
   * Determine the item attribute name from the given field name
   * @param fieldName The name of the field
   * @return The attribute name
   */
  getAttributeNameFromFieldName: function(fieldName) {
    var matches = fieldName.match(/^value-(.+)-[^-]*$/);
    if (matches && matches.length > 0) {
      return matches[1];
    }
    return '';
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

  connectFieldChangeEvents: function() {
    dojo.forEach(this.getDescendants(), function(widget) {
      this.fieldChangeHandles.push(widget.watch(dojo.hitch(this, 'handleValueChangeEvent')));
    }, this);

  },

  disconnectFieldChangeEvents: function() {
    dojo.forEach(this.fieldChangeHandles, function(handler) {
      handler.unwatch();
    });
    this.fieldChangeHandles = [];
  },

  handleValueChangeEvent: function(propertyName, oldValue, newValue) {
    if (propertyName == 'value' || propertyName == 'displayedValue' || propertyName == 'checked') {
      this.setDirty();
    }
  },

  handleItemChangeEvent: function(item, attribute, oldValue, newValue) {
    var store = this.getStore();
    if (store.getValues(item, "oid") == this.oid) {
      // update title and fields
      this.set("title", store.getLabel(item));
      this.setFieldValues(item);
    }
  },

  handleCloseEvent: function(e) {
    if (this.isDirty) {
      return confirm(wcmf.Message.get("Do you really want to close this panel and lose all changes?"))
    }
    return true;
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
 * @param detailDivId The detail div id
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
