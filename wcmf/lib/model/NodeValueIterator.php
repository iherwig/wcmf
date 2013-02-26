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

  protected $_end;               // indicates if the iteration is ended
  protected $_recursive;         // indicates if the iterator should also process child nodes
  protected $_nodeIterator;      // the NodeIterator used internally
  protected $_currentAttributes; // the list of attributes of the current node
  protected $_currentAttribute;  // the attribute the iterator points to

  /**
   * Constructor.
   * @param node The node to start from.
   * @param recursive True/False wether the iterator should also process child nodes
   */
  public function __construct(Node $node, $recursive) {
    $this->_recursive = $recursive;
    $this->_nodeIterator = new NodeIterator($node);
    $this->_currentAttributes = $node->getPersistentValueNames();
    $this->_currentAttribute = current($this->_currentAttributes);
    $this->_end = sizeof($this->_currentAttributes) == 0;
  }

  /**
   * Return the current element
   * @return Value of the current attribute
   */
  public function current() {
    $node = $this->_nodeIterator->current();
    return $node->getValue($this->_currentAttribute);
  }

  /**
   * Return the key of the current element
   * @return String, the name of the current attribute
   */
  public function key() {
    return $this->_currentAttribute;
  }

  /**
   * Move forward to next element
   */
  public function next() {
    $next = next($this->_currentAttributes);
    if ($next !== false) {
      $this->_currentAttribute = $next;
    }
    else {
      if ($this->_recursive) {
        $this->_nodeIterator->next();
        if (!$this->_nodeIterator->valid()) {
          $this->_end = true;
        }
        else {
          $nextNode = $this->_nodeIterator->current();
          $this->_currentAttributes = $nextNode->getPersistentValueNames();
          $this->_currentAttribute = current($this->_currentAttributes);
        }
      }
      else {
        $this->_end = true;
      }
    }
    return $this;
  }

  /**
   * Rewind the Iterator to the first element
   */
  public function rewind() {
    $this->_nodeIterator->rewind();
    $this->_currentAttributes = $this->_nodeIterator->current()->getPersistentValueNames();
    $this->_currentAttribute = current($this->_currentAttributes);
    $this->_end = (sizeof($this->_currentAttributes) == 0);
  }

  /**
   * Checks if current position is valid
   */
  public function valid() {
    return !$this->_end;
  }

  /**
   * Get the current node
   * @return Node instance
   */
  public function currentNode() {
    return $this->_nodeIterator->current();
  }
}
?>