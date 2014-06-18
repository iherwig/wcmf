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

use wcmf\lib\model\Node;

/**
 * NodeIterator is used to iterate over a tree/list build of Nodes
 * using a Depth-First-Algorithm. Classes used with the NodeIterator must
 * implement the getChildren() and getOID() methods.
 *
 * The following example shows the usage:
 *
 * @code
 * // load the node with depth 10
 * $node = ObjectFactory::getInstance('persistenceFacade')->load(new ObjectId('Page', 300), 10);
 *
 * // iterate over all children
 * $it = new NodeIterator($node);
 * foreach($it as $oid => $obj) {
 *   echo("current object id: ".$oid);
 *   echo("current object: ".$obj);
 * }
 * @endcode
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NodeIterator implements \Iterator {

  protected $_end;              // indicates if the iteration is ended
  protected $_nodeList;         // the list of seen nodes
  protected $_processedOidList; // the list of processed object ids
  protected $_currentNode;      // the node the iterator points to
  protected $_startNode;        // the start node
  protected $_aggregationKinds; // array of aggregation kind values to follow (empty: all)

  /**
   * Constructor.
   * @param node The node to start from.
   * @param aggregationKinds Array of aggregation kind values of relations to follow
   *   possible values: 'none', 'shared', 'composite'. Empty array means all (default: empty)
   */
  public function __construct($node, $aggregationKinds=array()) {
    $this->_end = false;
    $this->_nodeList = array();
    $this->_processedOidList = array();
    $this->_currentNode = $node;
    $this->_startNode = $node;
    $this->_aggregationKinds = $aggregationKinds;
  }

  /**
   * Return the current element
   * @return Node instance
   */
  public function current() {
    return $this->_currentNode;
  }

  /**
   * Return the key of the current element
   * @return String, the serialized object id
   */
  public function key() {
    return $this->_currentNode->getOID()->__toString();
  }

  /**
   * Move forward to next element
   */
  public function next() {
    // the current node was processed
    $this->_processedOidList[] = $this->_currentNode->getOID()->__toString();

    // collect navigable children for the given aggregation kinds
    $childrenArray = array();
    $mapper = $this->_currentNode->getMapper();
    $relations = $mapper->getRelations('child');
    $followAll = sizeof($this->_aggregationKinds) == 0;
    foreach ($relations as $relation) {
      $aggregationKind = $relation->getOtherAggregationKind();
      if ($relation->getOtherNavigability() && ($followAll || in_array($aggregationKind, $this->_aggregationKinds))) {
        $childValue = $this->_currentNode->getValue($relation->getOtherRole());
        if ($childValue != null) {
          $children = $relation->isMultiValued() ? $childValue : array($childValue);
          foreach ($children as $child) {
            $childrenArray[] = $child;
          }
        }
      }
    }
    $this->addToQueue($childrenArray);

    // set current node
    if (sizeof($this->_nodeList) != 0) {
      $node = array_pop($this->_nodeList);
      $oidStr = $node->getOID()->__toString();
      // not the last node -> search for unprocessed nodes
      while (sizeof($this->_nodeList) > 0 && in_array($oidStr, $this->_processedOidList)) {
        $node = array_pop($this->_nodeList);
        $oidStr = $node->getOID()->__toString();
      }
      // last node found, but it was processed already
      if (sizeof($this->_nodeList) == 0 && in_array($oidStr, $this->_processedOidList)) {
        $this->_end = true;
      }
      $this->_currentNode = $node;
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
    $this->_nodeList = array();
    $this->_processedOidList = array();
    $this->_currentNode = $this->_startNode;
  }

  /**
   * Checks if current position is valid
   */
  public function valid() {
    return !$this->_end;
  }

  /**
   * Add nodes to the processing queue.
   * @param nodeList An array of nodes.
   */
  protected function addToQueue($nodeList) {
    for ($i=sizeof($nodeList)-1; $i>=0; $i--) {
      if ($nodeList[$i] instanceof Node) {
        $this->_nodeList[] = $nodeList[$i];
      }
    }
  }
}
?>