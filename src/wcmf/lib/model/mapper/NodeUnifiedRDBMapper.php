<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\model\mapper;

use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\mapper\RDBManyToManyRelationDescription;
use wcmf\lib\model\mapper\RDBManyToOneRelationDescription;
use wcmf\lib\model\mapper\RDBMapper;
use wcmf\lib\model\mapper\RDBOneToManyRelationDescription;
use wcmf\lib\model\mapper\SelectStatement;
use wcmf\lib\model\mapper\SQLConst;
use wcmf\lib\model\Node;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\Criteria;
use wcmf\lib\persistence\DeleteOperation;
use wcmf\lib\persistence\InsertOperation;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PagingInfo;
use wcmf\lib\persistence\PersistentObject;
use wcmf\lib\persistence\PersistentObjectProxy;
use wcmf\lib\persistence\ReferenceDescription;
use wcmf\lib\persistence\RelationDescription;
use wcmf\lib\persistence\UpdateOperation;

/**
 * NodeUnifiedRDBMapper maps Node objects to a relational database schema where each Node
 * type has its own table.
 * The wCMFGenerator uses this class as base class for all mappers.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class NodeUnifiedRDBMapper extends RDBMapper {

  const CACHE_KEY = 'mapper';

  private $_fkRelations = null;

  /**
   * @see RDBMapper::prepareForStorage()
   */
  protected function prepareForStorage(PersistentObject $object) {
    $oldState = $object->getState();

    // set primary key values
    $oid = $object->getOID();
    $ids = $oid->getId();
    $pkNames = $this->getPkNames();
    for($i=0, $count=sizeof($pkNames); $i<$count; $i++) {
      // foreign keys don't get a new id
      $pkName = $pkNames[$i];
      if (!$this->isForeignKey($pkName)) {
        $pkValue = $ids[$i];
        // replace dummy ids with ids provided by the database sequence
        if (ObjectId::isDummyId($pkValue)) {
          $nextId = $this->getNextId();
          $object->setValue($pkName, $nextId);
        }
      }
    }

    if ($oldState == PersistentObject::STATE_NEW) {
      // set the sortkeys to the id value
      if ($this->isSortable()) {
        $value = join('', $object->getOID()->getId());
        foreach ($this->getRelations() as $curRelationDesc) {
          $sortkeyDef = $this->getSortkey($curRelationDesc->getOtherRole());
          if ($sortkeyDef != null) {
            $object->setValue($sortkeyDef['sortFieldName'], $value);
          }
        }
        $sortkeyDef = $this->getSortkey();
        if ($sortkeyDef != null) {
          $object->setValue($sortkeyDef['sortFieldName'], $value);
        }
      }
    }

    // handle relations
    if ($object instanceof Node) {
      $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

      // added nodes
      $addedNodes = $object->getAddedNodes();
      foreach ($addedNodes as $role => $addedNodes) {
        $relationDesc = $this->getRelation($role);
        // for a many to one relation, we need to update the appropriate
        // foreign key in the object
        if ($relationDesc instanceof RDBManyToOneRelationDescription) {
          // in a many to one only one parent is possible
          // so we take the last oid
          $parent = array_pop($addedNodes);
          $poid = $parent->getOID();
          if (ObjectId::isValid($poid)) {
            // set the foreign key to the parent id value
            $fkAttr = $this->getAttribute($relationDesc->getFkName());
            $object->setValue($fkAttr->getName(), $poid->getFirstId());
          }
        }
        elseif ($relationDesc instanceof RDBManyToManyRelationDescription) {
          // in a many to many relation we have to create the relation object
          // if it does not exist
          $relatives = $object->getChildrenEx(null, $relationDesc->getOtherRole());
          foreach ($relatives as $relative) {
            // check if the relation already exists
            $nmObjects = $this->loadRelationObjects(PersistentObjectProxy::fromObject($object),
                PersistentObjectProxy::fromObject($relative), $relationDesc, true);
            if (sizeof($nmObjects) == 0) {
              $thisEndRelation = $relationDesc->getThisEndRelation();
              $otherEndRelation = $relationDesc->getOtherEndRelation();
              $nmType = $thisEndRelation->getOtherType();
              $nmObj = $persistenceFacade->create($nmType);
              // add the parent nodes to the many to many object, don't
              // update the other side of the relation, because there may be no
              // relation defined to the many to many object
              $nmObj->addNode($object, $thisEndRelation->getThisRole(), true, false, false);
              $nmObj->addNode($relative, $otherEndRelation->getOtherRole(), true, false, false);
            }
          }
        }
      }

      // deleted nodes
      $deletedNodes = $object->getDeletedNodes();
      foreach ($deletedNodes as $role => $oids) {
        $relationDesc = $this->getRelation($role);
        // for a many to one relation, we need to update the appropriate
        // foreign key in the object
        if ($relationDesc instanceof RDBManyToOneRelationDescription) {
          // in a many to one only one parent is possible
          // so we take the last oid
          $poid = array_pop($oids);
          if (ObjectId::isValid($poid)) {
            // set the foreign key to null
            $fkAttr = $this->getAttribute($relationDesc->getFkName());
            $object->setValue($fkAttr->getName(), null);
          }
        }
        elseif ($relationDesc instanceof RDBManyToManyRelationDescription) {
          // in a many to many relation we have to delete the relation object
          // if it does exist
          foreach ($oids as $relativeOid) {
            // check if the relation exists
            $nmObjects = $this->loadRelationObjects(PersistentObjectProxy::fromObject($object),
                    new PersistentObjectProxy($relativeOid), $relationDesc);
            foreach ($nmObjects as $nmObj) {
              // delete the relation
              $nmObj->delete();
            }
          }
        }
      }

      // changed order
      $orderedNodes = $object->getNodeOrder();
      // order changes only make sense for arrays with more than one element
      if (sizeof($orderedNodes) > 1) {
        $relatives = array();
        $sortkeyValues = array();
        foreach ($orderedNodes as $curNode) {
          // find the role of the node
          $curRelationDesc = $object->getNodeRelation($curNode);
          if ($curRelationDesc != null) {
            // find the sortkey
            $otherMapper = $curRelationDesc->getOtherMapper();
            $sortkeyDef = $otherMapper->getSortkey($curRelationDesc->getThisRole());
            if ($sortkeyDef != null) {
              $sortkeyName = $sortkeyDef['sortFieldName'];
              // in a many to many relation, we have to modify the order of the relation objects
              if ($curRelationDesc instanceof RDBManyToManyRelationDescription) {
                  $nmObjects = $this->loadRelationObjects(PersistentObjectProxy::fromObject($object),
                        PersistentObjectProxy::fromObject($curNode), $curRelationDesc);
                  $curNode = $nmObjects[0];
              }
              // collect the objects and sortkey definitions
              $relatives[] = array('object' => $curNode, 'sortFieldName' => $sortkeyName);
              // collect the sortkey values
              $sortkeyValues[] = $curNode->getValue($sortkeyName);
            }
          }
        }
        // sort the values
        sort($sortkeyValues);
        // set the values on the objects
        for ($i=0, $count=sizeof($relatives); $i<$count; $i++) {
          $curRelative = $relatives[$i];
          $curRelative['object']->setValue($curRelative['sortFieldName'], $sortkeyValues[$i]);
        }
      }
    }
    $object->setState($oldState);
  }

  /**
   * @see RDBMapper::getSelectSQL()
   */
  public function getSelectSQL($criteria=null, $alias=null, $orderby=null, PagingInfo $pagingInfo=null, $queryId=null) {
    // use own query id, if none is given
    $queryId = $queryId == null ? $this->getCacheKey($alias, $criteria, $orderby, $pagingInfo) : $queryId;

    $selectStmt = SelectStatement::get($this, $queryId);
    if (!$selectStmt->isCached()) {
      // initialize the statement

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
      $this->addColumns($selectStmt, $tableName);

      // condition
      $bind = $this->addCriteria($selectStmt, $criteria, $tableName);

      // order
      $this->addOrderBy($selectStmt, $orderby, $tableName, $this->getDefaultOrder());

      // limit
      if ($pagingInfo != null) {
        $selectStmt->limit($pagingInfo->getPageSize(), $pagingInfo->getOffset());
      }
    }
    else {
      // on used statements only set bind
      $tableName = $alias != null ? $alias : $this->getRealTableName();
      $bind = $this->getBind($criteria, $tableName);
    }

    // set parameters
    $selectStmt->bind($bind);
    return $selectStmt;
  }

  /**
   * @see RDBMapper::getRelationSelectSQL()
   */
  protected function getRelationSelectSQL(array $otherObjectProxies,
          $otherRole, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null) {
    $relationDescription = $this->getRelationImpl($otherRole, true);
    if ($relationDescription instanceof RDBManyToOneRelationDescription) {
      return $this->getManyToOneRelationSelectSQL($relationDescription,
              $otherObjectProxies, $otherRole, $criteria, $orderby, $pagingInfo);
    }
    elseif ($relationDescription instanceof RDBOneToManyRelationDescription) {
      return $this->getOneToManyRelationSelectSQL($relationDescription,
              $otherObjectProxies, $otherRole, $criteria, $orderby, $pagingInfo);
    }
    elseif ($relationDescription instanceof RDBManyToManyRelationDescription) {
      return $this->getManyToManyRelationSelectSQL($relationDescription,
              $otherObjectProxies, $otherRole, $criteria, $orderby, $pagingInfo);
    }
    throw new IllegalArgumentException("Unknown RelationDescription for role: ".$otherRole);
  }

  /**
   * Get the statement for selecting a many to one relation
   * @see RDBMapper::getRelationSelectSQL()
   */
  protected function getManyToOneRelationSelectSQL(RelationDescription $relationDescription,
          array $otherObjectProxies, $otherRole, $criteria=null, $orderby=null,
          PagingInfo $pagingInfo=null) {
    $thisAttr = $this->getAttribute($relationDescription->getFkName());
    $tableName = $this->getRealTableName();

    // id bind parameters
    $bind = array();
    $idPlaceholder = ':'.$tableName.'_'.$thisAttr->getName();
    for ($i=0, $count=sizeof($otherObjectProxies); $i<$count; $i++) {
      $dbid = $otherObjectProxies[$i]->getValue($relationDescription->getIdName());
      if ($dbid === null) {
        $dbid = SQLConst::NULL();
      }
      $bind[$idPlaceholder.$i] = $dbid;
    }

    // statement
    $queryId = $this->getCacheKey($otherRole.sizeof($otherObjectProxies), $criteria, $orderby, $pagingInfo);
    $selectStmt = SelectStatement::get($this, $queryId);
    if (!$selectStmt->isCached()) {
      // initialize the statement

      $selectStmt->from($tableName, '');
      $this->addColumns($selectStmt, $tableName);
      $selectStmt->where($this->quoteIdentifier($tableName).'.'.
              $this->quoteIdentifier($thisAttr->getName()).' IN('.join(',', array_keys($bind)).')');
      // order
      $this->addOrderBy($selectStmt, $orderby, $tableName, $this->getDefaultOrder($otherRole));
      // additional conditions
      $bind = array_merge($bind, $this->addCriteria($selectStmt, $criteria, $tableName));
      // limit
      if ($pagingInfo != null) {
        $selectStmt->limit($pagingInfo->getPageSize(), $pagingInfo->getOffset());
      }
    }
    else {
      // on used statements only set bind
      $bind = array_merge($bind, $this->getBind($criteria, $tableName));
    }

    // set parameters
    $selectStmt->bind($bind);
    return array($selectStmt, $relationDescription->getIdName(), $relationDescription->getFkName());
  }

  /**
   * Get the statement for selecting a one to many relation
   * @see RDBMapper::getRelationSelectSQL()
   */
  protected function getOneToManyRelationSelectSQL(RelationDescription $relationDescription,
          array $otherObjectProxies, $otherRole, $criteria=null, $orderby=null,
          PagingInfo $pagingInfo=null) {
    $thisAttr = $this->getAttribute($relationDescription->getIdName());
    $tableName = $this->getRealTableName();

    // id bind parameters
    $bind = array();
    $idPlaceholder = ':'.$tableName.'_'.$thisAttr->getName();
    for ($i=0, $count=sizeof($otherObjectProxies); $i<$count; $i++) {
      $fkValue = $otherObjectProxies[$i]->getValue($relationDescription->getFkName());
      if ($fkValue === null) {
        $fkValue = SQLConst::NULL();
      }
       $bind[$idPlaceholder.$i] = $fkValue;
    }

    // statement
    $queryId = $this->getCacheKey($otherRole.sizeof($otherObjectProxies), $criteria, $orderby, $pagingInfo);
    $selectStmt = SelectStatement::get($this, $queryId);
    if (!$selectStmt->isCached()) {
      // initialize the statement

      $selectStmt->from($tableName, '');
      $this->addColumns($selectStmt, $tableName);
      $selectStmt->where($this->quoteIdentifier($tableName).'.'.
              $this->quoteIdentifier($thisAttr->getName()).' IN('.join(',', array_keys($bind)).')');
      // order
      $this->addOrderBy($selectStmt, $orderby, $tableName, $this->getDefaultOrder($otherRole));
      // additional conditions
      $bind = array_merge($bind, $this->addCriteria($selectStmt, $criteria, $tableName));
      // limit
      if ($pagingInfo != null) {
        $selectStmt->limit($pagingInfo->getPageSize(), $pagingInfo->getOffset());
      }
    }
    else {
      // on used statements only set bind
      $bind = array_merge($bind, $this->getBind($criteria, $tableName));
    }

    // set parameters
    $selectStmt->bind($bind);
    return array($selectStmt, $relationDescription->getFkName(), $relationDescription->getIdName());
  }

  /**
   * Get the statement for selecting a many to many relation
   * @see RDBMapper::getRelationSelectSQL()
   */
  protected function getManyToManyRelationSelectSQL(RelationDescription $relationDescription,
          array $otherObjectProxies, $otherRole, $criteria=null, $orderby=null,
          PagingInfo $pagingInfo=null) {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $thisRelationDesc = $relationDescription->getThisEndRelation();
    $otherRelationDesc = $relationDescription->getOtherEndRelation();
    $nmMapper = $persistenceFacade->getMapper($thisRelationDesc->getOtherType());
    $otherFkAttr = $nmMapper->getAttribute($otherRelationDesc->getFkName());
    $nmTablename = $nmMapper->getRealTableName();

    // id bind parameters
    $bind = array();
    $idPlaceholder = ':'.$nmTablename.'_'.$otherFkAttr->getName();
    for ($i=0, $count=sizeof($otherObjectProxies); $i<$count; $i++) {
      $dbid = $otherObjectProxies[$i]->getValue($thisRelationDesc->getIdName());
      if ($dbid === null) {
        $dbid = SQLConst::NULL();
      }
      $bind[$idPlaceholder.$i] = $dbid;
    }

    // statement
    $queryId = $this->getCacheKey($otherRole.sizeof($otherObjectProxies), $criteria, $orderby, $pagingInfo);
    $selectStmt = SelectStatement::get($this, $queryId);
    if (!$selectStmt->isCached()) {
      // initialize the statement

      $thisFkAttr = $nmMapper->getAttribute($thisRelationDesc->getFkName());
      $thisIdAttr = $this->getAttribute($thisRelationDesc->getIdName());

      $tableName = $this->getRealTableName();
      $selectStmt->from($tableName, '');
      $this->addColumns($selectStmt, $tableName);
      $joinCond = $this->quoteIdentifier($nmTablename).'.'.$this->quoteIdentifier($thisFkAttr->getName()).'='.
              $this->quoteIdentifier($tableName).'.'.$this->quoteIdentifier($thisIdAttr->getName());
      $selectStmt->join($nmTablename, $joinCond, array());
      $selectStmt->where($this->quoteIdentifier($nmTablename).'.'.
              $this->quoteIdentifier($otherFkAttr->getName()).' IN('.join(',', array_keys($bind)).')');
      // order (in this case we use the order of the many to many objects)
      $nmSortDefs = $nmMapper->getDefaultOrder($otherRole);
      $this->addOrderBy($selectStmt, $orderby, $nmTablename, $nmSortDefs);
      foreach($nmSortDefs as $nmSortDef) {
        // add the sort attribute from the many to many object
        $nmSortAttributeDesc = $nmMapper->getAttribute($nmSortDef['sortFieldName']);
        $selectStmt->columns(array($nmSortAttributeDesc->getName() => $nmSortAttributeDesc->getColumn()), $nmTablename);
      }
      // add proxy id
      $selectStmt->columns(array(self::INTERNAL_VALUE_PREFIX.'id' => $otherFkAttr->getName()), $nmTablename);
      // additional conditions
      $bind = array_merge($bind, $this->addCriteria($selectStmt, $criteria, $nmTablename));
      // limit
      if ($pagingInfo != null) {
        $selectStmt->limit($pagingInfo->getPageSize(), $pagingInfo->getOffset());
      }
    }
    else {
      // on used statements only set bind
      $bind = array_merge($bind, $this->getBind($criteria, $nmTablename));
    }

    // set parameters
    $selectStmt->bind($bind);
    return array($selectStmt, $thisRelationDesc->getIdName(), self::INTERNAL_VALUE_PREFIX.'id');
  }

  /**
   * @see RDBMapper::getInsertSQL()
   */
  protected function getInsertSQL(PersistentObject $object) {
    // get the attributes to store
    $values = $this->convertValuesForStorage($this->getPersistentValues($object));

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
  protected function getUpdateSQL(PersistentObject $object) {
    // get the attributes to store
    $values = $this->convertValuesForStorage($this->getPersistentValues($object));

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
  protected function getDeleteSQL(ObjectId $oid) {
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
   * @param selectStmt The select statement (instance of SelectStatement)
   * @param tableName The table name
   * @return SelectStatement
   */
  protected function addColumns(SelectStatement $selectStmt, $tableName) {
    // columns
    $attributeDescs = $this->getAttributes();
    foreach($attributeDescs as $curAttributeDesc) {
      if (!($curAttributeDesc instanceof ReferenceDescription)) {
        $selectStmt->columns(array($curAttributeDesc->getName() => $curAttributeDesc->getColumn()), $tableName);
      }
    }

    // references
    $selectStmt = $this->addReferences($selectStmt, $tableName);
    return $selectStmt;
  }

  /**
   * Add the columns and joins to select references to a given select statement.
   * @param selectStmt The select statement (instance of SelectStatement)
   * @param tableName The name for this table (the alias, if used).
   * @return SelectStatement
   */
  protected function addReferences(SelectStatement $selectStmt, $tableName) {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    // collect all references first
    $references = array();
    foreach($this->getReferences() as $curReferenceDesc) {
      $referencedType = $curReferenceDesc->getOtherType();
      $referencedValue = $curReferenceDesc->getOtherName();
      $relationDesc = $this->getRelation($referencedType);
      $otherMapper = $persistenceFacade->getMapper($relationDesc->getOtherType());
      if ($otherMapper) {
        $otherTable = $otherMapper->getRealTableName();
        $otherAttributeDesc = $otherMapper->getAttribute($referencedValue);
        if ($otherAttributeDesc instanceof RDBAttributeDescription) {
          // set up the join definition if not already defined
          if (!isset($references[$otherTable])) {
            $references[$otherTable] = array();
            $references[$otherTable]['attributes'] = array();

            $tableNameQ = $this->quoteIdentifier($tableName);
            $otherTableQ = $this->quoteIdentifier($otherTable);

            // determine the join condition
            if ($relationDesc instanceof RDBManyToOneRelationDescription) {
              // reference from parent
              $thisAttrNameQ = $this->quoteIdentifier($this->getAttribute($relationDesc->getFkName())->getColumn());
              $otherAttrNameQ = $this->quoteIdentifier($otherMapper->getAttribute($relationDesc->getIdName())->getColumn());
              $additionalCond = "";
            }
            else if ($relationDesc instanceof RDBOneToManyRelationDescription) {
              // reference from child
              $thisAttrNameQ = $this->quoteIdentifier($this->getAttribute($relationDesc->getIdName())->getColumn());
              $otherAttrNameQ = $this->quoteIdentifier($otherMapper->getAttribute($relationDesc->getFkName())->getColumn());
              $otherPkNames = $otherMapper->getPkNames();
              $otherPkNameQ = $this->quoteIdentifier($otherMapper->getAttribute($otherPkNames[0])->getColumn());
              $additionalCond = " AND ".$otherTableQ.".".$otherPkNameQ.
                      " = (SELECT MIN(".$otherTableQ.".".$otherPkNameQ.") FROM ".$otherTableQ.
                      " WHERE ".$otherTableQ.".".$otherAttrNameQ."=".$tableNameQ.".".$thisAttrNameQ.")";
            }
            $joinCond = $tableNameQ.".".$thisAttrNameQ."=".$otherTableQ.".".$otherAttrNameQ;
            if (strlen($additionalCond) > 0) {
              $joinCond = "(".$joinCond.$additionalCond.")";
            }
            $references[$otherTable]['joinCond'] = $joinCond;
          }

          // add the attributes
          $references[$otherTable]['attributes'][$curReferenceDesc->getName()] = $otherAttributeDesc->getColumn();
        }
      }
    }
    // add references from each referenced table
    foreach($references as $otherTable => $curReference) {
      $selectStmt->joinLeft($otherTable, $curReference['joinCond'], $curReference['attributes']);
    }
    return $selectStmt;
  }

  /**
   * Add the given criteria to the select statement
   * @param selectStmt The select statement (instance of SelectStatement)
   * @param criteria An array of Criteria instances that define conditions on the object's attributes (maybe null)
   * @param tableName The table name
   * @return Array of placeholder value pairs for bind
   */
  protected function addCriteria(SelectStatement $selectStmt, $criteria, $tableName) {
    $bind = array();
    if ($criteria != null) {
      foreach ($criteria as $criterion) {
        if ($criterion instanceof Criteria) {
          $placeholder = ':'.$tableName.'_'.$criterion->getAttribute();
          $condition = $this->renderCriteria($criterion, $placeholder, $tableName);
          if ($criterion->getCombineOperator() == Criteria::OPERATOR_AND) {
            $selectStmt->where($condition);
          }
          else {
            $selectStmt->orWhere($condition);
          }
          $bind[$placeholder] = $criterion->getValue();
        }
        else {
          throw new IllegalArgumentException("The select condition must be an instance of Criteria");
        }
      }
    }
    return $bind;
  }

  /**
   * Add the given order to the select statement
   * @param selectStmt The select statement (instance of SelectStatement)
   * @param orderby An array holding names of attributes to order by, maybe appended with 'ASC', 'DESC' (maybe null)
   * @param tableName The table name
   * @param defaultOrder The default order definition to use, if orderby is null (@see PersistenceMapper::getDefaultOrder())
   */
  protected function addOrderBy(SelectStatement $selectStmt, $orderby, $tableName, $defaultOrder) {
    $orderbyFinal = array();
    if ($orderby == null) {
      $orderby = array();
      // use default ordering
      if ($defaultOrder && sizeof($defaultOrder) > 0) {
        foreach ($defaultOrder as $orderDef) {
          $orderby[] = $orderDef['sortFieldName']." ".$orderDef['sortDirection'];
        }
      }
    }
    foreach($orderby as $orderExpression) {
      $orderbyFinal[] = $this->ensureTablePrefix($orderExpression, $tableName);
    }
    if (sizeof($orderby) > 0) {
      $selectStmt->order($orderbyFinal);
    }
  }

  /**
   * Get an array of placeholder value pairs for bind
   * @param criteria An array of Criteria instances that define conditions on the object's attributes (maybe null)
   * @param tableName The table name
   * @return Array of placeholder value pairs for bind
   */
  protected function getBind($criteria, $tableName) {
    $bind = array();
    if ($criteria != null) {
      foreach ($criteria as $criterion) {
        if ($criterion instanceof Criteria) {
          $placeholder = ':'.$tableName.'_'.$criterion->getAttribute();
          $bind[$placeholder] = $criterion->getValue();
        }
        else {
          throw new IllegalArgumentException("The select condition must be an instance of Criteria");
        }
      }
    }
    return $bind;
  }

  /**
   * Get an associative array of attribute name-value pairs to be stored for a
   * given oject (references are not included)
   * @param object The PeristentObject.
   * @return Associative array
   */
  protected function getPersistentValues(PersistentObject $object) {
    $values = array();

    // attribute definitions
    $attributeDescs = $this->getAttributes();
    foreach($attributeDescs as $curAttributeDesc) {
      if (!($curAttributeDesc instanceof ReferenceDescription)) {
        // add only attributes that are defined in the object
        $attribName = $curAttributeDesc->getName();
        //if ($object->hasValue($attribName)) {
          $values[$attribName] = $object->getValue($attribName);
        //}
      }
    }
    return $values;
  }

  /**
   * Convert values for before storage
   * @param values Associative Array
   * @return Associative Array
   */
  protected function convertValuesForStorage($values) {
    // filter values according to type
    foreach($values as $valueName => $value) {
      $type = $this->getAttribute($valueName)->getType();
      // integer
      if (strpos(strtolower($type), 'int') === 0) {
        $value = (strlen($value) == 0) ? null : intval($value);
        $values[$valueName] = $value;
      }
      // null values
      if ($value === null) {
        $values[$valueName] = SQLConst::NULL();
      }
    }
    return $values;
  }

  /**
   * Load the relation objects in a many to many relation from the database.
   * @param objectProxy The proxy at this end of the relation.
   * @param relativeProxy The proxy at the other end of the relation.
   * @param relationDesc The RDBManyToManyRelationDescription instance describing the relation.
   * @param includeTransaction Boolean whether to also search in the current transaction (default: false)
   * @return Array of PersistentObject instances
   */
  protected function loadRelationObjects(PersistentObjectProxy $objectProxy,
          PersistentObjectProxy $relativeProxy, RDBManyToManyRelationDescription $relationDesc,
          $includeTransaction=false) {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    $nmMapper = $persistenceFacade->getMapper($relationDesc->getThisEndRelation()->getOtherType());
    $nmType = $nmMapper->getType();

    $thisId = $objectProxy->getOID()->getFirstId();
    $otherId = $relativeProxy->getOID()->getFirstId();
    $thisEndRelation = $relationDesc->getThisEndRelation();
    $otherEndRelation = $relationDesc->getOtherEndRelation();
    $thisFkAttr = $nmMapper->getAttribute($thisEndRelation->getFkName());
    $otherFkAttr = $nmMapper->getAttribute($otherEndRelation->getFkName());

    $criteria1 = new Criteria($nmType, $thisFkAttr->getName(), "=", $thisId);
    $criteria2 = new Criteria($nmType, $otherFkAttr->getName(), "=", $otherId);
    $criteria = array($criteria1, $criteria2);
    $nmObjects = $nmMapper->loadObjects($nmType, BuildDepth::SINGLE, $criteria);

    if ($includeTransaction) {
      $transaction = $persistenceFacade->getTransaction();
      $objects = $transaction->getObjects();
      foreach ($objects as $object) {
        if ($object->getType() == $nmType && $object instanceof Node) {
          // we expect single valued relation ends
          $thisEndObject = $object->getValue($thisEndRelation->getThisRole());
          $otherEndObject = $object->getValue($otherEndRelation->getOtherRole());
          if ($objectProxy->getRealSubject() == $thisEndObject &&
                  $relativeProxy->getRealSubject() == $otherEndObject) {
            $nmObjects[] = $object;
          }
        }
      }
    }
    return $nmObjects;
  }

  /**
   * @see RDBMapper::createPKCondition()
   */
  protected function createPKCondition(ObjectId $oid) {
    $criterias = array();
    $type = $this->getType();
    $pkNames = $this->getPKNames();
    $ids = $oid->getId();
    for ($i=0, $count=sizeof($pkNames); $i<$count; $i++) {
      $pkValue = $ids[$i];
      $criterias[] = new Criteria($type, $pkNames[$i], "=", $pkValue);
    }
    return $criterias;
  }

  /**
   * Get all foreign key relations (used to reference a parent)
   * @return An array of RDBManyToOneRelationDescription instances
   */
  protected function getForeignKeyRelations() {
    if ($this->_fkRelations == null) {
      $this->_fkRelations = array();
      $relationDescs = $this->getRelations();
      foreach($relationDescs as $relationDesc) {
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
  public function isForeignKey($name) {
    $fkDescs = $this->getForeignKeyRelations();
    foreach($fkDescs as $fkDesc) {
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
  protected function quote($value) {
    if ($value === null) {
      return 'null';
    }
    else {
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
  protected function ensureTablePrefix($expression, $tableName) {
    if (strpos($expression, '.') === false) {
      $expression = $tableName.".".$expression;
    }
    return $expression;
  }

  /**
   * Get a unique string for the given parameter values
   * @param string
   * @param criteriaArray
   * @param stringArray
   * @param pagingInfo
   * @return String
   */
  protected function getCacheKey($string, $criteriaArray, $stringArray, PagingInfo $pagingInfo=null) {
    $result = $this->getRealTableName().','.$string.',';
    if ($criteriaArray != null) {
      foreach ($criteriaArray as $c) {
        $result .= $c->getId();
      }
    }
    if ($stringArray != null) {
      $result .= join(',', $stringArray);
    }
    if ($pagingInfo != null) {
      $result .= $pagingInfo->getOffset().','.$pagingInfo->getPageSize();
    }
    return $result;
  }
}
?>
