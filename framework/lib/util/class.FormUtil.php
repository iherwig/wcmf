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
require_once(BASE."wcmf/lib/util/class.Message.php");
require_once(BASE."wcmf/lib/util/class.StringUtil.php");
require_once(BASE."wcmf/lib/util/class.InifileParser.php");
require_once(BASE."wcmf/lib/presentation/class.DefaultControlRenderer.php");
require_once(BASE."wcmf/lib/util/class.SessionData.php");
require_once(BASE."wcmf/lib/i18n/class.Localization.php");

/**
 * @class FormUtil
 * @ingroup Util
 * @brief FormUtil provides basic support for HTML forms.
 * It's mainly for creating input controls from definition strings.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class FormUtil
{
  var $_language = null;
  var $_controlRenderer = null;

  /**
   * Constructor
   * @param language The lanugage if FormUtil should be localization aware. Optional,
   *                 default is Localization::getDefaultLanguage()
   */
  public function FormUtil($language=null)
  {
    $this->_language = $language;

    // create the control renderer instance
    $objectFactory = &ObjectFactory::getInstance();
    $this->_controlRenderer = &$objectFactory->createInstanceFromConfig('implementation', 'ControlRenderer');
    if ($this->_controlRenderer == null) {
      throw new ConfigurationException('ControlRenderer not defined in section implementation.');
    }
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
    if(($fieldDelimiter = $parser->getValue('inputFieldNameDelimiter', 'htmlform')) === false) {
      $fieldDelimiter = $FIELD_DELIMITER;
    }
    return $fieldDelimiter;
  }
  /**
   * Get a HTML input control for a given description.
   * @param name The name of the control (HTML name attribute)
   * @param inputType The description of the control as given in the input_type property of a value
   *        The description is of the form @code type @endcode or @code type[attributes]#list @endcode
   *        where list must be given for the types select, radio and checkbox
   *        - type: text|password|textarea|select|radio|checkbox|file|fileex(= file with delete checkbox)
   *        - attributes: a string of attributes for the input control as used in the HTML definition (e.g. 'cols="50" rows="4"')
   *        - list: one of the following:
   *              - fix:key1[val1]|key2[val2]|... or fix:$global_array_variable
   *              -  db:key[val]|table
   *              - fkt:name|param1,param2,... global function
   *              - config:section
   * @param value The predefined value of the control (maybe comma separated list for list controls)
   * @param editable True/False if this is set false the function returns only the translated value (processed by translateValue()) [default: true]
   * @return The HTML control string or the translated value string depending in the editable parameter
   * @see DefaultControlRenderer::renderControl
   */
  public function getInputControl($name, $inputType, $value, $editable=true)
  {
    $value = strval($value);
    $htmlString = '';
    // get definition and list from description
    if (strPos($inputType, '#'))
    {
      list($def, $list) = split('#', $inputType, 2);
      $listMap = $this->getListMap($list, $value);
    }
    else {
      $def = $inputType;
    }

    // if editable, make the value a list if we have a list type and the value contains comma separators
    // if not editable, translate the value (using the translateValue() method)
    if ($editable)
    {
      if ($list != '' && strPos($value, ',')) {
        $value = split(",", $value);
      }
      else {
        $value = htmlspecialchars($value);
      }
    }
    else {
      $value = $this->translateValue($value, $inputType);
    }

    // get type and attributes from definition
    preg_match_all("/[\w][^\[\]]+/", $def, $matches);
    if (sizeOf($matches[0]) > 0) {
      list($type, $attributes) = $matches[0];
    }
    if (!$type || $type == '') {
      $type = 'text';
    }

    // add '[]' to name if 'multiple' selection is given in attributes
    if (strPos($attributes, 'multiple')) {
      $name .= '[]';
    }
    // get error from session
   	$session = &SessionData::getInstance();

    // build input control
    return $this->_controlRenderer->renderControl($type, $editable, $name, $value, $session->getError($name), $attributes, $listMap, $inputType);
  }
  /**
   * Get a list of key/value pairs defined by description.
   * @param description One of the following strings:
   *        - fix:key1[val1]|key2[val2]|... or fix:$global_array_variable
   *        -  db:key[val]|table
   *        - fkt:name|param1,param2,... global function
   *        - config:section
   * @param value The selected value (maybe null, default: null)
   * @return An assoziative array containing the key/value pairs
   * @note The method will try to translate values with Message::get().
   * Keys and values are encoded using htmlentities(string, ENT_QUOTES, 'UTF-8').
   */
  private function getListMap($description, $value=null)
  {
    $map = array();
    // get type and list from description
    if (!strPos($description, ':')) {
      throw new ConfigurationException("No type found in list definition: ".$description);
    }
    else {
      list($type, $list) = split(':', $description, 2);
    }

    // build list
    switch ($type)
    {
      case 'fix':
        // see if we have an array variable or a list definition
        if (strPos($list, '$') === 0) {
          $entries = $GLOBALS[subStr($list,1)];
        }
        else {
          $entries = split('\|', $list);
        }
        if (!is_array($entries)) {
          throw new ConfigurationException($list." is no array.");
        }
        // process list
        foreach($entries as $curEntry)
        {
          preg_match_all("/([^\[]*)\[*([^\]]*)\]*/", $curEntry, $matches);
          if (sizeOf($matches) > 0)
          {
            $val1 = htmlentities($matches[1][0], ENT_QUOTES, 'UTF-8');
            $val2 = htmlentities($matches[2][0], ENT_QUOTES, 'UTF-8');

            if (!function_exists('html_entity_decode')) {
              $val1 = html_entity_decode($val1, ENT_QUOTES, 'UTF-8');
            }
            if ($val2 != '')
            {
              // value given
              $map[$val1] = $val2;
            }
            else
            {
              // only key given
              $map[$val1] = $val1;
            }
          }
        }
        break;
      case 'db':
        throw new ConfigurationException('db list type is not implemented yet!');
        break;
      case 'fkt':
        // maybe there are '|' chars in parameters
        $parts = split('\|', $list);
        $name = array_shift($parts);
        $params = join('|', $parts);
        if (function_exists($name)) {
          $map = call_user_func_array($name, split(',', $params));
        }
        else {
          throw new ConfigurationException('Function '.$name.' is not defined globally!');
        }
        break;
      case 'config':
        $parser = &InifileParser::getInstance();
        $map = $parser->getSection($list);
        if (($map = $parser->getSection($list, false)) === false) {
          throw new ConfigurationException($parser->getErrorMsg());
        }
        break;
      case 'async':
        // load the translated value only
        $parts = split('\|', $list);
        $entityType = array_shift($parts);
        // since this may be a multivalue field, the ids may be separated by commas
        $ids = split(',', $value);
        foreach ($ids as $id)
        {
          $oid = PersistenceFacade::composeOID(array('type' => $entityType, 'id' => $id));
          if (PersistenceFacade::isValidOID($oid))
          {
            $persistenceFacade = &PersistenceFacade::getInstance();
            $localization = &Localization::getInstance();
            $obj = &$persistenceFacade->load($oid, BUILDDEPTH_SINGLE);
            if ($obj != null) {
              // localize object if requested
              if ($this->_language != null) {
                $localization->loadTranslation($obj, $this->_language);
              }
              $map[$id] = $obj->getDisplayValue();
            }
          }
        }
        // fallback if the value can not be interpreted as an oid or the object does not exist
        if (sizeof($map) == 0) {
          $map = array($value => $value);
        }
        break;
      case 'asyncmult':
        // load the translated value only
        $parts = split('\|', $list);
        $persistenceFacade = &PersistenceFacade::getInstance();
        foreach($parts as $key=>$entityType)
        {
          // since this may be a multivalue field, the ids may be separated by commas
          $ids = split(',', $value);
          foreach ($ids as $id)
          {
            $oid = PersistenceFacade::composeOID(array('type' => $entityType, 'id' => $id));
            if (PersistenceFacade::isValidOID($oid))
            {
              $localization = &Localization::getInstance();
              $obj = &$persistenceFacade->load($oid, BUILDDEPTH_SINGLE);
              if ($obj != null) {
                // localize object if requested
                if ($this->_language != null) {
                  $localization->loadTranslation($obj, $this->_language);
                }
                $map[$id] = $obj->getDisplayValue();
              }
            }
          }
        }
        // fallback if the value can not be interpreted as an oid or the object does not exist
        if (sizeof($map) == 0) {
          $map = array($value => $value);
        }
        break;
      default:
        throw new ConfigurationException('Unknown list type: '.$type);
        break;
    }

    // translate
    $result = array();
    foreach($map as $key => $value) {
      $result[strval($key)] = strval(Message::get($value));
    }
    return $result;
  }
  /**
   * Translate a value with use of it's assoziated input type e.g get the location string from a location id.
   * (this is only done when the input type has a list definition).
   * @param value The value to translate (maybe comma separated list for list controls)
   * @param inputType The description of the control as given in the input_type property of a value (see CMS getInputControl())
   * @param replaceBR True/False wether to replace html line breaks with spaces or not [default:false]
   * @return The translated value
   */
  public function translateValue($value, $inputType, $replaceBR=false)
  {
    // get definition and list from description
    $translated = '';
    if (strPos($inputType, '#') && $value != '')
    {
      list(,$list) = split('#', $inputType, 2);
      $map = $this->getListMap($list, $value);
      if ($list != '' && strPos($value, ',')) {
        $value = split(",", $value);
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
}
?>
