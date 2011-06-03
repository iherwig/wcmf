// -------------------------------------------------------------------------
// Global variable definitions.
// -------------------------------------------------------------------------

// -------------------------------------------------------------------------
// Utility functions.
// -------------------------------------------------------------------------
// Open new window without posting form data
function newWindow(controller, context, action, name, windowDef)
{
  newWindowEx(controller, context, action, name, windowDef, '');
}
// Open new window without posting form data but with additional query string
// additionalQueryString: e.g. "&oid=...."
function newWindowEx(controller, context, action, name, windowDef, additionalQueryString)
{
  name = window.open($('form').action+
            "?controller="+controller+
            "&context="+context+
            "&action="+action+additionalQueryString, 
            name, windowDef);
  if (window.focus) {name.focus()}
}
function setController(controller) { $('input[name=controller]').val(controller); }
function setContext(context) { $('input[name=context]').val(context); }
function setAction(action) { $('input[name=action]').val(action); }
function setVariable(name, val) { $('input[name='+name+']').val(val); }
function getVariable(name) { return $('input[name='+name+']').val(); }
function setTarget(target) { $('form').target = target; }

modifiedFields = [];
function setDirty(fieldName)
{
  if (fieldName) {
    modifiedFields[fieldName] = true;
  }
}
function setClean(fieldName)
{
  if (fieldName) {
    modifiedFields[fieldName] = false;
  }
}

function canLeavePage(confirm)
{
  var modified = false;
  for (var field in modifiedFields) {
    // ignore fields with class ignoreDirty
    var formField = getVariable(field);
    if (formField && formField.className.indexOf('ignoreDirty') == -1) {
    if (modifiedFields[field] == true) {
        modified = true;
        break;
      }
    }
  }
  // save reminder
  if (modified)
  {
    if (typeof(confirm)=="undefined") confirm = true;
    if (confirm)
    {
      text = "There's possibly unsaved data in field '"+field+"' and you're about to leave this edit mask. If you continue, all unsaved data will be lost. Do you really want to continue?";
      check = confirm(text);
    }
    else {
      check = true;
    }
    return check;
  }
  return true;
}
// -------------------------------------------------------------------------
// CMS functions.
//
// To allow accumulation of actions (etc. copy more than one node) all 
// 'do...' functions don't submit the form. They just update the appropriate
// form variables.
// The submission of the form will actually be done by a call to 'submitAction'.
// The following controller will be chosen based on the action given to 
// 'submitAction' so don't expect that variables used in other action contexts
// will be considered in action processing.
//
// NOTE: all functions act on the first form in the document.
//
//
// form variable conventions:
//
// action:   variablename:            value:         description:
// =================================================================================
// display   oid                      oid            display node with oid 'oid'.
// save      value-name-oid           wert           change variable 'name'at node with
//                                                   oid 'oid'. NOTE: the '-' in variablename
//                                                   may be any application defined delimiter
// new       poid                     oid            add a new node of type 
//           newtype                  type           'newtype' to the parent node
//                                                   with oid 'poid'.
// delete    deleteoids               oids  array*   delete node(s) with oid(s)
//                                                   'deleteoids'
// paste     poid                     oid            add existing node(s) with oid(s)
//
// *array is a comma-separated string
//
//------------------------------------------------------------------------

//
// Display node with oid 'oid'.
//
function doDisplay(oid)
{
  setVariable('oid', oid);
}
//
// Create a new node of type 'type'.
// We actually only store the type of the node to create for later creation.
// The parent node of the 'new' action is defined by using 'doSetParent'.
//
function doNew(type)
{
  setVariable('newtype', type);
}
//
// Set the parent node for 'new' and 'paste' action to the node with oid 'poid'.
//
function doSetParent(poid)
{
  setVariable('poid', poid);
}
//
// Delete node with oid 'oid'.
// We actually only store the oid of the node to delete for later deletion.
//
function doDelete(oid, confirm, text)
{
  if (typeof(confirm)=="undefined") confirm = true;
  if (confirm)
  {
    if (typeof(text)=="undefined") {
      text = "Really Delete Node with OID "+oid+".";
    }
    check = confirm(text);
  }
  else {
    check = true;
  }
  if (check==true) {
    setVariable('deleteoids', oid);
  }
  return check;
}
//
// Save all changes.
//
function doSave()
{
  // nothing to do
  // all information is edited in the 'value-name-oid' variables
}
//
// Post form data with given action
//
function submitAction(action)
{
  // display save reminder if necessary
  if (action == 'dologin' || action.toLowerCase().indexOf('save') >= 0 || canLeavePage())
  {
    setAction(action);
    $('form').submit();
    setTarget('');
  }
}

// -------------------------------------------------------------------------
// Display functions.
// -------------------------------------------------------------------------
function displayMsg()
{
  var layer = document.getElementById("msg");
  if (!layer.style.display || layer.style.display == "none") {
    layer.style.display = "block";
  }
  else {
    layer.style.display = "none";
  }
}
function adjustIFrameSize(iframeId) 
{
  var iframe = document.getElementById(iframeId)
  if (iframe.contentDocument) // firefox 
  { 
    iframe.height = iframe.contentDocument.documentElement.scrollHeight + 20; 
  } 
  else // IE 
  { 
    iframe.style.height = iframe.contentWindow.document.body.scrollHeight + 20; 
  }
} 