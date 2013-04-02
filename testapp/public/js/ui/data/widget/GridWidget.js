/*jshint strict:false */

define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dojomat/_StateAware",
    "dgrid/OnDemandGrid",
    "dgrid/Selection",
    "dgrid/editor",
    "dojo/store/Observable",
    "dojo/dom-construct",
    "dojo/dom-style",
    "dojo/dom-attr",
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
    editor,
    Observable,
    domConstruct,
    domStyle,
    domAttr,
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
        templateString: template,
        gridWidget: null,

        constructor: function (params) {
            this.request = params.request;
            this.router = params.router;
            this.store = params.store;
            this.type = params.type;
        },

        postCreate: function () {
            this.inherited(arguments);
            this.store = Observable(this.store);
            this.gridWidget = this.buildGrid();
            this.gridWidget.set("store", this.store); // set store and run query
            this.own(
                on(window, "resize", lang.hitch(this, this.onResize))
            );
            this.onResize();
        },

        startup: function () {
            this.inherited(arguments);
        },

        buildGrid: function () {
            var columns = {
                    selector: {
                        label: ' ',
                        sortable: false
                    }
                };

            var displayValues = this.type.displayValues;
            for (var i=0, count=displayValues.length; i<count; i++) {
                var curValue = displayValues[i];
                columns[curValue] = {
                    label: curValue,
                    field: curValue,
                    sortable: true
                };
            }

            var gridWidget = new (declare([OnDemandGrid, Selection]))({
                    getBeforePut: true,
                    columns: columns,
                    selectionMode: 'none', // we'll do programmatic selection with a checkbox
                    //query: { find: 'xx' },
                    queryOptions: { sort: [{ attribute: 'title', descending: false }] },
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