define( [
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/topic",
    "dojo/dom-form",
    "dojo/query",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "../../_include/_NotificationMixin",
    "bootstrap/Button",
    "../../../model/meta/Model",
    "../../../persistence/Store",
    "../../../action/Edit",
    "../../../Loader",
    "./EntityRelationWidget",
    "dojo/text!./template/EntityFormWidget.html"
],
function(
    declare,
    lang,
    topic,
    domForm,
    query,
    _WidgetBase,
    _TemplatedMixin,
    _Notification,
    Button,
    Model,
    Store,
    Edit,
    Loader,
    EntityRelationWidget,
    template
) {
    return declare([_WidgetBase, _TemplatedMixin, _Notification], {

        templateString: template,
        entity: {},
        type: null,
        router: null,
        formId: "",
        headline: "",
        isNew: false,
        modified: false,

        constructor: function(args) {
            declare.safeMixin(this, args);

            this.type = Model.getTypeNameFromOid(this.entity.oid);
            this.formId = "entityForm_"+this.entity.oid;
            this.headline = Model.getDisplayValue(this.entity);
            this.isNew = Model.isDummyOid(this.entity.oid);
        },

        _setHeadlineAttr: function (val) {
            this.headlineNode.innerHTML = val;
        },

        postCreate: function() {
            this.inherited(arguments);

            // TODO: load input widgets referenced in attributes' input type
            new Loader("js/ui/data/widget/TextBox").then(lang.hitch(this, function(TextBox) {
                var typeClass = Model.getType(this.type);

                // add attribute widgets
                var attributes = typeClass.getAttributes('DATATYPE_ATTRIBUTE');
                for (var i=0, count=attributes.length; i<count; i++) {
                    var attribute = attributes[i];
                    var attributeWidget = new TextBox({
                        entity: this.entity,
                        attribute: attribute
                    });
                    this.own(attributeWidget.on('change', lang.hitch(this, function(widget) {
                        var widgetValue = widget.get("value");
                        var entityValue = this.entity.get(widget.attribute.name) || "";
                        if (widgetValue !== entityValue) {
                            this.setModified(true);
                        }
                    }, attributeWidget)));
                    var nodeToAppend = (attribute.isEditable) ? this.fieldsNodeLeft : this.fieldsNodeRight;
                    nodeToAppend.appendChild(attributeWidget.domNode);
                }

                // add relation widgets
                var relations = typeClass.getRelations();
                for (var i=0, count=relations.length; i<count; i++) {
                    var relation = relations[i];
                    var relationWidget = new EntityRelationWidget({
                        entity: this.entity,
                        relation: relation
                    });
                    this.relationsNode.appendChild(relationWidget.domNode);
                }
            }));
        },

        setModified: function(modified) {
            this.modified = modified;

            var state = modified === true ? "dirty" : "clean";
            this.entity.setState(state);
        },

        _save: function(e) {
            // prevent the page from navigating after submit
            e.preventDefault();

            if (this.modified) {
                // update entity from form data
                var data = domForm.toObject(this.formId);
                var store = Store.getStore(this.type, 'en');
                data = lang.mixin(lang.clone(this.entity), data);

                query(".btn.save").button("loading");
                this.hideNotification();

                var storeMethod = this.isNew ? "add" : "put";
                store[storeMethod](data, {overwrite: !this.isNew}).then(lang.hitch(this, function(response) {
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
                        var typeClass = Model.getType(this.type);
                        var attributes = typeClass.getAttributes();
                        for (var i=0, count=attributes.length; i<count; i++) {
                            var attributeName = attributes[i].name;
                            if (this.entity[attributeName] !== response[attributeName]) {
                                // notify listeners
                                this.entity.set(attributeName, response[attributeName]);
                            }
                        }
                        this.entity.set('oid', response.oid);
                        this.showNotification({
                            type: "ok",
                            message: "'"+Model.getDisplayValue(this.entity)+"' was successfully " + (this.isNew ? "created" : "updated"),
                            fadeOut: true,
                            onHide: lang.hitch(this, function() {
                                if (this.isNew) {
                                    this.isNew = false;
                                    // destroy page before navigage in order to notify tab panel
                                    topic.publish("tab-closed", Model.createDummyOid(this.type));
                                    // navigate to edit page
                                    new Edit({
                                        router: this.router
                                    }).execute(this.entity);
                                }
                            })
                        });
                        this.set("headline", Model.getDisplayValue(this.entity));
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
        },

        _reset: function(e) {
            // prevent the page from navigating after submit
            e.preventDefault();

            if (this.modified) {
                this.entity.reset();
            }
        }
    });
});