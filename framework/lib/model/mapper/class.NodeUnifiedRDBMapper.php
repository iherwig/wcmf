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
require_once(WCMF_BASE."wcmf/lib/util/class.StringUtil.php");
require_once(WCMF_BASE."wcmf/lib/model/class.Node.php");
require_once(WCMF_BASE."wcmf/lib/model/mapper/class.RDBMapper.php");
require_once(WCMF_BASE."wcmf/lib/model/mapper/class.RDBAttributeDescription.php");
require_once(WCMF_BASE."wcmf/lib/model/mapper/class.RDBManyToManyRelationDescription.php");
require_once(WCMF_BASE."wcmf/lib/model/mapper/class.RDBManyToOneRelationDescription.php");
require_once(WCMF_BASE."wcmf/lib/model/mapper/class.RDBOneToManyRelationDescription.php");
require_once(WCMF_BASE."wcmf/lib/model/mapper/class.ReferenceDescription.php");

/**
 * @class NodeUnifiedRDBMapper
 * @ingroup Mapper
 * @brief NodeUnifiedRDBMapper maps Node objects to a relational database schema where each Node
 * type has its own table.
 * The wCMFGenerator uses this class as base class for all mappers.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class NodeUnifiedRDBMapper extends RDBMapper
{
  private $_fkRelations = null;

  /**
   * @see PersistenceMapper::initialize()
   */
  public function initialize(PersistentObject $object) {}

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
      if (!$this->isForeignKey($pkName))
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
  public function getSelectSQL($condStr, $alias=null, $orderStr=null, $attribs=null, $asArray=false)
  {
    $connection = $this->getConnection();
    $selectStmt = $connection->select();

    // table
    $tableName = $this->getRealTableName();
    if ($alias != null) {
      $selectStmt->from(array($alias => $tableName), '');
      $tableName = $alias;
    }
    else {
      $selectStmt->from($tableName, '');
    }

    // columns
    $columns = array();
    $attributeDescs = $this->getAttributes();
    foreach($attributeDescs as $curAttributeDesc)
    {
      if (!($curAttributeDesc instanceof ReferenceDescription))
      {
        if ($attribs == null || in_array($curAttributeDesc->name, $attribs)) {
          $selectStmt->columns(array($curAttributeDesc->name => $curAttributeDesc->column), $tableName);
        }
      }
    }

    // references
    $this->getReferencesSQL($selectStmt, $tableName, $attribs);

    // condition
    $condStr = $this->translateAppToDatabase($condStr, $alias);
    if (strlen($condStr) == 0) {
      $condStr = "1";
    }
    $selectStmt->where($condStr);
    
    // order by
    $orderBy = array();
    if (strlen($orderStr) > 0)
    {
      $orderStr = $this->translateAppToDatabase($orderStr, $alias);
      $orderBy[] = $orderStr;
    }
    else
    {
      // use default ordering
      $orderByExpressions = $this->getDefaultOrder();
      if (is_array($orderByExpressions))
      {
        foreach($orderByExpressions as $orderByExpression)
        {
          list($attribute, $direction) = split(' ', $orderByExpression);
          $attribute = trim($attribute);
          if (strlen($attribute) > 0) {
            $attribute = $this->translateAppToDatabase($attribute);
            $orderBy[] = trim($tableName.".".$attribute." ".$direction);
          }
        }
      }
    }
    if (sizeof($orderBy) > 0) {
      $selectStmt->order($orderBy);
    }

    return $selectStmt->__toString();
  }
  /**
   * @see RDBMapper::getRelationSelectSQL()
   */
  protected function getRelationSelectSQL(PersistentObjectProxy $object, $relationDescription)
  {
    $persistenceFacade = PersistenceFacade::getInstance();
    $result = array();
    $oid = $object->getOID();

    if ($relationDescription->otherNavigability)
    {
      if ($relationDescription instanceof RDBOneToManyRelationDescription)
      {
        $dbid = $oid->getFirstId();
        $otherMapper = $persistenceFacade->getMapper($relationDescription->otherType);
        $otherAttr = $otherMapper->getAttribute($relationDescription->fkName);
        $sqlStr = $otherAttr->table.".".$otherAttr->name."=".$this->quote($dbid);
        $result = array('role' => $relationDescription->otherRole, 'type' => $relationDescription->otherType, 'criteria' => $sqlStr);
      }
      elseif ($relationDescription instanceof RDBManyToOneRelationDescription)
      {
        $otherMapper = $persistenceFacade->getMapper($relationDescription->otherType);
        $otherAttr = $otherMapper->getAttribute($relationDescription->idName);
        $sqlStr = $otherAttr->table.".".$otherAttr->name."=".$this->quote($object->getValue($relationDescription->fkName));
        $result = array('role' => $relationDescription->otherRole, 'type' => $relationDescription->otherType, 'criteria' => $sqlStr);
      }
      elseif ($relationDescription instanceof RDBManyToManyRelationDescription)
      {
        $thisRelDesc = $relationDescription->thisEndRelation;
        $otherRelDesc = $relationDescription->otherEndRelation;

        $dbid = $oid->getFirstId();
        $nmMapper = $persistenceFacade->getMapper($thisRelDesc->otherType);
        $thisFkAttr = $nmMapper->getAttribute($thisRelDesc->fkName);
        $otherMapper = $persistenceFacade->getMapper($otherRelDesc->otherType);
        $otherFkAttr = $nmMapper->getAttribute($otherRelDesc->fkName);
        $otherIdAttr = $otherMapper->getAttribute($otherRelDesc->idName);
        $sqlStr = $thisFkAttr->table.".".$thisFkAttr->name."=".$this->quote($dbid)." AND ".
                $otherFkAttr->table.".".$otherFkAttr->name."=".$otherIdAttr->table.".".$otherIdAttr->name;
        $result = array('role' => $otherRelDesc->otherRole, 'type' => $otherRelDesc->otherType, 'criteria' => $sqlStr);
      }
    }
    return $result;
  }
  /**
   * @see RDBMapper::getRelationObjectSelectSQL()
   */
  protected function getRelationObjectSelectSQL(PersistentObject $object, PersistentObject $relative,
    RDBManyToManyRelationDescription $relationDesc)
  {
    $persistenceFacade = PersistenceFacade::getInstance();

    $nmMapper = $persistenceFacade->getMapper($relationDesc->thisEndRelation->otherType);

    $thisId = $object->getOID()->getFirstId();
    $otherId = $relative->getOID()->getFirstId();
    $thisFkAttr = $nmMapper->getAttribute($relationDesc->thisEndRelation->fkName);
    $otherFkAttr = $nmMapper->getAttribute($relationDesc->otherEndRelation->fkName);

    $condStr = $thisFkAttr->table.".".$thisFkAttr->name."=".$this->quote($thisId)." AND ".
      $otherFkAttr->table.".".$otherFkAttr->name."=".$this->quote($otherId);
    return $nmMapper->getSelectSQL($condStr);
  }
  /**
   * @see RDBMapper::getChildrenDisassociateSQL()
   */
  protected function getChildrenDisassociateSQL(ObjectId $oid, $sharedOnly=false)
  {
    $persistenceFacade = PersistenceFacade::getInstance();
    $sqlArray = array();
    $dbid = $oid->getFirstId();
    $childDescs = $this->getRelations('child');
    foreach($childDescs as $curChildDesc)
    {
      if (!$sharedOnly || ($sharedOnly && $curChildDesc->thisAggregationKind != 'composite'))
      {
        $childMapper = $persistenceFacade->getMapper($curChildDesc->otherType);
        $fkAttr = $childMapper->getAttribute($curChildDesc->fkName);
        array_push($sqlArray, "UPDATE ".$childMapper->getRealTableName()." SET ".$fkAttr->name."=NULL WHERE ".
          $fkAttr->name."=".$this->quote($dbid).";");
      }
    }
    return $sqlArray;
  }
  /**
   * @see RDBMapper::getInsertSQL()
   */
  protected function getInsertSQL(PersistentObject $object)
  {
    $insertedAttributes = array();
    $attribNameStr = '';
    $attribValueStr = '';
    $tableName = $this->getRealTableName();

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
        $attribNameStr .= $tableName.".".$pkName.", ";
        $attribValueStr .= $this->quote($pkValue).", ";
        array_push($insertedAttributes, $pkName);
      }
    }

    // foreign key definition
    $fkDescs = $this->getForeignKeyRelations();
    for ($i=0, $count=sizeof($fkDescs); $i<$count; $i++)
    {
      $fkDesc = $fkDescs[$i];
      $parents = $object->getValue($fkDesc->otherRole);
      if (is_array($parents))
      {
        // in a ManyToOneRelation only one parent is possible
        $parent = $parents[0];
        $poid = $parent->getOID();
        if (ObjectId::isValid($poid))
        {
          $fkAttr = $this->getAttribute($fkDesc->fkName);
          $attribName = $fkAttr->name;

          $attribNameStr .= $tableName.".".$attribName.", ";
          $attribValueStr .= $this->quote($poid->getFirstId()).", ";
          array_push($insertedAttributes, $attribName);
        }
      }
    }

    // attribute definition
    $attributeDescs = $this->getAttributes();
    foreach($attributeDescs as $curAttributeDesc)
    {
      if (!($curAttributeDesc instanceof ReferenceDescription))
      {
        // insert only attributes that are defined in node
        $attribName = $curAttributeDesc->name;
        if ($object->hasValue($attribName))
        {
          // don't insert the same attribute twice
          if (!in_array($attribName, $insertedAttributes))
          {
            $attribNameStr .= $tableName.".".$curAttributeDesc->column.", ";
            $attribValueStr .= $this->quote($object->getValue($attribName)).", ";
            array_push($insertedAttributes, $attribName);
          }
        }
      }
    }
    $attribNameStr = StringUtil::removeTrailingComma($attribNameStr);
    $attribValueStr = StringUtil::removeTrailingComma($attribValueStr);

    // query
    $sqlArray = array
    (
      "INSERT INTO ".$tableName." (".$attribNameStr.") VALUES (".$attribValueStr.");"
    );
    return $sqlArray;
  }
  /**
   * @see RDBMapper::getUpdateSQL()
   */
  protected function getUpdateSQL(PersistentObject $object)
  {
    $updatedAttributes = array();
    $attribStr = '';
    $tableName = $this->getRealTableName();

    // primary key definition
    $pkStr = $this->createPKCondition($object->getOID());

    // foreign key definition
    $fkDescs = $this->getForeignKeyRelations();
    for ($i=0, $count=sizeof($fkDescs); $i<$count; $i++)
    {
      $fkDesc = $fkDescs[$i];
      $parents = $object->getValue($fkDesc->otherRole);
      if (is_array($parents) && sizeof($parents) == 1)
      {
        // in a ManyToOneRelation only one parent is possible
        $parent = $parents[0];
        $poid = $parent->getOID();
        if (ObjectId::isValid($poid))
        {
          $fkAttr = $this->getAttribute($fkDesc->fkName);
          $attribName = $fkAttr->name;

          $attribStr .= $tableName.".".$attribName."=".$this->quote($poid->getFirstId()).", ";
          array_push($updatedAttributes, $attribName);

        }
      }
    }

    // attribute definition
    $attributeDescs = $this->getAttributes();
    foreach($attributeDescs as $curAttributeDesc)
    {
      if (!($curAttributeDesc instanceof ReferenceDescription))
      {
        // update only attributes that are defined in node
        $attribName = $curAttributeDesc->name;
        if ($object->hasValue($attribName))
        {
          // don't insert the same attribute twice
          if (!in_array($attribName, $updatedAttributes))
          {
            $attribStr .= $tableName.".".$curAttributeDesc->column."=".$this->quote($object->getValue($attribName)).", ";
            array_push($updatedAttributes, $attribName);
          }
        }
      }
    }
    $attribStr = StringUtil::removeTrailingComma($attribStr);

    // query
    if (strlen($attribStr) > 0) {
      $sqlArray = array
      (
        "UPDATE ".$tableName." SET ".$attribStr." WHERE ".$pkStr.";"
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
      "DELETE FROM ".$this->getRealTableName()." WHERE ".$pkStr.";"
    );
    return $sqlArray;
  }
  /**
   * Get the SQL strings for use for the referenced values based on the given strings
   * @param selectStmt The select statement (instance of Zend_Db_Select)
   * @param tableName The name for this table (the alias, if used).
   * @param attribs The attributes to select or null to select all
   * @return The select statement
   */
  protected function getReferencesSQL($selectStmt, $tableName, $attribs=null)
  {
    $persistenceFacade = PersistenceFacade::getInstance();

    // collect all references first
    $references = array();
    $attributeDescs = $this->getAttributes();
    foreach($attributeDescs as $curAttributeDesc)
    {
      if ($curAttributeDesc instanceof ReferenceDescription)
      {
        $curReferenceDesc = $curAttributeDesc;
        if ($attribs == null || in_array($curReferenceDesc->name, $attribs))
        {
          $referencedType = $curReferenceDesc->otherType;
          $referencedValue = $curReferenceDesc->otherName;
          $relationDesc = $this->getRelation($referencedType);
          $otherMapper = $persistenceFacade->getMapper($relationDesc->otherType);
          if ($otherMapper)
          {
            $otherTable = $otherMapper->getRealTableName();
            $otherAttributeDesc = $otherMapper->getAttribute($referencedValue);
            if ($otherAttributeDesc instanceof RDBAttributeDescription)
            {
              // set up the join definition if not already defined
              if (!isset($references[$otherTable]))
              {
                $references[$otherTable] = array();
                $references[$otherTable]['attributes'] = array();

                // determine the join condition
                if ($relationDesc instanceof RDBManyToOneRelationDescription)
                {
                  // reference from parent
                  $thisAttr = $this->getAttribute($relationDesc->fkName);
                  $otherAttr = $otherMapper->getAttribute($relationDesc->idName);
                }
                else if ($relationDesc instanceof RDBOneToManyRelationDescription)
                {
                  // reference from child
                  $thisAttr = $this->getAttribute($relationDesc->idName);
                  $otherAttr = $otherMapper->getAttribute($relationDesc->fkName);
                }
                $joinCond = $this->quoteIdentifier($tableName).".".$this->quoteIdentifier($thisAttr->name).
                        "=".$this->quoteIdentifier($otherTable).".".$this->quoteIdentifier($otherAttr->name);
                $references[$otherTable]['joinCond'] = $joinCond;

                // add orderby, if reference from child
                if ($relationDesc instanceof RDBOneToManyRelationDescription)
                {
                  $defaultOrder = $otherMapper->getDefaultOrder();
                  if (sizeof($defaultOrder) > 0)
                  {
                    $orderArray = array();
                    foreach($defaultOrder as $orderBy)
                    {
                      if (strlen($orderBy) > 0) {
                        $orderArray[] = $otherMapper->translateAppToDatabase($otherTable.".".$orderBy);
                      }
                    }
                    $references[$otherTable]['orderBy'] = $orderArray;
                  }
                }
              }

              // add the attributes
              $references[$otherTable]['attributes'][$curReferenceDesc->name] = $otherAttributeDesc->column;
            }
          }
        }
      }
    }
    // add references from each referenced table
    foreach($references as $otherTable => $curReference)
    {
      $selectStmt->joinLeft($otherTable, $curReference['joinCond'], $curReference['attributes']);
      if (isset($curReference['orderBy'])) {
        $selectStmt->order($curReference['orderBy']);
      }
    }
    return $selectStmt;
  }
  /**
   * @see RDBMapper::createPKCondition()
   */
  protected function createPKCondition(ObjectId $oid)
  {
    $str = '';
    $tableName = $this->getRealTableName();
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
   * Get all foreign key relations (used to reference a parent)
   * @return An array of RDBManyToOneRelationDescription instances
   */
  public function getForeignKeyRelations()
  {
    if ($this->_fkRelations == null)
    {
      $this->_fkRelations = array();
      $relationDescs = $this->getRelations();
      foreach($relationDescs as $relationDesc)
      {
        if ($relationDesc instanceof RDBManyToOneRelationDescription) {
          $this->_fkRelations[] = $relationDesc;
        }
      }
    }
    return $this->_fkRelations;
  }
  /**
   * Check if a given attribute is a foreign key (used to reference a parent)
   * @param name The attribute name
   * @return True/False
   */
  public function isForeignKey($name)
  {
    $fkDescs = $this->getForeignKeyRelations();
    foreach($fkDescs as $fkDesc)
    {
      if ($fkDesc->fkName == $name) {
        return true;
      }
    }
    return false;
  }
  /**
   * Replace all attribute and type occurences to columns and table name
   * @param str The string to translate (e.g. an orderby clause)
   * @param alias The alias for the table name (default: null uses none).
   * @return The translated string
   */
  public function translateAppToDatabase($str, $alias=null)
  {
    // replace application attribute/table names with sql names
    $attributeDescs = $this->getAttributes();
    foreach($attributeDescs as $curAttributeDesc)
    {
      if (!($curAttributeDesc instanceof ReferenceDescription)) {
        $str = preg_replace('/'.$this->quoteIdentifier($curAttributeDesc->name).'/',
                $this->quoteIdentifier($curAttributeDesc->column), $str);
      }
    }
    if ($alias != null) {
      $tableName = $alias;
    }
    else {
      $tableName = $this->getRealTableName();
    }
    $str = preg_replace('/'.$this->quoteIdentifier($this->getType()).'/',
            $this->quoteIdentifier($tableName), $str);
    return $str;
  }
  /**
   * Quote a value to be inserted into the database
   * @param value The value to quote
   * @return The quoted value
   */
  protected function quote($value)
  {
    if ($value === null) {
      return 'null';
    }
    else
    {
      $conn = $this->getConnection();
      return $conn->quote($value);
    }
  }
}
?>
