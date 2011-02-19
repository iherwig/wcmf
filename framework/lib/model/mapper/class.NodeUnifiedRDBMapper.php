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
  protected function initialize(PersistentObject $object) {}

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
  protected function getSelectSQL($criteria=null, $alias=null, $orderby=null, $attribs=null)
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
    $this->addColumns($selectStmt, $attribs, $tableName);

    // condition
    if ($criteria != null)
    {
      foreach ($criteria as $curCriteria) {
        $selectStmt->where($this->quoteIdentifier($tableName).".".$this->quoteIdentifier($curCriteria->getAttribute()).
                " ".$curCriteria->getOperator()." (?)", $curCriteria->getValue());
      }
    }

    // order by
    $orderbyFinal = array();
    if ($orderby == null)
    {
      // use default ordering
      $orderby = $this->getDefaultOrder();
    }
    foreach($orderby as $orderExpression)
    {
      // add the table name if missing
      if (strpos($orderExpression, '.') === false) {
        $orderExpression = $tableName.".".$orderExpression;
      }
      $orderbyFinal[] = $orderExpression;
    }
    if (sizeof($orderby) > 0) {
      $selectStmt->order($orderbyFinal);
    }

    return $selectStmt;
  }
  /**
   * @see RDBMapper::getRelationSelectSQL()
   */
  protected function getRelationSelectSQL(PersistentObject $otherObject, $otherRole, $attribs=null)
  {
    $connection = $this->getConnection();
    $selectStmt = $connection->select();

    $relationDescription = $this->getRelation($otherRole);
    if ($relationDescription->getOtherNavigability())
    {
      $persistenceFacade = PersistenceFacade::getInstance();
      $oid = $otherObject->getOID();
      $tableName = $this->getRealTableName();

      if ($relationDescription instanceof RDBManyToOneRelationDescription)
      {
        $dbid = $oid->getFirstId();
        $thisAttr = $this->getAttribute($relationDescription->getFkName());

        $selectStmt->from($tableName, '');
        $this->addColumns($selectStmt, $attribs, $tableName);
        $selectStmt->where($this->quoteIdentifier($tableName).".".
                $this->quoteIdentifier($thisAttr->getName())."= ?", $dbid);
      }
      elseif ($relationDescription instanceof RDBOneToManyRelationDescription)
      {
        $thisAttr = $this->getAttribute($relationDescription->getIdName());

        $selectStmt->from($this->getRealTableName(), '');
        $this->addColumns($selectStmt, $attribs, $tableName);
        $selectStmt->where($this->quoteIdentifier($tableName).".".
                $this->quoteIdentifier($thisAttr->getName())."= ?", $otherObject->getValue($relationDescription->getFkName()));
      }
      elseif ($relationDescription instanceof RDBManyToManyRelationDescription)
      {
        $thisRelDesc = $relationDescription->getThisEndRelation();
        $otherRelDesc = $relationDescription->getOtherEndRelation();

        $dbid = $oid->getFirstId();
        $nmMapper = $persistenceFacade->getMapper($thisRelDesc->getOtherType());
        $otherFkAttr = $nmMapper->getAttribute($otherRelDesc->getFkName());
        $thisFkAttr = $nmMapper->getAttribute($thisRelDesc->getFkName());
        $thisIdAttr = $this->getAttribute($thisRelDesc->getIdName());

        $selectStmt->from($this->getRealTableName(), '');
        $this->addColumns($selectStmt, $attribs, $tableName);

        $joinCond = $this->quoteIdentifier($nmMapper->getRealTableName()).".".$this->quoteIdentifier($thisFkAttr->getName())."=".
                $this->quoteIdentifier($tableName).".".$this->quoteIdentifier($thisIdAttr->getName());
        $selectStmt->join($nmMapper->getRealTableName(), $joinCond, array());
        $selectStmt->where($this->quoteIdentifier($nmMapper->getRealTableName()).".".
                $this->quoteIdentifier($otherFkAttr->getName())."= ?", $dbid);
      }
    }
    return $selectStmt;
  }
  /**
   * @see RDBMapper::getRelationObjectSelectSQL()
   */
  protected function getRelationObjectSelectSQL(PersistentObject $object, PersistentObject $relative,
    RDBManyToManyRelationDescription $relationDesc)
  {
    $persistenceFacade = PersistenceFacade::getInstance();

    $nmMapper = $persistenceFacade->getMapper($relationDesc->getThisEndRelation()->getOtherType());

    $thisId = $object->getOID()->getFirstId();
    $otherId = $relative->getOID()->getFirstId();
    $thisFkAttr = $nmMapper->getAttribute($relationDesc->getThisEndRelation()->getFkName());
    $otherFkAttr = $nmMapper->getAttribute($relationDesc->getOtherEndRelation()->getFkName());

    $condStr = $thisFkAttr->getTable().".".$thisFkAttr->getName()."=".$this->quote($thisId)." AND ".
      $otherFkAttr->getTable().".".$otherFkAttr->getName()."=".$this->quote($otherId);
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
      if (!$sharedOnly || ($sharedOnly && $curChildDesc->getThisAggregationKind() != 'composite'))
      {
        $childMapper = $persistenceFacade->getMapper($curChildDesc->getOtherType());
        $fkAttr = $childMapper->getAttribute($curChildDesc->getFkName());
        array_push($sqlArray, "UPDATE ".$childMapper->getRealTableName()." SET ".$fkAttr->getName()."=NULL WHERE ".
          $fkAttr->getName()."=".$this->quote($dbid).";");
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
      $parents = $object->getValue($fkDesc->getOtherRole());
      if (is_array($parents))
      {
        // in a ManyToOneRelation only one parent is possible
        $parent = $parents[0];
        $poid = $parent->getOID();
        if (ObjectId::isValid($poid))
        {
          $fkAttr = $this->getAttribute($fkDesc->getFkName());
          $attribName = $fkAttr->getName();

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
        $attribName = $curAttributeDesc->getName();
        if ($object->hasValue($attribName))
        {
          // don't insert the same attribute twice
          if (!in_array($attribName, $insertedAttributes))
          {
            $attribNameStr .= $tableName.".".$curAttributeDesc->getColumn().", ";
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
      $parents = $object->getValue($fkDesc->getOtherRole());
      if (is_array($parents) && sizeof($parents) == 1)
      {
        // in a ManyToOneRelation only one parent is possible
        $parent = $parents[0];
        $poid = $parent->getOID();
        if (ObjectId::isValid($poid))
        {
          $fkAttr = $this->getAttribute($fkDesc->getFkName());
          $attribName = $fkAttr->getName();

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
        $attribName = $curAttributeDesc->getName();
        if ($object->hasValue($attribName))
        {
          // don't insert the same attribute twice
          if (!in_array($attribName, $updatedAttributes))
          {
            $attribStr .= $tableName.".".$curAttributeDesc->getColumn()."=".$this->quote($object->getValue($attribName)).", ";
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
   * Add the columns to a given select statement.
   * @param selectStmt The select statement (instance of Zend_Db_Select)
   * @param attribs The attributes to select (maybe null to select all)
   * @param tableName The table name
   * @return Zend_Db_Select
   */
  protected function addColumns(Zend_Db_Select $selectStmt, $attribs, $tableName)
  {
    // make sure that at least the primary keys are selected
    if ($attribs !== null) {
      $attribs = array_unique(array_merge($this->getPKNames(), $attribs));
    }

    // columns
    $attributeDescs = $this->getAttributes();
    foreach($attributeDescs as $curAttributeDesc)
    {
      if (!($curAttributeDesc instanceof ReferenceDescription))
      {
        if ($attribs === null || in_array($curAttributeDesc->getName(), $attribs)) {
          $selectStmt->columns(array($curAttributeDesc->getName() => $curAttributeDesc->getColumn()), $tableName);
        }
      }
    }

    // references
    $selectStmt = $this->addReferences($selectStmt, $tableName, $attribs);
    return $selectStmt;
  }
  /**
   * Add the columns and joins to select references to a given select statement.
   * @param selectStmt The select statement (instance of Zend_Db_Select)
   * @param tableName The name for this table (the alias, if used).
   * @param attribs The attributes to select or null to select all
   * @return Zend_Db_Select
   */
  protected function addReferences(Zend_Db_Select $selectStmt, $tableName, $attribs=null)
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
        if ($attribs === null || in_array($curReferenceDesc->getName(), $attribs))
        {
          $referencedType = $curReferenceDesc->getOtherType();
          $referencedValue = $curReferenceDesc->getOtherName();
          $relationDesc = $this->getRelation($referencedType);
          $otherMapper = $persistenceFacade->getMapper($relationDesc->getOtherType());
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
                  $thisAttr = $this->getAttribute($relationDesc->getFkName());
                  $otherAttr = $otherMapper->getAttribute($relationDesc->getIdName());
                }
                else if ($relationDesc instanceof RDBOneToManyRelationDescription)
                {
                  // reference from child
                  $thisAttr = $this->getAttribute($relationDesc->getIdName());
                  $otherAttr = $otherMapper->getAttribute($relationDesc->getFkName());
                }
                $joinCond = $this->quoteIdentifier($tableName).".".$this->quoteIdentifier($thisAttr->getName()).
                        "=".$this->quoteIdentifier($otherTable).".".$this->quoteIdentifier($otherAttr->getName());
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
              $references[$otherTable]['attributes'][$curReferenceDesc->getName()] = $otherAttributeDesc->getColumn();
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
    $criterias = array();
    $type = $this->getType();
    $pkNames = $this->getPKNames();
    $ids = $oid->getId();
    for ($i=0, $count=sizeof($pkNames); $i<$count; $i++)
    {
      $pkValue = $ids[$i];
      $criterias[] = new Criteria($type, $pkNames[$i], "=", $pkValue);
    }
    return $criterias;
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
      if ($fkDesc->getFkName() == $name) {
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
        $str = preg_replace('/'.$this->quoteIdentifier($curAttributeDesc->getName()).'/',
                $this->quoteIdentifier($curAttributeDesc->getColumn()), $str);
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
