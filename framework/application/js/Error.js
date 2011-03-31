/**
 * @class Error This class encapsulates functionality for error handling
 */
dojo.declare("wcmf.Error", null, {
});

/**
 * Show an error message.
 *
 * @param text
 *            The error message
 */
wcmf.Error.show = function(text) {
  dojo.style("error", "visibility", "visible");
  dojo.byId('errorMessage').innerHTML = text;
  wcmf.Error.toggler.show();
};

/**
 * Hide the error message.
 */
wcmf.Error.hide = function() {
  wcmf.Error.toggler.hide();
};


dojo.addOnLoad(function() {
  wcmf.Error.toggler = new dojo.fx.Toggler({
    node: "error",
    showDuration: 300,
    hideDuration: 1/*,
    showFunc: dojo.fx.wipeIn,
    hideFunc: dojo.fx.wipeOut*/
  });
  if (dojo.byId('errorMessage').innerHTML.length == 0) {
    dojo.style("error", "visibility", "hidden");
  }
  dojo.query('#errorMessage').onclick(function(e) {
    wcmf.Error.hide();
  });
});