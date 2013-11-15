define( [
    "dojo/_base/declare",
    "./_BrowserControl",
    "../../../_include/_HelpMixin"
],
function(
    declare,
    _BrowserControl,
    HelpIcon
) {
    return declare([_BrowserControl, HelpIcon], {

        browserUrl: appConfig.pathPrefix+'/media'
    });
});