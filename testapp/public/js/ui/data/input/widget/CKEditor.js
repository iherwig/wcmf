CKEDITOR_BASEPATH = appConfig.pathPrefix+'/vendor/ckeditor/';

define( [
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/topic",
    "dijit/form/TextBox",
    "ckeditor/ckeditor",
    "dojo/text!./template/CKEditor.html"
],
function(
    declare,
    lang,
    topic,
    TextBox,
    CKEditor,
    template
) {
    return declare([TextBox], {

        templateString: template,
        intermediateChanges: true,
        entity: {},
        attribute: {},
        original: {},
        editorInstance: null,

        constructor: function(args) {
            declare.safeMixin(this, args);

            this.label = this.attribute.name;
            this.disabled = !this.attribute.isEditable;
            this.name = this.attribute.name;
            this.value = this.entity[this.attribute.name];
        },

        postCreate: function() {
            this.inherited(arguments);

            this.editorInstance = CKEDITOR.replace(this.textbox, {
                customConfig: appConfig.pathPrefix+'/js/config/ckeditor_config.js',
                filebrowserBrowseUrl: 'main.php?usr_action=browsemedia',
                filebrowserLinkBrowseUrl: 'main.php?usr_action=browsecontent',
                filebrowserWindowWidth: '800',
                filebrowserWindowHeight: '700'
            });
            this.helpNode.innerHTML = this.original[this.attribute.name] || "";

            // subscribe to entity change events to change tab links
            this.own(
                topic.subscribe("entity-datachange", lang.hitch(this, function(data) {
                    if (data.name === this.attribute.name) {
                        this.set("value", data.newValue);
                        this.editorInstance.setData(data.newValue);
                    }
                })),
                this.editorInstance.on("instanceReady", lang.hitch(this, function() {
                    this.editorInstance.on("key", lang.hitch(this, function() {
                        setTimeout(lang.hitch(this, function() {
                            this.editorValueChanged();
                        }, 0));
                    }));
                }))
            );
        },

        editorValueChanged: function() {
            this.set("value", this.editorInstance.getData());
        }
    });
});