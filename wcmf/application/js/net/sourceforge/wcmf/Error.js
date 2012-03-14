define(["dojo/_base/declare", "dojo/fx"
], function(declare, fx) {

/**
 * @class Error This class encapsulates functionality for error handling
 */
var Error = declare("wcmf.Error", null, {
});

/**
 * Show an error message.
 *
 * @param text
 *            The error message
 */
Error.show = function(text) {
  dojo.style("error", "visibility", "visible");
  dojo.byId('errorMessage').innerHTML = text;
  fx.wipeIn({
    node: "error",
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
        node: "error",
        duration: 0
    }).play();
    Error.visible = false;
  }
};

return Error;
});

dojo.addOnLoad(function() {
  if (dojo.byId('errorMessage').innerHTML.length == 0) {
    dojo.style("error", "visibility", "hidden");
  }
  dojo.query('#error').onclick(function(e) {
    wcmf.Error.hide();
  });
});
