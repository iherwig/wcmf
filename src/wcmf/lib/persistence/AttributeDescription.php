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
namespace wcmf\lib\persistence;

/**
 * Instances of AttributeDescription describe attributes of PersistentObjects
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class AttributeDescription {

  protected $name = '';
  protected $type = 'string';
  protected $tags = array();
  protected $defaultValue = null;
  protected $restrictionsMatch = '';
  protected $restrictionsNotMatch = '';
  protected $restrictionsDescription = '';
  protected $isEditable = true;
  protected $inputType = 'text';
  protected $displayType = 'text';

  /**
   * Constructor.
   * @param name The attribute name
   * @param type The attribute type. This may be used to decide on value conversions in the assoziated DataConverter class
   * @param tags An array of application specific tags that this attribute is tagged with
   * @param defaultValue The default value (will be set when creating a blank object, see PersistenceMapper::create())
   * @param restrictionsMatch A regular expression that the value must match (e.g. '[0-3][0-9]\.[0-1][0-9]\.[0-9][0-9][0-9][0-9]' for date values)
   * @param restrictionsNotMatch A regular expression that the value must NOT match
   * @param restrictionsDescription A description of the resticitions
   * @param isEditable True/False whether the attribute should be editable, see Control::render()
   * @param inputType The input type for the value, see Control::render()
   * @param displayType The display type for the value
   */
  public function __construct($name, $type, array $tags, $defaultValue, $restrictionsMatch, $restrictionsNotMatch,
    $restrictionsDescription, $isEditable, $inputType, $displayType) {

    $this->name = $name;
    $this->type = $type;
    $this->tags = $tags;
    $this->defaultValue = $defaultValue;
    $this->restrictionsMatch = $restrictionsMatch;
    $this->restrictionsNotMatch = $restrictionsNotMatch;
    $this->restrictionsDescription = $restrictionsDescription;
    $this->isEditable = $isEditable;
    $this->inputType = $inputType;
    $this->displayType = $displayType;
  }

  /**
   * Check if this attribute is tagged with the given application specific tags
   * @param tags An array of tags that the attribute should match. Empty array results in true the given matchMode [default: empty array]
   * @param matchMode One of 'all', 'none', 'any', defines how the attribute's tags should match the given tags [default: 'all']
   * @return True if the attribute has all data types, false else
   */
  public function matchTags(array $tags=array(), $matchMode='all') {
    $numGivenTags = sizeof($tags);
    if (sizeof($numGivenTags) == 0) {
      return true;
    }
    $result = true;
    $diff = sizeof(array_diff($tags, $this->tags));
    switch ($matchMode) {
      case 'all':
        $result = ($diff == 0);
        break;
      case 'none':
        $result = ($diff == $numGivenTags);
        break;
      case 'any':
        $result = ($diff < $numGivenTags);
        break;
    }
    return $result;
  }

  /**
   * Return an array of property names defined in this attribute description
   * @return An array of names
   */
  public function getPropertyNames() {
    return array('name', 'type', 'tags', 'defaultValue', 'restrictionsMatch', 'restrictionsNotMatch',
      'restrictionsDescription', 'isEditable', 'inputType', 'displayType');
  }

  /**
   * Get the attribute name
   * @return String
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Get the attribute type
   * @return String
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Get the application specific tags that this attribute is tagged with
   * @return Array of String
   */
  public function getTags() {
    return $this->tags;
  }

  /**
   * Get the default value
   * @return Mixed
   */
  public function getDefaultValue() {
    return $this->defaultValue;
  }

  /**
   * Get the regular expression that the value must match
   * @return String
   */
  public function getRestrictionsMatch() {
    return $this->restrictionsMatch;
  }

  /**
   * Get the regular expression that the value must NOT match
   * @return String
   */
  public function getRestrictionsNotMatch() {
    return $this->restrictionsNotMatch;
  }

  /**
   * Get the description of the resticitions
   * @return String
   */
  public function getRestrictionsDescription() {
    return $this->restrictionsDescription;
  }

  /**
   * Check whether the attribute should be editable
   * @return Boolean
   */
  public function getIsEditable() {
    return $this->isEditable;
  }

  /**
   * Get the input type for the value
   * @return String
   */
  public function getInputType() {
    return $this->inputType;
  }

  /**
   * Get the display type for the value
   * @return String
   */
  public function getDisplayType() {
    return $this->displayType;
  }
}
?>
