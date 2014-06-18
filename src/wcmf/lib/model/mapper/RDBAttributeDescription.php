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
   * @see AttributeDescription::__construct
   * @param table The table name
   * @param column The column name
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
