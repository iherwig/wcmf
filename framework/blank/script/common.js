// -------------------------------------------------------------------------
// Global variable definitions.
// -------------------------------------------------------------------------

// -------------------------------------------------------------------------
// Utility functions.
// -------------------------------------------------------------------------
function addToStringArray(val, strArray, delim)
{
  if (typeof(delim)=="undefined") delim = ",";
  if (strArray != '')
  {
    var realArray = strArray.split(delim);
    realArray[realArray.length] = val;  
    return realArray.join(delim);
  }
  else
    return val;
}
function deleteFromStringArray(val, strArray, delim)
{
  if (typeof(delim)=="undefined") delim = ",";
  var realArray = strArray.split(delim);
  var tmpArray = new Array();
  for (i=0;i<realArray.length;i++)
    if (realArray[i] != val)
      tmpArray[tmpArray.length] = realArray[i];
  return tmpArray.join(delim);
}
function getForm()
{
  return document.forms[0];
}
// Open new window without posting form data
function newWindow(_controller, _context, _action, _name, _windowDef)
{
  newWindowEx(_controller, _context, _action, _name, _windowDef, '');
}
// Open new window without posting form data but with additional query string
// _additionalQueryString: e.g. "&oid=...."
function newWindowEx(_controller, _context, _action, _name, _windowDef, _additionalQueryString)
{
  _name = window.open(getForm().action+
            "?controller="+_controller+
            "&context="+_context+
            "&usr_action="+_action+_additionalQueryString, 
            _name, _windowDef);
  if (window.focus) {_name.focus()}
}
function setController(_controller) { getForm().controller.value=_controller; }
function setContext(_context) { getForm().context.value=_context; }
function setAction(_action) { getForm().usr_action.value=_action; }
function setVariable(_name, _val) { getForm()[_name].value = _val; }
function getVariable(_name) { return getForm()[_name].value; }
function setTarget(_target) { getForm().target=_target; }

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

function canLeavePage()
{
  var modified = false;
  for (var field in modifiedFields) {
    // ignore fields with class ignoreDirty
    var formField = getFormField(field);
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
    if (typeof(_confirm)=="undefined") _confirm = true;
    if (_confirm)
    {
      _text = "There's possibly unsaved data in field '"+field+"' and you're about to leave this edit mask. If you continue, all unsaved data will be lost. Do you really want to continue?";
      check = confirm(_text);
    }
    else
      check = true;
    
    return check;
  }
  return true;
}
function getFormField(name)
{ 
  var form = getForm();
  for (var i = 0; i < form.elements.length; i++) {
    if (form.elements[i].name == name) {
      return form.elements[i];
    }
  }
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
// save      value-datatype-name-oid  wert           change variable 'name' of
//                                                   type 'datatype' at node with
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
function doDisplay(_oid)
{
  getForm().oid.value=_oid;
}
//
// Create a new node of type 'type'.
// We actually only store the type of the node to create for later creation.
// The parent node of the 'new' action is defined by using 'doSetParent'.
//
function doNew(_type)
{
  getForm().newtype.value=_type;
}
//
// Set the parent node for 'new' and 'paste' action to the node with oid 'poid'.
//
function doSetParent(_poid)
{
  getForm().poid.value=_poid;
}
//
// Delete node with oid 'oid'.
// We actually only store the oid of the node to delete for later deletion.
//
function doDelete(_oid, _confirm, _text)
{
  if (typeof(_confirm)=="undefined") _confirm = true;
  if (_confirm)
  {
    if (typeof(_text)=="undefined")
      _text = "Really Delete Node with OID "+_oid+"."
    check = confirm(_text);
  }
  else
    check = true;
  if (check==true)
  {
    getForm().deleteoids.value=addToStringArray(_oid, getForm().deleteoids.value);
  }
  return check;
}
//
// Save all changes.
//
function doSave()
{
  // nothing to do
  // all information is edited in the 'value-datatype-name-id' variables
}
//
// Post form data with given action
//
function submitAction(_action)
{
  // display save reminder if necessary
  if (_action == 'dologin' || _action.toLowerCase().indexOf('save') >= 0 || canLeavePage())
  {
    setAction(_action);
    getForm().submit();
    getForm().target = '';
  }
}

// -------------------------------------------------------------------------
// Display functions.
// -------------------------------------------------------------------------
function displayMsg()
{
  var layer = document.getElementById("msg");
  if (!layer.style.display || layer.style.display == "none")
    layer.style.display = "block";
  else
    layer.style.display = "none";
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