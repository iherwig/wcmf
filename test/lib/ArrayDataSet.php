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
namespace test\lib;

//\PHPUnit_Util_Filter::addFileToFilter(__FILE__, 'PHPUNIT');

class ArrayDataSet extends \PHPUnit_Extensions_Database_DataSet_AbstractDataSet {
  protected $tables = array();

  /**
   * @param array $data
   */
  public function __construct(array $data) {
    foreach ($data AS $tableName => $rows) {
      $columns = array();
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