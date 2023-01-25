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
namespace wcmf\lib\model;

use wcmf\lib\core\ErrorHandler;
use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\core\LogTrait;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\NodeUtil;
use wcmf\lib\model\visitor\Visitor;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\impl\DefaultPersistentObject;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistentObject;
use wcmf\lib\persistence\PersistentObjectProxy;
use wcmf\lib\persistence\RelationDescription;
use wcmf\lib\util\StringUtil;

/**
 * Node adds the concept of relations to PersistentObject. It is the basic component for
 * building object trees (although a Node can have more than one parents).
 * Relations are stored as values where the value name is the role name.
 * The Node class implements the _Composite Pattern_.
 * Use the methods addNode(), deleteNode() to build/modify trees.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class Node extends DefaultPersistentObject {
  use LogTrait;

  const RELATION_STATE_UNINITIALIZED = -1;
  const RELATION_STATE_INITIALIZING = -2;
  const RELATION_STATE_INITIALIZED = -3;
  const RELATION_STATE_LOADED = -4;

  private array $relationStates = [];

  private array $addedNodes = [];
  private array $deletedNodes = [];
  private ?array $orderedNodes = null;

  private static ?\ReflectionMethod $parentGetValueMethod = null;

  /**
   * Constructor
   * @param $oid ObjectId instance (optional)
   * @param $initialData Associative array with initial data to override default data (optional)
   */
  public function __construct(?ObjectId $oid=null, ?array $initialData=null) {
    parent::__construct($oid, $initialData);
    // get parent::getValue method by reflection
    if (self::$parentGetValueMethod == null) {
      $reflector = new \ReflectionClass(__CLASS__);
      $parent = $reflector->getParentClass();
      self::$parentGetValueMethod = $parent->getMethod('getValue');
    }
  }

  /**
   * @see PersistentObject::__clone()
   */
  public function __clone() {
    $copy = parent::__clone();
    $copy->relationStates = $this->relationStates;

    return $copy;
  }

  /**
   * @see PersistentObject::getValueNames()
   */
  public function getValueNames(bool $excludeTransient=false): array {
    // exclude relations
    $allAttributes = parent::getValueNames($excludeTransient);
    $attributes = [];
    $mapper = $this->getMapper();
    foreach ($allAttributes as $attribute) {
      if (!$mapper->hasRelation($attribute)) {
        $attributes[] = $attribute;
      }
    }
    return $attributes;
  }

  /**
   * @see PersistentObject::getValue()
   */
  public function getValue($name) {
    // initialize a relation value, if not done before
    $value = parent::getValue($name);
    if (isset($this->relationStates[$name]) &&
            $this->relationStates[$name] == self::RELATION_STATE_UNINITIALIZED) {

      $this->relationStates[$name] = self::RELATION_STATE_INITIALIZING;
      $mapper = $this->getMapper();
      $allRelatives = $mapper->loadRelation([$this], $name, BuildDepth::PROXIES_ONLY);
      $oidStr = $this->getOID()->__toString();
      $relatives = isset($allRelatives[$oidStr]) ? $allRelatives[$oidStr] : null;
      $relDesc = $mapper->getRelation($name);
      if ($relDesc->isMultiValued()) {
        $mergeResult = self::mergeObjectLists($value, $relatives);
        $value = $mergeResult['result'];
      }
      else {
        $value = $relatives != null ? $relatives[0] : null;
      }
      $this->setValueInternal($name, $value);
      $this->relationStates[$name] = self::RELATION_STATE_INITIALIZED;
    }
    return $value;
  }

  /**
   * @see PersistentObject::setValue()
   */
  public function setValue(string $name, $value, bool $forceSet=false, bool $trackChange=true): bool {
    // if the attribute is a relation, a special handling is required
    $mapper = $this->getMapper();
    if ($mapper->hasRelation($name)) {
      if (!is_array($value)) {
        $value = [$value];
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
      $this->relationStates[$name] = self::RELATION_STATE_INITIALIZED;
      return $result;
    }
    // default behaviour
    return parent::setValue($name, $value, $forceSet, $trackChange);
  }

  /**
   * @see PersistentObject::removeValue()
   */
  public function removeValue(string $name): void {
    parent::removeValue($name);
    // set relation state to loaded in order to prevent lazy initialization
    $mapper = $this->getMapper();
    if ($mapper->hasRelation($name)) {
      $this->relationStates[$name] = self::RELATION_STATE_LOADED;
    }
  }

  /**
   * @see PersistentObject::getIndispensableObjects()
   */
  public function getIndispensableObjects(): array {
    // return the parent objects
    return $this->getParents();
  }

  /**
   * Get Nodes that match given conditions from a list.
   * @param $nodeList An array of nodes to filter or a single Node.
   * @param $oid The object id that the Nodes should match (optional, default: _null_)
   * @param $type The type that the Nodes should match (either fully qualified or simple, if not ambiguous)
   *        (optional, default: _null_)
   * @param $values An associative array holding key value pairs that the Node values should match
   *        (values are interpreted as regular expression, optional, default: _null_)
   * @param $properties An associative array holding key value pairs that the Node properties should match
   *        (values are interpreted as regular expression, optional, default: _null_)
   * @param $useRegExp Boolean whether to interpret the given values/properties as regular expressions or not (default: _true_)
   * @return array holding references to the Nodes that matched.
   */
  public static function filter(array $nodeList, ?ObjectId $oid=null, ?string $type=null, ?array $values=null,
          ?array $properties=null, ?bool $useRegExp=true): array {

    $returnArray = [];
    for ($i=0, $count=sizeof($nodeList); $i<$count; $i++) {
      $curNode = $nodeList[$i];
      if ($curNode instanceof PersistentObject) {
        $match = true;
        // check oid
        if ($oid != null && $curNode->getOID() != $oid) {
          $match = false;
        }
        // check type
        if ($type != null) {
          $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
          $fqType = $persistenceFacade->getFullyQualifiedType($type);
          if ($fqType != null && $curNode->getType() != $fqType) {
            $match = false;
          }
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
        self::logger()->warning(StringUtil::getDump($curNode)." found, where a PersistentObject was expected.\n".ErrorHandler::getStackTrace());
      }
    }
    return $returnArray;
  }

  /**
   * @see PersistentObject::mergeValues
   */
  public function mergeValues(PersistentObject $object): void {
    parent::mergeValues($object);
    // implement special handling for relation values
    $mapper = $this->getMapper();
    foreach ($mapper->getRelations() as $curRelationDesc) {
      $valueName = $curRelationDesc->getOtherRole();
      // use parent getters to avoid loading relations
      $existingValue = self::$parentGetValueMethod->invokeArgs($this, [$valueName]);
      $newValue = self::$parentGetValueMethod->invokeArgs($object, [$valueName]);
      if ($newValue != null) {
        if ($curRelationDesc->isMultiValued()) {
          $mergeResult = self::mergeObjectLists($existingValue, $newValue);
          $newValue = $mergeResult['result'];
        }
        $this->setValueInternal($valueName, $newValue);
      }
    }
  }

  /**
   * Merge two object lists using the following rules:
   * - proxies in list1 are replaced by the appropriate objects from list2
   * - proxies/objects from list2 that don't exist in list1 are added to list1
   * @param ?array $list1 Array of PersistentObject(Proxy) instances
   * @param ?array $list2 Array of PersistentObject(Proxy) instances
   * @return array with keys 'result' and 'added' and arrays of
   *   all and only added objects respectively.
   */
  protected static function mergeObjectLists(?array $list1, ?array $list2): array {
    // ensure arrays
    if (!is_array($list1)) {
      $list1 = [];
    }
    if (!is_array($list2)) {
      $list2 = [];
    }
    // create hashtables for better search performance
    $list1Map = [];
    $added = [];
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
    return ['result' => array_values($list1Map), 'added' => $added];
  }

  /**
   * Get the names of all relations.
   * @return array of relation names.
   */
  public function getRelationNames(): array {
    $result = [];
    $relations = $this->getRelations();
    foreach ($relations as $curRelation) {
      $result[] = $curRelation->getOtherRole();
    }
    return $result;
  }

  /**
   * Add a Node to the given relation. Delegates to setValue internally.
   * @param $other Node
   * @param $role The role of the Node in the created relation. If null, the role will be
   *        the Node's simple type (without namespace) (default: _null_)
   * @param $forceSet @see PersistentObject::setValue()
   * @param $trackChange @see PersistentObject::setValue()
   * @param $updateOtherSide Boolean whether to update also the other side of the relation (default: _true_)
   * @return bool whether the operation succeeds or not
   */
  public function addNode(Node $other, ?string $role=null, ?bool $forceSet=false, ?bool $trackChange=true, ?bool $updateOtherSide=true): bool {
    $mapper = $this->getMapper();

    // set role if missing
    if ($role == null) {
      $otherType = $other->getType();
      $relations = $mapper->getRelationsByType($otherType);
      $role = (sizeof($relations) > 0) ? $relations[0]->getOtherRole() : $otherType;
    }

    // get the relation description
    $relDesc = $mapper->getRelation($role);

    $value = $other;
    $oldValue = parent::getValue($role);
    $addedNodes = []; // this array contains the other node or nothing
    if (!$relDesc || $relDesc->isMultiValued()) {
      // check multiplicity if multivalued
      $maxMultiplicity = $relDesc->getOtherMaxMultiplicity();
      if ($relDesc->isMultiValued() && !($maxMultiplicity == 'unbounded') &&
          sizeof($oldValue) >= $maxMultiplicity) {
            throw new IllegalArgumentException("Maximum number of related objects exceeded: ".$role." (".(sizeof($oldValue)+1)." > ".$maxMultiplicity.")");
      }
      // make sure that the value is an array if multivalued
      $mergeResult = self::mergeObjectLists($oldValue, [$value]);
      $value = $mergeResult['result'];
      $addedNodes = $mergeResult['added'];
    }
    elseif ($oldValue == null && $value != null ||
            $oldValue->getOID()->__toString() != $value->getOID()->__toString()) {
      $addedNodes[] = $value;
    }
    $result1 = sizeof($addedNodes) > 0 && parent::setValue($role, $value, $forceSet, $trackChange);

    // remember the addition
    if (sizeof($addedNodes) > 0) {
      if (!isset($this->addedNodes[$role])) {
        $this->addedNodes[$role] = [];
      }
      $this->addedNodes[$role][] = $other;
    }

    // propagate add action to the other object
    $result2 = true;
    if ($updateOtherSide) {
      $thisRole = $relDesc ? $relDesc->getThisRole() : null;
      $result2 = $other->addNode($this, $thisRole, $forceSet, $trackChange, false);
    }
    return ($result1 & $result2);
  }

  /**
   * Get the object ids of the nodes that were added since the node was loaded.
   * Persistence mappers use this method when persisting the node relations.
   * @return array with the roles as keys and an array of PersistentObject instances
   *  as values
   */
  public function getAddedNodes(): array {
    return $this->addedNodes;
  }

  /**
   * Delete a Node from the given relation.
   * @param $other The Node to delete.
   * @param $role The role of the Node. If null, the role is the Node's type (without namespace) (default: _null_)
   * @param $updateOtherSide Boolean whether to update also the other side of the relation (default: _true_)
   */
  public function deleteNode(Node $other, ?string $role=null, ?bool $updateOtherSide=true): void {

    $mapper = $this->getMapper();

    // set role if missing
    if ($role == null) {
      $otherType = $other->getType();
      $relations = $mapper->getRelationsByType($otherType);
      $role = (sizeof($relations) > 0) ? $relations[0]->getOtherRole() : $otherType;
    }

    $nodes = $this->getValue($role);
    if (empty($nodes)) {
      // nothing to delete
      return;
    }

    // get the relation description
    $relDesc = $mapper->getRelation($role);

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
    if (!isset($this->deletedNodes[$role])) {
      $this->deletedNodes[$role] = [];
    }
    $this->deletedNodes[$role][] = $other->getOID();
    $this->setState(PersistentOBject::STATE_DIRTY);

    // propagate add action to the other object
    if ($updateOtherSide) {
      $thisRole = $relDesc ? $relDesc->getThisRole() : null;
      $other->deleteNode($this, $thisRole, false);
    }
  }

  /**
   * Get the object ids of the nodes that were deleted since the node was loaded.
   * Persistence mappers use this method when persisting the node relations.
   * @return array with the roles as keys and an array of ObjectId instances
   *  as values
   */
  public function getDeletedNodes(): array {
    return $this->deletedNodes;
  }

  /**
   * Define the order of related Node instances. The mapper is responsible for
   * persisting the order of the given Node instances in relation to this Node.
   * @note Note instances, that are not explicitly sortable by a sortkey
   * (@see PersistenceMapper::getDefaultOrder()) will be ignored. If a given
   * Node instance is not related to this Node yet, an exception will be thrown.
   * Any not persisted definition of a previous call will be overwritten
   * @param $orderedList Array of ordered Node instances
   * @param $movedList Array of repositioned Node instances (optional, improves performance)
   * @param $role Role name of the Node instances (optional)
   */
  public function setNodeOrder(array $orderedList, ?array $movedList=null, ?string $role=null): void {
    $this->orderedNodes = [
        'ordered' => $orderedList,
        'moved' => $movedList,
        'role' => $role
    ];
    $this->setState(PersistentOBject::STATE_DIRTY);
  }

  /**
   * Get the order of related Node instances, if it was defined using
   * the Node::setNodeOrder() method.
   * @return array of with keys 'ordered', 'moved', 'role' or null
   */
  public function getNodeOrder(): array {
    return $this->orderedNodes;
  }

  /**
   * Load the children of a given role and add them. If all children should be
   * loaded, set the role parameter to null.
   * @param $role The role of children to load (maybe null, to load all children) (default: _null_)
   * @param $buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        (default: _BuildDepth::SINGLE_)
   */
  public function loadChildren(?string $role=null, ?int $buildDepth=BuildDepth::SINGLE): void {
    if ($role != null) {
      $this->loadRelations([$role], $buildDepth);
    }
    else {
      $this->loadRelations(array_keys($this->getPossibleChildren()), $buildDepth);
    }
  }

  /**
   * Get the number of children of the Node.
   * @param $memOnly Boolean whether to only get the number of loaded children or all children (default: _true_).
   * @return int
   */
  public function getNumChildren(bool $memOnly=true): int {
    return $this->getNumRelatives('child', $memOnly);
  }

  /**
   * Get the first child that matches given conditions.
   * @param $role The role that the child should match (optional, default: _null_).
   * @param $type The type that the child should match (either fully qualified or simple, if not ambiguous)
   *        (optional, default: _null_).
   * @param $values An associative array holding key value pairs that the child values should match (optional, default: _null_).
   * @param $properties An associative array holding key value pairs that the child properties should match (optional, default: _null_).
   * @param $useRegExp Boolean whether to interpret the given values/properties as regular expressions or not (default: _true_)
   * @return Node instance or null.
   */
  public function getFirstChild(?string $role=null, ?string $type=null, ?array $values=null, ?bool $properties=null, ?bool $useRegExp=true): ?Node {
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
   * @param $memOnly Boolean whether to only get the loaded children or all children (default: _true_).
   * @return array PersistentObject instances.
   */
  public function getChildren(?bool $memOnly=true): array {
    return $this->getRelatives('child', $memOnly);
  }

  /**
   * Get the children that match given conditions.
   * @note This method will only return objects that are already loaded, to get all objects in
   * the given relation (including proxies), use the Node::getValue() method and filter the returned
   * list afterwards.
   * @param $oid The object id that the children should match (optional, default: _null_).
   * @param $role The role that the children should match (optional, default: _null_).
   * @param $type The type that the children should match (either fully qualified or simple, if not ambiguous)
   *        (optional, default: _null_).
   * @param $values An associative array holding key value pairs that the children values should match (optional, default: _null_).
   * @param $properties An associative array holding key value pairs that the children properties should match (optional, default: _null_).
   * @param $useRegExp Boolean whether to interpret the given values/properties as regular expressions or not (default: _true_)
   * @return array containing children Nodes that matched (proxies not included).
   */
  public function getChildrenEx(?ObjectId $oid=null, ?string $role=null, ?string $type=null, ?array $values=null,
          ?array $properties=null, ?bool $useRegExp=true): array {
    $result = [];
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
        $nodes = [$nodes];
      }
      // sort out proxies
      $children = [];
      foreach($nodes as $curNode) {
        if ($curNode instanceof PersistentObject) {
          $children[] = $curNode;
        }
      }
      $result = self::filter($children, $oid, $type, $values, $properties, $useRegExp);
    }
    else {
      $result = self::filter($this->getChildren(), $oid, $type, $values, $properties, $useRegExp);
    }
    return $result;
  }

  /**
   * Get possible children of this node type (independent of existing children).
   * @return array with role names as keys and RelationDescription instances as values.
   */
  public function getPossibleChildren(): array {
    $result = [];
    $relations = $this->getRelations('child');
    foreach ($relations as $curRelation) {
      $result[$curRelation->getOtherRole()] = $curRelation;
    }
    return $result;
  }

  /**
   * Load the parents of a given role and add them. If all parents should be
   * loaded, set the role parameter to null.
   * @param $role The role of parents to load (maybe null, to load all parents) (default: _null_)
   * @param $buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        (default: _BuildDepth::SINGLE_)
   */
  public function loadParents(?string $role=null, ?int $buildDepth=BuildDepth::SINGLE): void {
    if ($role != null) {
      $this->loadRelations([$role], $buildDepth);
    }
    else {
      $this->loadRelations(array_keys($this->getPossibleParents()), $buildDepth);
    }
  }

  /**
   * Get the number of parents of the Node.
   * @param $memOnly Boolean whether to only get the number of loaded parents or all parents (default: _true_).
   * @return int
   */
  public function getNumParents(?bool $memOnly=true): int {
    return $this->getNumRelatives('parent', $memOnly);
  }

  /**
   * Get the Node's parent. This method exists for compatibility with previous
   * versions. It returns the first parent.
   * @return Node
   */
  public function getParent(): ?Node {
    $parents = $this->getParents();
    return (sizeof($parents) > 0) ? $parents[0] : null;
  }

  /**
   * Get the first parent that matches given conditions.
   * @param $role The role that the parent should match (optional, default: _null_).
   * @param $type The type that the parent should match (either fully qualified or simple, if not ambiguous)
   *        (optional, default: _null_).
   * @param $values An associative array holding key value pairs that the parent values should match (optional, default: _null_).
   * @param $properties An associative array holding key value pairs that the parent properties should match (optional, default: _null_).
   * @param $useRegExp Boolean whether to interpret the given values/properties as regular expressions or not (default: _true_)
   * @return Node instance or null.
   */
  public function getFirstParent(?string $role=null, ?string $type=null, ?array $values=null, ?array $properties=null, ?bool $useRegExp=true): ?Node {
    $parents = $this->getParentsEx(null, $role, $type, $values, $properties, $useRegExp);
    return (sizeof($parents) > 0) ? $parents[0] : null;
  }

  /**
   * Get the Nodes parents.
   * @param $memOnly Boolean whether to only get the loaded parents or all parents (default: _true_).
   * @return array of PersistentObject instances.
   */
  public function getParents(?bool $memOnly=true): array {
    return $this->getRelatives('parent', $memOnly);
  }

  /**
   * Get the parents that match given conditions.
   * @note This method will only return objects that are already loaded, to get all objects in
   * the given relation (including proxies), use the Node::getValue() method and filter the returned
   * list afterwards.
   * @param $oid The object id that the parent should match (optional, default: _null_).
   * @param $role The role that the parents should match (optional, default: _null_).
   * @param $type The type that the parents should match (either fully qualified or simple, if not ambiguous)
   *        (optional, default: _null_).
   * @param $values An associative array holding key value pairs that the parent values should match (optional, default: _null_).
   * @param $properties An associative array holding key value pairs that the parent properties should match (optional, default: _null_).
   * @param $useRegExp Boolean whether to interpret the given values/properties as regular expressions or not (default: _true_)
   * @return array containing parent Nodes that matched (proxies not included).
   */
  public function getParentsEx(?ObjectId $oid=null, ?string $role=null, ?string $type=null, ?array $values=null,
          ?array $properties=null, ?bool $useRegExp=true): array {
    $result = [];
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
        $nodes = [$nodes];
      }
      // sort out proxies
      $parents = [];
      foreach($nodes as $curNode) {
        if ($curNode instanceof PersistentObject) {
          $parents[] = $curNode;
        }
      }
      $result = self::filter($parents, $oid, $type, $values, $properties, $useRegExp);
    }
    else {
      $result = self::filter($this->getParents(), $oid, $type, $values, $properties, $useRegExp);
    }
    return $result;
  }

  /**
   * Get possible parents of this node type (independent of existing parents).
   * @return array with role names as keys and RelationDescription instances as values.
   */
  public function getPossibleParents(): array {
    $result = [];
    $relations = $this->getRelations('parent');
    foreach ($relations as $curRelation) {
      $result[$curRelation->getOtherRole()] = $curRelation;
    }
    return $result;
  }

  /**
   * Get the relation description for a given node.
   * @param $object PersistentObject instance to look for
   * @return RelationDescription instance or null, if the Node is not related
   */
  public function getNodeRelation(PersistentObject $object): ?RelationDescription {
    $relations = $this->getRelations();
    foreach ($relations as $curRelation) {
      $curRelatives = parent::getValue($curRelation->getOtherRole());
      if ($curRelatives instanceof Node && $curRelatives->getOID() == $object->getOID()) {
        return $curRelation;
      }
      elseif (is_array($curRelatives)) {
        foreach ($curRelatives as $curRelative) {
          if ($curRelative->getOID() == $object->getOID()) {
            return $curRelation;
          }
        }
      }
    }
    return null;
  }

  /**
   * Load all objects in the given set of relations
   * @param $roles An array of relation (=role) names
   * @param $buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        (default: _BuildDepth::SINGLE_)
   */
  protected function loadRelations(array $roles, ?int $buildDepth=BuildDepth::SINGLE): void {
    $oldState = $this->getState();
    foreach ($roles as $curRole) {
      if (isset($this->relationStates[$curRole]) &&
              $this->relationStates[$curRole] != self::RELATION_STATE_LOADED) {
        $relatives = [];

        // resolve proxies if the relation is already initialized
        if ($this->relationStates[$curRole] == self::RELATION_STATE_INITIALIZED) {
          $proxies = $this->getValue($curRole);
          if (is_array($proxies)) {
            foreach ($proxies as $curRelative) {
              if ($curRelative instanceof PersistentObjectProxy) {
                // resolve proxies
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
          $allRelatives = $mapper->loadRelation([$this], $curRole, $buildDepth);
          $oidStr = $this->getOID()->__toString();
          $relatives = isset($allRelatives[$oidStr]) ? $allRelatives[$oidStr] : null;
          $relDesc = $mapper->getRelation($curRole);
          if (!$relDesc->isMultiValued()) {
            $relatives = $relatives != null ? $relatives[0] : null;
          }
        }
        $this->setValueInternal($curRole, $relatives);
        $this->relationStates[$curRole] = self::RELATION_STATE_LOADED;
      }
    }
    $this->setState($oldState);
  }

  /**
   * Get the relation descriptions of a given hierarchyType.
   * @param $hierarchyType @see PersistenceMapper::getRelations (default: 'all')
   * @return array containing the RelationDescription instances.
   */
  protected function getRelations(?string $hierarchyType='all'): array {
    return $this->getMapper()->getRelations($hierarchyType);
  }

  /**
   * Get the relatives of a given hierarchyType.
   * @param $hierarchyType @see PersistenceMapper::getRelations
   * @param $memOnly Boolean whether to only get the relatives in memory or all relatives (including proxies) (default: _true_).
   * @return array containing the relatives.
   */
  public function getRelatives(string $hierarchyType, ?bool $memOnly=true): array {
    $relatives = [];
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
        $curRelatives = [$curRelatives];
      }
      foreach ($curRelatives as $curRelative) {
        if ($curRelative instanceof PersistentObjectProxy && $memOnly) {
          // ignore proxies
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
   * @param $hierarchyType @see PersistenceMapper::getRelations
   * @param $memOnly Boolean whether to only get the number of the relatives in memory or all relatives (default: _true_).
   * @return int
   */
  public function getNumRelatives(string $hierarchyType, ?bool $memOnly=true): int {
    return sizeof($this->getRelatives($hierarchyType, $memOnly));
  }

  /**
   * Accept a Visitor. For use with the _Visitor Pattern_.
   * @param $visitor Visitor instance.
   */
  public function acceptVisitor(Visitor $visitor): void {
    $visitor->visit($this);
  }

  /**
   * Add an uninitialized relation. The relation will be
   * initialized (proxies for related objects will be added)
   * on first access.
   * @param $name The relation name (= role)
   */
  public function addRelation(string $name): void {
    if (!$this->hasValue($name)) {
      $this->relationStates[$name] = self::RELATION_STATE_UNINITIALIZED;
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
  public function getDisplayValue(): string {
    return NodeUtil::getDisplayValue($this);
  }

  /**
   * Get a string representation of the Node.
   * @return The string representation of the Node.
   */
  public function __toString(): string {
    $pStr = parent::__toString();
    $str = $this->getDisplayValue();
    if ($pStr != $str) {
      return $this->getDisplayValue().' ['.$pStr.']';
    }
    return $str;
  }
}
?>
