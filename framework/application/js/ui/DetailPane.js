/**
 * @class DetailPane This class displays the detail view of an object.
 */
dojo.provide("wcmf.ui");

dojo.require("dojox.layout.ContentPane");

dojo.declare("wcmf.ui.DetailPane", dojox.layout.ContentPane, {

  /**
   * The type of object that is edited
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
   * A list of connect handles for widget change events
   */
  onChangeHandles: [],

  /**
   * Constructor
   * @param options Parameter object:
   *    - modelClass The model class whose instances this pane contains
   *    - oid The object id of the displayed object
   *    - isNewNode True if the node does not exist yet, false else
   *    + All other options defined for dijit.layout.ContentPane
   */
  constructor: function(options) {
    this.modelClass = options.modelClass;
    this.oid = options.oid;
    this.isNewNode = options.isNewNode;

    this.title = this.isNewNode ? wcmf.Message.get("New %1%", [this.modelClass.type]) : this.oid;
    this.href = this.isNewNode ? '?action=detail&type='+this.modelClass.type : '?action=detail&oid='+this.oid;

    dojo.mixin(this, {
      // default options
      parseOnLoad: true,
      closable: true
    }, options);

    dojo.connect(this, 'onLoad', this, this.initControls);
    dojo.connect(this, 'onClose', this, this.handleCloseEvent);

    // mark dirty if the oid is null
    if (this.isNewNode) {
      this.setDirty();
    }
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
   */
  save: function() {
    wcmf.Error.hide();
    if (this.isDirty) {
      // get the store
      var store = wcmf.persistence.Store.getStore(this.modelClass);
      var self = this;

      if (!this.isNewNode) {
        // update the existing object in the store
        store.fetchItemByIdentity({
          scope: this,
          identity: this.oid,
          onItem: function(item) {
            if (item) {
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
                },
                onError: function(errorData) {
                  wcmf.Error.show(wcmf.Message.get("The object could not be saved. " + errorData));
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
          onComplete: function() {
            // 'this' is the saved object
            self.afterSave(this);
          },
          onError: function(errorData) {
            wcmf.Error.show(wcmf.Message.get("The object could not be created. " + errorData));
          }
        });
      }
    }
  },

  /**
   * Called, after a property of the contained object changed
   * @param pane The wcmf.ui.DetailPane which containes the changed object
   * @param propertyName The name of the property that changed
   * @param oldValue The old value of the property
   * @param newValue The new value of the property
   */
  onChange: function(pane, propertyName, oldValue, newValue) {
    // only defined for other widgets to connect to
  },

  /**
   * Called, after the pane content was saved. The two oid parameters may
   * differ, if the contained object was not contained in the store before.
   * @param pane The wcmf.ui.DetailPane that containes the saved object
   * @param oldOid The object id of the contained object before saving
   * @param newOid The object id of the contained object after saving
   */
  onSaved: function(pane, oldOid, newOid) {
    // only defined for other widgets to connect to
  },

  initControls: function() {
    this.connectWidgetChangeEvents();
  },

  afterSave: function(item) {
    var oldOid = this.oid;
    // load the item from the store to get the current content
    var store = wcmf.persistence.Store.getStore(this.modelClass);
    // set the oid
    this.oid = item.oid;
    // update title and fields
    this.set("title", store.getLabel(item));
    this.setFieldValues(item);
    this.unsetDirty();
    this.isNewNode = false;
    // notify listeners
    this.onSaved(this, oldOid, this.oid);
  },

  setDirty: function() {
    if (!this.isDirty) {
      this.set("title", "*"+this.get("title"));
      this.isDirty = true;
    }
  },

  unsetDirty: function() {
    if (this.isDirty) {
      this.set("title", this.get("title").replace(/^\*/, ''));
      this.isDirty = false;
    }
  },

  getFieldValues: function() {
    var values = {};
    dojo.forEach(this.getDescendants(), function(widget) {
      if (widget.name) {
        var attribute = this.getAttributeNameFromControlName(widget.name);
        if (attribute) {
          values[attribute] = widget.get('value');
        }
      }
    }, this);
    return values;
  },

  setFieldValues: function(item) {
    // disable handleValueChangeEvent temporarily
    this.disconnectWidgetChangeEvents();
    dojo.forEach(this.getDescendants(), function(widget) {
      if (widget.name) {
        var attribute = this.getAttributeNameFromControlName(widget.name);
        if (attribute) {
          widget.set('value', item[attribute]);
        }
      }
    }, this);
    // enable handleValueChangeEvent again
    this.connectWidgetChangeEvents();
  },

  getAttributeNameFromControlName: function(controlName) {
    var matches = controlName.match(/^value-(.+)-[^-]*$/);
    if (matches && matches.length > 0) {
      return matches[1];
    }
    return '';
  },

  connectWidgetChangeEvents: function() {
    dojo.forEach(this.getDescendants(), function(widget) {
      this.onChangeHandles.push(widget.watch(dojo.hitch(this, 'handleValueChangeEvent')));
    }, this);

  },

  disconnectWidgetChangeEvents: function() {
    dojo.forEach(this.onChangeHandles, function(handler) {
      handler.unwatch();
    });
    this.onChangeHandles = [];
  },

  handleValueChangeEvent: function(propertyName, oldValue, newValue) {
    if (propertyName == 'value' || propertyName == 'displayedValue') {
      this.setDirty();
      // notify listeners
      this.onChange(this, propertyName, oldValue, newValue);
    }
  },

  handleCloseEvent: function(e) {
    if (this.isDirty) {
      return confirm(wcmf.Message.get("Do you really want to close this panel and lose all changes?"))
    }
    return true;
  }
});
