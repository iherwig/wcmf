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

  protected $_end;          // indicates if the iteration is ended
  protected $_nodeList;     // the list of seen nodes
  protected $_nodeIdList;   // the list of seen object ids
  protected $_currentNode;  // the node the iterator points to
  protected $_startNode;    // the start node

  /**
   * Constructor.
   * @param node The node to start from.
   */
  public function __construct(Node $node) {
    $this->_end = false;
    $this->_nodeList = array();
    $this->_nodeIdList = array();
    $this->_currentNode = $node;
    $this->_startNode = $node;
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
    $childrenArray = $this->_currentNode->getChildren();
    $this->addToSeenList($childrenArray);

    if (sizeOf($this->_nodeList) != 0) {
      // array_pop destroys the reference to the node
      $this->_currentNode = &$this->_nodeList[sizeOf($this->_nodeList)-1];
      array_pop($this->_nodeList);
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
    $this->_nodeIdList = array();
    $this->_currentNode = $this->_startNode;
  }

  /**
   * Checks if current position is valid
   */
  public function valid() {
    return !$this->_end;
  }

  /**
   * Add nodes, only if they are not already in the internal processed node list.
   * @param nodeList An array of nodes.
   */
  protected function addToSeenList($nodeList) {
    for ($i=sizeOf($nodeList)-1; $i>=0; $i--) {
      if ($nodeList[$i] instanceof Node) {
        if (!in_array($nodeList[$i]->getOID()->__toString(), $this->_nodeIdList)) {
          $this->_nodeList[] = $nodeList[$i];
          $this->_nodeIdList[] = $nodeList[$i]->getOID()->__toString();
        }
      }
    }
  }
}
?>