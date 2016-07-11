<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2016 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\model;

use wcmf\lib\core\Session;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistenceFacade;

/**
 * PersistentIterator is used to iterate over a tree/list built of persistent objects
 * using a Depth-First-Algorithm. To persist its state use the PersistentIterator::save() method,
 * to restore its state use the static PersistentIterator::load() method, which returns the loaded instance.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PersistentIterator implements \Iterator {

  private $id = null;
  private $persistenceFacade = null;
  private $session = null;

  protected $end;              // indicates if the iteration is ended
  protected $oidList;          // the list of oids to process
  protected $processedOidList; // the list of all seen object ids
  protected $currentOid;       // the oid the iterator points to
  protected $startOid;         // the oid the iterator started with
  protected $currentDepth;     // the depth in the tree of the oid the iterator points to
  protected $aggregationKinds; // array of aggregation kind values to follow (empty: all)

  /**
   * Constructor.
   * @param $id The unique iterator id used to store iterator's the state in the session
   * @param $persistenceFacade
   * @param $session
   * @param $oid The object id to start from.
   * @param $aggregationKinds Array of aggregation kind values of relations to follow
   *   possible values: 'none', 'shared', 'composite'. Empty array means all (default: empty)
   */
  public function __construct($id, PersistenceFacade $persistenceFacade,
          Session $session,
          ObjectId $oid,
          $aggregationKinds=array()) {
    $this->id = $id;
    $this->persistenceFacade = $persistenceFacade;
    $this->session = $session;

    $this->end = false;
    $this->oidList = array();
    $this->processedOidList = array();
    $this->currentOid = $oid;
    $this->startOid = $oid;
    $this->currentDepth = 0;
    $this->aggregationKinds = $aggregationKinds;
    $this->session->remove($this->id);
  }

  /**
   * Save the iterator state to the session
   */
  public function save() {
    $state = array('end' => $this->end, 'oidList' => $this->oidList, 'processedOidList' => $this->processedOidList,
      'currentOID' => $this->currentOid, 'currentDepth' => $this->currentDepth, 'aggregationKinds' => $this->aggregationKinds);
    $this->session->set($this->id, $state);
  }

  /**
   * Reset the iterator with the given id
   * @param $id The iterator id
   * @param $session
   */
  public static function reset($id, Session $session) {
    $session->remove($id);
  }

  /**
   * Load an iterator state from the session
   * @param $id  The unique iterator id used to store iterator's the state in the session
   * @param $persistenceFacade
   * @param $session
   * @return PersistentIterator instance holding the saved state or null if unique id is not found
   */
  public static function load($id, $persistenceFacade, $session) {
    // get state from session
    $state = $session->get($id);
    if ($state == null) {
      return null;
    }
    // create instance
    $instance = new PersistentIterator($id, $persistenceFacade, $session,
            $state['currentOID']);
    $instance->end = $state['end'];
    $instance->oidList = $state['oidList'];
    $instance->processedOidList = $state['processedOidList'];
    $instance->currentDepth = $state['currentDepth'];
    $instance->aggregationKinds = $state['aggregationKinds'];
    return $instance;
  }

  /**
   * Return the current element
   * @return ObjectId, the current object id
   */
  public function current() {
    return $this->currentOid;
  }

  /**
   * Return the key of the current element
   * @return Number, the current depth
   */
  public function key() {
    return $this->currentDepth;
  }

  /**
   * Move forward to next element
   */
  public function next() {
    // the current oid was processed
    $this->processedOidList[] = $this->currentOid->__toString();

    $node = $this->persistenceFacade->load($this->currentOid);

    // collect navigable children for the given aggregation kinds
    $childOIDs = array();
    $mapper = $node->getMapper();
    $relations = $mapper->getRelations('child');
    $followAll = sizeof($this->aggregationKinds) == 0;
    foreach ($relations as $relation) {
      $aggregationKind = $relation->getOtherAggregationKind();
      if ($relation->getOtherNavigability() && ($followAll || in_array($aggregationKind, $this->aggregationKinds))) {
        $childValue = $node->getValue($relation->getOtherRole());
        if ($childValue != null) {
          $children = $relation->isMultiValued() ? $childValue : array($childValue);
          foreach ($children as $child) {
            $childOIDs[] = $child->getOID();
          }
        }
      }
    }
    $this->addToQueue($childOIDs, ++$this->currentDepth);

    // set current node
    if (sizeof($this->oidList) != 0) {
      list($oid, $depth) = array_pop($this->oidList);
      $oidStr = $oid->__toString();
      // not the last node -> search for unprocessed nodes
      while (sizeof($this->oidList) > 0 && in_array($oidStr, $this->processedOidList)) {
        list($oid, $depth) = array_pop($this->oidList);
        $oidStr = $oid->__toString();
      }
      // last node found, but it was processed already
      if (sizeof($this->oidList) == 0 && in_array($oidStr, $this->processedOidList)) {
        $this->end = true;
      }
      $this->currentOid = $oid;
      $this->currentDepth = $depth;
    }
    else {
      $this->end = true;
    }
    return $this;
  }

  /**
   * Rewind the Iterator to the first element
   */
  public function rewind() {
    $this->end = false;
    $this->oidList= array();
    $this->processedOidList = array();
    $this->currentOid = $this->startOid;
    $this->currentDepth = 0;
  }

  /**
   * Checks if current position is valid
   */
  public function valid() {
    return !$this->end;
  }

  /**
   * Add object ids to the processing queue.
   * @param $oidList An array of object ids.
   * @param $depth The depth of the object ids in the tree.
   */
  protected function addToQueue($oidList, $depth) {
    for ($i=sizeOf($oidList)-1; $i>=0; $i--) {
      $this->oidList[] = array($oidList[$i], $depth);
    }
  }
}
?>