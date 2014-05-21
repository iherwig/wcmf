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
     * new ObjectSelectDlg({
     *      type: "Author",
     *      title: "Choose Objects",
     *      message: "Select objects, you want to link to '"+Model.getTypeFromOid(data.oid).getDisplayValue(data)+"'",
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
     * }).show();
     * @endcode
     */
    return declare([PopupDlg], {

        type: "",
        grid: null,
        style: "width: 500px",

        postCreate: function () {
            this.inherited(arguments);

            var gridNode = domConstruct.create("div", null, this.content.contentNode.parentNode);
            this.grid = new GridWidget({
                type: this.type,
                store: Store.getStore(this.type, appConfig.defaultLanguage),
                actions: [],
                canEdit: false,
                height: 198
            }, gridNode);
        },

        getSelectedOids: function () {
            return this.grid.getSelectedOids();
        }
    });
});