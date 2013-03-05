define([
    "dojo/_base/declare",
    "dojo",
    "dojo/fx"
], function(
    declare,
    dojo,
    fx
) {
    /**
     * @class Error This class encapsulates functionality for error handling
     */
    var Error = declare(null, {
    });

    /**
     * Show an error message.
     *
     * @param text The error message
     */
    Error.show = function(text) {
        dojo.style('error', 'visibility', 'visible');
        dojo.byId('error').innerHTML = text;
        fx.wipeIn({
            node: 'error',
            duration: 0
        }).play();
        Error.visible = true;
    };

    /**
     * Hide the error message.
     */
    Error.hide = function() {
        if (Error.visible) {
          fx.wipeOut({
              node: 'error',
              duration: 0
          }).play();
          Error.visible = false;
        }
    };

    return Error;
});
