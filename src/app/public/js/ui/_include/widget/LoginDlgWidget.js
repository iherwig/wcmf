define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/request",
    "dojo/dom-form",
    "dijit/form/TextBox",
    "../../../User",
    "../../../locale/Dictionary",
    "./PopupDlgWidget",
    "dojo/text!./template/LoginDlgWidget.html"
], function (
    declare,
    lang,
    request,
    domForm,
    TextBox,
    User,
    Dict,
    PopupDlg,
    template
) {
    /**
     * Modal login dialog. Usage:
     * @code
     * new LoginDlg({
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
    var LoginDlg = declare([PopupDlg], {

        style: "width: 400px",
        title: '<i class="fa fa-sign-in"></i> '+Dict.translate("Sign in"),
        okCallback: lang.hitch(this, function(dlg) {
            var data = domForm.toObject("loginForm");
            data.action = "login";

            return request.post(appConfig.backendUrl, {
                data: data,
                headers: {
                    Accept: "application/json"
                },
                handleAs: 'json'

            }).then(lang.hitch(this, function(response) {
                // success
                User.create(data.user, response.roles);
            }));
        }),

        /**
         * Provide custom template
         */
        getTemplate: function() {
          return template;
        },

        /**
         * Make sure there is only one instance
         */
        show: function() {
            if (!LoginDlg.isShowing) {
                LoginDlg.isShowing = true;
                this.inherited(arguments);
            }
        },

        /**
         * Reset showing flag
         */
        hide: function() {
            LoginDlg.isShowing = false;
            this.inherited(arguments);
        }
    });

    LoginDlg.isShowing = false;

    return LoginDlg;
});