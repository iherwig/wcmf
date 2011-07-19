dojo.provide("wcmf.ui.AttributePane");

dojo.require("dojox.layout.ContentPane");

/**
 * @class AttributePane This class displays the attributes of an objects.
 * It's content is supposed to be defined externally.
 */
dojo.declare("wcmf.ui.AttributePane", dojox.layout.ContentPane, {

  /**
   * The type of the displayed object
   */
  modelClass: null,
  /**
   * The object id of the displayed object
   */
  oid: null,
  /**
   * The language of the displayed attributes
   */
  language: null,
  /**
   * Indicates if the displayed object is new
   */
  isNewNode: null,
  /**
   * Indicates if changes were made to the attribute values
   */
  isDirty: null,
  /**
   * Indicates if the panel already displays the attribute values
   */
  attributesLoaded: false,
  /**
   * A list of connect handles for field change events
   */
  fieldChangeHandles: [],
  /**
   * A list of sub-widgets for editing the values of the displayed object
   */
  objectValueWidgets: null,

  /**
   * Constructor
   * @param options Parameter object:
   *    - oid The object id of the object
   *    - language The language of the displayed attributes
   *    - isNewNode True if the displayed object does not exist yet, false else
   *    + All other options defined for dijit.layout.ContentPane
   */
  constructor: function(options) {
    this.oid = options.oid;
    this.language = options.language;
    this.isNewNode = options.isNewNode;
    this.attributesLoaded = false;
    this.modelClass = wcmf.model.meta.Model.getTypeFromOid(this.oid);
    this.isDirty = false;

    dojo.mixin(this, {
      // default options
      closable: false
    }, options);
  },

  postCreate: function() {
    this.inherited(arguments);
    this.connect(this, "onShow", this.handleShowEvent);

    var store = this.getStore();
    this.connect(store, 'onSet', this.handleItemChangeEvent);
  },

  /**
   * Get the language of the displayed attributes
   * @return String
   */
  getLanguage: function() {
    return this.language;
  },

  /**
   * Called, after a property of the contained object changed
   * @param pane The wcmf.ui.AttributePane which containes the changed object
   */
  onChange: function(pane) {
    // only defined for other widgets to connect to
  },

  /**
   * Save the contained object data
   * @return dojo.Deferred promise (The only parameter is the saved item)
   */
  save: function() {
    var deferred = new dojo.Deferred();
    if (!this.isDirty && !this.isNewNode) {
      // immediatly return, if we don't need to save
      wcmf.persistence.Store.fetch(this.oid, this.language).then(function(item) {
        deferred.callback(item);
      });
    }
    else {
      // get the store
      var store = this.getStore();
      var self = this;

      if (!this.isNewNode) {
        // update the existing object in the store
        wcmf.persistence.Store.fetch(this.oid, this.language).then(function(item) {
          // use changing to issue one server request only
          store.changing(item);
          var values = self.getFieldValues();
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
   * Update the AttributePane after the contained item was saved
   * @param item The contained persistent item
   */
  afterSave: function(item) {
    this.setFieldValues(item);
    // set the oid
    var store = this.getStore();
    this.oid = store.getValue(item, "oid");
    this.isDirty = false;
    this.isNewNode = false;
  },

  handleShowEvent: function() {
    if (!this.attributesLoaded && !this.isNewNode) {
      var self = this;
      wcmf.persistence.Store.fetch(this.oid, this.language).then(function(item) {
        self.setFieldValues(item);
        self.connectFieldChangeEvents();
      });
    }
    this.attributesLoaded = true;
  },

  /**
   * Get all input field values
   * @return Name/Value pairs
   */
  getFieldValues: function() {
    var values = {};
    dojo.forEach(this.getObjectValueWidgets(), function(widget) {
      if (widget.name) {
        var attribute = wcmf.ui.Form.getAttributeNameFromFieldName(widget.name);
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
    var store = this.getStore();
    dojo.forEach(this.getObjectValueWidgets(), function(widget) {
      if (widget.name) {
        var attribute = wcmf.ui.Form.getAttributeNameFromFieldName(widget.name);
        if (attribute) {
          widget.set('value', store.getValue(item, attribute));
        }
      }
    }, this);
    // enable handleValueChangeEvent again
    this.connectFieldChangeEvents();
  },

  getObjectValueWidgets: function() {
    if (this.objectValueWidgets == null) {
      this.objectValueWidgets = [];
      dojo.forEach(this.getDescendants(), function(widget) {
        if (widget.name && widget.name.indexOf("value-") == 0) {
          this.objectValueWidgets.push(widget);
        }
      }, this);
    }
    return this.objectValueWidgets;
  },

  connectFieldChangeEvents: function() {
    dojo.forEach(this.getObjectValueWidgets(), function(widget) {
      this.fieldChangeHandles.push(widget.watch(dojo.hitch(this, 'handleValueChangeEvent', widget)));
    }, this);

  },

  disconnectFieldChangeEvents: function() {
    dojo.forEach(this.fieldChangeHandles, function(handler) {
      handler.unwatch();
    });
    this.fieldChangeHandles = [];
  },

  handleValueChangeEvent: function(widget, propertyName, oldValue, newValue) {
    if (propertyName == 'value' || propertyName == 'displayedValue' || propertyName == 'checked') {
      this.onChange();
      this.isDirty = true;
    }
  },

  handleItemChangeEvent: function(item, attribute, oldValue, newValue) {
    var store = this.getStore();
    if (store.getValue(item, "oid") == this.oid) {
      // update fields
      this.setFieldValues(item);
    }
  },

  /**
   * Get the store that handles item persistence
   * @return wcmf.persistence.Store
   */
  getStore: function() {
    return wcmf.persistence.Store.getStore(this.modelClass, this.language);
  }
});
