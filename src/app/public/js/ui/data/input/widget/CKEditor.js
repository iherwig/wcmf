CKEDITOR_BASEPATH = appConfig.pathPrefix+'/vendor/ckeditor/';

define( [
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/on",
    "dojo/topic",
    "dijit/form/TextBox",
    "ckeditor/ckeditor",
    "../Factory",
    "../../../../model/meta/Model",
    "../../../../locale/Dictionary",
    "../../../_include/_HelpMixin",
    "./_AttributeWidgetMixin",
    "dojo/text!./template/CKEditor.html"
],
function(
    declare,
    lang,
    on,
    topic,
    TextBox,
    CKEditor,
    ControlFactory,
    Model,
    Dict,
    _HelpMixin,
    _AttributeWidgetMixin,
    template
) {
    return declare([TextBox, _HelpMixin, _AttributeWidgetMixin], {

        templateString: template,
        intermediateChanges: true,
        entity: {},
        attribute: {},
        original: {},
        editorInstance: null,

        constructor: function(args) {
            declare.safeMixin(this, args);

            var typeClass = Model.getTypeFromOid(this.entity.oid);

            this.label = Dict.translate(this.attribute.name);
            this.disabled = typeClass ? !typeClass.isEditable(this.attribute, this.entity) : false;
            this.name = this.attribute.name;
            this.value = this.entity[this.attribute.name];
            this.helpText = Dict.translate(this.attribute.description);
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
                toolbar: this.getToolbarName(),
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
                }))
            );
            this.editorInstance.on("instanceReady", lang.hitch(this, function() {
                this.editorInstance.on("key", lang.hitch(this, this.editorValueChanged));
                this.editorInstance.on("paste", lang.hitch(this, this.editorValueChanged));
                this.editorInstance.on("afterCommandExec", lang.hitch(this, this.editorValueChanged));
            }))
        },

        editorValueChanged: function() {
            setTimeout(lang.hitch(this, function() {
                this.set("value", this.editorInstance.getData());
                // send change event
                this.emit("change", this);
            }, 0));
        },

        getToolbarName: function() {
            var options = ControlFactory.getOptions(this.attribute.inputType);
            return (options.toolbarSet) ? options.toolbarSet : "wcmf";
        },

        destroy: function() {
            this.editorInstance.removeAllListeners();
            this.inherited(arguments);
        }
    });
});