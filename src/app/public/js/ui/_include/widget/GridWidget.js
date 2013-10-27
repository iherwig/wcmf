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
    "dojo/dom-attr",
    "dojo/query",
    "dojo/NodeList-traverse",
    "dojo/window",
    "dojo/topic",
    "dojo/on",
    "../../../model/meta/Model",
    "../../../locale/Dictionary",
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
    domAttr,
    query,
    traverse,
    win,
    topic,
    on,
    Model,
    Dict,
    Renderer,
    template
) {
    return declare([_WidgetBase, _TemplatedMixin], {

        type: null,
        store: null,
        actions: [],
        autoReload: true,
        enabledFeatures: [], // array of strings matching items in optionalFeatures

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
            this.gridWidget = this.buildGrid();
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
                        if (this.autoReload) {
                            this.gridWidget.refresh({
                                keepScrollPosition: true
                            });
                        }
                        this.needsRefresh = true;
                    }
                })),
                topic.subscribe("/dnd/drop", function(source, nodes, copy, target) {
                    // TODO: check if we really need a refresh
                    console.log("drop");
                })
            );
            this.onResize();
        },

        buildGrid: function () {
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
                columns.push({
                    label: Dict.translate(curValue),
                    field: curValue,
                    sortable: true,
                    formatter: lang.hitch(curAttributeDef, function(data, obj) {
                        return Renderer.render(data, this.displayType);
                    })
                });
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
                topic.publish('ui/_include/widget/GridWidget/unknown-error', {
                    notification: {
                        message: Dict.translate("Backend error"),
                        type: 'error'
                    }
                });
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

        postponeRefresh: function(deferred) {
            var oldAutoReload = this.autoReload;
            this.autoReload = false;
            deferred.then(lang.hitch(this, function() {
                this.refresh();
                this.autoReload = oldAutoReload;
            }));
        },

        onResize: function() {
            // TODO: remove magic number
            var vs = win.getBox();
            var h = this.height ? this.height : vs.h-222;
            if (h >= 0) {
                domAttr.set(this.gridWidget.domNode, "style", {height: h+"px"});
            }
        }
    });
});