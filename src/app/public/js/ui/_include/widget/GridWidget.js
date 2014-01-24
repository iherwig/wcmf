define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dgrid/OnDemandGrid",
    "dgrid/Selection",
    "dgrid/Keyboard",
    "dgrid/extensions/DnD",
    "dgrid/extensions/ColumnHider",
    "dgrid/extensions/ColumnResizer",
    "dgrid/extensions/DijitRegistry",
    "dgrid/editor",
    "dojo/dom-attr",
    "dojo/dom-construct",
    "dojo/query",
    "dojo/NodeList-traverse",
    "dojo/window",
    "dojo/topic",
    "dojo/on",
    "dojo/has",
    "../../../model/meta/Model",
    "../../../locale/Dictionary",
    "../../data/input/Factory",
    "../../data/display/Renderer",
    "dojo/text!./template/GridWidget.html"
], function (
    declare,
    lang,
    _WidgetBase,
    _TemplatedMixin,
    OnDemandGrid,
    Selection,
    Keyboard,
    DnD,
    ColumnHider,
    ColumnResizer,
    DijitRegistry,
    editor,
    domAttr,
    domConstruct,
    query,
    traverse,
    win,
    topic,
    on,
    has,
    Model,
    Dict,
    ControlFactory,
    Renderer,
    template
) {
    return declare([_WidgetBase, _TemplatedMixin], {

        type: null,
        store: null,
        actions: [],
        enabledFeatures: [], // array of strings matching items in optionalFeatures
        canEdit: true,

        actionsByName: {},
        templateString: lang.replace(template, Dict.tplTranslate),
        gridWidget: null,

        defaultFeatures: [Selection, Keyboard, ColumnHider, ColumnResizer, DijitRegistry],
        optionalFeatures: [DnD],

        constructor: function (params) {
            if (params && params.actions) {
                params.actionsByName = {};
                for (var i=0,count=params.actions.length; i<count; i++) {
                    var action = params.actions[i];
                    params.actionsByName[action.name] = action;
                }
            }
            declare.safeMixin(this, params);
        },

        postCreate: function () {
            this.inherited(arguments);

            ControlFactory.loadControlClasses(this.type).then(lang.hitch(this, function(controls) {

                this.gridWidget = this.buildGrid(controls);
                this.gridWidget.set("store", this.store);
                this.own(
                    on(window, "resize", lang.hitch(this, this.onResize)),
                    on(this.gridWidget, "click", lang.hitch(this, function(e) {
                        // process grid clicks
                        var links = query(e.target).closest("a");
                        if (links.length > 0) {
                          var actionName = domAttr.get(links[0], "data-action");
                          var action = this.actionsByName[actionName];
                          if (action) {
                              // cell action
                              e.preventDefault();

                              var columnNode = e.target.parentNode;
                              var row = this.gridWidget.row(columnNode);
                              action.execute(e, row.data);
                          }
                        }
                    })),
                    topic.subscribe("store-datachange", lang.hitch(this, function(data) {
                        var typeName = Model.getFullyQualifiedTypeName(Model.getTypeNameFromOid(data.oid));
                        if (data.store.target === this.store.target ||
                                this.store.typeName === typeName) {
                            this.gridWidget.refresh({
                                keepScrollPosition: true
                            });
                        }
                    })),
                    topic.subscribe("store-error", lang.hitch(this, function(error) {
                        topic.publish('ui/_include/widget/GridWidget/error', error);
                    })),
                    topic.subscribe("/dnd/drop", lang.hitch(this, function(source, nodes, copy, target) {
                        var targetRow;
                        var anchor = source._targetAnchor;
                        if (anchor) { // (falsy if drop occurred in empty space after rows)
                            targetRow = target.before ? anchor.previousSibling : anchor.nextSibling;
                        }
                        nodes.forEach(function(node) {
                            domConstruct.place(node, targetRow, targetRow ? "before" : "after");
                        });
//                        this.gridWidget.refresh({
//                            keepScrollPosition: true
//                        });
                    }))
                );
                this.onResize();
            }));
        },

        buildGrid: function (controls) {
            var columns = [{
                label: 'oid',
                field: 'oid',
                hidden: true,
                unhidable: true,
                sortable: true
            }];

            var typeClass = Model.getType(this.type);
            var displayValues = typeClass.displayValues;
            for (var i=0, count=displayValues.length; i<count; i++) {
                var curValue = displayValues[i];
                var curAttributeDef = typeClass.getAttribute(curValue);
                var controlClass = controls[curAttributeDef.inputType];
                columns.push(editor({
                    label: Dict.translate(curValue),
                    field: curValue,
                    editor: controlClass,
                    editorArgs: {
                        attribute: curAttributeDef,
                        style: 'height:20px; padding:0;'
                    },
                    editOn: "click",
                    canEdit: this.canEdit ? lang.hitch(curAttributeDef, function(obj, value) {
                        return this.isEditable;
                    }) : function(obj, value) {return false; },
                    autoSave: true,
                    sortable: true,
                    formatter: lang.hitch(curAttributeDef, function(value) {
                        return Renderer.render(value, this.displayType);
                    })
                }));
            }

            // add actions column
            if (this.actions.length > 0) {
                columns.push({
                    label: " ",
                    field: "actions-"+this.actions.length,
                    unhidable: true,
                    sortable: false,
                    resizable: false,
                    formatter: lang.hitch(this, function(data, obj) {
                        var html = '<div>';
                        for (var name in this.actionsByName) {
                            var action = this.actionsByName[name];
                            html += '<a class="btn" href="#" data-action="'+name+'"><i class="'+action.iconClass+'"></i></a>';
                        }
                        html += '</div>';
                        return html;
                    })
                });
            }

            // select features
            var features = this.defaultFeatures;
            for (var idx in this.enabledFeatures) {
                var featureFct = eval(this.enabledFeatures[idx]);
                if (featureFct instanceof Function) {
                    features.push(featureFct);
                }
            }

            // create widget
            var gridWidget = new (declare([OnDemandGrid].concat(features)))({
                getBeforePut: true,
                columns: columns,
                selectionMode: "extended",
                //query: { find: 'xx' },
                //queryOptions: { sort: [{ attribute: 'title', descending: false }] },
                loadingMessage: Dict.translate("Loading"),
                noDataMessage: Dict.translate("No data")
            }, this.gridNode);

            gridWidget.on("dgrid-error", function (evt) {
                topic.publish('ui/_include/widget/GridWidget/error', evt.error);
            });

            // click on title column header
            gridWidget.on(".dgrid-header .dgrid-column-title:click", lang.hitch(this, function (evt) {
                //console.dir(gridWidget.cell(evt))
            }));

            // click on title data cell
            gridWidget.on(".dgrid-row .dgrid-column-title:click", lang.hitch(this, function (evt) {
                //console.dir(gridWidget.row(evt));
            }));

            // row selected
            gridWidget.on("dgrid-select", lang.hitch(this, function (evt) {
                //console.dir(evt.gridWidget.selection);
            }));

            // row deselected
            gridWidget.on("dgrid-deselect", lang.hitch(this, function (evt) {
                //console.dir(evt.gridWidget.selection);
            }));

            gridWidget.on("dgrid-datachange", lang.hitch(this, function (evt) {
                //console.dir(evt);
            }));

            gridWidget.on("dgrid-refresh-complete", lang.hitch(this, function (evt) {
                gridWidget.resize();
            }));

            return gridWidget;
        },

        getSelectedOids: function() {
            var oids = [];
            for (var oid in this.gridWidget.selection) {
                if (this.gridWidget.selection[oid]) {
                    oids.push(oid);
                }
            }
            return oids;
        },

        refresh: function() {
            this.gridWidget.refresh({
                keepScrollPosition: true
            });
        },

        onResize: function() {
            // TODO: remove magic number
            var vs = win.getBox();
            var h = this.height ? this.height : vs.h-280;
            if (h >= 0) {
                domAttr.set(this.gridWidget.domNode, "style", {height: h+"px"});
            }
        }
    });
});