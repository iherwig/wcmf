dojo.provide("wcmf.ui.CkEditorWidget");

/**
 * @class CkEditorWidget
 *
 * CkEditorWidget integrates CkEditor into a dojo widget. It uses
 * the original Textarea instance only for interaction with other
 * widgets and synchonizes an internal CkEditor instance with the
 * changes.
 */
dojo.declare("wcmf.ui.CkEditorWidget",  [dijit.form.Textarea], {

  /**
   * The CkEditor instance
   */
  ckEditorInstance: null,

  /**
   * The options object to be passed to the editor instance
   */
  options: {},

  /**
   * Constructor
   * @param options Parameter object:
   *    - filebrowserUrl The url of the filebrowser to use with the editor
   *    - customConfig The url of a file containing the ckEditor configuration
   *    + All other options defined for CkEditor
   */
  constructor: function(options) {
    this.options = options;
  },

  postCreate: function(args, frag) {
    this.inherited('postCreate', arguments);

    // create the editor instance
    var ckEditorDiv = dojo.create("div", {innerHTML: this.value}, this.domNode, "after");
    this.ckEditorInstance = CKEDITOR.replace(ckEditorDiv, this.options);
    // hide the original textbox
    dojo.style(this.domNode, "display", "none");

    // add event listeners
    this.ckEditorInstance.on('change', dojo.hitch(this, 'handleCkEditorChangeEvent'));
    this.watch(dojo.hitch(this, 'handleHiddenFieldChangeEvent'));
  },

  /**
   * Called when the CkEditor value changes
   */
  handleCkEditorChangeEvent: function(e) {
    var newValue = this.ckEditorInstance.getData();
    if (this.get('value') != newValue) {
      this.set('value', newValue);
    }
  },

  /**
   * Called when the Textarea value changes
   */
  handleHiddenFieldChangeEvent: function(propertyName, oldValue, newValue) {
    if (propertyName == 'displayedValue' && this.ckEditorInstance.getData() != newValue) {
      this.ckEditorInstance.setData(newValue);
    }
  },

  destroy: function() {
    this.ckEditorInstance.destroy(true);
    this.inherited(arguments);
  }
});