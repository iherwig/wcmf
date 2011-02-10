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
   * Indicates if any changes happened to the object data
   */
  isDirty: false,

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
      parseOnLoad: true,
      closable: true      
    }, options);
    
    dojo.connect(this, 'onLoad', this, this.initControls);    
  },
  
  /**
   * Save the contained object data
   */
  save: function() {
    wcmf.Error.hide();
    if (this.isDirty) {
      // get the store
      var store = wcmf.persistence.Store.getStore(this.modelClass);
      
      if (this.oid) {
        // update the existing object in the store
        store.fetchItemByIdentity({
          identity: this.oid,
          onItem: function(item) {
            if (item) {
              store.changing(item);
              var values = this.getObjectValues();
              for (var attribute in values) {
                item[attribute] = values[attribute];
              }
              store.save({
                onComplete: function() {
                  this.unsetDirty();
                },
                onError: function(errorData) {
                  wcmf.Error.show(wcmf.Message.get("The data could not be saved. " + errorData));
                },
                scope: this
              });
            }
          },
          scope: this
        });
      }
    }
  },
    
  initControls: function() {
    dojo.forEach(this.getDescendants(), function(widget) {
      dojo.connect(widget, "onChange", this, this.onValueChanged);
    }, this);
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
  
  getObjectValues: function() {
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
  
  getAttributeNameFromControlName: function(controlName) {
    var matches = controlName.match(/^value-(.+)-[^-]*$/);
    if (matches && matches.length > 0) {
      return matches[1];
    }
    return '';
  },
  
  onValueChanged: function() {
    this.setDirty();
  },
  
  onClose: function(e) {
    if (this.isDirty) {
      return confirm(wcmf.Message.get("Do you really want to close this panel and lose all changes?"))
    }
    return true;
  }
});
