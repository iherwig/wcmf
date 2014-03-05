define( [
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/on",
    "../../../_include/widget/Button",
    "./_BrowserControl"
],
function(
    declare,
    lang,
    on,
    Button,
    _BrowserControl
) {
    return declare([_BrowserControl], {

        browserUrl: appConfig.pathPrefix+'/link',

        postCreate: function() {
            this.inherited(arguments);

            var testBtn = new Button({
                innerHTML: '<i class="fa fa-external-link"></i>',
                "class": "btn btn-mini"
            });
            this.own(
                on(testBtn, "onClick", lang.hitch(this, function() {
                    var url = this.getLinkUrl();
                    if (url) {
                        location.href = url;
                    }
                }))
            );
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