/*jshint strict:false */

define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dojomat/_StateAware",
    "dgrid/OnDemandGrid",
    "dgrid/Selection",
    "dgrid/Keyboard",
    "dgrid/extensions/ColumnHider",
    "dgrid/editor",
    "dojo/store/Observable",
    "dojo/dom-construct",
    "dojo/dom-style",
    "dojo/dom-attr",
    "dojo/dom-class",
    "dojo/window",
    "dojo/aspect",
    "dojo/topic",
    "dojo/on",
    "dojo/when",
    "dojo/promise/all",
    "dojo/text!./template/GridWidget.html"
], function (
    declare,
    lang,
    _WidgetBase,
    _TemplatedMixin,
    _StateAware,
    OnDemandGrid,
    Selection,
    Keyboard,
    ColumnHider,
    editor,
    Observable,
    domConstruct,
    domStyle,
    domAttr,
    domClass,
    win,
    aspect,
    topic,
    on,
    when,
    all,
    template
) {
    return declare([_WidgetBase, _TemplatedMixin, _StateAware], {

        router: null,
        store: null,
        type: null,
        actions: [],
        templateString: template,
        gridWidget: null,

        constructor: function (params) {
            this.request = params.request;
            this.router = params.router;
            this.store = params.store;
            this.type = params.type;
            this.actions = params.actions;
        },

        postCreate: function () {
            this.inherited(arguments);
            this.store = Observable(this.store);
            this.gridWidget = this.buildGrid();
            this.gridWidget.set("store", this.store); // set store and run query
            this.own(
                on(window, "resize", lang.hitch(this, this.onResize)),
                on(this.gridWidget, "click", lang.hitch(this.gridWidget, function(event) {
                    if (event.target) {
                      var columnNode = event.target.parentNode;
                      if (domClass.contains(columnNode, "field-action")) {
                          var row = this.row(columnNode);
                          var column = this.column(columnNode);
                          if (column && column.action) {
                              column.action.call(column, row.data);
                          }
                      }
                    }
                }))
            );
            this.onResize();
        },

        startup: function () {
            this.inherited(arguments);
        },

        buildGrid: function () {
            var columns = [{
                label: 'oid',
                field: 'oid',
                hidden: true,
                sortable: true
            }];

            var displayValues = this.type.displayValues;
            for (var i=0, count=displayValues.length; i<count; i++) {
                var curValue = displayValues[i];
                columns.push({
                    label: curValue,
                    field: curValue,
                    sortable: true
                });
            }

            // add cells for actions
            for(var i=0, count=this.actions.length; i<count; i++) {
                columns.push({
                    label: " ",
                    field: "action",
                    formatter: lang.hitch(this.actions[i], function(data, obj) {
                        return '<span class="'+this.iconClass+'"></span>';
                    }),
                    action: lang.hitch(this.actions[i], function(data) {
                        this.execute.call(this, data);
                    })
                });
            }

            var gridWidget = new (declare([OnDemandGrid, Selection, Keyboard, ColumnHider]))({
                    getBeforePut: true,
                    columns: columns,
                    selectionMode: "extended",
                    //query: { find: 'xx' },
                    //queryOptions: { sort: [{ attribute: 'title', descending: false }] },
                    loadingMessage: "Loading",
                    noDataMessage: "No data"
                }, this.gridNode),
                countSelectedItems = function () {
                    var count = 0, i;

                    for (i in gridWidget.selection) {
                        if (gridWidget.selection.hasOwnProperty(i)) {
                            count = count + 1;
                        }
                    }

                    return count;
                }
            ;

            if (this.request.getQueryParam('find')) {
                gridWidget.set('query', {
                    title: this.request.getQueryParam('find')
                });
            }

            gridWidget.on("dgrid-error", function (evt) {
                topic.publish('ui/data/widget/GridWidget/unknown-error', {
                    notification: {
                        message: "Unknown Error",
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
            }));

            //var loadHandle = aspect.after(gridWidget, '_trackError', lang.hitch(this, function (promiseOrResult) {
            var loadHandle = aspect.after(this.store, 'query', lang.hitch(this, function (promiseOrResult) {
                when(
                    promiseOrResult,
                    lang.hitch(this, function (data) {
                        if (data.length) {
                            loadHandle.remove(); // only run once and remove when data has loaded
                        }
                    })
                );
                return promiseOrResult;
            }));

            return gridWidget;
        },

        onResize: function() {
            var vs = win.getBox();
            var h = vs.h-185;
            if (h >= 0){
                domAttr.set(this.gridWidget.domNode, "style", {height: h+"px"});
            }
        }
    });
});