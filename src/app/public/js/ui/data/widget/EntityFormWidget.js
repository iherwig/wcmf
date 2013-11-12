define( [
    "require",
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/topic",
    "dojo/dom-class",
    "dojo/dom-construct",
    "dojo/query",
    "dijit/registry",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dijit/_WidgetsInTemplateMixin",
    "dijit/form/DropDownButton",
    "dijit/Menu",
    "dijit/MenuItem",
    "dijit/Tooltip",
    "../../_include/FormLayout",
    "../../_include/_NotificationMixin",
    "../../_include/widget/Button",
    "../../../model/meta/Model",
    "../../../persistence/Store",
    "../../../persistence/RelationStore",
    "../../../action/Delete",
    "../../../locale/Dictionary",
    "../input/Factory",
    "./EntityRelationWidget",
    "dojo/text!./template/EntityFormWidget.html"
],
function(
    require,
    declare,
    lang,
    topic,
    domClass,
    domConstruct,
    query,
    registry,
    _WidgetBase,
    _TemplatedMixin,
    _WidgetsInTemplateMixin,
    DropDownButton,
    Menu,
    MenuItem,
    Tooltip,
    FormLayout,
    _Notification,
    Button,
    Model,
    Store,
    RelationStore,
    Delete,
    Dict,
    ControlFactory,
    EntityRelationWidget,
    template
) {
    return declare([_WidgetBase, _TemplatedMixin, _WidgetsInTemplateMixin, _Notification], {

        templateString: lang.replace(template, Dict.tplTranslate),
        contextRequire: require,

        entity: {}, // entity to edit
        sourceOid: null, // object id of the source object of a relation
                         // (ignored if isNew == false)
        relation: null, // the relation in which the object should be created
                        // related to sourceOid (ignored if isNew == false)
        page: null,

        type: null,
        formId: "",
        fieldContainerId: "",
        headline: "",
        isNew: false,
        modified: false,

        language: appConfig.defaultLanguage,
        isTranslation: false,
        original: null, // untranslated entity

        onCreated: null, // function to be called after the widget is created

        attributeWidgets: [],

        constructor: function(args) {
            declare.safeMixin(this, args);

            this.type = Model.getTypeNameFromOid(this.entity.oid);
            this.formId = "entityForm_"+this.entity.oid;
            this.fieldContainerId = "fieldContainer_"+this.entity.oid;
            this.headline = Model.getDisplayValue(this.entity);
            this.isNew = Model.isDummyOid(this.entity.oid);
            this.isTranslation = this.language !== appConfig.defaultLanguage;

            this.languageName = appConfig.languages[this.language];
        },

        _setHeadlineAttr: function (val) {
            this.headlineNode.innerHTML = val;
        },

        postCreate: function() {
            this.inherited(arguments);

            // load input widgets referenced in attributes' input type
            ControlFactory.loadControlClasses(this.type).then(lang.hitch(this, function(controls) {
                var layoutWidget = registry.byNode(this.fieldsNode.domNode);

                // add attribute widgets
                this.attributeWidgets = [];
                var attributes = this.getAttributes();
                for (var i=0, count=attributes.length; i<count; i++) {
                    var attribute = attributes[i];
                    var controlClass = controls[attribute.inputType];
                    var attributeWidget = new controlClass({
                        entity: this.entity,
                        attribute: attribute,
                        original: this.original
                    });
                    this.own(attributeWidget.on('change', lang.hitch(this, function(widget) {
                        var widgetValue = widget.get("value");
                        var entityValue = this.entity.get(widget.attribute.name) || "";
                        if (widgetValue !== entityValue) {
                            this.setModified(true);
                        }
                    }, attributeWidget)));
                    attributeWidget.startup();
                    layoutWidget.addChild(attributeWidget);
                    new Tooltip({
                        connectId: [attributeWidget.id],
                        label: Dict.translate(attribute.description)
                    });
                    this.attributeWidgets.push(attributeWidget);
                }
                layoutWidget.startup();
                if (this.onCreated instanceof Function) {
                    this.onCreated(this);
                }
            }), lang.hitch(this, function(error) {
                // error
                this.showNotification({
                    type: "error",
                    message: error.message || Dict.translate("Backend error")
                });
            }));

            // add relation widgets
            if (!this.isNew) {
                var relations = this.getRelations();
                for (var i=0, count=relations.length; i<count; i++) {
                    var relation = relations[i];
                    var relationWidget = new EntityRelationWidget({
                        entity: this.entity,
                        relation: relation,
                        page: this.page
                    });
                    this.relationsNode.appendChild(relationWidget.domNode);
                }
            }

            // set button states
            this.setBtnState("save", false);
            if (this.isNew) {
                this.setBtnState("delete", false);
            }

            if (!this.isNew) {
                this.buildLanguageMenu();
            }
        },

        /**
         * Get the type's attributes to display in the widget
         * @returns Array
         */
        getAttributes: function() {
            var typeClass = Model.getType(this.type);
            return typeClass.getAttributes('DATATYPE_ATTRIBUTE');
        },

        /**
         * Get the type's relations to display in the widget
         * @returns Array
         */
        getRelations: function() {
            var typeClass = Model.getType(this.type);
            return typeClass.getRelations();
        },

        buildLanguageMenu: function() {
            if (!this.languageMenuPopupNode) {
                return;
            }
            var languageCount = 0;
            var menu = registry.byId(this.languageMenuPopupNode.get("id"));
            var form = this;
            for (var langKey in appConfig.languages) {
                var menuItem = new MenuItem({
                    label: appConfig.languages[langKey],
                    langKey: langKey,
                    onClick: function() {
                        var route = form.page.getRoute("entity");
                        var queryParams = this.langKey !== appConfig.defaultLanguage ? {lang: this.langKey} : undefined;
                        var url = route.assemble({
                            type: Model.getSimpleTypeName(form.type),
                            id: Model.getIdFromOid(form.entity.oid)
                        }, queryParams);
                        form.page.pushConfirmed(url);
                    }
                });
                if (langKey === this.language) {
                    menuItem.set("disabled", true);
                }
                menu.addChild(menuItem);
                languageCount++;
            }
            if (languageCount <= 1) {
                // destroy menu
                domConstruct.destroy(this.languageMenuNode);
            }
            else {
                // show menu
                query(this.languageMenuNode).style("display", "block");
            }
        },

        setBtnState: function(btnName, isEnabled) {
            var btn = this[btnName+"Btn"];
            if (btn) {
                btn.set('disabled', !isEnabled);
            }
        },

        setModified: function(modified) {
            this.modified = modified;

            var state = modified === true ? "dirty" : "clean";
            this.entity.setState(state);
            this.setBtnState("save", modified);
        },

        isRelatedObject: function() {
            return (this.sourceOid && this.relation);
        },

        _save: function(e) {
            // prevent the page from navigating after submit
            e.preventDefault();

            if (this.modified) {
                // update entity from form data
                var data = {};
                for (var i=0, c=this.attributeWidgets.length; i<c; i++) {
                    var widget = this.attributeWidgets[i];
                    data[widget.get("name")] = widget.get("value");
                }
                data = lang.mixin(lang.clone(this.entity), data);

                this.saveBtn.setProcessing();
                this.hideNotification();

                var store = null;
                if (this.isRelatedObject()) {
                    store = RelationStore.getStore(this.sourceOid, this.relation);
                }
                else {
                    store = Store.getStore(this.type, this.language);
                }

                var storeMethod = this.isNew ? "add" : "put";
                store[storeMethod](data, {overwrite: !this.isNew}).then(lang.hitch(this, function(response) {
                    // callback completes
                    this.saveBtn.reset();
                    if (response.errorMessage) {
                        // error
                        this.showNotification({
                            type: "error",
                            message: response.errorMessage || Dict.translate("Backend error")
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
                        var message = this.isNew ? Dict.translate("'%0%' was successfully created", [Model.getDisplayValue(this.entity)]) :
                                Dict.translate("'%0%' was successfully updated", [Model.getDisplayValue(this.entity)]);
                        this.showNotification({
                            type: "ok",
                            message: message,
                            fadeOut: true,
                            onHide: lang.hitch(this, function() {
                                this.setBtnState("save", false);
                                if (this.isNew) {
                                    this.isNew = false;

                                    if (this.isRelatedObject()) {
                                        // close own tab
                                        topic.publish("tab-closed", {
                                            oid: Model.createDummyOid(this.type)
                                        });
                                        this.destroyRecursive();
                                    }
                                    else {
                                        // update current tab
                                        topic.publish("tab-closed", {
                                            oid: Model.createDummyOid(this.type),
                                            nextOid: this.entity.oid
                                        });
                                    }
                                }
                            })
                        });
                        this.set("headline", Model.getDisplayValue(this.entity));
                        this.setModified(false);
                    }
                }), lang.hitch(this, function(error) {
                    // error
                    this.saveBtn.reset();
                    this.showNotification({
                        type: "error",
                        message: error.message || error.response.data.errorMessage || Dict.translate("Backend error")
                    });
                }));
            }
        },

        _delete: function(e) {
            // prevent the page from navigating after submit
            e.preventDefault();

            if (this.isNew) {
                return;
            }

            new Delete({
                page: this.page,
                init: lang.hitch(this, function(data) {
                    this.hideNotification();
                }),
                callback: lang.hitch(this, function(data, result) {
                    // success
                    // notify tab panel to close tab
                    topic.publish("tab-closed", {
                        oid: this.entity.oid
                    });
                    this.destroyRecursive();
                }),
                errback: lang.hitch(this, function(data, result) {
                    // error
                    this.showNotification({
                        type: "error",
                        message: Dict.translate("Backend error")
                    });
                })
            }).execute(e, this.entity);
        }
    });
});