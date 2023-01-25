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

use wcmf\lib\model\Node;
use wcmf\lib\persistence\PersistentObject;

/**
 * NodeIterator is used to iterate over a tree/list built of Nodes
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

  protected bool $end;              // indicates if the iteration is ended
  protected array $nodeList;         // the list of seen nodes
  protected array $processedOidList; // the list of processed object ids
  protected PersistentObject $currentNode;      // the node the iterator points to
  protected PersistentObject $startNode;        // the start node
  protected array $aggregationKinds; // array of aggregation kind values to follow (empty: all)

  /**
   * Constructor.
   * @param PersistentObject $node The node to start from.
   * @param array<string> $aggregationKinds Array of aggregation kind values of relations to follow
   *   possible values: 'none', 'shared', 'composite'. Empty array means all (default: empty)
   */
  public function __construct(PersistentObject $node, array $aggregationKinds=[]) {
    $this->end = false;
    $this->nodeList = [];
    $this->processedOidList = [];
    $this->currentNode = $node;
    $this->startNode = $node;
    $this->aggregationKinds = $aggregationKinds;
  }

  /**
   * Return the current element
   * @return PersistentObject instance
   */
  public function current(): PersistentObject {
    return $this->currentNode;
  }

  /**
   * Return the key of the current element
   * @return string The serialized object id
   */
  public function key(): string {
    return $this->currentNode->getOID()->__toString();
  }

  /**
   * Move forward to next element
   */
  public function next(): void {
    // the current node was processed
    $this->processedOidList[] = $this->currentNode->getOID()->__toString();

    // collect navigable children for the given aggregation kinds
    $childrenArray = [];
    $mapper = $this->currentNode->getMapper();
    $relations = $mapper->getRelations('child');
    $followAll = sizeof($this->aggregationKinds) == 0;
    foreach ($relations as $relation) {
      $aggregationKind = $relation->getOtherAggregationKind();
      if ($relation->getOtherNavigability() && ($followAll || in_array($aggregationKind, $this->aggregationKinds))) {
        $childValue = $this->currentNode->getValue($relation->getOtherRole());
        if ($childValue != null) {
          $children = $relation->isMultiValued() ? $childValue : [$childValue];
          foreach ($children as $child) {
            $childrenArray[] = $child;
          }
        }
      }
    }
    $this->addToQueue($childrenArray);

    // set current node
    if (sizeof($this->nodeList) != 0) {
      $node = array_pop($this->nodeList);
      $oidStr = $node->getOID()->__toString();
      // not the last node -> search for unprocessed nodes
      while (sizeof($this->nodeList) > 0 && in_array($oidStr, $this->processedOidList)) {
        $node = array_pop($this->nodeList);
        $oidStr = $node->getOID()->__toString();
      }
      // last node found, but it was processed already
      if (sizeof($this->nodeList) == 0 && in_array($oidStr, $this->processedOidList)) {
        $this->end = true;
      }
      $this->currentNode = $node;
    }
    else {
      $this->end = true;
    }
  }

  /**
   * Rewind the Iterator to the first element
   */
  public function rewind(): void {
    $this->end = false;
    $this->nodeList = [];
    $this->processedOidList = [];
    $this->currentNode = $this->startNode;
  }

  /**
   * Checks if current position is valid
   * @return bool
   */
  public function valid(): bool {
    return !$this->end;
  }

  /**
   * Add nodes to the processing queue.
   * @param array<PersistentObject> $nodeList An array of nodes.
   */
  protected function addToQueue(array $nodeList): void {
    for ($i=sizeof($nodeList)-1; $i>=0; $i--) {
      if ($nodeList[$i] instanceof Node) {
        $this->nodeList[] = $nodeList[$i];
      }
    }
  }
}
?>