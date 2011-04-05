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
  dojo.fadeIn({
    node: "error",
    duration: 300,
    end: 0.75
  }).play();
};

/**
 * Hide the error message.
 */
wcmf.Error.hide = function() {
  dojo.fadeOut({
    node: "error",
    duration: 1
  }).play();
};


dojo.addOnLoad(function() {
  if (dojo.byId('errorMessage').innerHTML.length == 0) {
    dojo.style("error", "visibility", "hidden");
  }
  dojo.query('#error').onclick(function(e) {
    wcmf.Error.hide();
  });
});
