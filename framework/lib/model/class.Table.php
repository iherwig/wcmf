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
require_once(BASE."wcmf/lib/util/class.Message.php");
require_once(BASE."wcmf/lib/persistence/class.PersistentObject.php");
require_once(BASE."wcmf/lib/persistence/class.PersistenceFacade.php");

/**
 * @class Table
 * @ingroup Model
 * @brief Table is a PersistentObject that holds a table structure.
 * The rows of the table correspond to the value names of the PersistentObject
 * and the columns to the datatypes. So if you want to set the value of 
 * row $i and column $j to $value call $table->setValue($i, $value, $j).
 * To retrieve the corresponding value call $value = $table->getValue($i, $j).
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class Table extends PersistentObject
{
  var $_valueProperties = array();
  /**
   * Constructor.
   * @param type The Tables type.
   * @param oid The Tables oid (, optional will be calculated if not given or not valid).
   */
  function Table($type, $oid=null)
  {
    $this->_type = $type;
    if (!(isset($oid)) || !PersistenceFacade::isValidOID($oid))
    {
      // no oid is given -> new node
      $id = md5(uniqid(ip2long($_SERVER['REMOTE_ADDR']) ^ (int)$_SERVER['REMOTE_PORT'] ^ @getmypid() ^ @disk_free_space('/tmp'), 1));
      $this->_oid = PersistenceFacade::composeOID(array('type' => $this->_type, 'id' => $id));
      $this->setState(STATE_NEW);
    }
    else
    {
      // old node
      $this->_oid = $oid;
      $this->setState(STATE_CLEAN);
    }    
  }
  /**
   * Reset all row indizes to an ascending sequence starting by 0.
   */
  function reIndex()
  {
    foreach($this->getColumns() as $column)
      $this->_data[$column] = array_slice($this->_data[$column], 0);
  }
  /**
   * Get all row names of the table.
   * @return An array of row names.
   */
  function getNumRows()
  {
    $columns = $this->getColumns();
    return sizeof($this->getValueNames($columns[0]));
  }
  /**
   * Get all column names of the table.
   * @return An array of column names.
   */
  function getColumns()
  {
    return $this->getDataTypes();
  }
  /**
   * Get all column values of a row.
   * @param index The index of the row (zero based).
   * @return An array of column values.
   */
  function getRow($index)
  {
    $row = array();
    $columns = $this->getColumns();
    for ($i=0; $i<sizeof($columns); $i++)
      $row[$columns[$i]] = $this->getValue($index, $columns[$i]);
    return $row;
  }
  /**
   * Get all row values of a column.
   * @param name The name of the cloumn.
   * @return An array of row values.
   */
  function getColumn($name)
  {
    $column = array();
    $numRows = $this->getNumRows();
    for ($i=0; $i<$numRows; $i++)
      $column[$i] = $this->getValue($i, $name);
    return $column;
  }
  /**
   * Insert a new row before a given row.
   * @param index The index of the row before which the new row should be inserted (zero based) [maybe null].
   * @param values An assoziative array with column name keys and values to insert.
   * If the name is empty or does not exist the row will be appended to the end of the table.
   * @note this method only works for numerical names.
   */
  function insertRow($index=null, $values=array())
  {
    $numRows = $this->getNumRows();
    foreach($this->getColumns() as $column)
    {
      if ($index !== null && $index <= $numRows)
      {
        $firstRows = array_slice($this->_data[$column], 0, $index);
        $lastRows = array_slice($this->_data[$column], $index);
        array_push($firstRows, array('value' => $values[$column]));
        $this->_data[$column] = array_merge($firstRows, $lastRows);
      }
      else
        array_push($this->_data[$column], array('value' => $values[$column]));
    }
    $this->setState(STATE_DIRTY);
  }
  /**
   * Remove a row.
   * @param index The index of the row to remove (zero based).
   * @note this method only works for numerical names.
   */
  function deleteRow($index)
  {
    foreach($this->getColumns() as $column)
      array_splice($this->_data[$column], $index, 1);
    $this->setState(STATE_DIRTY);
  }
  /**
   * @see PersistenceObject::getValueProperties()
   * We override this method because we only need to store the value properties once, not for every row.
   * @note The type (= column name) must be given, the name (= row name) is ignored.
   */
  function getValueProperties($name, $type=null)
  {
    if ($type != null && is_array($this->_data[$type]))
      return $this->_valueProperties[$type];
    else 
      return null;
  }
  /**
   * @see PersistenceObject::setValueProperties()
   * We override this method because we only need to store the value properties once, not for every row.
   * @note The type (= column name) must be given, the name (= row name) is ignored.
   */
  function setValueProperties($name, $properties, $type=null)
  {
    if ($type != null)
    {
      $this->_valueProperties[$type] = $properties;
      PersistentObject::setState(STATE_DIRTY);
      return true;
    }
    else
      return false;
  }
}
?>
