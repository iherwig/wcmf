<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2009 wemove digital solutions GmbH
 *
 * Licensed under the terms of any of the following licenses
 * at your choice:
 *
 * - GNU Lesser General Public License (LGPL)
 *   http://www.gnu.org/licenses/lgpl.html
 * - Eclipse Public License (EPL)
 *   http://www.eclipse.org/org/documents/epl-v10.php
 *
 * See the license.txt file distributed with this work for
 * additional information.
 *
 * $Id$
 */
require_once(WCMF_BASE."wcmf/lib/util/class.Message.php");
require_once(WCMF_BASE."wcmf/lib/util/class.StringUtil.php");
require_once(WCMF_BASE."wcmf/lib/util/class.InifileParser.php");
require_once(WCMF_BASE."wcmf/lib/util/class.SessionData.php");
require_once(WCMF_BASE."wcmf/lib/util/class.ObjectFactory.php");
require_once(WCMF_BASE."wcmf/lib/i18n/class.Localization.php");

/**
 * @class Control
 * @ingroup Presentation
 * @brief Control is the base class for html controls. Each Control
 * instance has a view template assigned, which defines the actual
 * representation of the control in html. A Control class may use
 * several view templates to render different html controls. The main 
 * purpose of Control classes is the assignment of control specific 
 * values to the associated view before it will be rendered 
 * (@see Control::assignViewValues()).
 * 
 * Controls are be bound to input types in the configuration section
 * named 'htmlform' in the following way:
 * @code
 * [htmlform]
 * inputType = {controlClass, viewTemplate} e.g.
 * text = {TextControl, wcmf/application/views/forms/text.tpl}
 * @endcode
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class Control
{
  const CONTROL_SECTION_NAME = 'htmlform';
  private static $_listStrategies = array();
  
  private $_controlIndex = array();
  private $_viewTpl = null;
  
  /**
   * Register a IListStrategy implementation for resolving list values.
   * This method must be called for all IListStrategy implementations in order
   * to be usable.
   * @param listType The list type of the list type as used in input_type
   * @param className The name of the implementation class
   */
  public static function registerListStrategy($listType, $className)
  {
    $impl = new $className;
    if (!($impl instanceof IListStrategy)) {
      throw new ConfigurationException($className." must implement IListStrategy.");
    }
    self::$_listStrategies[$listType] = $className;
  }
  /**
   * Get the delimiter for HTML input control names to be used if a control name
   * consists of different parts.
   * @return The delimitor
   * @note If 'inputFieldNameDelimiter' is given in the configuration file (section 'htmlform')
   *       it will be taken (else it defaults to '-').
   */
  public static function getInputFieldDelimiter()
  {
    $FIELD_DELIMITER = '-';
    // try to get default field delimiter
    $parser = InifileParser::getInstance();
    if(($fieldDelimiter = $parser->getValue('inputFieldNameDelimiter', self::CONTROL_SECTION_NAME)) === false) {
      $fieldDelimiter = $FIELD_DELIMITER;
    }
    return $fieldDelimiter;
  }
  /**
   * Get a HTML input control name for a given object value.
   * @param obj A reference to the PersistentObject which contains the value
   * @param name The name of the value to construct the control for
   * @return The HTML control name string in the form value-<name>-<oid>
   */
  public static function getControlName(PersistentObject $obj, $name)
  {
    $fieldDelimiter = self::getInputFieldDelimiter();
    return 'value'.$fieldDelimiter.$name.$fieldDelimiter.$obj->getOID();
  }
  /**
   * Get the object value definition from a HTML input control name.
   * @param name The name of input control in the format defined by Control::getControlName
   * @return An associative array with keys 'oid', 'name' or null if the name is not valid
   */
  public static function getValueDefFromInputControlName($name)
  {
    if (!(strpos($name, 'value') == 0)) {
      return null;
    }
    $def = array();
    $fieldDelimiter = StringUtil::escapeForRegex(self::getInputFieldDelimiter());
    $pieces = preg_split('/'.$fieldDelimiter.'/', $name);
    if (sizeof($pieces) != 3) {
      return null;
    }
    $forget = array_shift($pieces);
    $def['name'] = array_shift($pieces);
    $def['oid'] = array_shift($pieces);

    return $def;
  }
  /**
   * Get an instance of the control class that matches the given inputType.
   * The implementation searches in the configuration section 'htmlform' for the 
   * most specific key that matches the inputType.
   * @param inputType The input type to get the control for
   * @return A Control instance
   */
  public static function getControl($inputType)
  {
    $parser = InifileParser::getInstance();
    if(($controlSection = $parser->getSection(self::CONTROL_SECTION_NAME)) === false) {
      throw new ConfigurationException("No controls defined. ".$parser->getErrorMsg());
    }
    // get best matching control definition
    $bestMatch = '';
    foreach (array_keys($controlSection) as $controlDef)
    {
      if (strpos($inputType, $controlDef) === 0 && strlen($controlDef) > strlen($bestMatch)) {
        $bestMatch = $controlDef;
      }
    }
    // instantiate the control
    if (strlen($bestMatch) > 0)
    {
      $controlDef = $controlSection[$bestMatch];
      if (!is_array($controlDef) || sizeof($controlDef) != 2) {
        throw new ConfigurationException("Expected an array of size 2 for key '".
          $bestMatch."' in section '".self::CONTROL_SECTION_NAME."'");
      }
      $controlClass = $controlDef[0];
      $viewTpl = $controlDef[1];
      $control = ObjectFactory::createInstance($controlClass);
      if (!$control instanceof Control) {
        throw new ConfigurationException($controlClass." does not inherit from Control");        
      }
      $control->_viewTpl = $viewTpl;
      return $control;
    }    
    // no match found
    throw new ConfigurationException("No control found for input type '".
      $inputType."' in section '".self::CONTROL_SECTION_NAME."'");
  }
  /**
   * Get a HTML input control for a given description.
   * @param name The name of the control (HTML name attribute)
   * @param inputType The definition of the control as given in the input_type property of a value
   *        The definition is of the form @code type @endcode or @code type[attributes]#list @endcode
   *        where list must be given for controls that allow to select from a list of values
   *        - type: a control type defined in the configuration file (section 'htmlform')
   *        - attributes: a string of attributes for the input control as used in the HTML definition (e.g. 'cols="50" rows="4"')
   *        - list: a list definition for which a ListStrategy is registered (@see Control::registerListStrategy).
   *                The list definition has the form @code listType:typeSpecificConfiguration @endcode
   * @param value The predefined value of the control (maybe comma separated list for list controls)
   * @param editable True/False if this is set false the function returns only the translated value (processed by translateValue()) [default: true]
   * @param language The lanugage if the Control should be localization aware. Optional,
   *                 default null (= Localization::getDefaultLanguage())
   * @param parentView The IView instance, in which the control should be embedded. Optional,
   *                 default null
   * @return The HTML control string or the translated value string depending in the editable parameter
   */
  public function render($name, $inputType, $value, $editable=true, $language=null, $parentView=null)
  {
    $value = strval($value);
    
    // get definition and list from description
    if (strPos($inputType, '#'))
    {
      list($def, $list) = preg_split('/#/', $inputType, 2);
      $listMap = $this->getListMap($list, $value, null, $language);
    }
    else {
      $def = $inputType;
    }

    // if editable, make the value a list if we have a list type and the value contains comma separators
    // if not editable, translate the value (using the translateValue() method)
    if ($editable)
    {
      if (strlen($list) == 0 && strPos($value, ',')) {
        $value = preg_split('/,/', $value);
      }
      else {
        $value = htmlspecialchars($value);
      }
    }
    else {
      $value = $this->translateValue($value, $inputType, false, null, $language);
    }

    // get type and attributes from definition
    preg_match_all("/[\w][^\[\]]+/", $def, $matches);
    if (sizeOf($matches[0]) > 0) {
      list($type, $attributes) = $matches[0];
    }
    if (!$type || strlen($type) == 0) {
      $type = 'text';
    }

    // add '[]' to name if 'multiple' selection is given in attributes
    if (strPos($attributes, 'multiple')) {
      $name .= '[]';
    }
    
    // get error from session
    $session = SessionData::getInstance();
    $error = $session->getError($name);

    // split attributes into an array
    $attributeList = array();
    $attributeParts = preg_split("/[\s,;]+/", $attributes);
    foreach($attributeParts as $attribute)
    {
      if (strlen($attribute) > 0)
      {
        list($key, $value) = preg_split("/[=:]+/", $attribute);
        $key = trim(stripslashes($key));
        $value = trim(stripslashes($value));
        $attributeList[$key] = $value;
      }
    }
    
    // setup the control view
    $view = ObjectFactory::createInstanceFromConfig('implementation', 'View');
    $view->setup();
    
    // assign view values
    $view->assign('enabled', $editable);
    $view->assign('name', $name);
    $view->assign('value', $value);
    $view->assign('error', $error);
    $view->assign('listMap', $listMap);
    $view->assign('inputType', $inputType);
    $view->assign('controlIndex', $this->getControlIndex($parentView));
    $view->assign('attributes', $attributes);
    $view->assign('attributeList', $attributeList);
    
    // add subclass parameters
    $this->assignViewValues($view);
    
    // render the view
    $htmlString = $view->fetch(WCMF_BASE.$this->_viewTpl);
    $this->registerWithView($parentView);
    return $htmlString;
  }
  /**
   * Get a HTML input control for a given object value. The control is defined by the
   * 'input_type' property of the value. The property 'is_editable' is used to determine
   * wether the control should be enabled or not.
   * @param obj A reference to the PersistentObject which contains the value
   * @param name The name of the value to construct the control for
   * @param language The lanugage if the Control should be localization aware. Optional,
   *                 default null (= Localization::getDefaultLanguage())
   * @param parentView The IView instance, in which the control should be embedded. Optional,
   *                 default null
   * @return The HTML control string
   */
  public function renderFromProperty(PersistentObject $obj, $name, $language=null, $parentView=null)
  {
    $controlName = self::getControlName($obj, $name);
    $properties = $obj->getValueProperties($name);
    $value = $obj->getValue($name);
    return $this->render($controlName, $properties['input_type'], $value, $properties['is_editable'],
      $language, $parentView);
  }
  /**
   * Assign the control specific values to the view. Parameters assigned by default
   * may be retrieved by $view->getTemplateVars($paramName) 
   * @param view The view instance
   */
  protected abstract function assignViewValues(IView $view);
  /**
   * Get a list of key/value pairs defined by description.
   * @param definition A list definition for which a ListStrategy is registered
   *                 (@see Control::render) 
   * @param value The selected value (maybe null, default: null)
   * @param nodeOid Serialized oid of the node containing this value (for determining remote oids) [default: null]
   * @param language The lanugage if Control should be localization aware. Optional,
   *                 default is Localization::getDefaultLanguage()
   * @return An assoziative array containing the key/value pairs
   * @note The method will try to translate values with Message::get().
   * Keys and values are encoded using htmlentities(string, ENT_QUOTES, 'UTF-8').
   */
  private function getListMap($definition, $value=null, $nodeOid=null, $language=null)
  {
    $map = array();
    // get type and list from description
    if (!strPos($definition, ':')) {
      throw new ConfigurationException("No type found in list definition: ".$definition);
    }
    else {
      list($type, $list) = preg_split('/:/', $definition, 2);
    }

    // build list
    if (isset(self::$_listStrategies[$type])) {
      $strategy = new self::$_listStrategies[$type];
      $map = $strategy->getListMap($list, $value, $nodeOid, $language);
    }
    else {
      throw new ConfigurationException('No IListStrategy implementation registered for '.$type);
    }

    // escape and translate all keys and values
    $result = array();
    foreach($map as $key => $value) {
      $key = htmlentities($key, ENT_QUOTES, 'UTF-8');
      $value = htmlentities($value, ENT_QUOTES, 'UTF-8');
      if (!function_exists('html_entity_decode')) {
        $key = html_entity_decode($key, ENT_QUOTES, 'UTF-8');
      }
      $result[strval($key)] = strval(Message::get($value));
    }
    return $result;
  }
  /**
   * Translate a value with use of it's assoziated input type e.g get the location string from a location id.
   * (this is only done when the input type has a list definition).
   * @param value The value to translate (maybe comma separated list for list controls)
   * @param inputType The description of the control as given in the input_type property of a value (see Control::render())
   * @param replaceBR True/False wether to replace html line breaks with spaces or not [default:false]
   * @param nodeOid Serialized oid of the node containing this value (for determining remote oids) [default: null]
   * @param language The lanugage if Control should be localization aware. Optional,
   *                 default is Localization::getDefaultLanguage()
   * @return The translated value
   */
  public function translateValue($value, $inputType, $replaceBR=false, $nodeOid = null, $language=null)
  {
    // get definition and list from description
    $translated = '';
    if (strPos($inputType, '#') && $value != '')
    {
      list(,$list) = preg_split('/#/', $inputType, 2);
      $map = $this->getListMap($list, $value, $nodeOid, $language);
      if ($list != '' && strPos($value, ',')) {
        $value = preg_split('/,/', $value);
      }
      if (is_array($value))
      {
        foreach($value as $curValue) {
          $translated .= $map[$curValue].", ";
        }
        $translated = StringUtil::removeTrailingComma($translated);
      }
      else {
        $translated = $map[$value];
      }
      return $translated;
    }
    $value = nl2br($value);
    if ($replaceBR) {
      $value = str_replace('<br />', ' ', $value);
    }
    return $value;
  }
  /**
   * Get the index of the control in the given parent view.
   * This method relies on a call to Control::registerWithView()
   * @param parentView The parent IView instance, with which the control is registered
   * @return The number of times that the control was registered with the parent view
   */
  private function getControlIndex($parentView)
  {
    $parentKey = spl_object_hash($parentView);
    if (isset($this->_controlIndex[$parentKey])) {
      return $this->_controlIndex[$parentKey];
    }
    return 0;
  }
  /**
   * Register this control with a given parent view.
   * @param parentView The parent IView instance, with which the control should be registered
   */
  private function registerWithView($parentView)
  {
    $parentKey = spl_object_hash($parentView);
    if (!isset($this->_controlIndex[$parentKey])) {
      $this->_controlIndex[$parentKey] = 0;
    }
    $this->_controlIndex[$parentKey]++;
  }
}
?>
