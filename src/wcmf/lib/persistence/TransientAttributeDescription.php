<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2017 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\persistence;

/**
 * Instances of TransientAttributeDescription describe transient attributes of PersistentObjects
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class TransientAttributeDescription extends AttributeDescription {

  protected $name = '';
  protected $type = 'String';

  /**
   * Constructor.
   * @param $name The attribute name
   * @param $type The attribute type
   */
  public function __construct($name, $type) {
    $this->name = $name;
    $this->type = $type;
    $this->isEditable = false;
    $this->tags = ["DATATYPE_ATTRIBUTE"];
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
}
?>
