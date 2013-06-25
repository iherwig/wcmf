define( [
    "dojo/_base/declare",
    "./_BrowserControl"
],
function(
    declare,
    _BrowserControl
) {
    return declare([_BrowserControl], {

        browserUrl: appConfig.pathPrefix+'/link'
    });
});