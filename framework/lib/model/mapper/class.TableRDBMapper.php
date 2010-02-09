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
require_once(BASE."wcmf/lib/model/mapper/class.RDBMapper.php");
require_once(BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(BASE."wcmf/lib/persistence/converter/class.DataConverter.php");
require_once(BASE."wcmf/lib/model/class.Table.php");  

/**
 * @class TableRDBMapper
 * @ingroup Mapper
 * @brief TableRDBMapper maps Table objects to a relational database schema where one
 * object contains several table rows.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class TableRDBMapper extends RDBMapper
{
  /**
   * @see RDBMapper::createObject()
   */
  function &createObject($oid=null)
  {
    return new Table($this->getType(), $oid);
  }
  /**
   * @see RDBMapper::appendObject()
   */
  function appendObject(&$object, &$dependendObject)
  {
    // do nothing because tables have no other tables included!
  }
  /**
   * @see RDBMapper::applyDataOnLoad()
   */
  function applyDataOnLoad(&$object, $objectData, $attribs)
  {
    $persistenceFacade = &PersistenceFacade::getInstance();

    // set object data
    foreach($objectData['_datadef'] as $dataItem)
    {
      if ($attribs == null || in_array($dataItem['name'], $attribs))
      {
        $valueProperties = array();
        foreach($dataItem as $key => $value)
          if ($key != 'name')
            $valueProperties[$key] = $value;

        // loop over all rows
        foreach($objectData['_data'] as $row)
        {
          $rowId = $row['id'];
          $value = $this->_dataConverter->convertStorageToApplication($row[$dataItem['name']], $dataItem['db_data_type'], $dataItem['name']);
          $object->setValue($rowId, $value, $dataItem['name']);
          $object->setValueProperties($rowId, $valueProperties, $dataItem['name']);
        }
      }
    }
    $object->reIndex();
  }
  /**
   * @see RDBMapper::applyDataOnCreate()
   */
  function applyDataOnCreate(&$object, $objectData, $attribs)
  {
    // set object data
    foreach($objectData['_datadef'] as $dataItem)
    {
      if ($attribs == null || in_array($dataItem['name'], $attribs))
      {
        $valueProperties = array();
        foreach($dataItem as $key => $value)
          if ($key != 'name')
            $valueProperties[$key] = $value;

        $value = $this->_dataConverter->convertStorageToApplication($dataItem['default'], $dataItem['db_data_type'], $dataItem['name']);
        $object->setValue(0, $value, $dataItem['name']);
        $object->setValueProperties(0, $valueProperties, $dataItem['name']);
      }
    }
  }
  /**
   * @see RDBMapper::getObjectDefinition()
   *
   * The _datadef key holds the following structure:
   *
   * An array of assoziative arrays with the key 'name' plus application specific keys for every data item. 
   * All keys except 'name' will become keys in the objects valueProperties array hold for each data item while 'name'
   * is used to identify the values types, see PersistentObject::getValue(). 'name' entries correspond to table columns.
   * (e.g. array('name' => 'title', 'db_data_type' => 'VARCHAR(255)', 'default' => 'Hello World!')) @n
   * Known keys are: 
   * - @em db_data_type: The database data type of the attribute. This may be used to decide on value conversions in the assoziated DataConverter class
   * - @em default: The default value (will be set when creating a blank object, see PersistenceMapper::create())
   * - @em restrictions_match: A regular expression that the value must match (e.g. '[0-3][0-9]\.[0-1][0-9]\.[0-9][0-9][0-9][0-9]' for date values)
   * - @em restrictions_not_match:  A regular expression that the value must NOT match
   * - @em is_editable: true, false whether the value should be editable, see FormUtil::getInputControl()
   * - @em input_type: The HTML input type for the value, see FormUtil::getInputControl()
   *
   * @note the content of the _children array is ignored by this mapper because tables have no other tables included.
   */
  function getObjectDefinition()
  {
    WCMFException::throwEx("getObjectDefinition() must be implemented by derived class: ".get_class($this), __FILE__, __LINE__);
  }
  /**
   * @see RDBMapper::getChildrenSelectSQL()
   */
  function getChildrenSelectSQL($oid, $compositionOnly=false)
  {
    // tables have no other tables included!
    return array();
  }
}
?>
