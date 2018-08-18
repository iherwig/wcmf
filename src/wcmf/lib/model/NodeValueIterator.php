<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2018 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\model;

use wcmf\lib\model\Node;
use wcmf\lib\model\NodeIterator;

/**
 * NodeValueIterator is used to iterate over all persistent values of a Node
 * (not including relations).
 *
 * The following example shows the usage:
 *
 * @code
 * // load the node with depth 10
 * $node = ObjectFactory::getInstance('persistenceFacade')->load(new ObjectId('Page', 300), 10);
 *
 * // iterate over all values
 * $it = new NodeValueIterator($node);
 * foreach($it as $name => $value) {
 *   echo("current attribute name: ".$name);
 *   echo("current attribute value: ".$value);
 * }
 *
 * // iterate over all values with access to the current object
 * for($it->rewind(); $it->valid(); $it->next()) {
 *   echo("current object: ".$it->currentNode());
 *   echo("current attribute name: ".$it->key());
 *   echo("current attribute value: ".$it->current());
 * }
 * @endcode
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NodeValueIterator implements \Iterator {

  protected $end;               // indicates if the iteration is ended
  protected $recursive;         // indicates if the iterator should also process child nodes
  protected $nodeIterator;      // the NodeIterator used internally
  protected $currentAttributes; // the list of attributes of the current node
  protected $currentAttribute;  // the attribute the iterator points to

  /**
   * Constructor.
   * @param $node The node to start from.
   * @param $recursive Boolean whether the iterator should also process child nodes
   */
  public function __construct($node, $recursive) {
    $this->recursive = $recursive;
    $this->nodeIterator = new NodeIterator($node);
    $this->currentAttributes = $node->getValueNames(false);
    $this->currentAttribute = current($this->currentAttributes);
    $this->end = sizeof($this->currentAttributes) == 0;
  }

  /**
   * Return the current element
   * @return Value of the current attribute
   */
  public function current() {
    $node = $this->nodeIterator->current();
    return $node->getValue($this->currentAttribute);
  }

  /**
   * Return the key of the current element
   * @return String, the name of the current attribute
   */
  public function key() {
    return $this->currentAttribute;
  }

  /**
   * Move forward to next element
   */
  public function next() {
    $next = next($this->currentAttributes);
    if ($next !== false) {
      $this->currentAttribute = $next;
    }
    else {
      if ($this->recursive) {
        $this->nodeIterator->next();
        if (!$this->nodeIterator->valid()) {
          $this->end = true;
        }
        else {
          $nextNode = $this->nodeIterator->current();
          $this->currentAttributes = $nextNode->getValueNames(false);
          $this->currentAttribute = current($this->currentAttributes);
        }
      }
      else {
        $this->end = true;
      }
    }
    return $this;
  }

  /**
   * Rewind the Iterator to the first element
   */
  public function rewind() {
    $this->nodeIterator->rewind();
    $this->currentAttributes = $this->nodeIterator->current()->getValueNames(false);
    $this->currentAttribute = current($this->currentAttributes);
    $this->end = (sizeof($this->currentAttributes) == 0);
  }

  /**
   * Checks if current position is valid
   */
  public function valid() {
    return !$this->end;
  }

  /**
   * Get the current node
   * @return Node instance
   */
  public function currentNode() {
    return $this->nodeIterator->current();
  }
}
?>