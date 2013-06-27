define( [
    "dojo/_base/declare"
],
function(
    declare
) {
    var Dictionary = declare(null, {
    });

    Dictionary.translate = function(_, text) {
        var key = text.replace(/^translate:/, "");
        return window.texts === undefined ? key : window.texts[key];
    };

    return Dictionary.translate;
});