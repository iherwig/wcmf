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

use PHPUnit\DbUnit\Database\DataSet;
use PHPUnit\DbUnit\Database\TableIterator;
use PHPUnit\DbUnit\DataSet\ITable;
use PHPUnit\DbUnit\DataSet\ITableIterator;
use PHPUnit\DbUnit\DataSet\DefaultTable;
use PHPUnit\DbUnit\DataSet\DefaultTableMetadata;
use PHPUnit\DbUnit\Exception\InvalidArgumentException;

//\PHPUnit_Util_Filter::addFileToFilter(__FILE__, 'PHPUNIT');

class ArrayDataSet extends DataSet {
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

      $metaData = new DefaultTableMetaData($tableName, $columns);
      $table = new DefaultTable($metaData);

      foreach ($rows AS $row) {
        $table->addRow($row);
      }
      $this->tables[$tableName] = $table;
    }
  }

  protected function createIterator($reverse=FALSE): ITableIterator {
    return new TableIterator($this->tables, $this, $reverse);
  }

  public function getTable($tableName): ITable {
    if ($tableName instanceof ITable) {
      return $tableName;
    }
    if (!isset($this->tables[$tableName])) {
      throw new InvalidArgumentException("$tableName is not a table in the current database.");
    }

    return $this->tables[$tableName];
  }
}
?>