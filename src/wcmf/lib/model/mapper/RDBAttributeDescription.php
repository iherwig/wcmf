<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\model\mapper;

use wcmf\lib\persistence\AttributeDescription;

/**
 * Instances of RDBAttributeDescription describe attributes of PersistentObjects
 * in a relational database.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class RDBAttributeDescription extends AttributeDescription {

  protected $table = '';
  protected $column = '';

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
   * @param $table The table name
   * @param $column The column name
   */
  public function __construct($name, $type, array $tags, $defaultValue, $validateType,
    $validateDescription, $isEditable, $inputType, $displayType, $table, $column) {

    parent::__construct($name, $type, $tags, $defaultValue, $validateType,
      $validateDescription, $isEditable, $inputType, $displayType);

    $this->table = $table;
    $this->column = $column;
  }

  /**
   * Get the table name
   * @return String
   */
  public function getTable() {
    return $this->table;
  }

  /**
   * Get the column name
   * @return String
   */
  public function getColumn() {
    return $this->column;
  }
}
?>
