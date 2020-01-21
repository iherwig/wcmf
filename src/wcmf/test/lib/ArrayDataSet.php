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
namespace wcmf\test\lib;

//\PHPUnit_Util_Filter::addFileToFilter(__FILE__, 'PHPUNIT');

class ArrayDataSet extends \PHPUnit_Extensions_Database_DataSet_AbstractDataSet {
  protected $tables = [];

  /**
   * @param array $data
   */
  public function __construct(array $data) {
    foreach ($data AS $tableName => $rows) {
      $columns = [];
      if (isset($rows[0])) {
        $columns = array_keys($rows[0]);
      }

      $metaData = new \PHPUnit_Extensions_Database_DataSet_DefaultTableMetaData($tableName, $columns);
      $table = new \PHPUnit_Extensions_Database_DataSet_DefaultTable($metaData);

      foreach ($rows AS $row) {
        $table->addRow($row);
      }
      $this->tables[$tableName] = $table;
    }
  }

  protected function createIterator($reverse = FALSE) {
    return new \PHPUnit_Extensions_Database_DataSet_DefaultTableIterator($this->tables, $reverse);
  }

  public function getTable($tableName) {
    if (!isset($this->tables[$tableName])) {
      throw new InvalidArgumentException("$tableName is not a table in the current database.");
    }

    return $this->tables[$tableName];
  }
}
?>