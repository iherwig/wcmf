define( [
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/dom-form",
    "dojo/query",
    "dojo/when",
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
    when,
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
                    var textBox = new TextBox({
                        nodeData: this.nodeData,
                        attribute: attribute
                    });
                    var nodeToAppend = (attribute.isEditable) ? this.fieldsNodeLeft : this.fieldsNodeRight;
                    nodeToAppend.appendChild(textBox.domNode);
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

        _save: function(e) {
            // prevent the page from navigating after submit
            e.preventDefault();

            var data = domForm.toObject(this.formId);
            var store = Store.getStore(this.type.typeName, 'en');

            // update node from form data
            var modified = false;
            for (var key in data) {
                if (this.nodeData.hasOwnProperty(key)) {
                    if (this.nodeData[key] !== data[key]) {
                        this.nodeData.set(key, data[key]);
                        modified = true;
                    }
                }
            }
            if (modified) {
                query(".btn").button("loading");
                this.hideNotification();
                store.put(this.nodeData, {overwrite: true}).then(lang.hitch(this, function(response) {
                    query(".btn").button("reset");
                    // success
                    if (response.errorMessage) {
                        // error
                        query(".btn").button("reset");
                        this.showNotification({
                            type: "error",
                            message: response.errorMessage || "Backend error"
                        });
                    }
                    else {
                        response.oid = this.nodeData.oid;
                        declare.safeMixin(this.nodeData, response);
                        store.updateCache(response);
                        this.showNotification({
                            type: "ok",
                            message: "'"+Model.getDisplayValue(this.nodeData)+"' was successfully updated",
                            fadeOut: true
                        });
                        this.set("headline", Model.getDisplayValue(this.nodeData));
                    }
                }), lang.hitch(this, function(error) {
                    // error
                    query(".btn").button("reset");
                    this.showNotification({
                        type: "error",
                        message: error.response.data.errorMessage || "Backend error"
                    });
                }));
            }
        }
    });
});