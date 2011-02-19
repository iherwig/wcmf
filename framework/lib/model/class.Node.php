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
require_once(WCMF_BASE."wcmf/lib/util/class.Log.php");
require_once(WCMF_BASE."wcmf/lib/util/class.StringUtil.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistentObject.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(WCMF_BASE."wcmf/lib/model/class.NodeUtil.php");
require_once(WCMF_BASE."wcmf/lib/util/class.ArrayUtil.php");

/**
 * @class Node
 * @ingroup Model
 * @brief Node adds the concept of relations to PersistentObject. It is the basic component for
 * building object trees (although a Node can have one than more parents).
 * The Node class implements the 'Composite Pattern', so no special tree class is required, all interaction
 * is performed using the Node interface.
 * Subclasses for specialized Nodes must implement this interface so that clients don't have to know
 * about special Node classes.
 * Use the methods addNode(), deleteNode() to build/modify trees.
 *
 * @author   ingo herwig <ingo@wemove.com>
 */
class Node extends PersistentObject
{
  const SORTTYPE_ASC = -1;  // sort children ascending
  const SORTTYPE_DESC = -2; // sort children descending
  const SORTBY_OID = -3;  // sort by oid
  const SORTBY_TYPE = -4; // sort by type

  const RELATION_STATE_UNINITIALIZED = -1;
  const RELATION_STATE_INITIALIZING = -2;
  const RELATION_STATE_INITIALIZED = -3;
  const RELATION_STATE_LOADED = -4;

  private static $_sortCriteria;
  private $_depth = -1;
  private $_path = '';
  private $_relationStates = array();

  /**
   * Constructor.
   * @param type The Nodes type.
   * @param oid The Node's oid (, optional will be calculated if not given or not valid).
   */
  public function __construct($type, ObjectId $oid=null)
  {
    parent::__construct($type, $oid);
  }
  /**
   * @see PersistentObject::getValue
   */
  public function getValue($name)
  {
    // initialize a relation value if not done before
    $value = parent::getValue($name);
    if (isset($this->_relationStates[$name]) &&
            $this->_relationStates[$name] == Node::RELATION_STATE_UNINITIALIZED)
    {
      $this->_relationStates[$name] = Node::RELATION_STATE_INITIALIZING;
      $mapper = $this->getMapper();
      if ($mapper)
      {
        $value = $mapper->loadRelation($this, $name, BUILDDEPTH_PROXIES_ONLY);
        $this->setValueInternal($name, $value);
        $this->_relationStates[$name] = Node::RELATION_STATE_INITIALIZED;
      }
    }
    return $value;
  }
  /**
   * Get the number of children of the Node.
   * @param memOnly True/False wether to only get the number of loaded children or all children [default: true].
   * @return The number of children.
   */
  public function getNumChildren($memOnly=true)
  {
    return $this->getNumRelatives('child', $memOnly);
  }
  /**
   * Add a Node to the given relation.
   * @param other The Node to add.
   * @param role The role of the Node in the created relation. If null, the role will be
   *        the Node's type. [default: null]
   * @param strict True/False wether to add the Node according to an existing RelationDescription or not.
   *        If true, the value for the relation will be an array, if the multiplicity is > 1 and
   *        a single value otherwise. If false, the value will always be an array [default: true]
   * @param updateOtherSide True/False wether to update also the other side of the relation [default: true]
   */
  public function addNode(PersistentObject $other, $role=null, $strict=true, $updateOtherSide=true)
  {
    if ($role == null) {
      $role = $other->getType();
    }

    // get the relation description
    $relDesc = null;
    $mapper = $this->getMapper();
    if ($mapper) {
      $relDesc = $mapper->getRelation($role);
    }

    if ($strict && $relDesc && !$relDesc->isMultiValued()) {
      // just set the value if strict and not multivalued
      $this->setValue($role, $other);
    }
    else {
      // make sure that the value is an array if multivalued or not strict
      $this->addValue($role, $other);
    }

    // propagate add action to the other object
    if ($updateOtherSide)
    {
      $thisRole = $this->getType();
      if ($relDesc) {
        $thisRole = $relDesc->getThisRole();
      }
      $other->addNode($this, $thisRole, $strict, false);
    }
  }
  /**
   * Delete a Node from the given relation.
   * @param oid The object id of the Node to delete.
   * @param role The role of the Node. If null, the role is the Node's type. [default: null]
   * @param reallyDelete True/false [default: false].
   * (if reallyDelete==false mark it and it's descendants as deleted).
   */
  public function deleteNode(ObjectId $oid, $role=null, $reallyDelete=false)
  {
    if ($role == null) {
      $role = $oid->getType();
    }
    $nodes = $this->getValue($role);
    if (empty($nodes)) {
      return;
    }

    if (is_array($nodes))
    {
      for($i=0, $count=sizeof($nodes); $i<$count; $i++)
      {
        if ($nodes[$i]->getOID() == $oid)
        {
          if (!$reallyDelete) {
            // mark child as deleted
            $nodes[$i]->setState(STATE_DELETED);
            break;
          }
          else {
            // remove child
            array_splice($nodes, $i, 1);
            break;
          }
        }
      }
    }
    else
    {
      if ($nodes->getOID() == $oid)
      {
        if (!$reallyDelete) {
          // mark child as deleted
          $nodes->setState(STATE_DELETED);
        }
        else {
          // remove child
          $nodes = null;
        }
      }
    }
    $this->setValue($role, $nodes);
  }
  /**
   * Load the children of a given role and add them. If all children should be
   * loaded, set the role parameter to null.
   * @param role The role of children to load (maybe null, to load all children) [default: null]
   * @param buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        [default: BUILDDEPTH_SINGLE)]
   */
  public function loadChildren($role=null, $buildDepth=BUILDDEPTH_SINGLE)
  {
    if ($role != null) {
      $this->loadRelations(array($role), $buildDepth);
    }
    else {
      $this->loadRelations(array_keys($this->getPossibleChildren()), $buildDepth);
    }
  }
  /**
   * Get the first child that matches given conditions.
   * @param roleOrType The role or type that the child should match [maybe null, default: null].
   * @param values An assoziative array holding key value pairs that the child values should match [maybe null, default: null].
   * @param properties An assoziative array holding key value pairs that the child properties should match [maybe null, default: null].
   * @param useRegExp True/False wether to interpret the given values/properties as regular expressions or not [default:true]
   * @return An reference to the first child that matched or null.
   */
  public function getFirstChild($roleOrType=null, $values=null, $properties=null, $useRegExp=true)
  {
    $children = $this->getChildrenEx(null, $roleOrType, $values, $properties, $useRegExp);
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
  public function getChildren($memOnly=true)
  {
    return $this->getRelatives('child', $memOnly);
  }
  /**
   * Get the children that match given conditions.
   * @param oid The object id that the children should match [maybe null, default: null].
   * @param roleOrType The role or type that the children should match [maybe null, default: null].
   * @param values An assoziative array holding key value pairs that the children values should match [maybe null, default: null].
   * @param properties An assoziative array holding key value pairs that the children properties should match [maybe null, default: null].
   * @param useRegExp True/False wether to interpret the given values/properties as regular expressions or not [default:true]
   * @return An Array holding references to the children that matched.
   */
  public function getChildrenEx(ObjectId $oid=null, $roleOrType=null, $values=null, $properties=null, $useRegExp=true)
  {
    if ($roleOrType != null && $this->hasValue($roleOrType)) {
      // nodes of a given role are requested
      return self::filter($this->getValue($roleOrType), $oid, $roleOrType, $values, $properties, $useRegExp);
    }
    else {
      return self::filter($this->getChildren(), $oid, $roleOrType, $values, $properties, $useRegExp);
    }
  }
  /**
   * Get possible chilren of this node type (independent of existing children).
   * @return An Array with role names as keys and RelationDescription instances as values.
   */
  public function getPossibleChildren()
  {
    $result = array();
    $relations = $this->getRelations('child');
    foreach ($relations as $curRelation) {
      $result[$curRelation->getOtherRole()] = $curRelation;
    }
    return $result;
  }
  /**
   * Sort children by a given criteria.
   * @param criteria An assoziative array of criteria - SORTTYPE constant pairs OR a single criteria string.
   *        possible criteria: Node::OID, Node::TYPE or any value/property name
   *        (e.g. array(Node::OID => Node::SORTTYPE_ASC, 'sortkey' => Node::SORTTYPE_DESC) OR 'sortkey')
   *        @note If criteria is only a string we will sort by this criteria with Node::SORTTYPE_ASC
   * @param recursive True/False whether the descendants of the children schould be sorted too (default: false)
   * @param changeSortkey True/False whether the sortkey should be changed according to the new order (default: false)
   * @param sortFunction The name of a global compare function to use. If given criteria will be ignored (default: "")
   * @deprecated
   */
  public function sortChildren($criteria, $recursive=false, $changeSortkey=false, $sortFunction='')
  {
    Log::warn("use of deprecated method Node::sortChildren. ".
    	"use Node::getChildren() and afterwards Node::sort() instead.\n".
        Application::getStackTrace(), __CLASS__);
  }
  /**
   * Sort Node list by a given criteria.
   * @note static method
   * @param nodeList A reference to an array of Nodes
   * @param criteria An assoziative array of criteria - SORTTYPE constant pairs OR a single criteria string.
   *        possible criteria: Node::OID, Node::TYPE or any value/property name
   *        (e.g. array(Node::OID => Node::SORTTYPE_ASC, 'sortkey' => Node::SORTTYPE_DESC) OR 'sortkey')
   *        @note If criteria is only a string we will sort by this criteria with Node::SORTTYPE_ASC
   * @param changeSortkey True/False whether the sortkey should be changed according to the new order (default: false)
   * @param sortFunction The name of a global compare function to use. If given criteria will be ignored (default: "")
   * @return The sorted array of Nodes
   */
  public static function sort(array $nodeList, $criteria, $changeSortkey=false, $sortFunction='')
  {
    if (strlen($sortFunction) == 0)
    {
      // sort with internal sort function
      if (!is_array($criteria)) {
        self::$_sortCriteria = array($criteria => Node::SORTTYPE_ASC);
      }
      else {
        self::$_sortCriteria = $criteria;
      }
      usort($nodeList, array('Node', 'nodeCmpFunction'));
    }
    else {
      usort($nodeList, $sortFunction);
    }
    // change sortkey
    if ($changeSortkey)
    {
      $sortkey = 0;
      for($i=0, $count=sizeof($nodeList); $i<$count; $i++)
      {
        $nodeList[$i]->setValue('sortkey', $sortkey);
        $sortkey++;
      }
    }
    return $nodeList;
  }
  /**
   * Get Nodes that match given conditions from a list.
   * @param nodeList An reference to an array of nodes to filter.
   * @param oid The object id that the Nodes should match [maybe null, default: null].
   * @param type The type that the Nodes should match [maybe null, default: null].
   * @param values An assoziative array holding key value pairs that the Node values should match
   *        [values are interpreted as regular expression, parameter maybe null, default: null].
   * @param properties An assoziative array holding key value pairs that the Node properties should match
   *        [values are interpreted as regular expression, parameter maybe null, default: null].
   * @param useRegExp True/False wether to interpret the given values/properties as regular expressions or not [default:true]
   * @return An Array holding references to the Nodes that matched.
   */
  public static function filter(array $nodeList, ObjectId $oid=null, $type=null, $values=null, $properties=null, $useRegExp=true)
  {
    $returnArray = array();
    for($i=0, $count=sizeof($nodeList); $i<$count; $i++)
    {
      $curNode = $nodeList[$i];
      if ($curNode instanceof Node)
      {
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
        if ($values != null && is_array($values))
        {
          foreach($values as $key => $value)
          {
            $nodeValue = $curNode->getValue($key);
            if ($useRegExp && !preg_match("/".$value."/m", $nodeValue) || !$useRegExp && $value != $nodeValue)
            {
              $match = false;
              break;
            }
          }
        }
        // check properties
        if ($properties != null && is_array($properties))
        {
          foreach($properties as $key => $value)
          {
            $nodeProperty = $curNode->getProperty($key);
            if ($useRegExp && !preg_match("/".$value."/m", $nodeProperty) || !$useRegExp && $value != $nodeProperty)
            {
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
        Log::warn(StringUtil::getDump($curNode)." found, where a Node was expected.\n".Application::getStackTrace(),
          __CLASS__);
      }
    }
    return $returnArray;
  }
  /**
   * Get the next sibling of the Node.
   * @return The next sibling of the node or NULL if it does not exists.
   */
  public function getNextSibling()
  {
    $parent = $this->getParent();
    if ($parent != null)
    {
      $pChildren = $parent->getChildren();
      $nextSibling = null;
      for ($i=0, $count=sizeOf($pChildren); $i<$count; $i++)
      {
        if ($pChildren[$i]->getOID() == $this->_oid && $i<($count-1))
        {
          $nextSibling = $pChildren[++$i];
          break;
        }
      }
      if ($nextSibling != null) {
        return $nextSibling;
      }
    }
    return null;
  }
  /**
   * Get the previous sibling of the Node.
   * @return The previous sibling of the node or NULL if it does not exists.
   */
  public function getPreviousSibling()
  {
    $parent = $this->getParent();
    if ($parent != null)
    {
      $pChildren = $parent->getChildren();
      $prevSibling = null;
      for ($i=0, $count=sizeOf($pChildren); $i<$count; $i++)
      {
        if ($pChildren[$i]->getOID() == $this->_oid && $i>0)
        {
          $prevSibling = $pChildren[--$i];
          break;
        }
      }
      if ($prevSibling != null) {
        return $prevSibling;
      }
    }
    return null;
  }
  /**
   * Load the parents of a given role and add them. If all parents should be
   * loaded, set the role parameter to null.
   * @param role The role of parents to load (maybe null, to load all parents) [default: null]
   * @param buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        [default: BUILDDEPTH_SINGLE)]
   */
  public function loadParents($role=null, $buildDepth=BUILDDEPTH_SINGLE)
  {
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
  public function getNumParents($memOnly=true)
  {
    return $this->getNumRelatives('parent', $memOnly);
  }
  /**
   * Get the Node's parent. This method exists for compatibility with previous
   * versions. It returns the first parent.
   * @return A reference to the Nodes parent.
   */
  public function getParent()
  {
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
   * @param roleOrType The role or type that the parent should match [maybe null, default: null].
   * @param values An assoziative array holding key value pairs that the parent values should match [maybe null, default: null].
   * @param properties An assoziative array holding key value pairs that the parent properties should match [maybe null, default: null].
   * @param useRegExp True/False wether to interpret the given values/properties as regular expressions or not [default:true]
   * @return An reference to the first parent that matched or null.
   */
  public function getFirstParent($roleOrType=null, $values=null, $properties=null, $useRegExp=true)
  {
    $parents = $this->getParentsEx(null, $roleOrType, $values, $properties, $useRegExp);
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
  public function getParents($memOnly=true)
  {
    return $this->getRelatives('parent', $memOnly);
  }
  /**
   * Get the parents that match given conditions.
   * @param oid The object id that the parent should match [maybe null, default: null].
   * @param roleOrType The role or type that the parents should match [maybe null, default: null].
   * @param values An assoziative array holding key value pairs that the parent values should match [maybe null, default: null].
   * @param properties An assoziative array holding key value pairs that the parent properties should match [maybe null, default: null].
   * @param useRegExp True/False wether to interpret the given values/properties as regular expressions or not [default:true]
   * @return An Array holding references to the parents that matched.
   */
  public function getParentsEx(ObjectId $oid=null, $roleOrType=null, $values=null, $properties=null, $useRegExp=true)
  {
    if ($roleOrType != null && $this->hasValue($roleOrType)) {
      // nodes of a given role are requested
      return self::filter($this->getValue($roleOrType), $oid, null, $values, $properties, $useRegExp);
    }
    else {
      return self::filter($this->getParents(), $oid, $roleOrType, $values, $properties, $useRegExp);
    }
  }
  /**
   * Get possible parents of this node type (independent of existing parents).
   * @return An Array with role names as keys and RelationDescription instances as values.
   */
  public function getPossibleParents()
  {
    $result = array();
    $relations = $this->getRelations('parent');
    foreach ($relations as $curRelation) {
      $result[$curRelation->getOtherRole()] = $curRelation;
    }
    return $result;
  }
  /**
   * Load all objects in the given set of relations
   * @param roles An array of relation (=role) names
   * @param buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        [default: BUILDDEPTH_SINGLE)]
   */
  protected function loadRelations(array $roles, $buildDepth=BUILDDEPTH_SINGLE)
  {
    $oldState = $this->getState();
    foreach ($roles as $curRole)
    {
      if ($this->_relationStates[$curRole] != Node::RELATION_STATE_LOADED)
      {
        $relatives = array();

        // resolve proxies if the relation is already initialized
        if ($this->_relationStates[$curRole] == Node::RELATION_STATE_INITIALIZED)
        {
          $proxies = $this->getValue($curRole);
          if (is_array($proxies))
          {
            foreach ($proxies as $curRelative)
            {
              if ($curRelative instanceof PersistentObjectProxy)
              {
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
        else
        {
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
  protected function getRelations($hierarchyType='all')
  {
    $mapper = $this->getMapper();
    if ($mapper != null) {
      return $mapper->getRelations($hierarchyType);
    }
    return array();
  }
  /**
   * Get the relatives of a given hierarchyType.
   * @param hierarchyType @see PersistenceMapper::getRelations
   * @param memOnly True/False wether to only get the relatives in memory or all relatives [default: true].
   * @return An array containing the relatives.
   */
  protected function getRelatives($hierarchyType, $memOnly=true)
  {
    $relatives = array();
    $relations = $this->getRelations($hierarchyType);
    foreach ($relations as $curRelation)
    {
      $curRelatives = $this->getValue($curRelation->getOtherRole());
      if (is_array($curRelatives))
      {
        foreach ($curRelatives as $curRelative)
        {
          if ($curRelative instanceof PersistentObjectProxy && $memOnly) {
            continue;
          }
          else {
            $relatives[] = $curRelative;
          }
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
  protected function getNumRelatives($hierarchyType, $memOnly=true)
  {
    $count = 0;
    $relations = $this->getRelations($hierarchyType);
    foreach ($relations as $curRelation)
    {
      $relatives = $this->getValue($curRelation->getOtherRole());
      if (is_array($relatives))
      {
        foreach ($relatives as $curRelative)
        {
          if ($curRelative instanceof PersistentObjectProxy && $memOnly) {
            continue;
          }
          else {
            $count++;
          }
        }
      }
    }
    return $count;
  }
  /**
   * Compare function for sorting Nodes by a given criteria.
   * @param a, b The Nodes to compare.
   * @return -1, 0 or 1 whether a is less, equal or greater than b in respect of the criteria
   */
  protected static function nodeCmpFunction($a, $b)
  {
    // we compare for each criteria and sum the results for $a, $b
    // afterwards we compare the sums and return -1,0,1 appropriate
    $sumA = 0;
    $sumB = 0;
    $maxWeight = sizeOf(self::$_sortCriteria);
    $i = 0;
    foreach (self::$_sortCriteria as $criteria => $sortType)
    {
      $weightedValue = ($maxWeight-$i)*($maxWeight-$i);
      $AGreaterB = 0;
      // sort by id
      if ($criteria == Node::SORTBY_OID)
      {
        if ($a->getOID() != $b->getOID()) {
          ($a->getOID() > $b->getOID()) ? $AGreaterB = 1 : $AGreaterB = -1;
        }
      }
      // sort by type
      else if ($criteria == Node::SORTBY_TYPE)
      {
        if ($a->getType() != $b->getType()) {
          ($a->getType() > $b->getType()) ? $AGreaterB = 1 : $AGreaterB = -1;
        }
      }
      // sort by value
      else if($a->getValue($criteria) != null || $b->getValue($criteria) != null)
      {
        $aValue = strToLower($a->getValue($criteria));
        $bValue = strToLower($b->getValue($criteria));
        if ($aValue != $bValue) {
          ($aValue > $bValue) ? $AGreaterB = 1 : $AGreaterB = -1;
        }
      }
      // sort by property
      else if($a->getProperty($criteria) != null || $b->getProperty($criteria) != null)
      {
        $aProperty = strToLower($a->getProperty($criteria));
        $bProperty = strToLower($b->getProperty($criteria));
        if ($aProperty != $bProperty) {
          ($aProperty > $bProperty) ? $AGreaterB = 1 : $AGreaterB = -1;
        }
      }
      // calculate result of current criteria depending on current sorttype
      if ($sortType == Node::SORTTYPE_ASC)
      {
        if ($AGreaterB == 1) { $sumA += $weightedValue; }
        else if ($AGreaterB == -1) { $sumB += $weightedValue; }
      }
      else if ($sortType == Node::SORTTYPE_DESC)
      {
        if ($AGreaterB == 1) { $sumB += $weightedValue; }
        else if ($AGreaterB == -1) { $sumA += $weightedValue; }
      }
      else {
        throw new IllegalArgumentException("Unknown SORTTYPE.");
      }
      $i++;
    }
    if ($sumA == $sumB) { return 0; }
    return ($sumA > $sumB) ? 1 : -1;
  }
  /**
   * Get the Nodes depth.
   * @return The number of parents of the Node.
   */
  public function getDepth()
  {
    $this->_depth = 0;
    $parent = $this->getParent();
    while ($parent != null && $parent instanceof Node)
    {
      $this->_depth++;
      $parent = $parent->getParent();
    }
    return $this->_depth;
  }
  /**
   * Get the Nodes path (to root).
   * @return The Node path.
   */
  public function getPath()
  {
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
  public function acceptVisitor($visitor)
  {
    $visitor->visit($this);
  }
  /**
   * Set the state of the node.
   * @param state The state to set.
   * @param recursive True/False [Default: True]
   */
  public function setState($state, $recursive=true)
  {
    parent::setState($state);
    if ($recursive)
    {
      $children = $this->getChildren();
      for($i=0, $count=sizeOf($children); $i<$count; $i++) {
        $children[$i]->setState($state, $recursive);
      }
    }
  }
  /**
   * Add an uninitialized relation. The relation will be
   * initialized (proxies for related objects will be added)
   * on first access.
   * @param name The relation name (= role)
   */
  public function addRelation($name)
  {
    $this->_relationStates[$name] = Node::RELATION_STATE_UNINITIALIZED;
    $this->setValueInternal($name, null);
  }

  /**
   * Output
   */

  /**
   * @see PersistentObject::getDisplayValue()
   * Delegates to NodeUtil::getDisplayValue
   */
  public function getDisplayValue($useDisplayType=false)
  {
    return NodeUtil::getDisplayValue($this, $useDisplayType);
  }
  /**
   * Delegates to NodeUtil::getDisplayValues
   */
  public function getDisplayValues($useDisplayType=false)
  {
    return NodeUtil::getDisplayValues($this, $useDisplayType);
  }
  /**
   * Get a string representation of the Node.
   * @param verbose True to get a verbose output [default: false]
   * @return The string representation of the Node.
   */
  public function __toString()
  {
    return NodeUtil::getDisplayValue($this).' ['.parent::__toString().']';
  }
}
?>
