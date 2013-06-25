CKEDITOR_BASEPATH = appConfig.pathPrefix+'/vendor/ckeditor/';

define( [
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/on",
    "dojo/topic",
    "dijit/form/TextBox",
    "ckeditor/ckeditor",
    "dojo/text!./template/CKEditor.html"
],
function(
    declare,
    lang,
    on,
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

            var mediaBrowserRoute = appConfig.pathPrefix+'/media';
            var linkBrowserRoute = appConfig.pathPrefix+'/link';
            var mediaFileBasePath = appConfig.pathPrefix+'/media';

            this.editorInstance = CKEDITOR.replace(this.textbox, {
                customConfig: appConfig.pathPrefix+'/js/config/ckeditor_config.js',
                filebrowserBrowseUrl: mediaBrowserRoute,
                filebrowserLinkBrowseUrl: linkBrowserRoute,
                baseHref: mediaFileBasePath,
                filebrowserWindowWidth: '800',
                filebrowserWindowHeight: '700'
            });

            // subscribe to entity change events to change tab links
            this.own(
                topic.subscribe("entity-datachange", lang.hitch(this, function(data) {
                    if (data.name === this.attribute.name) {
                        this.set("value", data.newValue);
                        this.editorInstance.setData(data.newValue);
                    }
                })),
                this.editorInstance.on("instanceReady", lang.hitch(this, function() {
                    this.editorInstance.on("key", lang.hitch(this, this.editorValueChanged));
                    this.editorInstance.on("paste", lang.hitch(this, this.editorValueChanged));
                    this.editorInstance.on("afterCommandExec", lang.hitch(this, this.editorValueChanged));
                }))
            );
        },

        editorValueChanged: function() {
            setTimeout(lang.hitch(this, function() {
                this.set("value", this.editorInstance.getData());
                // send change event
                this.emit("change", this);
            }, 0));
        }
    });
});