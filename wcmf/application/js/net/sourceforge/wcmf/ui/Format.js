dojo.provide("wcmf.ui.Format");

/**
 * @class Format
 *
 * Format methods are to be used to display object attributes in
 * a formatted way.
 */
dojo.declare("wcmf.ui.Format", null, {
});

/**
 * Display the given value as text
 */
wcmf.ui.Format.text = function(value) {
  return value;
};

/**
 * Display the given value as image
 */
wcmf.ui.Format.image = function(value) {
  return '<img src="'+value+'" height="20">';
};
