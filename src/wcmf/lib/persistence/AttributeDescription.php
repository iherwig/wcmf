<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2020 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\persistence;

/**
 * Instances of AttributeDescription describe attributes of PersistentObjects
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class AttributeDescription {

  protected $name = '';
  protected $type = 'String';
  protected $tags = [];
  protected $defaultValue = null;
  protected $validateType = '';
  protected $validateDescription = '';
  protected $isEditable = true;
  protected $inputType = 'text';
  protected $displayType = 'text';

  /**
   * Constructor.
   * @param $name The attribute name
   * @param $type The attribute type
   * @param $tags An array of application specific tags that this attribute is tagged with
   * @param $defaultValue The default value (will be set when creating a blank object, see PersistenceMapper::create())
   * @param $validateType A validation type for the value
   * @param $validateDescription A description for the validation type
   * @param $isEditable Boolean whether the attribute should be editable, see Control::render()
   * @param $inputType The input type for the value, see Control::render()
   * @param $displayType The display type for the value
   */
  public function __construct($name, $type, array $tags, $defaultValue, $validateType,
    $validateDescription, $isEditable, $inputType, $displayType) {
    $this->name = $name;
    $this->type = $type;
    $this->tags = $tags;
    $this->defaultValue = $defaultValue;
    $this->validateType = $validateType;
    $this->validateDescription = $validateDescription;
    $this->isEditable = $isEditable;
    $this->inputType = $inputType;
    $this->displayType = $displayType;
  }

  /**
   * Check if this attribute has the given application specific tag
   * @param $tag Tag that the attribute should have
   * @return Boolean
   */
  public function hasTag($tag) {
    return in_array($tag, $this->tags);
  }

  /**
   * Check if this attribute is tagged with the given application specific tags
   * @param $tags An array of tags that the attribute should match. Empty array results in true the given matchMode (default: empty array)
   * @param $matchMode One of 'all', 'none', 'any', defines how the attribute's tags should match the given tags (default: 'all')
   * @return True if the attribute tags satisfy the match mode, false else
   */
  public function matchTags(array $tags=[], $matchMode='all') {
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
    return ['name', 'type', 'tags', 'defaultValue', 'validateType',
      'validateDescription', 'isEditable', 'inputType', 'displayType'];
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
   * Get the validation type for the value
   * @return String
   */
  public function getValidateType() {
    return $this->validateType;
  }

  /**
   * Get the description for the validation type
   * @return String
   */
  public function getValidateDescription() {
    return $this->validateDescription;
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
