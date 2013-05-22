define([
    "dojo/_base/declare",
    "./PopupDlgWidget"
], function (
    declare,
    PopupDlg
) {
    /**
     * Modal confirmation dialog. Usage:
     * @code
     * new ConfirmDlg({
     *      title: "Confirm Object Deletion",
     *      content: "Do you really want to delete '"+Model.getDisplayValue(data)+"'?",
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

        _setContentAttr: function (val) {
            this.inherited(arguments, ['<i class="icon-question-sign icon-3x pull-left"></i> &nbsp;'+val]);
        }
    });
});