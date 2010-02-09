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
require_once(BASE."wcmf/lib/util/class.StringUtil.php");
require_once(BASE."wcmf/lib/model/class.Node.php");
require_once(BASE."wcmf/lib/model/mapper/class.NodeRDBMapper.php");
require_once(BASE."wcmf/lib/model/mapper/class.RDBAttributeDescription.php");
require_once(BASE."wcmf/lib/model/mapper/class.RDBManyToManyRelationDescription.php");
require_once(BASE."wcmf/lib/model/mapper/class.RDBManyToOneRelationDescription.php");
require_once(BASE."wcmf/lib/model/mapper/class.RDBOneToManyRelationDescription.php");
require_once(BASE."wcmf/lib/model/mapper/class.ReferenceDescription.php");

/**
 * @class NodeUnifiedRDBMapper
 * @ingroup Mapper
 * @brief NodeUnifiedRDBMapper maps Node objects to a relational database schema where each Node
 * type has its own table.
 * In comparison to NodeRDBMapper it implements almost all template methods from the
 * base classes and defines the sql queries. The newly defined template methods make it easier
 * for application developers to implement own mapper subclasses. The wCMFGenerator uses this class
 * as base class for all mappers.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class NodeUnifiedRDBMapper extends NodeRDBMapper implements ChangeListener
{
  /**
   * @see PersistenceMapper::initialize()
   */
  public function initialize(PersistentObject $object)
  {
    // add this as ChangeListener
    if ($object != null) {
      $object->addChangeListener($this);
    }
  }

  /**
   * @see RDBMapper::prepareInsert()
   */
  protected function prepareInsert(PersistentObject $object)
  {
    $oid = $object->getOID();
    $ids = $oid->getId();
    $pkNames = $this->getPkNames();
    for($i=0, $count=sizeof($pkNames); $i<$count; $i++)
    {
      // foreign keys don't get a new id
      $pkName = $pkNames[$i];
      if (!$this->isForeignKey($pkNames))
      {
        $pkValue = $ids[$i];
        // replace dummy ids with ids provided by the database sequence
        if (ObjectId::isDummyId($pkValue))
        {
          $nextId = $this->getNextId();
          $object->setValue($pkName, $nextId);
        }
      }
    }
  }
  /**
   * @see RDBMapper::getSelectSQL()
   */
  protected function getSelectSQL($condStr, $alias=null, $orderStr=null, $attribs=null, $asArray=false)
  {
    // replace application attribute/table names with sql names
    $condStr = $this->translateAppToDatabase($condStr, $alias);
    $orderStr = $this->translateAppToDatabase($orderStr, $alias);

    // construct query parts
    $attribStr = '';
    $tableName = $this->getTableName();
    $tableStr = $tableName;

    // map to alias if requested
    if ($alias != null)
    {
      $tableName = $alias;
      $tableStr .= ' as '.$alias;
    }

    // parents
    $parentStr = '';
    $i=0;
    $parentDescriptions = $this->getRelations('parent');
    foreach($parentDescriptions as $curParentDesc)
    {
      if ($curParentDesc->otherNavigability)
      {
        $parentStr .= $this->quote($curParentDesc->otherType)." AS ptype$i, ".$this->quote($curParentDesc->otherRole)." AS prole$i, ".
                        $tableName.".".$curParentDesc->fkColumn." AS pid$i, ";
        $i++;
      }
    }
    if (strlen($parentStr) > 0) {
      $parentStr = StringUtil::removeTrailingComma($parentStr);
    }
    else {
      $parentStr = "'' AS ptype0, '' AS prole0, null AS pid0";
    }

    // attributes
    $attributeDescriptions = $this->getAttributes();
    foreach($attributeDescriptions as $curAttributeDesc)
    {
      if (!($curAttributeDesc instanceof ReferenceDescription))
      {
        if ($attribs == null || in_array($curAttributeDesc->name, $attribs)) {
          $attribStr .= $tableName.".".$curAttributeDesc->column." AS ".$this->quote($curAttributeDesc->name).", ";
        }
      }
    }

    // references
    $refStrings = $this->getSQLForRefs($attribStr, $tableStr, $condStr, $orderStr, $attribs);
    $attribStr = $refStrings['attribStr'];
    $tableStr = $refStrings['tableStr'];
    $condStr = $refStrings['condStr'];
    $orderStr = $refStrings['orderStr'];

    if (strlen($attribStr) > 0) {
      $attribStr .= ",";
    }
    if (strlen($condStr) == 0) {
      $condStr = "1";
    }
    $completeOrderStr = $orderStr;
    if (strlen($orderStr) > 0)
      $completeOrderStr = " ORDER BY ".$orderStr;
    else
    {
      // use default ordering
      $orderByNames = $this->getDefaultOrder();
      if (is_array($orderByNames))
      {
        $completeOrderStr = '';
        foreach($orderByNames as $orderByName) {
          if (strlen(trim($orderByName)) > 0) {
            $completeOrderStr .= $this->translateAppToDatabase($this->getTableName().".".$orderByName.", ");
          }
        }
        if (strlen($completeOrderStr) > 0) {
          $completeOrderStr = ' ORDER BY '.StringUtil::removeTrailingComma($completeOrderStr);
        }
      }
    }

    if ($asArray) {
      return array(
        'attributeStr' => $attribStr." ".$parentStr,
        'tableStr' => $this->_dbPrefix.$tableStr,
        'conditionStr' => $condStr,
        'orderStr' => $orderStr
      );
    }
    else {
      return "SELECT ".$attribStr." ".$parentStr." FROM ".$this->_dbPrefix.$tableStr." WHERE ".$condStr.$completeOrderStr.";";
    }
  }
  /**
   * @see RDBMapper::getRelationSelectSQL()
   */
  protected function getRelationSelectSQL(PersistentObject $object, $compositionOnly=false)
  {
    $sqlArray = array();
    $oid = $object->getOID();
    $dbid = $oid->getId();
    $relDescs = $this->getRelations();
    foreach($relDescs as $relDesc)
    {
      if ($relDesc->otherNavigability)
      {
        if (!$compositionOnly || ($compositionOnly && $relDesc->otherAggregationKind == 'composite'))
        {
          if ($relDesc instanceof RDBOneToManyRelationDescription)
          {
            $sqlStr = $relDesc->otherTable.".".$relDesc->fkColumn."=".$this->quote($dbid[0]);
            $sqlArray[$relDesc->otherRole] = array('type' => $relDesc->otherType, 'criteria' => $sqlStr);
          }
          elseif ($relDesc instanceof RDBManyToOneRelationDescription)
          {
            $fkName = $this->getAttributeName($relDesc->fkColumn);
            $sqlStr = $relDesc->otherTable.".".$relDesc->idColumn."=".$this->quote($object->getValue($fkName));
            $sqlArray[$relDesc->otherRole] = array('type' => $relDesc->otherType, 'criteria' => $sqlStr);
          }
          elseif ($relDesc instanceof RDBManyToManyRelationDescription)
          {
            $thisRelDesc = $relDesc->thisEndRelation;
            $otherRelDesc = $relDesc->otherEndRelation;
            $sqlStr = $thisRelDesc->otherTable.".".$thisRelDesc->fkColumn."=".$this->quote($dbid[0]);
            $sqlArray[$otherRelDesc->otherRole] = array('type' => $thisRelDesc->otherType, 'criteria' => $sqlStr);
          }
        }
      }
    }
    return $sqlArray;
  }
  /**
   * @see RDBMapper::getChildrenDisassociateSQL()
   */
  protected function getChildrenDisassociateSQL(ObjectId $oid, $sharedOnly=false)
  {
    $sqlArray = array();
    $dbid = $oid->getId();
    $nodeDef = $this->getObjectDefinitionImpl();
    foreach($nodeDef['_children'] as $childDef) {
      if (!$sharedOnly || ($sharedOnly && $childDef['composition'] == false)) {
        array_push($sqlArray, "UPDATE ".$this->_dbPrefix.$childDef['table_name']." SET ".$childDef['fk_columns']."=NULL WHERE ".
          $childDef['fk_columns']."=".$this->quote($dbid[0]).";");
      }
    }
    return $sqlArray;
  }
  /**
   * @see RDBMapper::getInsertSQL()
   */
  protected function getInsertSQL(PersistentObject $object)
  {
    $persistenceFacade = PersistenceFacade::getInstance();

    $insertedAttributes = array();
    $attribNameStr = '';
    $attribValueStr = '';
    $tableName = $this->getTableName();

    // primary key definition
    $oid = $object->getOID();
    $ids = $oid->getId();
    $pkNames = $this->getPkNames();
    for($i=0, $count=sizeof($pkNames); $i<$count; $i++)
    {
      // foreign keys are handled afterwards (they don't get a new id)
      $pkName = $pkNames[$i];
      if (!$this->isForeignKey($pkName))
      {
        $pkValue = $ids[$i];
        $attribNameStr .= $pkName.", ";
        $attribValueStr .= $this->quote($pkValue).", ";
        array_push($insertedAttributes, $pkName);
      }
    }

    // parent definition
    $parents = $object->getParents();
    for ($i=0; $i<sizeof($parents); $i++)
    {
      $parent = $parents[$i];
      $poid = $parent->getOID();
      if ($parent != null && $poid->isValid() && PersistenceFacade::isKnownType($parent->getType()))
      {
        $parentIds = $poid->getId();
        $attribName = $this->getFKColumnName($parent->getType(), $object->getRole($poid));

        $attribNameStr .= $attribName.", ";
        $attribValueStr .= $this->quote($parentIds[0]).", ";
        array_push($insertedAttributes, $attribName);
      }
    }

    // attribute definition
    $nodeDef = $this->getObjectDefinitionImpl();
    foreach($nodeDef['_datadef'] as $curDef)
    {
      // insert only attributes that are defined in node
      if (in_array($curDef['name'], $object->getValueNames($curDef['app_data_type'])))
      {
        $attribName = $curDef['name'];
        // don't insert the same attribute twice
        if (!in_array($attribName, $insertedAttributes))
        {
          $attribNameStr .= $tableName.".".$curDef['column_name'].", ";
          $attribValueStr .= $this->quote($object->getValue($attribName, $curDef['app_data_type'])).", ";
          array_push($insertedAttributes, $attribName);
        }
      }
    }
    $attribNameStr = StringUtil::removeTrailingComma($attribNameStr);
    $attribValueStr = StringUtil::removeTrailingComma($attribValueStr);

    // query
    $sqlArray = array
    (
      "INSERT INTO ".$this->_dbPrefix.$tableName." (".$attribNameStr.") VALUES (".$attribValueStr.");"
    );
    return $sqlArray;
  }
  /**
   * @see RDBMapper::getUpdateSQL()
   */
  protected function getUpdateSQL(PersistentObject $object)
  {
    $persistenceFacade = PersistenceFacade::getInstance();

    $updatedAttributes = array();
    $attribStr = '';
    $tableName = $this->getTableName();

    // primary key definition
    $pkStr = $this->createPKCondition($object->getOID());

    // parent definition
    $parents = $object->getParents();
    for ($i=0, $count=sizeof($parents); $i<$count; $i++)
    {
      $parent = $parents[$i];
      $poid = $parent->getOID();
      if ($parent != null && $poid->isValid() && PersistenceFacade::isKnownType($parent->getType()))
      {
        $parentIds = $poid->getId();
        $attribName = $this->getFKColumnName($parent->getType(), $object->getRole($poid));
        $attribStr = $tableName.".".$attribName."=".$this->quote($parentIds[0]).", ";
        array_push($updatedAttributes, $attribName);
      }
    }

    // attribute definition
    $nodeDef = $this->getObjectDefinitionImpl();
    foreach($nodeDef['_datadef'] as $curDef)
    {
      // update only attributes that are defined in node
      if (in_array($curDef['name'], $object->getValueNames($curDef['app_data_type'])))
      {
        $attribName = $curDef['name'];
        // don't insert the same attribute twice
        if (!in_array($attribName, $updatedAttributes))
        {
          $attribStr .= $tableName.".".$curDef['column_name']."=".$this->quote($object->getValue($attribName, $curDef['app_data_type'])).", ";
          array_push($updatedAttributes, $attribName);
        }
      }
    }
    $attribStr = StringUtil::removeTrailingComma($attribStr);

    // query
    if (strlen($attribStr) > 0) {
      $sqlArray = array
      (
        "UPDATE ".$this->_dbPrefix.$tableName." SET ".$attribStr." WHERE ".$pkStr.";"
      );
    }
    else {
      $sqlArray = array();
    }
    return $sqlArray;
  }
  /**
   * @see RDBMapper::getDeleteSQL()
   */
  protected function getDeleteSQL(ObjectId $oid)
  {
    // primary key definition
    $pkStr = $this->createPKCondition($oid);

    $sqlArray = array
    (
      "DELETE FROM ".$this->_dbPrefix.$this->getTableName()." WHERE ".$pkStr.";"
    );
    return $sqlArray;
  }
  /**
   * @see RDBMapper::createPKCondition()
   */
  protected function createPKCondition(ObjectId $oid)
  {
    $str = '';
    $tableName = $this->getTableName();
    $pkNames = $this->getPKNames();
    $ids = $oid->getId();
    for ($i=0, $count=sizeof($pkNames); $i<$count; $i++)
    {
      $pkValue = $ids[$i];
      $str .= $tableName.".".$pkNames[$i]."=".$this->quote($pkValue).' AND ';
    }
    return substr($str, 0, -5);
  }
  /**
   * Check if a given name is the name of a value
   * @param name The name to check
   * @return True/False
   */
  protected function isAttribute($name)
  {
    $nodeDef = $this->getObjectDefinition();
    foreach($nodeDef['_datadef'] as $curDef)
    {
      if ($curDef['name'] == $name) {
        return true;
      }
    }
    return false;
  }
  /**
   * Check if a given type is a child type
   * @param type The type to check
   * @param childDef The child array as defined in the _children key (see NodeRDBMapper::getObjectDefinition())
   * @return True/False
   */
  protected function isChild($type, $childDef)
  {
    return NodeUnifiedRDBMapper::getChildDef($type, $childDef) != null;
  }
  /**
   * Get the definition of a given child type
   * @param type The type to get the definition for
   * @param childDef The child array as defined in the _children key (see NodeRDBMapper::getObjectDefinition())
   * @return The child definition or null if not found
   */
  protected function getChildDef($type, $childDef)
  {
    foreach($childDef as $curChild) {
      if ($curChild['type'] == $type) {
        return $curChild;
      }
    }
    return null;
  }
  /**
   * Check if a given column is a foreign key (used to reference a parent)
   * @param column The column name
   * @return True/False
   */
  protected function isForeignKey($column)
  {
    $nodeDef = $this->getObjectDefinitionImpl();
    // search in parents
    foreach($nodeDef['_parents'] as $parent)
    {
      if ($parent['fk_columns'] == $column) {
        return true;
      }
    }
    return false;
  }
  /**
   * Get the SQL strings for use for the referenced values based on the given strings
   * @param attribStr The SQL attribute string to append to
   * @param tableStr The SQL table string to append to
   * @param condStr The SQL condition string to append to
   * @param orderStr The SQL order string to append to
   * @param attribs The attributes to select or null to select all
   * @return An assoziative array with the following keys 'attribStr', 'tableStr', 'condStr', 'orderStr' which hold the updated strings
   */
  protected function getSQLForRefs($attribStr, $tableStr, $condStr, $orderStr, $attribs=null)
  {
    // references
    $joinStr = '';
    $aliasIndex = 0;
    $tableName = $this->getTableName();
    $referencedTables = array();

    $attributeDescriptions = $this->getAttributes();
    foreach($attributeDescriptions as $curAttributeDesc) {
    {
      if ($curAttributeDesc instanceof ReferenceDescription)
      {
        $curReferenceDesc = $curAttributeDesc;
        if ($attribs == null || in_array($curReferenceDesc->name, $attribs))
        {
          $referencedType = $curReferenceDesc->otherType;
          $referencedValue = $curReferenceDesc->otherName;
          $relationDesc = $this->getRelation($referencedType);
          $otherMapper = PersistenceFacade::getInstance()->getMapper($relationDesc->otherType);
          if ($otherMapper)
          {
            $otherAttributeDesc = $otherMapper->getAttribute($referencedValue);
            if ($otherAttributeDesc instanceof RDBAttributeDescription)
            {
              $attribStr .= $relationDesc->otherTable.".".$otherAttributeDesc->column." AS ".$this->quote($curReferenceDesc->name).", ";
              if (!in_array($relationDesc->otherTable, $referencedTables))
              {
                if ($relationDesc instanceof RDBManyToOneRelationDescription)
                {
                  // reference from parent
                  $joinStr .= " LEFT JOIN ".$this->_dbPrefix.$relationDesc->otherTable." ON ".
                          $this->_dbPrefix.$relationDesc->otherTable.".".$relationDesc->idColumn."=".
                          $this->_dbPrefix.$relationDesc->thisTable.".".$relationDesc->fkColumn;
                }
                else if ($relationDesc instanceof RDBOneToManyRelationDescription)
                {
                  // reference from child
                    $joinStr .= " LEFT JOIN ".$this->_dbPrefix.$relationDesc->otherTable." ON ".
                            $this->_dbPrefix.$relationDesc->otherTable.".".$relationDesc->fkColumn."=".
                            $this->_dbPrefix.$relationDesc->thisTable.".".$relationDesc->idColumn;
                  }
                }
                array_push($referencedTables, $relationDesc->otherTable);
              }
              $condStr = str_replace($curReferenceDesc->name, $relationDesc->otherTable.".".$otherAttributeDesc->column, $condStr);

              // add orderby, if reference from child
              if (sizeof($otherMapper->getDefaultOrder()) > 0)
              {
                $tmpOrderStr = '';
                foreach($otherMapper->getDefaultOrder() as $orderBy)
                {
                  if (strlen($orderBy)) {
                    $tmpOrderStr .= $relationDesc->otherTable.".".$orderBy.", ";
                  }
                }
                $orderStr .= $this->translateAppToDatabase($tmpOrderStr);
              }
            }
          }
          $aliasIndex++;
        }
      }
    }
    $attribStr = StringUtil::removeTrailingComma($attribStr);
    $tableStr .= $joinStr;
    $orderStr = StringUtil::removeTrailingComma($orderStr);
    return array('attribStr' => $attribStr, 'tableStr' => $tableStr, 'condStr' => $condStr, 'orderStr' => $orderStr);
  }
  /**
   * Replace all attribute and type occurences to columns and table name
   * @param str The string to translate (e.g. an orderby clause)
   * @param alias The alias for the table name (default: null uses none).
   * @return The translated string
   */
  protected function translateAppToDatabase($str, $alias=null)
  {
    // replace application attribute/table names with sql names
    $attributeDescriptions = $this->getAttributes();
    foreach($attributeDescriptions as $curAttributeDesc)
    {
      if (!($curAttributeDesc instanceof ReferenceDescription)) {
        $str = preg_replace('/\b'.$curAttributeDesc->name.'\b/', $curAttributeDesc->column, $str);
      }
    }
    if ($alias != null) {
      $tableName = $alias;
    }
    else {
      $tableName = $this->getTableName();
    }
    $str = preg_replace('/\b'.$this->getType().'\b/', $tableName, $str);
    return $str;
  }
  /**
   * Get the name of a column
   * @param attributeName The name of the corresponding attribute
   * @param dataType The data type of the attribute
   * @return The name of the column or null if not existent
   */
  protected function getColumnName($attributeName, $dataType=null)
  {
    $nodeDef = $this->getObjectDefinitionImpl();
    // search in attributes
    foreach($nodeDef['_datadef'] as $dataItem)
    {
      if ($dataItem['name'] == $attributeName && ($dataType == null || ($dataType != null && $dataType == $dataItem['app_data_type']))) {
        return $dataItem['column_name'];
      }
    }
    // search refs
    foreach($nodeDef['_ref'] as $dataItem)
    {
      if ($dataItem['name'] == $attributeName) {
        return null;
      }
    }
    throw new PersistenceException($this->getType()." has no attribute ".$attributeName, __FILE__, __LINE__);
  }
  /**
   * Get the name of an attribute
   * @param columnName The name of the corresponding column
   * @return The name of the attribute or null if not existent
   */
  protected function getAttributeName($columnName)
  {
    $attributeDescs = $this->getAttributes();
    // search in attributes
    foreach($attributeDescs as $attributeDesc)
    {
      if ($attributeDesc instanceof RDBAttributeDescription && $attributeDesc->column == $columnName) {
        return $attributeDesc->name;
      }
    }
    throw new PersistenceException($this->getType()." has no column ".$columnName);
  }
  /**
   * Get the name of the foreign key column defined in a given child type that connects from that type to the primary key column
   * of this type.
   * @param childType The child type
   * @param isRequired True/False wether it is required to find a fk column or not
   * @return The name of the column
   */
   /*
  function getChildFKColumnName($childType, $isRequired=true)
  {
    $nodeDef = $this->getObjectDefinitionImpl();
    foreach($nodeDef['_children'] as $childDef)
    {
      if ($childDef['type'] == $childType) {
        return $childDef['fk_columns'];
      }
    }
    if ($isRequired) {
      WCMFException::throwEx("No foreign key name found for '".$childType."' in '".$this->getType()."'", __FILE__, __LINE__);
    }
  }
  */
  /**
   * Get the name of the foreign key column defined in this type that connects to the primary key column
   * of a parent type.
   * @param parentRole The current role of the parent object
   * @param role The current role of the object that defines the foreign key
   * @param isRequired True/False wether it is required to find a fk column or not
   * @return The name of the column
   */
  protected function getFKColumnName($parentRole, $role, $isRequired=true)
  {
    // check if parent type is listed
    $fkName = $this->getFKColumnNameImpl($parentRole, $role);
    if (strlen($fkName) > 0) {
      return $fkName;
    }
    if ($isRequired) {
      WCMFException::throwEx("No foreign key name found for parent role '".$parentRole."' and role '".$role."' in '".$this->getType()."'", __FILE__, __LINE__);
    }
  }
  /**
   * Quote a value to be inserted into the database
   * @param value The value to quote
   * @return The quoted value
   */
  protected function quote($value)
  {
    $conn = $this->getConnection();
    if ($value == null) {
      return 'null';
    }
    else {
      return $conn->qstr($value);
    }
  }

  /**
   * ChangeListener interface implementation
   */

  /**
   * @see ChangeListener::getId()
   */
  public function getId()
  {
    return __CLASS__;
  }
  /**
   * @see ChangeListener::valueChanged()
   */
  public function valueChanged(PersistentObject $object, $name, $type, $oldValue, $newValue) {}
  /**
   * @see ChangeListener::propertyChanged()
   */
  public function propertyChanged(PersistentObject $object, $name, $oldValue, $newValue)
  {
    if ($name == 'parentoids')
    {
      $nodeDef = $this->getObjectDefinitionImpl();

      // update the foreign key column values
      foreach($newValue as $newOID)
      {
        $oidParts = PersistenceFacade::decomposeOID($newOID);
        foreach($nodeDef['_parents'] as $curParent)
        {
          if ($curParent['type'] == $oidParts['type']) {
            $object->setValue($curParent['fk_columns'], $oidParts['id'][0]);
          }
        }
      }
    }
  }
  /**
   * @see ChangeListener::stateChanged()
   */
  public function stateChanged(PersistentObject $object, $oldValue, $newValue) {}

  /**
   * TEMPLATE METHODS
   * Subclasses must implement this method to define their object type.
   */

  /**
   * Get the name of the database table, where this type is mapped to
   * @return The name of the table
   */
  abstract protected function getTableName();
}
?>
