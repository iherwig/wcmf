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
namespace wcmf\lib\model;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\ObjectId;

/**
 * PersistentIterator is used to iterate over a tree/list built of oids
 * using a Depth-First-Algorithm. To persist its state use the PersistentIterator::save() method,
 * to restore its state use the static PersistentIterator::load() method, which returns the loaded instance.
 * States are identified by an unique id, which is provided after saving.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PersistentIterator implements \Iterator {

  protected $_end;              // indicates if the iteration is ended
  protected $_oidList;          // the list of oids to process
  protected $_processedOidList; // the list of all seen object ids
  protected $_currentOid;       // the oid the iterator points to
  protected $_startOid;         // the oid the iterator started with
  protected $_currentDepth;     // the depth in the tree of the oid the iterator points to
  protected $_aggregationKinds; // array of aggregation kind values to follow (empty: all)

  /**
   * Constructor.
   * @param $oid The oid to start from.
   * @param $aggregationKinds Array of aggregation kind values of relations to follow
   *   possible values: 'none', 'shared', 'composite'. Empty array means all (default: empty)
   */
  public function __construct(ObjectId $oid, $aggregationKinds=array()) {
    $this->_end = false;
    $this->_oidList = array();
    $this->_processedOidList = array();
    $this->_currentOid = $oid;
    $this->_startOid = $oid;
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
    $state = array('end' => $this->_end, 'oidList' => $this->_oidList, 'processedOidList' => $this->_processedOidList,
      'currentOID' => $this->_currentOid, 'currentDepth' => $this->_currentDepth, 'aggregationKinds' => $this->_aggregationKinds);
    $session->set('PersistentIterator.'.$uid, $state);
    return $uid;
  }

  /**
   * Load an iterator state from the session
   * @param $uid The unique id returned from the save method, see PersistentIterator::save()
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
    $instance->_processedOidList = $state['processedOidList'];
    $instance->_currentDepth = $state['currentDepth'];
    $instance->_aggregationKinds = $state['aggregationKinds'];
    return $instance;
  }

  /**
   * Return the current element
   * @return ObjectId, the current object id
   */
  public function current() {
    return $this->_currentOid;
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
    // the current oid was processed
    $this->_processedOidList[] = $this->_currentOid->__toString();

    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $node = $persistenceFacade->load($this->_currentOid);

    // collect navigable children for the given aggregation kinds
    $childOIDs = array();
    $mapper = $node->getMapper();
    $relations = $mapper->getRelations('child');
    $followAll = sizeof($this->_aggregationKinds) == 0;
    foreach ($relations as $relation) {
      $aggregationKind = $relation->getOtherAggregationKind();
      if ($relation->getOtherNavigability() && ($followAll || in_array($aggregationKind, $this->_aggregationKinds))) {
        $childValue = $node->getValue($relation->getOtherRole());
        if ($childValue != null) {
          $children = $relation->isMultiValued() ? $childValue : array($childValue);
          foreach ($children as $child) {
            $childOIDs[] = $child->getOID();
          }
        }
      }
    }
    $this->addToQueue($childOIDs, ++$this->_currentDepth);

    // set current node
    if (sizeof($this->_oidList) != 0) {
      list($oid, $depth) = array_pop($this->_oidList);
      $oidStr = $oid->__toString();
      // not the last node -> search for unprocessed nodes
      while (sizeof($this->_oidList) > 0 && in_array($oidStr, $this->_processedOidList)) {
        list($oid, $depth) = array_pop($this->_oidList);
        $oidStr = $oid->__toString();
      }
      // last node found, but it was processed already
      if (sizeof($this->_oidList) == 0 && in_array($oidStr, $this->_processedOidList)) {
        $this->_end = true;
      }
      $this->_currentOid = $oid;
      $this->_currentDepth = $depth;
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
    $this->_processedOidList = array();
    $this->_currentOid = $this->_startOid;
    $this->_currentDepth = 0;
  }

  /**
   * Checks if current position is valid
   */
  public function valid() {
    return !$this->_end;
  }

  /**
   * Add oids to the processing queue.
   * @param $oidList An array of oids.
   * @param $depth The depth of the oids in the tree.
   */
  protected function addToQueue($oidList, $depth) {
    for ($i=sizeOf($oidList)-1; $i>=0; $i--) {
      $this->_oidList[] = array($oidList[$i], $depth);
    }
  }
}
?>