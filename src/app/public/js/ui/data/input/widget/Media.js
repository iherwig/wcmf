define( [
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dijit/form/TextBox",
    "../../../_include/widget/Button",
    "../../../_include/widget/PopupDlgWidget",
    "./_BrowserControl",
    "../../../../locale/Dictionary"
],
function(
    declare,
    lang,
    TextBox,
    Button,
    PopupDlg,
    _BrowserControl,
    Dict
) {
    return declare([_BrowserControl], {

        browserUrl: appConfig.pathPrefix+'/media',

        postCreate: function() {
            this.inherited(arguments);

            // create embed button
            var codeTextBox = new TextBox({
                placeHolder: Dict.translate("Embed Code")
            });
            var embedBtn = new Button({
                innerHTML: '<i class="fa fa-external-link"></i>',
                "class": "btn-mini",
                onClick: lang.hitch(this, function() {
                    new PopupDlg({
                        title: Dict.translate("External source"),
                        message: "",
                        contentWidget: codeTextBox,
                        okCallback: lang.hitch(this, function() {
                            // extract src from iframe
                            var text = codeTextBox.get("value");
                            var re = new RegExp('^<iframe .*src="([^"]+)".*');
                            var matches = re.exec(text);
                            if (matches && matches.length > 1) {
                                var url = matches[1];
                                this.set("value", url);
                            }
                        })
                    }).show();
                })
            });
            this.addChild(embedBtn);
        }
    });
});