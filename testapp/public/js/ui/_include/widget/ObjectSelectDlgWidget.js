define([
    "dojo/_base/declare",
    "dojo/dom-construct",
    "./PopupDlgWidget",
    "./GridWidget",
    "../../../persistence/Store"
], function (
    declare,
    domConstruct,
    PopupDlg,
    GridWidget,
    Store
) {
    /**
     * Modal link dialog. Usage:
     * @code
     * ObjectSelectDlg.showConfirm({
     *      type: "Author",
     *      title: "Choose Objects",
     *      content: "Select objects, you want to link to '"+Model.getDisplayValue(data)+"'",
     *      okCallback: function() {
     *          // will be called when OK button is clicked
     *          var deferred = new Deferred();
     *          // do something
     *          return deferred;
     *      },
     *      cancelCallback: function() {
     *          // will be called when Cancel button is clicked
     *          ....
     *      }
     * });
     * @endcode
     */
    var ObjectSelectDlg = declare([PopupDlg], {

        type: "",
        grid: null,

        postCreate: function () {
            this.inherited(arguments);

            var gridNode = domConstruct.create("div", null, this.contentNode.parentNode);
            this.grid = new GridWidget({
                type: this.type,
                store: Store.getStore(this.type, 'en'),
                actions: [],
                height: 198
            }, gridNode);
        },

        getSelectedOids: function () {
            return this.grid.getSelectedOids();
        }
    });

    return PopupDlg.extend(ObjectSelectDlg);
});