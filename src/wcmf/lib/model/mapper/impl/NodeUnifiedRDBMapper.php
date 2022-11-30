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
namespace wcmf\lib\model\mapper\impl;

use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\model\mapper\impl\AbstractRDBMapper;
use wcmf\lib\model\mapper\RDBAttributeDescription;
use wcmf\lib\model\mapper\RDBManyToManyRelationDescription;
use wcmf\lib\model\mapper\RDBManyToOneRelationDescription;
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
use wcmf\lib\persistence\TransientAttributeDescription;
use wcmf\lib\persistence\UpdateOperation;

/**
 * NodeUnifiedRDBMapper maps Node objects to a relational database schema where each Node
 * type has its own table.
 * The wCMFGenerator uses this class as base class for all mappers.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class NodeUnifiedRDBMapper extends AbstractRDBMapper {

  const CACHE_KEY = 'mapper';

  private $fkRelations = null;

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
          if ($sortkeyDef != null && $object->getValue($sortkeyDef['sortFieldName']) === null) {
            $object->setValue($sortkeyDef['sortFieldName'], $value);
          }
        }
        $sortkeyDef = $this->getSortkey();
        if ($sortkeyDef != null && $object->getValue($sortkeyDef['sortFieldName']) === null) {
          $object->setValue($sortkeyDef['sortFieldName'], $value);
        }
      }
    }

    // handle relations
    if ($object instanceof Node) {
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
              $nmObj = $this->persistenceFacade->create($nmType);
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
      $nodeOrder = $object->getNodeOrder();
      if ($nodeOrder != null) {
        $containerMapper = $object->getMapper();
        $orderedList = $nodeOrder['ordered'];
        $movedList = $nodeOrder['moved'];
        $role = $nodeOrder['role'];
        $defaultRelationDesc = $role != null ? $containerMapper->getRelation($role) : null;

        if ($movedList) {
          // only handle affected nodes, if movedList is defined
          $movedLookup = array_reduce($movedList, function($result, $item) {
            $result[$item->getOID()->__toString()] = true;
            return $result;
          }, []);
          for ($i=0, $count=sizeof($orderedList); $i<$count; $i++) {
            $orderedNode = $orderedList[$i];
            // check if node is repositioned
            if ($movedList == null || isset($movedLookup[$orderedNode->getOID()->__toString()])) {
              // determine the sortkey and value according to the container object
              [$sortNode, $sortkey] = $this->getSortableObject(PersistentObjectProxy::fromObject($object),
                      PersistentObjectProxy::fromObject($orderedNode), $defaultRelationDesc);

              // get previous sortkey value
              $prevValue = null;
              if ($i > 0) {
                $prevNode = $orderedList[$i-1];
                [$prevSortNode, $sortkeyPrev, $sortdirPrev] = $this->getSortableObject(PersistentObjectProxy::fromObject($object),
                      PersistentObjectProxy::fromObject($prevNode), $defaultRelationDesc);
                $prevValue = $prevSortNode->getValue($sortkeyPrev);
              }

              // get next sortkey value
              $nextValue = null;
              if ($i < $count-1) {
                $nextNode = $orderedList[$i+1];
                [$nextSortNode, $sortkeyNext, $sortdirNext] = $this->getSortableObject(PersistentObjectProxy::fromObject($object),
                      PersistentObjectProxy::fromObject($nextNode), $defaultRelationDesc);
                $nextValue = $nextSortNode->getValue($sortkeyNext);
              }

              // set edge values
              if ($prevValue == null) {
                $prevValue = ceil($sortdirNext == 'ASC' ? $nextValue-1 : $nextValue+1);
              }
              if ($nextValue == null) {
                $nextValue = ceil($sortdirPrev == 'ASC' ? $prevValue+1 : $prevValue-1);
              }

              // set the sortkey value to the average
              $sortNode->setValue($sortkey, ($nextValue+$prevValue)/2);
            }
          }
        }
        else {
          // sort all nodes, if movedList is missing
          // collect sortkey values
          $sortkeyValues = [];
          for ($i=0, $count=sizeof($orderedList); $i<$count; $i++) {
            $orderedNode = $orderedList[$i];
            // determine the sortkey and value according to the container object
            [$sortNode, $sortkey] = $this->getSortableObject(PersistentObjectProxy::fromObject($object),
                    PersistentObjectProxy::fromObject($orderedNode), $defaultRelationDesc);
            $sortkeyValues[] = $sortNode->getValue($sortkey);
          }

          // order sortkey values and resolve duplicates
          sort($sortkeyValues);
          $uniqueSortkeyValues = [$sortkeyValues[0]];
          $prevValue = $sortkeyValues[0];
          for ($i=1, $count=sizeof($sortkeyValues); $i<$count; $i++) {
            $curValue = $sortkeyValues[$i];
            if ($curValue == $prevValue) {
              $nextValue = $i>$count-1 ? $sortkeyValues[$i+1] : $curValue+1;
              $curValue = ($prevValue+$nextValue)/2;
            }
            $uniqueSortkeyValues[] = $curValue;
            $prevValue = $curValue;
          }

          // set ordered sortkey values
          for ($i=0, $count=sizeof($orderedList); $i<$count; $i++) {
            $orderedNode = $orderedList[$i];
            // determine the sortkey and value according to the container object
            [$sortNode, $sortkey] = $this->getSortableObject(PersistentObjectProxy::fromObject($object),
                    PersistentObjectProxy::fromObject($orderedNode), $defaultRelationDesc);
            $sortNode->setValue($sortkey, $uniqueSortkeyValues[$i]);
          }
        }
      }
    }
    $object->setState($oldState);
  }

  /**
   * @see RDBMapper::getSelectSQL()
   */
  public function getSelectSQL($criteria=null, $alias=null, $attributes=null, $orderby=null, PagingInfo $pagingInfo=null, $queryId=null) {
    // use own query id, if none is given
    $queryId = $queryId == null ? $this->getCacheKey($alias, $attributes, $criteria, $orderby, $pagingInfo) : $queryId;

    $selectStmt = SelectStatement::get($this, $queryId);
    if (!$selectStmt->isCached()) {
      // initialize the statement

      // table
      $tableName = $this->getRealTableName();
      if ($alias != null) {
        $selectStmt->from([$alias => $tableName]);
        $tableName = $alias;
      }
      else {
        $selectStmt->from($tableName);
      }

      // columns
      $this->addColumns($selectStmt, $tableName, $attributes);

      // condition
      $parameters = $this->addCriteria($selectStmt, $criteria, $tableName);

      // order
      $this->addOrderBy($selectStmt, $orderby, $this->getType(), $tableName, $this->getDefaultOrder());

      // limit
      if ($pagingInfo != null) {
        $selectStmt->limit($pagingInfo->getPageSize());
      }
    }
    else {
      // on used statements only set parameters
      $tableName = $alias != null ? $alias : $this->getRealTableName();
      $parameters = $this->getParameters($criteria, $tableName);
    }

    // set parameters
    $selectStmt->setParameters($parameters);

    // always update offset, since it's most likely not contained in the cache id
    if ($pagingInfo != null) {
      $selectStmt->offset($pagingInfo->getOffset());
    }
    return $selectStmt;
  }

  /**
   * @see RDBMapper::getRelationSelectSQL()
   */
  protected function getRelationSelectSQL(array $otherObjectProxies,
          $otherRole, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null) {
    $relationDescription = $this->getRelationIncludingNM($otherRole);
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
   * Get the statement for selecting a many-to-one relation
   * @see RDBMapper::getRelationSelectSQL()
   */
  protected function getManyToOneRelationSelectSQL(RelationDescription $relationDescription,
          array $otherObjectProxies, $otherRole, $criteria=null, $orderby=null,
          PagingInfo $pagingInfo=null) {
    $thisAttr = $this->getAttribute($relationDescription->getFkName());
    $tableName = $this->getRealTableName();

    // id parameters
    $parameters = [];
    $idPlaceholder = ':'.$tableName.'_'.$thisAttr->getName();
    for ($i=0, $count=sizeof($otherObjectProxies); $i<$count; $i++) {
      $dbid = $otherObjectProxies[$i]->getValue($relationDescription->getIdName());
      if ($dbid === null) {
        $dbid = SQLConst::NULL();
      }
      $parameters[$idPlaceholder.$i] = $dbid;
    }

    // statement
    $selectStmt = $this->getRelationStatement($thisAttr, $parameters,
          $otherObjectProxies, $otherRole, $criteria, $orderby, $pagingInfo);
    return [$selectStmt, $relationDescription->getIdName(), $relationDescription->getFkName()];
  }

  /**
   * Get the statement for selecting a one-to-many relation
   * @see RDBMapper::getRelationSelectSQL()
   */
  protected function getOneToManyRelationSelectSQL(RelationDescription $relationDescription,
          array $otherObjectProxies, $otherRole, $criteria=null, $orderby=null,
          PagingInfo $pagingInfo=null) {
    $thisAttr = $this->getAttribute($relationDescription->getIdName());
    $tableName = $this->getRealTableName();

    // id parameters
    $parameters = [];
    $idPlaceholder = ':'.$tableName.'_'.$thisAttr->getName();
    for ($i=0, $count=sizeof($otherObjectProxies); $i<$count; $i++) {
      $fkValue = $otherObjectProxies[$i]->getValue($relationDescription->getFkName());
      if ($fkValue === null) {
        $fkValue = SQLConst::NULL();
      }
       $parameters[$idPlaceholder.$i] = $fkValue;
    }

    // statement
    $selectStmt = $this->getRelationStatement($thisAttr, $parameters,
          $otherObjectProxies, $otherRole, $criteria, $orderby, $pagingInfo);
    return [$selectStmt, $relationDescription->getFkName(), $relationDescription->getIdName()];

  }

  /**
   * Get the select statement for a many-to-one or one-to-many relation.
   * This method is the common part used in both relations.
   * @see RDBMapper::getRelationSelectSQL()
   */
  protected function getRelationStatement($thisAttr, $parameters,
          array $otherObjectProxies, $otherRole,
          $criteria=null, $orderby=null, PagingInfo $pagingInfo=null) {
    $queryId = $this->getCacheKey($otherRole.sizeof($otherObjectProxies), null, $criteria, $orderby, $pagingInfo);
    $tableName = $this->getRealTableName();
    $selectStmt = SelectStatement::get($this, $queryId);
    if (!$selectStmt->isCached()) {
      // initialize the statement
      $selectStmt->from($tableName, '');
      $this->addColumns($selectStmt, $tableName);
      $selectStmt->where($this->quoteIdentifier($tableName).'.'.
              $this->quoteIdentifier($thisAttr->getColumn()).' IN('.join(',', array_keys($parameters)).')');
      // order
      $this->addOrderBy($selectStmt, $orderby, $this->getType(), $tableName, $this->getDefaultOrder($otherRole));
      // additional conditions
      $parameters = array_merge($parameters, $this->addCriteria($selectStmt, $criteria, $tableName));
      // limit
      if ($pagingInfo != null) {
        $selectStmt->limit($pagingInfo->getPageSize());
      }
    }
    else {
      // on used statements only set parameters
      $parameters = array_merge($parameters, $this->getParameters($criteria, $tableName));
    }

    // set parameters
    $selectStmt->setParameters($parameters);

    // always update offset, since it's most likely not contained in the cache id
    if ($pagingInfo != null) {
      $selectStmt->offset($pagingInfo->getOffset());
    }
    return $selectStmt;
  }

  /**
   * Get the statement for selecting a many-to-many relation
   * @see RDBMapper::getRelationSelectSQL()
   */
  protected function getManyToManyRelationSelectSQL(RelationDescription $relationDescription,
          array $otherObjectProxies, $otherRole, $criteria=null, $orderby=null,
          PagingInfo $pagingInfo=null) {
    $thisRelationDesc = $relationDescription->getThisEndRelation();
    $otherRelationDesc = $relationDescription->getOtherEndRelation();
    $nmMapper = self::getMapper($thisRelationDesc->getOtherType());
    $otherFkAttr = $nmMapper->getAttribute($otherRelationDesc->getFkName());
    $nmTableName = $nmMapper->getRealTableName();

    // id parameters
    $parameters = [];
    $idPlaceholder = ':'.$nmTableName.'_'.$otherFkAttr->getName();
    for ($i=0, $count=sizeof($otherObjectProxies); $i<$count; $i++) {
      $dbid = $otherObjectProxies[$i]->getValue($otherRelationDesc->getIdName());
      if ($dbid === null) {
        $dbid = SQLConst::NULL();
      }
      $parameters[$idPlaceholder.$i] = $dbid;
    }

    // statement
    $queryId = $this->getCacheKey($otherRole.sizeof($otherObjectProxies), null, $criteria, $orderby, $pagingInfo);
    $selectStmt = SelectStatement::get($this, $queryId);
    if (!$selectStmt->isCached()) {
      // initialize the statement

      $thisFkAttr = $nmMapper->getAttribute($thisRelationDesc->getFkName());
      $thisIdAttr = $this->getAttribute($thisRelationDesc->getIdName());

      $tableName = $this->getRealTableName();
      $selectStmt->from($tableName, '');
      $this->addColumns($selectStmt, $tableName);
      $joinCond = $nmTableName.'.'.$thisFkAttr->getColumn().'='.$tableName.'.'.$thisIdAttr->getColumn();
      $joinColumns = [];
      $selectStmt->where($this->quoteIdentifier($nmTableName).'.'.
          $this->quoteIdentifier($otherFkAttr->getColumn()).' IN('.join(',', array_keys($parameters)).')');
      // order (in this case we use the order of the many to many objects)
      $nmSortDefs = $nmMapper->getDefaultOrder($otherRole);
      $hasNmOrder = sizeof($nmSortDefs) > 0;
      $orderType = $hasNmOrder ? $nmMapper->getType() : $this->getType();
      $orderTable = $hasNmOrder ? $nmTableName : $tableName;
      $defaultOrderDef = $hasNmOrder ? $nmSortDefs : $this->getDefaultOrder($otherRole);
      $this->addOrderBy($selectStmt, $orderby, $orderType, $orderTable, $defaultOrderDef);
      foreach($nmSortDefs as $nmSortDef) {
        // add the sort attribute from the many to many object
        $nmSortAttributeDesc = $nmMapper->getAttribute($nmSortDef['sortFieldName']);
        $joinColumns[$nmSortAttributeDesc->getName()] = $nmSortAttributeDesc->getColumn();
      }
      // add proxy id
      $joinColumns[self::INTERNAL_VALUE_PREFIX.'id'] = $otherFkAttr->getColumn();
      $selectStmt->join($nmTableName, $joinCond, $joinColumns);
      // additional conditions
      $parameters = array_merge($parameters, $this->addCriteria($selectStmt, $criteria, $nmTableName));
      // limit
      if ($pagingInfo != null) {
        $selectStmt->limit($pagingInfo->getPageSize());
      }
    }
    else {
      // on used statements only set parameters
      $parameters = array_merge($parameters, $this->getParameters($criteria, $nmTableName));
    }

    // set parameters
    $selectStmt->setParameters($parameters);

    // always update offset, since it's most likely not contained in the cache id
    if ($pagingInfo != null) {
      $selectStmt->offset($pagingInfo->getOffset());
    }
    return [$selectStmt, $otherRelationDesc->getIdName(), self::INTERNAL_VALUE_PREFIX.'id'];
  }

  /**
   * @see RDBMapper::getInsertSQL()
   */
  protected function getInsertSQL(PersistentObject $object) {
    // get the attributes to store
    $values = $this->convertValuesForStorage($this->getPersistentValues($object));

    // operations
    $insertOp = new InsertOperation($this->getType(), $values);
    $operations = [$insertOp];
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
    $operations = [$updateOp];
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
    $operations = [$deleteOp];
    return $operations;
  }

  /**
   * Add the columns to a given select statement.
   * @param $selectStmt The select statement (instance of SelectStatement)
   * @param $tableName The table name
   * @param $attributes Array of attribute names (optional)
   * @return SelectStatement
   */
  protected function addColumns(SelectStatement $selectStmt, $tableName, $attributes=null) {
    // columns
    $attributeDescs = $this->getAttributes();
    $columns = [];
    foreach($attributeDescs as $curAttributeDesc) {
      $name = $curAttributeDesc->getName();
      if (($attributes == null || in_array($name, $attributes)) && $curAttributeDesc instanceof RDBAttributeDescription) {
        $columns[$curAttributeDesc->getName()] = $curAttributeDesc->getColumn();
      }
    }
    $selectStmt->columns($columns, true);

    // references
    $selectStmt = $this->addReferences($selectStmt, $tableName);
    return $selectStmt;
  }

  /**
   * Add the columns and joins to select references to a given select statement.
   * @param $selectStmt The select statement (instance of SelectStatement)
   * @param $tableName The name for this table (the alias, if used).
   * @return SelectStatement
   */
  protected function addReferences(SelectStatement $selectStmt, $tableName) {
    // collect all references first
    $references = [];
    foreach($this->getReferences() as $curReferenceDesc) {
      $referencedType = $curReferenceDesc->getOtherType();
      $referencedValue = $curReferenceDesc->getOtherName();
      $relationDescs = $this->getRelationsByType($referencedType);
      // get relation try role name if ambiguous
      $relationDesc = sizeof($relationDescs) == 1 ? $relationDescs[0] : $this->getRelation($referencedType);
      $otherMapper = self::getMapper($relationDesc->getOtherType());
      if ($otherMapper) {
        $otherTable = $otherMapper->getRealTableName();
        $otherAttributeDesc = $otherMapper->getAttribute($referencedValue);
        if ($otherAttributeDesc instanceof RDBAttributeDescription) {
          // set up the join definition if not already defined
          if (!isset($references[$otherTable])) {
            $references[$otherTable] = [];
            $references[$otherTable]['attributes'] = [];

            $tableNameQ = $tableName;
            $otherTableQ = $otherTable.'Ref';

            // determine the join condition
            if ($relationDesc instanceof RDBManyToOneRelationDescription) {
              // reference from parent
              $thisAttrNameQ = $this->getAttribute($relationDesc->getFkName())->getColumn();
              $otherAttrNameQ = $otherMapper->getAttribute($relationDesc->getIdName())->getColumn();
              $additionalCond = "";
            }
            else if ($relationDesc instanceof RDBOneToManyRelationDescription) {
              // reference from child
              $thisAttrNameQ = $this->getAttribute($relationDesc->getIdName())->getColumn();
              $otherAttrNameQ = $otherMapper->getAttribute($relationDesc->getFkName())->getColumn();
              $otherPkNames = $otherMapper->getPkNames();
              $otherPkNameQ = $otherMapper->getAttribute($otherPkNames[0])->getColumn();
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
      $selectStmt->join([$otherTable.'Ref' => $otherTable], $curReference['joinCond'],
              $curReference['attributes'], SelectStatement::JOIN_LEFT);
    }
    return $selectStmt;
  }

  /**
   * Add the given criteria to the select statement
   * @param $selectStmt The select statement (instance of SelectStatement)
   * @param $criteria An array of Criteria instances that define conditions on the object's attributes (maybe null)
   * @param $tableName The table name
   * @return Array of placeholder/value pairs
   */
  protected function addCriteria(SelectStatement $selectStmt, $criteria, $tableName) {
    $parameters = [];
    if ($criteria != null) {
      foreach ($criteria as $criterion) {
        if ($criterion instanceof Criteria) {
          $placeholder = ':'.$tableName.'_'.$criterion->getAttribute();
          list($criteriaCondition, $criteriaPlaceholder) =
                  $this->renderCriteria($criterion, $placeholder, $tableName);
          $selectStmt->where($criteriaCondition, $criterion->getCombineOperator());
          if ($criteriaPlaceholder) {
            $value = $criterion->getValue();
            if (is_array($criteriaPlaceholder)) {
              $parameters = array_merge($parameters, array_combine($criteriaPlaceholder, $value));
            }
            else {
              $parameters[$criteriaPlaceholder] = $value;
            }
          }
        }
        else {
          throw new IllegalArgumentException("The select condition must be an instance of Criteria");
        }
      }
    }
    return $parameters;
  }

  /**
   * Add the given order to the select statement
   * @param $selectStmt The select statement (instance of SelectStatement)
   * @param $orderby An array holding names of attributes to order by, maybe appended with 'ASC', 'DESC' (maybe null)
   * @param $orderType The type that define the attributes in orderby (maybe null)
   * @param $aliasName The table alias name to be used (maybe null)
   * @param $defaultOrder The default order definition to use, if orderby is null (@see PersistenceMapper::getDefaultOrder())
   */
  protected function addOrderBy(SelectStatement $selectStmt, $orderby, $orderType, $aliasName, $defaultOrder) {
    if ($orderby == null) {
      $orderby = [];
      // use default ordering
      if ($defaultOrder && sizeof($defaultOrder) > 0) {
        foreach ($defaultOrder as $orderDef) {
          $orderby[] = $orderDef['sortFieldName']." ".$orderDef['sortDirection'];
          $orderType = $orderDef['sortType'];
        }
      }
    }
    for ($i=0, $count=sizeof($orderby); $i<$count; $i++) {
      $curOrderBy = $orderby[$i];
      $orderByParts = preg_split('/ /', $curOrderBy);
      $orderAttribute = $orderByParts[0];
      $orderDirection = sizeof($orderByParts) > 1 ? $orderByParts[1] : 'ASC';
      if (strpos($orderAttribute, '.') > 0) {
        // the type is included in the attribute
        $orderAttributeParts = preg_split('/\./', $orderAttribute);
        $orderAttribute = array_pop($orderAttributeParts);
      }
      $mapper = $orderType != null ? self::getMapper($orderType) : $this;
      $orderAttributeDesc = $mapper->getAttribute($orderAttribute);
      if ($orderAttributeDesc instanceof ReferenceDescription) {
      	// add the referenced column without table name
      	$mapper = self::getMapper($orderAttributeDesc->getOtherType());
      	$orderAttributeDesc = $mapper->getAttribute($orderAttributeDesc->getOtherName());
      	$orderColumnName = $orderAttributeDesc->getColumn();
      }
      elseif ($orderAttributeDesc instanceof TransientAttributeDescription) {
      	// skip, because no column exists
      	continue;
      }
      else {
      	// add the column with table name
      	$tableName = $aliasName != null ? $aliasName : $mapper->getRealTableName();
      	$orderColumnName = $tableName.'.'.$orderAttributeDesc->getColumn();
      }
      $selectStmt->order([$orderColumnName.' '.$orderDirection]);
    }
  }

  /**
   * Get an array of placeholder/value pairs
   * @param $criteria An array of Criteria instances that define conditions on the object's attributes (maybe null)
   * @param $tableName The table name
   * @return Array of placeholder/value pairs
   */
  protected function getParameters($criteria, $tableName) {
    $parameters = [];
    if ($criteria != null) {
      foreach ($criteria as $criterion) {
        if ($criterion instanceof Criteria) {
          $placeholder = ':'.$tableName.'_'.$criterion->getAttribute();
          list($criteriaCondition, $criteriaPlaceholder) = $this->renderCriteria($criterion, $placeholder, '', '');
          if ($criteriaPlaceholder) {
            $value = $criterion->getValue();
            if (is_array($criteriaPlaceholder)) {
              $parameters = array_merge($parameters, array_combine($criteriaPlaceholder, $value));
            }
            else {
              $parameters[$criteriaPlaceholder] = $value;
            }
          }
        }
        else {
          throw new IllegalArgumentException("The select condition must be an instance of Criteria");
        }
      }
    }
    return $parameters;
  }

  /**
   * Get an associative array of attribute name-value pairs to be stored for a
   * given oject (references are not included)
   * @param $object The PeristentObject.
   * @return Associative array
   */
  protected function getPersistentValues(PersistentObject $object) {
    $values = [];

    // attribute definitions
    $attributeDescs = $this->getAttributes();
    foreach($attributeDescs as $curAttributeDesc) {
      if ($curAttributeDesc instanceof RDBAttributeDescription) {
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
   * Convert values before putting into storage
   * @param $values Associative Array
   * @return Associative Array
   */
  protected function convertValuesForStorage($values) {
    // filter values according to type
    foreach($values as $valueName => $value) {
      $type = $this->getAttribute($valueName)->getType();
      // integer
      if (strpos(strtolower($type), 'int') === 0) {
        $value = (strlen($value ?? '') == 0) ? null : intval($value);
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
   * Get the object which carries the sortkey in the relation of the given object
   * and relative.
   * @param PersistentObjectProxy $objectProxy
   * @param PersistentObjectProxy $relativeProxy
   * @param RelationDescription $relationDesc The relation description to use (optional)
   * @return Array of PersistentObjectProxy, sortkey name and sort direction
   */
  protected function getSortableObject(PersistentObjectProxy $objectProxy,
          PersistentObjectProxy $relativeProxy, RelationDescription $relationDesc=null) {
    $relationDesc = $relationDesc ?: $objectProxy->getNodeRelation($relativeProxy);
    $sortkeyDef = $relativeProxy->getMapper()->getSortkey($relationDesc->getThisRole());
    $sortkey = $sortkeyDef['sortFieldName'];
    $sortdir = strtoupper($sortkeyDef['sortDirection']);

    $sortNode = $relativeProxy;

    // in a many to many relation, we have to modify the order of the relation objects
    if ($relationDesc instanceof RDBManyToManyRelationDescription) {
        $nmObjects = $this->loadRelationObjects($objectProxy, $relativeProxy, $relationDesc);
        $sortNode = $nmObjects[0];
    }

    return [$sortNode, $sortkey, $sortdir];
  }

  /**
   * Load the relation objects in a many to many relation from the database.
   * @param $objectProxy The proxy at this end of the relation.
   * @param $relativeProxy The proxy at the other end of the relation.
   * @param $relationDesc The RDBManyToManyRelationDescription instance describing the relation.
   * @param $includeTransaction Boolean whether to also search in the current transaction (default: false)
   * @return Array of PersistentObject instances
   */
  protected function loadRelationObjects(PersistentObjectProxy $objectProxy,
          PersistentObjectProxy $relativeProxy, RDBManyToManyRelationDescription $relationDesc,
          $includeTransaction=false) {
    $nmMapper = self::getMapper($relationDesc->getThisEndRelation()->getOtherType());
    $nmType = $nmMapper->getType();

    $thisId = $objectProxy->getOID()->getFirstId();
    $otherId = $relativeProxy->getOID()->getFirstId();
    $thisEndRelation = $relationDesc->getThisEndRelation();
    $otherEndRelation = $relationDesc->getOtherEndRelation();
    $thisFkAttr = $nmMapper->getAttribute($thisEndRelation->getFkName());
    $otherFkAttr = $nmMapper->getAttribute($otherEndRelation->getFkName());

    $criteria1 = new Criteria($nmType, $thisFkAttr->getName(), "=", $thisId);
    $criteria2 = new Criteria($nmType, $otherFkAttr->getName(), "=", $otherId);
    $criteria = [$criteria1, $criteria2];
    $nmObjects = $nmMapper->loadObjects($nmType, BuildDepth::SINGLE, $criteria);

    if ($includeTransaction) {
      $transaction = $this->persistenceFacade->getTransaction();
      $objects = $transaction->getObjects();
      foreach ($objects as $object) {
        if ($object->getType() == $nmType && $object instanceof Node) {
          // we expect single valued relation ends
          $thisEndObject = $object->getValue($thisEndRelation->getThisRole());
          $otherEndObject = $object->getValue($otherEndRelation->getOtherRole());
          if ($objectProxy->getOID() == $thisEndObject->getOID() &&
                  $relativeProxy->getOID() == $otherEndObject->getOID()) {
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
    $criterias = [];
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
    if ($this->fkRelations == null) {
      $this->fkRelations = [];
      $relationDescs = $this->getRelations();
      foreach($relationDescs as $relationDesc) {
        if ($relationDesc instanceof RDBManyToOneRelationDescription) {
          $this->fkRelations[] = $relationDesc;
        }
      }
    }
    return $this->fkRelations;
  }

  /**
   * Check if a given attribute is a foreign key (used to reference a parent)
   * @param $name The attribute name
   * @return Boolean
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
   * Get a unique string for the given parameter values
   * @param $alias
   * @param $attributeArray
   * @param $criteriaArray
   * @param $orderArray
   * @param $pagingInfo
   * @return String
   */
  protected function getCacheKey($alias, $attributeArray, $criteriaArray, $orderArray, PagingInfo $pagingInfo=null) {
    $result = $this->getRealTableName().','.$alias.',';
    if ($attributeArray != null) {
      $result .= join(',', $attributeArray);
    }
    if ($criteriaArray != null) {
      foreach ($criteriaArray as $c) {
        $result .= $c->getId();
      }
    }
    if ($orderArray != null) {
      $result .= join(',', $orderArray);
    }
    if ($pagingInfo != null) {
      $result .= ','.$pagingInfo->getOffset().','.$pagingInfo->getPageSize();
    }
    return $result;
  }
}
?>
