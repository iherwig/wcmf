define( [
    "dojo/_base/declare",
    "dojo/_base/lang",
    "../../../_include/widget/Button",
    "../../../_include/widget/ConfirmDlgWidget",
    "../../../../persistence/Entity",
    "../../../../locale/Dictionary",
    "./_BrowserControl"
],
function(
    declare,
    lang,
    Button,
    ConfirmDlg,
    Entity,
    Dict,
    _BrowserControl
) {
    return declare([_BrowserControl], {

        browserUrl: appConfig.pathPrefix+'/link',

        postCreate: function() {
            this.inherited(arguments);

            var testBtn = new Button({
                innerHTML: '<i class="fa fa-external-link"></i>',
                "class": "btn-mini",
                onClick: lang.hitch(this, function() {
                    var url = this.getLinkUrl();
                    if (url) {
                        if (this.isDirty()) {
                            new ConfirmDlg({
                                title: Dict.translate("Confirm Leave Page"),
                                message: Dict.translate("'%0%' has unsaved changes. Leaving the page will discard these. Do you want to proceed?",
                                    [Entity.getDisplayValue(this.entity)]),
                                okCallback: lang.hitch(this, function(dlg) {
                                    location.href = url;
                                })
                            }).show();
                        }
                        else {
                            location.href = url;
                        }
                    }
                })
            });
            this.addChild(testBtn);
        },

        getLinkUrl: function() {
            var val = this.get("value");
            if (val) {
                return val.replace(/^link:/, '');
            }
            return null;
        }
    });
});