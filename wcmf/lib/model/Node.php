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
namespace wcmf\lib\model;

use wcmf\lib\core\ErrorHandler;
use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\core\Log;
use wcmf\lib\model\NodeUtil;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistenceMapper;
use wcmf\lib\persistence\PersistentObject;
use wcmf\lib\persistence\PersistentObjectProxy;

/**
 * Node adds the concept of relations to PersistentObject. It is the basic component for
 * building object trees (although a Node can have more than one parents).
 * The Node class implements the 'Composite Pattern'.
 * Use the methods addNode(), deleteNode() to build/modify trees.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class Node extends PersistentObject {

  const RELATION_STATE_UNINITIALIZED = -1;
  const RELATION_STATE_INITIALIZING = -2;
  const RELATION_STATE_INITIALIZED = -3;
  const RELATION_STATE_LOADED = -4;

  private $_depth = -1;
  private $_path = '';
  private $_relationStates = array();

  private $_addedNodes = array();
  private $_deletedNodes = array();
  private $_orderedNodes = array();

  /**
   * @see PersistentObject::getValue
   */
  public function getValue($name) {
    // initialize a relation value, if not done before
    $value = parent::getValue($name);
    if (isset($this->_relationStates[$name]) &&
            $this->_relationStates[$name] == Node::RELATION_STATE_UNINITIALIZED) {

      $this->_relationStates[$name] = Node::RELATION_STATE_INITIALIZING;
      $mapper = $this->getMapper();
      if ($mapper) {
        $relatives = $mapper->loadRelation($this, $name, BuildDepth::PROXIES_ONLY);
        $relDesc = $mapper->getRelation($name);
        if ($relDesc->isMultiValued()) {
          $mergeResult = self::mergeObjectLists($value, $relatives);
          $value = $mergeResult['result'];
        }
        else {
          $value = $relatives;
        }
        $this->setValueInternal($name, $value);
        $this->_relationStates[$name] = Node::RELATION_STATE_INITIALIZED;
      }
    }
    return $value;
  }

  /**
   * @see PersistentObject::setValue
   */
  public function setValue($name, $value, $forceSet=false, $trackChange=true) {
    // if the attribute is a relation, a special handling is required
    $mapper = $this->getMapper();
    if ($mapper && $mapper->hasRelation($name)) {
      if (!is_array($value)) {
        $value = array($value);
      }
      // clean the value
      parent::setValue($name, null, true, false);
      // delegate to addNode
      $result = true;
      for($i=0, $count=sizeof($value); $i<$count; $i++) {
        $curValue = $value[$i];
        if ($curValue != null) {
          $result &= $this->addNode($curValue, $name, $forceSet, $trackChange);
        }
      }
      return $result;
    }
    else {
      // default behaviour
      return parent::setValue($name, $value, $forceSet, $trackChange);
    }
    return true;
  }

  /**
   * Get Nodes that match given conditions from a list.
   * @param nodeList An array of nodes to filter or a single Node.
   * @param oid The object id that the Nodes should match [maybe null, default: null].
   * @param type The type that the Nodes should match [maybe null, default: null].
   * @param values An assoziative array holding key value pairs that the Node values should match
   *        [values are interpreted as regular expression, parameter maybe null, default: null].
   * @param properties An assoziative array holding key value pairs that the Node properties should match
   *        [values are interpreted as regular expression, parameter maybe null, default: null].
   * @param useRegExp True/False wether to interpret the given values/properties as regular expressions or not [default:true]
   * @return An Array holding references to the Nodes that matched.
   */
  public static function filter(array $nodeList, ObjectId $oid=null, $type=null, $values=null,
          $properties=null, $useRegExp=true) {

    $returnArray = array();
    for($i=0, $count=sizeof($nodeList); $i<$count; $i++) {
      $curNode = $nodeList[$i];
      if ($curNode instanceof PersistentObject || $curNode instanceof PersistentObjectProxy) {
        $match = true;
        // check oid
        if ($oid != null && $curNode->getOID() != $oid) {
          $match = false;
        }
        // check type
        if ($type != null && $curNode->getType() != $type) {
          $match = false;
        }
        // check values
        if ($values != null && is_array($values)) {
          foreach($values as $key => $value) {
            $nodeValue = $curNode->getValue($key);
            if ($useRegExp && !preg_match("/".$value."/m", $nodeValue) ||
                    !$useRegExp && $value != $nodeValue) {
              $match = false;
              break;
            }
          }
        }
        // check properties
        if ($properties != null && is_array($properties)) {
          foreach($properties as $key => $value) {
            $nodeProperty = $curNode->getProperty($key);
            if ($useRegExp && !preg_match("/".$value."/m", $nodeProperty) ||
                    !$useRegExp && $value != $nodeProperty) {
              $match = false;
              break;
            }
          }
        }
        if ($match) {
          $returnArray[] = $curNode;
        }
      }
      else {
        Log::warn(StringUtil::getDump($curNode)." found, where a PersistentObject was expected.\n".ErrorHandler::getStackTrace(),
          __CLASS__);
      }
    }
    return $returnArray;
  }

  /**
   * @see PersistentObject::mergeValues
   */
  public function mergeValues(PersistentObject $object) {
    parent::mergeValues($object);
    // implement special handling for relation values
    $mapper = $this->getMapper();
    if ($mapper) {
      foreach ($mapper->getRelations() as $curRelationDesc) {
        $valueName = $curRelationDesc->getOtherRole();
        // use parent getters to avoid loading relations
        $existingValue = parent::getValue($valueName);
        $newValue = parent::getValue($valueName);
        if ($curRelationDesc->isMultiValued()) {
          $mergeResult = self::mergeObjectLists($existingValue, $newValue);
          $newValue = $mergeResult['result'];
          $this->setValueInternal($valueName, $newValue);
        }
        elseif ($existingValue instanceof PersistentObjectProxy &&
                  $newValue instanceof PersistentObject) {
            $this->setValueInternal($valueName, $newValue);
        }
      }
    }
  }

  /**
   * Merge two object lists using the following rules:
   * - proxies in list1 are replaced by the appropriate objects from list2
   * - proxies/objects from list2 that don't exist in list1 are added to list1
   * @param list1 Array of PersistentObject(Proxy) instances
   * @param list2 Array of PersistentObject(Proxy) instances
   * @return Associative array with keys 'result' and 'added' and arrays of
   *   all and only added objects respectively.
   */
  protected static function mergeObjectLists($list1, $list2) {
    // ensure arrays
    if (!is_array($list1)) {
      $list1 = array();
    }
    if (!is_array($list2)) {
      $list2 = array();
    }
    // create hashtables for better search performance
    $list1Map = array();
    $added = array();
    foreach ($list1 as $curObject) {
      $list1Map[$curObject->getOID()->__toString()] = $curObject;
    }
    // merge
    foreach ($list2 as $curObject) {
      $curOidStr = $curObject->getOID()->__toString();
      if (!isset($list1Map[$curOidStr])) {
        // add the object, if it doesn't exist yet
        $list1Map[$curOidStr] = $curObject;
        $added[] = $curObject;
      }
      elseif ($list1Map[$curOidStr] instanceof PersistentObjectProxy &&
              $curObject instanceof PersistentObject) {
        // overwrite a proxy by a real subject
        $list1Map[$curOidStr] = $curObject;
      }
    }
    return array('result' => array_values($list1Map), 'added' => $added);
  }

  /**
   * Get the number of children of the Node.
   * @param memOnly True/False wether to only get the number of loaded children or all children [default: true].
   * @return The number of children.
   */
  public function getNumChildren($memOnly=true) {
    return $this->getNumRelatives('child', $memOnly);
  }

  /**
   * Add a Node to the given relation. Delegates to setValue internally.
   * @param other PersistentObject or PersistentObjectProxy
   * @param role The role of the Node in the created relation. If null, the role will be
   *        the Node's type. [default: null]
   * @param forceSet @see PersistentObject::setValue()
   * @param trackChange @see PersistentObject::setValue()
   * @param updateOtherSide True/False wether to update also the other side of the relation [default: true]
   * @return Boolean wether the operation succeeds or not
   */
  public function addNode($other, $role=null, $forceSet=false, $trackChange=true, $updateOtherSide=true) {

    if (!($other instanceof PersistentObject) && !($other instanceof PersistentObjectProxy)) {
      throw new IllegalArgumentException("Node::addNode expects a PersistentObject or ".
        "PersistentObjectProxy as first argument.", __CLASS__);
    }
    if ($role == null) {
      $role = $other->getType();
    }

    // get the relation description
    $relDesc = null;
    $mapper = $this->getMapper();
    if ($mapper) {
      $relDesc = $mapper->getRelation($role);
    }

    $value = $other;
    $oldValue = parent::getValue($role);
    $addedNodes = array(); // this array contains the other node or nothing
    if (!$relDesc || $relDesc->isMultiValued()) {
      // make sure that the value is an array if multivalued
      $mergeResult = self::mergeObjectLists($oldValue, array($value));
      $value = $mergeResult['result'];
      $addedNodes = $mergeResult['added'];
    }
    elseif ($oldValue != $value) {
      $addedNodes[] = $value;
    }
    $result1 = parent::setValue($role, $value, $forceSet, $trackChange);

    // remember the addition
    if (sizeof($addedNodes) > 0) {
      if (!isset($this->_addedNodes[$role])) {
        $this->_addedNodes[$role] = array();
      }
      $this->_addedNodes[$role][] = $other->getOID();
    }

    // propagate add action to the other object
    $result2 = true;
    if ($updateOtherSide) {
      $thisRole = $this->getType();
      if ($relDesc) {
        $thisRole = $relDesc->getThisRole();
      }
      $result2 = $other->addNode($this, $thisRole, $forceSet, $trackChange, false);
    }
    return ($result1 & $result2);
  }

  /**
   * Get the object ids of the nodes that were added since the node was loaded.
   * Persistence mappers use this method when persisting the node relations.
   * @return Associative array with the roles as keys and an array of ObjectId instances
   *  as values
   */
  public function getAddedNodes() {
    return $this->_addedNodes;
  }

  /**
   * Delete a Node from the given relation.
   * @param oid The object id of the Node to delete.
   * @param role The role of the Node. If null, the role is the Node's type. [default: null]
   * @param updateOtherSide True/False wether to update also the other side of the relation [default: true]
   */
  public function deleteNode(PersistentObject $other, $role=null, $updateOtherSide=true) {
    if ($role == null) {
      $role = $other->getType();
    }

    $nodes = $this->getValue($role);
    if (empty($nodes)) {
      // nothing to delete
      return;
    }

    // get the relation description
    $relDesc = null;
    $mapper = $this->getMapper();
    if ($mapper) {
      $relDesc = $mapper->getRelation($role);
    }

    $oid = $other->getOID();
    if (is_array($nodes)) {
      // multi valued relation
      for($i=0, $count=sizeof($nodes); $i<$count; $i++) {
        if ($nodes[$i]->getOID() == $oid) {
          // remove child
          array_splice($nodes, $i, 1);
          break;
        }
      }
    }
    else {
      // single valued relation
      if ($nodes->getOID() == $oid) {
        // remove child
        $nodes = null;
      }
    }
    parent::setValue($role, $nodes);

    // remember the deletion
    if (!isset($this->_deletedNodes[$role])) {
      $this->_deletedNodes[$role] = array();
    }
    $this->_deletedNodes[$role][] = $other->getOID();
    $this->setState(PersistentOBject::STATE_DIRTY);

    // propagate add action to the other object
    if ($updateOtherSide) {
      $thisRole = $this->getType();
      if ($relDesc) {
        $thisRole = $relDesc->getThisRole();
      }
      $other->deleteNode($this, $thisRole, false);
    }
  }

  /**
   * Get the object ids of the nodes that were deleted since the node was loaded.
   * Persistence mappers use this method when persisting the node relations.
   * @return Associative array with the roles as keys and an array of ObjectId instances
   *  as values
   */
  public function getDeletedNodes() {
    return $this->_deletedNodes;
  }

  /**
   * Define the order of related Node instances. The mapper is responsible for
   * persisting the order of the given Node instances in relation to this Node.
   * @note Note instances, that are not explicitly sortable by a sortkey
   * (@see PersistenceMapper::getDefaultOrder()) will be ignored. If a given
   * Node instance is not related to this Node yet, an exception will be thrown.
   * Any not persisted definition of a previous call will be overwritten
   * @param nodeList Array of sorted Node instances
   */
  public function setNodeOrder(array $nodeList) {
    $this->_orderedNodes = $nodeList;
    $this->setState(PersistentOBject::STATE_DIRTY);
  }

  /**
   * Get the order of related Node instances, if it was defined using
   * the Node::setNodeOrder() method.
   * @return Array of sorted Node instances
   */
  public function getNodeOrder() {
    return $this->_orderedNodes;
  }

  /**
   * Load the children of a given role and add them. If all children should be
   * loaded, set the role parameter to null.
   * @param role The role of children to load (maybe null, to load all children) [default: null]
   * @param buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        [default: BuildDepth::SINGLE)]
   */
  public function loadChildren($role=null, $buildDepth=BuildDepth::SINGLE) {
    if ($role != null) {
      $this->loadRelations(array($role), $buildDepth);
    }
    else {
      $this->loadRelations(array_keys($this->getPossibleChildren()), $buildDepth);
    }
  }

  /**
   * Get the first child that matches given conditions.
   * @param role The role that the child should match [maybe null, default: null].
   * @param type The type that the child should match [maybe null, default: null].
   * @param values An assoziative array holding key value pairs that the child values should match [maybe null, default: null].
   * @param properties An assoziative array holding key value pairs that the child properties should match [maybe null, default: null].
   * @param useRegExp True/False wether to interpret the given values/properties as regular expressions or not [default:true]
   * @return Node instance or null.
   */
  public function getFirstChild($role=null, $type=null, $values=null, $properties=null, $useRegExp=true) {
    $children = $this->getChildrenEx(null, $role, $type, $values, $properties, $useRegExp);
    if (sizeof($children) > 0) {
      return $children[0];
    }
    else {
      return null;
    }
  }

  /**
   * Get the Node's children.
   * @param memOnly True/False wether to only get the loaded children or all children [default: true].
   * @return An array of Node and/or PersistentObjectProxy instances.
   */
  public function getChildren($memOnly=true) {
    return $this->getRelatives('child', $memOnly);
  }

  /**
   * Get the children that match given conditions.
   * @note This method will only return objects that are already loaded, to get all objects in
   * the given relation (including proxies), use the Node::getValue() method and filter the returned
   * list afterwards.
   * @param oid The object id that the children should match [maybe null, default: null].
   * @param role The role that the children should match [maybe null, default: null].
   * @param type The type that the children should match [maybe null, default: null].
   * @param values An assoziative array holding key value pairs that the children values should match [maybe null, default: null].
   * @param properties An assoziative array holding key value pairs that the children properties should match [maybe null, default: null].
   * @param useRegExp True/False wether to interpret the given values/properties as regular expressions or not [default:true]
   * @return Array containing children Nodes that matched (proxies not included).
   */
  public function getChildrenEx(ObjectId $oid=null, $role=null, $type=null, $values=null,
          $properties=null, $useRegExp=true) {
    if ($role != null) {
      // nodes of a given role are requested
      // make sure it is a child role
      $childRoles = $this->getPossibleChildren();
      if (!isset($childRoles[$role])) {
        throw new IllegalArgumentException("No child role defined with name: ".$role);
      }
      // we are only looking for nodes that are in memory already
      $nodes = parent::getValue($role);
      if (!is_array($nodes)) {
        $nodes = array($nodes);
      }
      // sort out proxies
      $children = array();
      foreach($nodes as $curNode) {
        if ($curNode instanceof PersistentObject) {
          $children[] = $curNode;
        }
      }
      return self::filter($children, $oid, $type, $values, $properties, $useRegExp);
    }
    else {
      return self::filter($this->getChildren(), $oid, $type, $values, $properties, $useRegExp);
    }
  }

  /**
   * Get possible chilren of this node type (independent of existing children).
   * @return An Array with role names as keys and RelationDescription instances as values.
   */
  public function getPossibleChildren() {
    $result = array();
    $relations = $this->getRelations('child');
    foreach ($relations as $curRelation) {
      $result[$curRelation->getOtherRole()] = $curRelation;
    }
    return $result;
  }

  /**
   * Load the parents of a given role and add them. If all parents should be
   * loaded, set the role parameter to null.
   * @param role The role of parents to load (maybe null, to load all parents) [default: null]
   * @param buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        [default: BuildDepth::SINGLE)]
   */
  public function loadParents($role=null, $buildDepth=BuildDepth::SINGLE) {
    if ($role != null) {
      $this->loadRelations(array($role), $buildDepth);
    }
    else {
      $this->loadRelations(array_keys($this->getPossibleParents()), $buildDepth);
    }
  }

  /**
   * Get the number of parents of the Node.
   * @param memOnly True/False wether to only get the number of loaded parents or all parents [default: true].
   * @return The number of parents.
   */
  public function getNumParents($memOnly=true) {
    return $this->getNumRelatives('parent', $memOnly);
  }

  /**
   * Get the Node's parent. This method exists for compatibility with previous
   * versions. It returns the first parent.
   * @return A reference to the Nodes parent.
   */
  public function getParent() {
    $parents = $this->getParents();
    if (sizeof($parents) > 0) {
      return $parents[0];
    }
    else {
      return null;
    }
  }

  /**
   * Get the first parent that matches given conditions.
   * @param role The role that the parent should match [maybe null, default: null].
   * @param type The type that the parent should match [maybe null, default: null].
   * @param values An assoziative array holding key value pairs that the parent values should match [maybe null, default: null].
   * @param properties An assoziative array holding key value pairs that the parent properties should match [maybe null, default: null].
   * @param useRegExp True/False wether to interpret the given values/properties as regular expressions or not [default:true]
   * @return Node instance or null.
   */
  public function getFirstParent($role=null, $type=null, $values=null, $properties=null, $useRegExp=true) {

    $parents = $this->getParentsEx(null, $role, $type, $values, $properties, $useRegExp);
    if (sizeof($parents) > 0) {
      return $parents[0];
    }
    else {
      return null;
    }
  }

  /**
   * Get the Nodes parents.
   * @param memOnly True/False wether to only get the loaded parents or all parents [default: true].
   * @return An array of Node and/or PersistentObjectProxy instances.
   */
  public function getParents($memOnly=true) {
    return $this->getRelatives('parent', $memOnly);
  }

  /**
   * Get the parents that match given conditions.
   * @note This method will only return objects that are already loaded, to get all objects in
   * the given relation (including proxies), use the Node::getValue() method and filter the returned
   * list afterwards.
   * @param oid The object id that the parent should match [maybe null, default: null].
   * @param role The role that the parents should match [maybe null, default: null].
   * @param type The type that the parents should match [maybe null, default: null].
   * @param values An assoziative array holding key value pairs that the parent values should match [maybe null, default: null].
   * @param properties An assoziative array holding key value pairs that the parent properties should match [maybe null, default: null].
   * @param useRegExp True/False wether to interpret the given values/properties as regular expressions or not [default:true]
   * @return Array containing parent Nodes that matched (proxies not included).
   */
  public function getParentsEx(ObjectId $oid=null, $role=null, $type=null, $values=null,
          $properties=null, $useRegExp=true) {
    if ($role != null) {
      // nodes of a given role are requested
      // make sure it is a parent role
      $parentRoles = $this->getPossibleParents();
      if (!isset($parentRoles[$role])) {
        throw new IllegalArgumentException("No parent role defined with name: ".$role);
      }
      // we are only looking for nodes that are in memory already
      $nodes = parent::getValue($role);
      if (!is_array($nodes)) {
        $nodes = array($nodes);
      }
      // sort out proxies
      $parents = array();
      foreach($nodes as $curNode) {
        if ($curNode instanceof PersistentObject) {
          $parents[] = $curNode;
        }
      }
      return self::filter($parents, $oid, $type, $values, $properties, $useRegExp);
    }
    else {
      return self::filter($this->getParents(), $oid, $type, $values, $properties, $useRegExp);
    }
  }

  /**
   * Get possible parents of this node type (independent of existing parents).
   * @return An Array with role names as keys and RelationDescription instances as values.
   */
  public function getPossibleParents() {
    $result = array();
    $relations = $this->getRelations('parent');
    foreach ($relations as $curRelation) {
      $result[$curRelation->getOtherRole()] = $curRelation;
    }
    return $result;
  }

  /**
   * Get the relation description for a given node.
   * @param node The Node/PersistentObjectProxy instance to look for
   * @return RelationDescription instance or null, if the Node is not related
   */
  public function getNodeRelation($node) {
    $relations = $this->getRelations();
    foreach ($relations as $curRelation) {
      $curRelatives = parent::getValue($curRelation->getOtherRole());
      if ($curRelatives instanceof Node && $curRelatives->getOID() == $node->getOID()) {
        return $curRelation;
      }
      elseif (is_array($curRelatives)) {
        foreach ($curRelatives as $curRelative) {
          if ($curRelative->getOID() == $node->getOID()) {
            return $curRelation;
          }
        }
      }
    }
    return null;
  }

  /**
   * Load all objects in the given set of relations
   * @param roles An array of relation (=role) names
   * @param buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        [default: BuildDepth::SINGLE)]
   */
  protected function loadRelations(array $roles, $buildDepth=BuildDepth::SINGLE) {
    $oldState = $this->getState();
    foreach ($roles as $curRole) {
      if (isset($this->_relationStates[$curRole]) &&
              $this->_relationStates[$curRole] != Node::RELATION_STATE_LOADED) {
        $relatives = array();

        // resolve proxies if the relation is already initialized
        if ($this->_relationStates[$curRole] == Node::RELATION_STATE_INITIALIZED) {
          $proxies = $this->getValue($curRole);
          if (is_array($proxies)) {
            foreach ($proxies as $curRelative) {
              if ($curRelative instanceof PersistentObjectProxy) {
                $curRelative->resolve($buildDepth);
                $relatives[] = $curRelative->getRealSubject();
              }
              else {
                $relatives[] = $curRelative;
              }
            }
          }
        }
        // otherwise load the objects directly
        else {
          $mapper = $this->getMapper();
          if ($mapper) {
            $relatives = $mapper->loadRelation($this, $curRole, $buildDepth);
          }
        }
        $this->setValueInternal($curRole, $relatives);
        $this->_relationStates[$curRole] = Node::RELATION_STATE_LOADED;
      }
    }
    $this->setState($oldState);
  }

  /**
   * Get the relation descriptions of a given hierarchyType.
   * @param hierarchyType @see PersistenceMapper::getRelations [default: 'all']
   * @return An array containing the RelationDescription instances.
   */
  protected function getRelations($hierarchyType='all') {
    $mapper = $this->getMapper();
    if ($mapper != null) {
      return $mapper->getRelations($hierarchyType);
    }
    return array();
  }

  /**
   * Get the relatives of a given hierarchyType.
   * @param hierarchyType @see PersistenceMapper::getRelations
   * @param memOnly Boolean wether to only get the relatives in memory or all relatives (including proxies) [default: true].
   * @return An array containing the relatives.
   */
  protected function getRelatives($hierarchyType, $memOnly=true) {
    $relatives = array();
    $relations = $this->getRelations($hierarchyType);
    foreach ($relations as $curRelation) {
      $curRelatives = null;
      if ($memOnly) {
        $curRelatives = parent::getValue($curRelation->getOtherRole());
      }
      else {
        $curRelatives = $this->getValue($curRelation->getOtherRole());
      }
      if (!$curRelatives) {
        continue;
      }
      if (!is_array($curRelatives)) {
        $curRelatives = array($curRelatives);
      }
      foreach ($curRelatives as $curRelative) {
        if ($curRelative instanceof PersistentObjectProxy && $memOnly) {
          continue;
        }
        else {
          $relatives[] = $curRelative;
        }
      }
    }
    return $relatives;
  }

  /**
   * Get the number of relatives of a given hierarchyType.
   * @param hierarchyType @see PersistenceMapper::getRelations
   * @param memOnly True/False wether to only get the number of the relatives in memory or all relatives [default: true].
   * @return The number of relatives.
   */
  protected function getNumRelatives($hierarchyType, $memOnly=true) {
    return sizeof($this->getRelatives($hierarchyType, $memOnly));
  }

  /**
   * Get the Nodes depth.
   * @return The number of parents of the Node.
   */
  public function getDepth() {
    $this->_depth = 0;
    $parent = $this->getParent();
    while ($parent != null && $parent instanceof Node) {
      $this->_depth++;
      $parent = $parent->getParent();
    }
    return $this->_depth;
  }

  /**
   * Get the Nodes path (to root).
   * @return The Node path.
   */
  public function getPath() {
    $this->_path = $this->getType();
    $parent = $this->getParent();
    while ($parent != null && $parent instanceof Node)
    {
      $this->_path = $parent->getType().'/'.$this->_path;
      $parent = $parent->getParent();
    }
    return $this->_path;
  }

  /**
   * Accept a Visitor. For use with the 'Visitor Pattern'.
   * @param visitor The Visitor.
   */
  public function acceptVisitor($visitor) {
    $visitor->visit($this);
  }

  /**
   * Add an uninitialized relation. The relation will be
   * initialized (proxies for related objects will be added)
   * on first access.
   * @param name The relation name (= role)
   */
  public function addRelation($name) {
    if (!$this->hasValue($name)) {
      $this->_relationStates[$name] = Node::RELATION_STATE_UNINITIALIZED;
      $this->setValueInternal($name, null);
    }
  }

  /**
   * Output
   */

  /**
   * @see PersistentObject::getDisplayValue()
   * Delegates to NodeUtil::getDisplayValue
   */
  public function getDisplayValue($useDisplayType=false) {
    return NodeUtil::getDisplayValue($this, $useDisplayType);
  }

  /**
   * Delegates to NodeUtil::getDisplayValues
   */
  public function getDisplayValues($useDisplayType=false) {
    return NodeUtil::getDisplayValues($this, $useDisplayType);
  }

  /**
   * Get a string representation of the Node.
   * @return The string representation of the Node.
   */
  public function __toString() {
    $pStr = parent::__toString();
    $str = $this->getDisplayValue();
    if ($pStr != $str) {
      return $this->getDisplayValue().' ['.$pStr.']';
    }
    return $str;
  }
}
?>
