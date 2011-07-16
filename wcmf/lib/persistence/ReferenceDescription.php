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
require_once(WCMF_BASE."wcmf/lib/persistence/AttributeDescription.php");

/**
 * @class ReferenceDescription
 * @ingroup Persistence
 * @brief Instances of ReferenceDescription describe reference attributes of PersistentObjects
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ReferenceDescription extends AttributeDescription
{
  protected $otherType = '';
  protected $otherName = '';

  /**
   * Constructor.
   * @param name The name of the reference
   * @param otherType The name of the referenced type (must be the role name, @see RelationDescription)
   * @param otherName The name of the referenced attribute in the referenced type
   */
  public function __construct($name, $otherType, $otherName)
  {
    $this->name = $name;
    $this->otherType = $otherType;
    $this->otherName = $otherName;
  }

  /**
   * Get the name of the referenced type
   * @return String
   */
  public function getOtherType()
  {
    return $this->otherType;
  }

  /**
   * Get the name of the referenced attribute in the referenced type
   * @return String
   */
  public function getOtherName()
  {
    return $this->otherName;
  }
  
  /**
   * Get the attribute name
   * @return String
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   * Get the attribute type
   * @return String
   */
  public function getType()
  {
    $attribute = $this->getReferencedAttribute();
    return $attribute->getType();
  }

  /**
   * Get the application specific tags that this attribute is tagged with
   * @return Array of String
   */
  public function getTags()
  {
    $attribute = $this->getReferencedAttribute();
    return $attribute->getTags();
  }

  /**
   * Get the default value
   * @return Mixed
   */
  public function getDefaultValue()
  {
    $attribute = $this->getReferencedAttribute();
    return $attribute->getDefaultValue();
  }

  /**
   * Get the regular expression that the value must match
   * @return String
   */
  public function getRestrictionsMatch()
  {
    $attribute = $this->getReferencedAttribute();
    return $attribute->getRestrictionsMatch();
  }

  /**
   * Get the regular expression that the value must NOT match
   * @return String
   */
  public function getRestrictionsNotMatch()
  {
    $attribute = $this->getReferencedAttribute();
    return $attribute->getRestrictionsNotMatch();
  }

  /**
   * Get the description of the resticitions
   * @return String
   */
  public function getRestrictionsDescription()
  {
    $attribute = $this->getReferencedAttribute();
    return $attribute->getRestrictionsDescription();
  }

  /**
   * Check whether the attribute should be editable
   * @return Boolean
   */
  public function getIsEditable()
  {
    return false;
  }

  /**
   * Get the input type for the value
   * @return String
   */
  public function getInputType()
  {
    $attribute = $this->getReferencedAttribute();
    return $attribute->getInputType();
  }

  /**
   * Get the display type for the value
   * @return String
   */
  public function getDisplayType()
  {
    $attribute = $this->getReferencedAttribute();
    return $attribute->getDisplayType();
  }
  
  /**
   * Get the referenced attribute
   * @return AttributeDescription instance
   */
  private function getReferencedAttribute()
  {
    $mapper = PersistenceFacade::getInstance()->getMapper($this->otherType);
    return $mapper->getAttribute($this->otherName);
  }
}
?>
