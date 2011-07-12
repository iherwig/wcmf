dojo.provide("wcmf.ui.Form");

/**
 * @class Form This class provides methods for form field handling
 */
dojo.declare("wcmf.ui.Form", null, {
});

/**
 * Determine the item's attribute name from the given form field name
 * @param fieldName The name of the form field
 * @return The attribute name
 */
wcmf.ui.Form.getAttributeNameFromFieldName = function(fieldName) {
  var matches = fieldName.match(/^value-[^-]+?-(.+)-[^-]*$/);
  if (matches && matches.length > 0) {
    return matches[1];
  }
  return '';
};

/**
 * Determine the item's object id from the given form field name
 * @param fieldName The name of the form field
 * @return The attribute name
 */
wcmf.ui.Form.getOidNameFromFieldName = function(fieldName) {
  var matches = fieldName.match(/^value-.+-([^-]*)$/);
  if (matches && matches.length > 0) {
    return matches[1];
  }
  return '';
};
