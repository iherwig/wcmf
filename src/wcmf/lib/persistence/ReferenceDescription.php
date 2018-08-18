<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2018 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\persistence;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\AttributeDescription;

/**
 * Instances of ReferenceDescription describe reference attributes of PersistentObjects
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ReferenceDescription extends AttributeDescription {

  protected $otherType = '';
  protected $otherName = '';

  /**
   * Constructor.
   * @param $name The name of the reference
   * @param $otherType The name of the referenced type (must be the role name, @see RelationDescription)
   * @param $otherName The name of the referenced attribute in the referenced type
   */
  public function __construct($name, $otherType, $otherName) {
    $this->name = $name;
    $this->otherType = $otherType;
    $this->otherName = $otherName;
  }

  /**
   * Get the name of the referenced type
   * @return String
   */
  public function getOtherType() {
    return $this->otherType;
  }

  /**
   * Get the name of the referenced attribute in the referenced type
   * @return String
   */
  public function getOtherName() {
    return $this->otherName;
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
    $attribute = $this->getReferencedAttribute();
    return $attribute->getType();
  }

  /**
   * Get the application specific tags that this attribute is tagged with
   * @return Array of String
   */
  public function getTags() {
    $attribute = $this->getReferencedAttribute();
    return $attribute->getTags();
  }

  /**
   * Get the default value
   * @return Mixed
   */
  public function getDefaultValue() {
    $attribute = $this->getReferencedAttribute();
    return $attribute->getDefaultValue();
  }

  /**
   * Get the validation type for the value
   * @return String
   */
  public function getValidateType() {
    $attribute = $this->getReferencedAttribute();
    return $attribute->getValidateType();
  }

  /**
   * Get the description for the validation type
   * @return String
   */
  public function getValidateDescription() {
    $attribute = $this->getReferencedAttribute();
    return $attribute->getValidateDescription();
  }

  /**
   * Check whether the attribute should be editable
   * @return Boolean
   */
  public function getIsEditable() {
    return false;
  }

  /**
   * Get the input type for the value
   * @return String
   */
  public function getInputType() {
    $attribute = $this->getReferencedAttribute();
    return $attribute->getInputType();
  }

  /**
   * Get the display type for the value
   * @return String
   */
  public function getDisplayType() {
    $attribute = $this->getReferencedAttribute();
    return $attribute->getDisplayType();
  }

  /**
   * Get the referenced attribute
   * @return AttributeDescription instance
   */
  private function getReferencedAttribute() {
    $mapper = ObjectFactory::getInstance('persistenceFacade')->getMapper($this->otherType);
    return $mapper->getAttribute($this->otherName);
  }
}
?>
