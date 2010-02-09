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
require_once(BASE."wcmf/lib/util/class.StringUtil.php");
require_once(BASE."wcmf/lib/model/class.Table.php");  
require_once(BASE."wcmf/lib/model/mapper/class.TableRDBMapper.php");

/**
 * @class TableUnifiedRDBMapper
 * @ingroup Mapper
 * @brief TableRDBMapper maps Table objects to a relational database schema where one
 * object contains several table rows.
 * In comparison to TableRDBMapper it implements the template methods in a way that
 * handles unified table definitions (e.g. tables are named as the type, primary keys are 
 * named 'id' and attribute names correspond to table columns).
 * Subclasses simply need to define $_type as the mapper type and implement the 
 * getObjectDefinition() method.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class TableUnifiedRDBMapper extends TableRDBMapper
{
  /**
   * @see TableRDBMapper::getSelectSQL()
   */
  function getSelectSQL($condStr, $alias=null, $orderStr=null, $attribs=null, $asArray=false) 
  {
    // construct query parts
    $type = $this->getType();
    $attribStr = '';
    $tableStr = $type;
    
    // map to alias if requested
    if ($alias != null)
    {
      $type = $alias;
      $tableStr .= ' as '.$alias;
    }
    
    // attributes
    $tableDef = $this->getObjectDefinition();
    foreach($tableDef['_datadef'] as $curDef)
      if ($attribs == null || in_array($curDef['name'], $attribs))
        $attribStr .= $type.".".$curDef['name'].", ";
    $attribStr = StringUtil::removeTrailingComma($attribStr);

    if (strlen($condStr) == 0)
      $condStr = "1";

   $completeOrderStr = $orderStr;
    if (strlen($orderStr) > 0)
      $completeOrderStr = " ORDER BY ".$orderStr;
      
    if ($asArray)
      return array(
        'attributeStr' => $attribStr, 
        'tableStr' => $this->_dbPrefix.$tableStr,
        'conditionStr' => $condStr,
        'orderStr' => $orderStr
      );
    else
      return "SELECT ".$attribStr." FROM ".$this->_dbPrefix.$tableStr." WHERE ".$condStr.$completeOrderStr.";";
  }
  /**
   * @see TableRDBMapper::getInsertSQL()
   * @note This mapper empties the corresponding database table first and fills in the values afterwards.
   */
  function getInsertSQL(&$object)
  {
    return $this->getUpdateSQL($object);
  }
  /**
   * @see TableRDBMapper::getUpdateSQL()
   * @note This mapper empties the corresponding database table first and fills in the values afterwards.
   */
  function getUpdateSQL(&$object)
  {
    $type = $this->getType();

    $sqlArray = $this->getDeleteSQL(0);
    $rows = $object->getRows();
    for($i=0; $i<sizeof($rows); $i++)
    {
      $valueStr = '';
      foreach($object->getRow($rows[$i]) as $value)
        $valueStr .= $this->_conn->qstr($value).", ";
    
      array_push($sqlArray, "INSERT INTO ".$this->_dbPrefix.$type." VALUES(".$i.", ".StringUtil::removeTrailingComma($valueStr).");");
    }
    return $sqlArray;
  }
  /**
   * @see TableRDBMapper::getDeleteSQL()
   * @note This mapper empties the corresponding database table.
   */
  function getDeleteSQL($oid)
  {
    return array("DELETE FROM ".$this->_dbPrefix.$this->getType());
  }
  /**
   * @see PersistenceMapper::isValidOID()
   */
  function isValidOID($oid)
  {
    $oidParts = PersistenceFacade::decomposeOID($oid);
    
    // check the type parameter
    if ($oidParts['type'] != $this->getType())
      return false;

    return true;
  }
  /**
   * @see NodeRDBMapper::createPKCondition()
   */
  function createPKCondition($oid)
  {
    return "1";
  }
}
?>
