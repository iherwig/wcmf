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

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\ObjectId;

/**
 * PersistentIterator is used to iterate over a tree/list build of oids
 * using a Depth-First-Algorithm. To persist its state use the PersistentIterator::save() method,
 * to restore its state use the static PersistentIterator::load() method, which returns the loaded instance.
 * States are identified by an unique id, which is provided after saving.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PersistentIterator implements \Iterator {

  protected $_end;        // indicates if the iteration is ended
  protected $_oidList;    // the list of oids to process
  protected $_allList;    // the list of all seen object ids
  protected $_currentOID; // the oid the iterator points to
  protected $_startOID;   // the oid the iterator started with
  protected $_currentDepth; // the depth in the tree of the oid the iterator points to
  protected $_aggregationKinds; // array of aggregation kind values to follow (empty: all)

  /**
   * Constructor.
   * @param oid The oid to start from.
   * @param aggregationKinds Array of aggregation kind values of relations to follow
   *   possible values: 'none', 'shared', 'composite'. Empty array means all (default: empty)
   */
  public function __construct(ObjectId $oid, $aggregationKinds=array()) {
    $this->_end = false;
    $this->_oidList = array();
    $this->_allList = array();
    $this->_currentOID = $oid;
    $this->_startOID = $oid;
    $this->_currentDepth = 0;
    $this->_aggregationKinds = $aggregationKinds;
  }

  /**
   * Save the iterator state to the session
   * @return A unique id to provide for load, see PersistentIterator::load()
   */
  public function save() {
    $session = ObjectFactory::getInstance('session');

    $uid = md5(uniqid(""));
    $state = array('end' => $this->_end, 'oidList' => $this->_oidList, 'allList' => $this->_allList, 'currentOID' => $this->_currentOID,
      'currentDepth' => $this->_currentDepth, 'aggregationKinds' => $this->_aggregationKinds);
    $session->set('PersistentIterator.'.$uid, $state);
    return $uid;
  }

  /**
   * Load an iterator state from the session
   * @param uid The unique id returned from the save method, see PersistentIterator::save()
   * @return PersistentIterator instance holding the saved state or null if unique id is not found
   */
  public static function load($uid) {
    // get state from session
    $session = ObjectFactory::getInstance('session');
    $state = $session->get('PersistentIterator.'.$uid);
    if ($state == null) {
      return null;
    }
    // create instance
    $instance = new PersistentIterator($state['currentOID']);
    $instance->_end = $state['end'];
    $instance->_oidList = $state['oidList'];
    $instance->_allList = $state['allList'];
    $instance->_currentDepth = $state['currentDepth'];
    $instance->_aggregationKinds = $state['aggregationKinds'];
    return $instance;
  }

  /**
   * Return the current element
   * @return ObjectId, the current object id
   */
  public function current() {
    return $this->_currentOID;
  }

  /**
   * Return the key of the current element
   * @return Number, the current depth
   */
  public function key() {
    return $this->_currentDepth;
  }

  /**
   * Move forward to next element
   */
  public function next() {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $node = $persistenceFacade->load($this->_currentOID);

    // collect navigable children for the given aggregation kinds
    $childOIDs = array();
    $mapper = $node->getMapper();
    $relations = $mapper->getRelations('child');
    $followAll = sizeof($this->_aggregationKinds) == 0;
    foreach ($relations as $relation) {
      $aggregationKind = $relation->getOtherAggregationKind();
      if ($relation->getOtherNavigability() && ($followAll || in_array($aggregationKind, $this->_aggregationKinds))) {
        $childValue = $node->getValue($relation->getOtherRole());
        $children = $relation->isMultiValued() ? $childValue : ($childValue != null ? array($childValue) : array());
        foreach ($children as $child) {
          $childOIDs[] = $child->getOID();
        }
      }
    }
    $this->addToSeenList($childOIDs, ++$this->_currentDepth);

    if (sizeOf($this->_oidList) != 0) {
      list($this->_currentOID, $this->_currentDepth) = array_pop($this->_oidList);
    }
    else {
      $this->_end = true;
    }
    return $this;
  }

  /**
   * Rewind the Iterator to the first element
   */
  public function rewind() {
    $this->_end = false;
    $this->_oidList= array();
    $this->_allList = array();
    $this->_currentOID = $this->_startOID;
    $this->_currentDepth = 0;
  }

  /**
   * Checks if current position is valid
   */
  public function valid() {
    return !$this->_end;
  }

  /**
   * Add oids to the internal processed oid list.
   * @attention Internal use only.
   * @param oidList An array of oids.
   * @param depth The depth of the oids in the tree.
   */
  protected function addToSeenList($oidList, $depth) {
    for ($i=sizeOf($oidList)-1; $i>=0; $i--) {
      if (!in_array($oidList[$i], $this->_allList)) {
        $this->_oidList[] = array($oidList[$i], $depth);
        $this->_allList[] = $oidList[$i];
      }
    }
  }
}
?>