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
require_once(BASE."wcmf/lib/util/class.StringUtil.php");

/**
 * @class AttributeDescription
 * @ingroup Persistence
 * @brief Instances of AttributeDescription describe attributes of PersistentObjects
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class AttributeDescription
{
  public $name = '';
  public $type = 'string';
  public $appDataTypes = array();
  public $defaultValue = null;
  public $restrictionsMatch = '';
  public $restrictionsNotMatch = '';
  public $restrictionsDescription = '';
  public $isEditable = true;
  public $inputType = 'text';
  public $displayType = 'text';

  /**
   * Constructor.
   * @param name The attribute name
   * @param type The attribute type. This may be used to decide on value conversions in the assoziated DataConverter class
   * @param appDataTypes An array of application specific data types that this attribute is tagged with
   * @param defaultValue The default value (will be set when creating a blank object, see PersistenceMapper::create())
   * @param restrictionsMatch A regular expression that the value must match (e.g. '[0-3][0-9]\.[0-1][0-9]\.[0-9][0-9][0-9][0-9]' for date values)
   * @param restrictionsNotMatch A regular expression that the value must NOT match
   * @param restrictionsDescription A description of the resticitions
   * @param isEditable True/False whether the attribute should be editable, see FormUtil::getInputControl()
   * @param inputType The HTML input type for the value, see FormUtil::getInputControl()
   * @param displayType The HTML display type for the value, see NodeUtil::getDisplayValue()
   */
  public function __construct($name, $type, array $appDataTypes, $defaultValue, $restrictionsMatch, $restrictionsNotMatch,
    $restrictionsDescription, $isEditable, $inputType, $displayType)
  {
    $this->name = $name;
    $this->type = $type;
    $this->appDataTypes = $appDataTypes;
    $this->defaultValue = $defaultValue;
    $this->restrictionsMatch = $restrictionsMatch;
    $this->restrictionsNotMatch = $restrictionsNotMatch;
    $this->restrictionsDescription = $restrictionsDescription;
    $this->isEditable = $isEditable;
    $this->inputType = $inputType;
    $this->displayType = $displayType;
  }

  /**
   * Check if this attribute has the given application specific data types
   * @param dataTypes An array of data type names defined in the mapper. Empty array means all values [default:empty array]
   * @return True if the attribute has all data types, false else
   */
  public function hasDataTypes(array $dataTypes=array())
  {
    foreach ($dataTypes as $dataType)
    {
      if (!in_array($dataType, $this->appDataTypes)) {
        return false;
      }
    }
    return true;
  }

  /**
   * Return an array of property names defined in this attribute description
   * @return An array of names
   */
  public function getPropertyNames()
  {
    return array('name', 'type', 'appDataTypes', 'defaultValue', 'restrictionsMatch', 'restrictionsNotMatch',
      'restrictionsDescription', 'isEditable', 'inputType', 'displayType');
  }

  /**
   * Allow to get properties that are given in an underscore notation.
   * For example this->input_type will return the inputType property value.
   */
  public function __get($name)
  {
    $propName = StringUtil::underScoreToCamelCase($name, true);
    if(strlen($propName) > 0) {
      return $this->$propName;
    }
  }
}
?>
