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
   * @see RDBMapper::prepareForStorage()
   */
  protected function prepareForStorage(PersistentObject $object)
  {
    $oldState = $object->getState();

    // set primary key values
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

    // handle relations
    if ($object instanceof Node)
    {
      $persistenceFacade = PersistenceFacade::getInstance();

      // added nodes
      $addedNodes = $object->getAddedNodes();
      foreach ($addedNodes as $role => $oids)
      {
        $relationDesc = $this->getRelation($role);
        // for a many to one relation, we need to update the appropriate
        // foreign key in the object
        if ($relationDesc instanceof RDBManyToOneRelationDescription)
        {
          // in a many to one only one parent is possible
          // so we take the last oid
          $poid = array_pop($oids);
          if (ObjectId::isValid($poid))
          {
            // set the foreign key to the parent id value
            $fkAttr = $this->getAttribute($relationDesc->getFkName());
            $object->setValue($fkAttr->getName(), $poid->getFirstId());
          }
        }
        elseif ($relationDesc instanceof RDBManyToManyRelationDescription)
        {
          // in a many to many relation we have to create the relation object
          // if it does not exist
          $relatives = $object->getChildrenEx(null, $relationDesc->getOtherRole());
          foreach ($relatives as $relative)
          {
            // check if the relation already exists
            $nmObjects = $this->loadRelationObjects(PersistentObjectProxy::fromObject($object),
                PersistentObjectProxy::fromObject($relative), $relationDesc);
            if (sizeof($nmObjects) == 0)
            {
              $thisEndRelation = $relationDesc->getThisEndRelation();
              $otherEndRelation = $relationDesc->getOtherEndRelation();
              $nmType = $thisEndRelation->getOtherType();
              $nmMapper = $persistenceFacade->getMapper($nmType);
              // don't use PersistenceFacade::create to instantiate the object,
              // because it would be attached to the transaction, but we want
              // to save it explicitly (see below)
              $nmObj = $nmMapper->create($nmType);
              // add the parent nodes to the many to many object, don't
              // update the other side of the relation, because there may be no
              // relation defined to the many to many object
              $nmObj->addNode($object, $thisEndRelation->getThisRole(), true, true, false);
              $nmObj->addNode($relative, $otherEndRelation->getOtherRole(), true, true, false);
              // this relation must be saved immediatly, in order to be
              // available when the other side of the relation is processed
              // (otherwise two objects would be inserted)
              $nmMapper->save($nmObj);
            }
          }
        }
      }

      // deleted nodes
      $deletedNodes = $object->getDeletedNodes();
      foreach ($deletedNodes as $role => $oids)
      {
        $relationDesc = $this->getRelation($role);
        // for a many to one relation, we need to update the appropriate
        // foreign key in the object
        if ($relationDesc instanceof RDBManyToOneRelationDescription)
        {
          // in a many to one only one parent is possible
          // so we take the last oid
          $poid = array_pop($oids);
          if (ObjectId::isValid($poid))
          {
            // set the foreign key to the null
            $fkAttr = $this->getAttribute($relationDesc->getFkName());
            $object->setValue($fkAttr->getName(), new Zend_Db_Expr('NULL'));
          }
        }
        elseif ($relationDesc instanceof RDBManyToManyRelationDescription)
        {
          // in a many to many relation we have to delete the relation object
          // if it does exist
          foreach ($oids as $relativeOid)
          {
            // check if the relation exists
            $nmObjects = $this->loadRelationObjects(PersistentObjectProxy::fromObject($object),
                    new PersistentObjectProxy($relativeOid), $relationDesc);
            foreach ($nmObjects as $nmObj) {
              // this relation can be deleted immediatly, in order to be
              // already deleted when the other side of the relation is processed
              // (otherwise we would try to delete it twice)
              $nmObj->getMapper()->delete($nmObj);
            }
          }
        }
      }
    }
    $object->setState($oldState, false);
  }
  /**
   * @see RDBMapper::getSelectSQL()
   */
  public function getSelectSQL($criteria=null, $alias=null, $orderby=null, $attribs=null)
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
      foreach ($criteria as $curCriteria)
      {
        if ($curCriteria instanceof Criteria)
        {
          $condition = $this->renderCriteria($curCriteria, true, $tableName);
          if ($curCriteria->getCombineOperator() == Criteria::OPERATOR_AND) {
            $selectStmt->where($condition, $curCriteria->getValue());
          }
          else {
            $selectStmt->orWhere($condition, $curCriteria->getValue());
          }
        }
        else {
          throw new IllegalArgumentException("The select condition must be an instance of Criteria");
        }
      }
    }

    // order by
    $orderbyFinal = array();
    if ($orderby == null)
    {
      // use default ordering
      $defaultOrder = $this->getDefaultOrder();
      if ($defaultOrder) {
        $orderby = array($defaultOrder['sortFieldName']." ".$defaultOrder['sortDirection']);
      }
      else {
        $orderby = array();
      }
    }
    foreach($orderby as $orderExpression) {
      $orderbyFinal[] = $this->ensureTablePrefix($orderExpression, $tableName);
    }
    if (sizeof($orderby) > 0) {
      $selectStmt->order($orderbyFinal);
    }

    return $selectStmt;
  }
  /**
   * @see RDBMapper::getRelationSelectSQL()
   */
  protected function getRelationSelectSQL(PersistentObjectProxy $otherObjectProxy, $otherRole, $attribs=null)
  {
    $connection = $this->getConnection();
    $selectStmt = $connection->select();

    $relationDescription = $this->getRelation($otherRole);
    if ($relationDescription->getOtherNavigability())
    {
      $persistenceFacade = PersistenceFacade::getInstance();
      $oid = $otherObjectProxy->getOID();
      $tableName = $this->getRealTableName();

      if ($relationDescription instanceof RDBManyToOneRelationDescription)
      {
        $thisAttr = $this->getAttribute($relationDescription->getFkName());
        $dbid = $oid->getFirstId();
        if ($dbid === null) {
          $dbid = new Zend_Db_Expr('NULL');
        }

        $selectStmt->from($tableName, '');
        $this->addColumns($selectStmt, $attribs, $tableName);
        $selectStmt->where($this->quoteIdentifier($tableName).".".
                $this->quoteIdentifier($thisAttr->getName())."= ?", $dbid);
        $defaultOrder = $this->getDefaultOrder($otherRole);
        if ($defaultOrder) {
          $selectStmt->order($this->ensureTablePrefix($defaultOrder['sortFieldName']." ".$defaultOrder['sortDirection'],
                  $tableName));
        }
      }
      elseif ($relationDescription instanceof RDBOneToManyRelationDescription)
      {
        $thisAttr = $this->getAttribute($relationDescription->getIdName());
        $fkValue = $otherObjectProxy->getValue($relationDescription->getFkName());
        if ($fkValue === null) {
          $fkValue = new Zend_Db_Expr('NULL');
        }

        $selectStmt->from($tableName, '');
        $this->addColumns($selectStmt, $attribs, $tableName);
        $selectStmt->where($this->quoteIdentifier($tableName).".".
                $this->quoteIdentifier($thisAttr->getName())."= ?", $fkValue);
        $defaultOrder = $this->getDefaultOrder($otherRole);
        if ($defaultOrder) {
          $selectStmt->order($this->ensureTablePrefix($defaultOrder['sortFieldName']." ".$defaultOrder['sortDirection'],
                  $tableName));
        }
      }
      elseif ($relationDescription instanceof RDBManyToManyRelationDescription)
      {
        $thisRelationDesc = $relationDescription->getThisEndRelation();
        $otherRelationDesc = $relationDescription->getOtherEndRelation();

        $dbid = $oid->getFirstId();
        if ($dbid === null) {
          $dbid = new Zend_Db_Expr('NULL');
        }
        $nmMapper = $persistenceFacade->getMapper($thisRelationDesc->getOtherType());
        $otherFkAttr = $nmMapper->getAttribute($otherRelationDesc->getFkName());
        $thisFkAttr = $nmMapper->getAttribute($thisRelationDesc->getFkName());
        $thisIdAttr = $this->getAttribute($thisRelationDesc->getIdName());
        $nmTablename = $nmMapper->getRealTableName();

        $selectStmt->from($tableName, '');
        $this->addColumns($selectStmt, $attribs, $tableName);

        $joinCond = $this->quoteIdentifier($nmTablename).".".$this->quoteIdentifier($thisFkAttr->getName())."=".
                $this->quoteIdentifier($tableName).".".$this->quoteIdentifier($thisIdAttr->getName());
        $selectStmt->join($nmTablename, $joinCond, array());
        $selectStmt->where($this->quoteIdentifier($nmTablename).".".
                $this->quoteIdentifier($otherFkAttr->getName())."= ?", $dbid);
        $defaultOrder = $nmMapper->getDefaultOrder($otherRole);
        if ($defaultOrder) {
          $selectStmt->order($this->ensureTablePrefix($defaultOrder['sortFieldName']." ".$defaultOrder['sortDirection'],
                  $nmTablename));
        }
      }
    }
    return $selectStmt;
  }
  /**
   * @see RDBMapper::getInsertSQL()
   */
  protected function getInsertSQL(PersistentObject $object)
  {
    // get the attributes to store
    $values = $this->getPersistentValues($object);

    // operations
    $insertOp = new InsertOperation($this->getType(), $values);
    $operations = array(
        $insertOp
    );
    return $operations;
  }
  /**
   * @see RDBMapper::getUpdateSQL()
   */
  protected function getUpdateSQL(PersistentObject $object)
  {
    // get the attributes to store
    $values = $this->getPersistentValues($object);

    // primary key definition
    $pkCriteria = $this->createPKCondition($object->getOID());

    // operations
    $updateOp = new UpdateOperation($this->getType(), $values, $pkCriteria);
    $operations = array(
        $updateOp
    );
    return $operations;
  }
  /**
   * @see RDBMapper::getDeleteSQL()
   */
  protected function getDeleteSQL(ObjectId $oid)
  {
    // primary key definition
    $pkCriteria = $this->createPKCondition($oid);

    // operations
    $deleteOp = new DeleteOperation($this->getType(), $pkCriteria);
    $operations = array(
        $deleteOp
    );
    return $operations;
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
    }
    return $selectStmt;
  }
  /**
   * Get an associative array of attribute name-value pairs to be stored for a
   * given oject (primary keys and references are not included)
   * @param object The PeristentObject.
   * @return Array
   */
  protected function getPersistentValues(PersistentObject $object)
  {
    $values = array();

    // attribute definitions
    $attributeDescs = $this->getAttributes();
    foreach($attributeDescs as $curAttributeDesc)
    {
      if (!($curAttributeDesc instanceof ReferenceDescription))
      {
        // add only attributes that are defined in the object
        $attribName = $curAttributeDesc->getName();
        if ($object->hasValue($attribName)) {
          $values[$attribName] = $object->getValue($attribName);
        }
      }
    }

    return $values;
  }
  /**
   * Load the relation objects in a many to many relation from the database.
   * @param objectProxy The proxy at this end of the relation.
   * @param relativeProxy The proxy at the other end of the relation.
   * @param relationDesc The RDBManyToManyRelationDescription instance describing the relation.
   * @return Array of PersistentObject instances
   */
  protected function loadRelationObjects(PersistentObjectProxy $objectProxy,
          PersistentObjectProxy $relativeProxy, RDBManyToManyRelationDescription $relationDesc)
  {
    $persistenceFacade = PersistenceFacade::getInstance();

    $nmMapper = $persistenceFacade->getMapper($relationDesc->getThisEndRelation()->getOtherType());

    $thisId = $objectProxy->getOID()->getFirstId();
    $otherId = $relativeProxy->getOID()->getFirstId();
    $thisFkAttr = $nmMapper->getAttribute($relationDesc->getThisEndRelation()->getFkName());
    $otherFkAttr = $nmMapper->getAttribute($relationDesc->getOtherEndRelation()->getFkName());

    $criteria1 = new Criteria($nmMapper->getType(), $thisFkAttr->getName(), "=", $thisId);
    $criteria2 = new Criteria($nmMapper->getType(), $otherFkAttr->getName(), "=", $otherId);
    $criteria = array($criteria1, $criteria2);
    $nmObjects = $nmMapper->loadObjects($nmMapper->getType(), BUILDDEPTH_SINGLE, $criteria);
    return $nmObjects;
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
  protected function getForeignKeyRelations()
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
   * @note Public in order to be callable by ObjectQuery
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
  /**
   * Make sure that the given table name is prefixed before the given expression
   * and return the modified expression.
   * @param expression The expression
   * @param tableName The table name
   * @return String
   */
  protected function ensureTablePrefix($expression, $tableName)
  {
    if (strpos($expression, '.') === false) {
      $expression = $tableName.".".$expression;
    }
    return $expression;
  }
}
?>
