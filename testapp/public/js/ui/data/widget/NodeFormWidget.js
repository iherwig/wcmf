define( [
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/dom-form",
    "dojo/query",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "../../_include/_NotificationMixin",
    "bootstrap/Button",
    "../../../model/meta/Model",
    "../../../persistence/Store",
    "../../../Loader",
    "./NodeRelationWidget",
    "dojo/text!./template/NodeFormWidget.html"
],
function(
    declare,
    lang,
    domForm,
    query,
    _WidgetBase,
    _TemplatedMixin,
    _Notification,
    Button,
    Model,
    Store,
    Loader,
    NodeRelationWidget,
    template
) {
    return declare([_WidgetBase, _TemplatedMixin, _Notification], {

        templateString: template,
        nodeData: {},
        type: null,
        formId: "",
        headline: "",
        modified: false,

        constructor: function(args) {
            declare.safeMixin(this, args);

            this.type = Model.getTypeFromOid(this.nodeData.oid);
            this.formId = "nodeForm_"+this.nodeData.oid;
            this.headline = Model.getDisplayValue(this.nodeData);
        },

        _setHeadlineAttr: function (val) {
            this.headlineNode.innerHTML = val;
        },

        postCreate: function() {
            this.inherited(arguments);

            // TODO: load input widgets referenced in attributes' input type
            new Loader("js/ui/data/widget/TextBox").then(lang.hitch(this, function(TextBox) {

                // add attribute widgets
                var attributes = this.type.getAttributes('DATATYPE_ATTRIBUTE');
                for (var i=0, count=attributes.length; i<count; i++) {
                    var attribute = attributes[i];
                    var attributeWidget = new TextBox({
                        nodeData: this.nodeData,
                        attribute: attribute
                    });
                    this.own(attributeWidget.on('change', lang.hitch(this, function(value) {
                        this.setModified(true);
                    })));
                    var nodeToAppend = (attribute.isEditable) ? this.fieldsNodeLeft : this.fieldsNodeRight;
                    nodeToAppend.appendChild(attributeWidget.domNode);
                }

                // add relation widgets
                var relations = this.type.getRelations();
                for (var i=0, count=relations.length; i<count; i++) {
                    var relation = relations[i];
                    var relationWidget = new NodeRelationWidget({
                        node: this.nodeData,
                        relation: relation
                    });
                    this.relationsNode.appendChild(relationWidget.domNode);
                }
            }));
        },

        setModified: function(modified) {
            this.modified = modified;
        },

        _save: function(e) {
            // prevent the page from navigating after submit
            e.preventDefault();

            if (this.modified) {
                // update node from form data
                var data = domForm.toObject(this.formId);
                var store = Store.getStore(this.type.typeName, 'en');
                data = lang.mixin(lang.clone(this.nodeData), data);

                query(".btn.save").button("loading");
                this.hideNotification();

                store.put(data, {overwrite: true}).then(lang.hitch(this, function(response) {
                    // callback completes
                    query(".btn.save").button("reset");
                    if (response.errorMessage) {
                        // error
                        this.showNotification({
                            type: "error",
                            message: response.errorMessage || "Backend error"
                        });
                    }
                    else {
                        // success
                        for (var key in response) {
                            if (this.nodeData.hasOwnProperty(key)) {
                                if (this.nodeData[key] !== response[key]) {
                                    // notify listeners
                                    this.nodeData.set(key, response[key]);
                                }
                            }
                        }
                        store.updateCache(this.nodeData);
                        this.showNotification({
                            type: "ok",
                            message: "'"+Model.getDisplayValue(this.nodeData)+"' was successfully updated",
                            fadeOut: true
                        });
                        this.set("headline", Model.getDisplayValue(this.nodeData));
                        this.setModified(false);
                    }
                }), lang.hitch(this, function(error) {
                    // error
                    query(".btn.save").button("reset");
                    this.showNotification({
                        type: "error",
                        message: error.response.data.errorMessage || "Backend error"
                    });
                }));
            }
        }
    });
});