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
require_once(BASE."wcmf/lib/util/class.Log.php");
require_once(BASE."wcmf/lib/util/class.StringUtil.php");
require_once(BASE."wcmf/lib/persistence/class.PersistentObject.php");
require_once(BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(BASE."wcmf/lib/model/class.NodeUtil.php");
require_once(BASE."wcmf/lib/util/class.ArrayUtil.php");

/**
 * Some constants describing the build process
 */
define("ADDCHILD_FRONT", -1); // add child at front of children list
define("ADDCHILD_BACK",  -2);  // add child at back of children list
/**
 * Some constants describing the sort process
 */
define("SORTTYPE_ASC",  -1); // sort children ascending
define("SORTTYPE_DESC", -2); // sort children descending
define("OID",  -3); // sort by oid
define("TYPE", -4); // sort by type

/**
 * @class Node
 * @ingroup Model
 * @brief Node is the basic component for building trees (although a Node can have one than more parents).
 * The Node class implements the 'Composite Pattern', so no special tree class is required, all interaction
 * is performed using the Node interface.
 * Subclasses for specialized Nodes must implement this interface so that clients don't have to know
 * about special Node classes.
 * Use the methods addChild(), deleteChild() to build/modify trees.
 *
 * @author   ingo herwig <ingo@wemove.com>
 */
class Node extends PersistentObject
{
  private static $_sortCriteria;
  private $_depth = -1;
  private $_path = '';

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
   * Get the number of children of the Node.
   * @param memOnly True/False wether to only get the number of loaded children or all children [default: true].
   * @return The number of children.
   */
  public function getNumChildren($memOnly=true)
  {
    return $this->getNumRelatives('child', $memOnly);
  }
  /**
   * Add a Node to the Nodes childrenlist.
   * @param child The Node to add.
   * @param role The role of the Node in the created relation. If null, the role will be
   *        the Node's type. [default: null]
   * @param addtype One of the ADDCHILD constants
   */
  public function addChild(PersistentObject $child, $role=null, $addtype=ADDCHILD_BACK)
  {
    if ($role == null) {
      $role = $child->getType();
    }
    $children = $this->getValue($role);
    if (!is_array($children)) {
      $children = array();
    }
    if ($addtype == ADDCHILD_BACK || $addtype == ADDCHILD_FRONT)
    {
      if ($addtype == ADDCHILD_BACK) {
        ArrayUtil::array_insert($children, sizeof($children), $child);
      }
      elseif ($addtype == ADDCHILD_FRONT) {
        ArrayUtil::array_insert($children, 0, $child);
      }
    }
    else {
      throw new IllegalArgumentException("Unknown ADDTYPE.");
    }
    $this->setValue($role, $children);

    // propagate add action to the other object
    $thisRole = $this->getType();
    $mapper = $this->getMapper();
    if ($mapper && $mapper->hasRelation($role))
    {
      $relDesc = $mapper->getRelation($role);
      $thisRole = $relDesc->thisRole;
    }
    $child->updateParent($this, $thisRole);
  }
  /**
   * Set a given parent. Works recursively.
   * @param parent A reference to the Node to set the parent to.
   */
  private function updateParent(PersistentObject $parent, $role)
  {
    $parents = $this->getValue($role);
    if (!is_array($parents)) {
      $parents = array();
    }
    else {
      // check if the parent already exists
      foreach ($parents as $curParent)
      {
        if ($curParent->getOID() == $parent->getOID()) {
          // no need to update
          return;
        }
      }
    }

    // add the new parent
    $parents[] = $parent;
    $this->setValue($role, $parents);
  }
  /**
   * Delete a Node's child.
   * @param childOID The object id of the child Node to delete.
   * @param role The role of the child. If null, the role is the child's type. [default: null]
   * @param reallyDelete True/false [default: false].
   * (if reallyDelete==false mark it and it's descendants as deleted).
   */
  public function deleteChild(ObjectId $childOID, $role=null, $reallyDelete=false)
  {
    if ($role == null) {
      $role = $childOID->getType();
    }
    $children = $this->getValue($role);
    for($i=0, $count=sizeOf($children); $i<$count; $i++)
    {
      if ($children[$i]->getOID() == $childOID)
      {
        if (!$reallyDelete)
        {
          // mark child as deleted
          $children[$i]->setState(STATE_DELETED);
          break;
        }
        else
        {
          // remove child
          array_splice($children, $i, 1);
          break;
        }
      }
    }
    $this->setValue($role, $children);
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
      $this->resolveProxies(array($role), $buildDepth);
    }
    else {
      $this->resolveProxies(array_keys($this->getPossibleChildren()), $buildDepth);
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
      return Node::filter($this->getValue($roleOrType), $oid, null, $values, $properties, $useRegExp);
    }
    else {
      return Node::filter($this->getChildren(), $oid, $roleOrType, $values, $properties, $useRegExp);
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
      $result[$curRelation->otherRole] = $curRelation;
    }
    return $result;
  }
  /**
   * Sort children by a given criteria.
   * @param criteria An assoziative array of criteria - SORTTYPE constant pairs OR a single criteria string.
   *        possible criteria: OID, TYPE or any value/property name
   *        (e.g. array(OID => SORTTYPE_ASC, 'sortkey' => SORTTYPE_DESC) OR 'sortkey')
   *        @note If criteria is only a string we will sort by this criteria with SORTTYPE_ASC
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
   *        possible criteria: OID, TYPE or any value/property name
   *        (e.g. array(OID => SORTTYPE_ASC, 'sortkey' => SORTTYPE_DESC) OR 'sortkey')
   *        @note If criteria is only a string we will sort by this criteria with SORTTYPE_ASC
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
        self::$_sortCriteria = array($criteria => SORTTYPE_ASC);
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
          $returnArray[sizeof($returnArray)] = $curNode;
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
      $this->resolveProxies(array($role), $buildDepth);
    }
    else {
      $this->resolveProxies(array_keys($this->getPossibleParents()), $buildDepth);
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
      return Node::filter($this->getValue($roleOrType), $oid, null, $values, $properties, $useRegExp);
    }
    else {
      return Node::filter($this->getParents(), $oid, $roleOrType, $values, $properties, $useRegExp);
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
      $result[$curRelation->otherRole] = $curRelation;
    }
    return $result;
  }
  /**
   * Resolve all PersistentObjectProxies of a given set of roles.
   * @param roles An array of role names
   * @param buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        [default: BUILDDEPTH_SINGLE)]
   */
  protected function resolveProxies(array $roles, $buildDepth=BUILDDEPTH_SINGLE)
  {
    $oldState = $this->getState();
    foreach ($roles as $curRole)
    {
      $relatives = $this->getValue($curRole);
      if (is_array($relatives))
      {
        $resolvedRelatives = array();
        foreach ($relatives as $curRelative)
        {
          if ($curRelative instanceof PersistentObjectProxy)
          {
            $curRelative->resolve($buildDepth);
            $resolvedRelatives[] = $curRelative->getRealSubject();
          }
          else {
            $resolvedRelatives[] = $curRelative;
          }
        }
        $this->setValue($curRole, $resolvedRelatives);
      }
    }
    $this->setState($oldState);
  }
  /**
   * Get the relation descriptions of a given hierarchyType.
   * @param hierarchyType @see PersistenceMapper::getRelations
   * @return An array containing the RelationDescription instances.
   */
  protected function getRelations($hierarchyType)
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
      $curRelatives = $this->getValue($curRelation->otherRole);
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
      $relatives = $this->getValue($curRelation->otherRole);
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
      if ($criteria == OID)
      {
        if ($a->getOID() != $b->getOID()) {
          ($a->getOID() > $b->getOID()) ? $AGreaterB = 1 : $AGreaterB = -1;
        }
      }
      // sort by type
      else if ($criteria == TYPE)
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
      if ($sortType == SORTTYPE_ASC)
      {
        if ($AGreaterB == 1) { $sumA += $weightedValue; }
        else if ($AGreaterB == -1) { $sumB += $weightedValue; }
      }
      else if ($sortType == SORTTYPE_DESC)
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
