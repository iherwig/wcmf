define( [
    "dojo/_base/declare",
    "dojo/_base/lang",
    "../../../_include/widget/Button",
    "./_BrowserControl"
],
function(
    declare,
    lang,
    Button,
    _BrowserControl
) {
    return declare([_BrowserControl], {

        browserUrl: appConfig.pathPrefix+'/link',

        postCreate: function() {
            this.inherited(arguments);

            var testBtn = new Button({
                innerHTML: '<i class="icon-external-link"></i>',
                class: "btn-mini",
                onClick: lang.hitch(this, function() {
                    var url = this.getLinkUrl();
                    if (url) {
                        location.href = url;
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