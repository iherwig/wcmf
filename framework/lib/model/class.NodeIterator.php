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

/**
 * @class NodeIterator
 * @ingroup Model
 * @brief NodeIterator is used to iterate over a tree/list build of Nodes
 * using a Depth-First-Algorithm. Classes used with the NodeIterator must
 * implement the getChildren() and getOID() methods.
 * NodeIterator implements the 'Iterator Pattern'.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NodeIterator
{
  protected $_end;          // indicates if the iteration is ended
  protected $_nodeList;     // the list of seen nodes
  protected $_nodeIdList;   // the list of seen object ids
  protected $_currentNode;  // the node the iterator points to
  /**
   * Constructor.
   * @param node The node to start from.
   */
  public function __construct(Node $node)
  {
    $this->_end = false;
    $this->_nodeList = array();
    $this->_nodeIdList = array();
    $this->_currentNode = &$node;
  }
  /**
   * Proceed to next node.
   * Subclasses may override this method to implement spezial traversion algorithms.
   * @return A reference to the NodeIterator.
   */
  public function proceed()
  {
    $childrenArray = $this->_currentNode->getChildren();
    $this->addToSeenList($childrenArray);

    if (sizeOf($this->_nodeList) != 0)
    {
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
   * Get the current node.
   * Subclasses may override this method to return only special nodes.
   * @return A reference to the current node.
   */
  public function getCurrentNode()
  {
    return $this->_currentNode;
  }
  /**
   * Find out whether iteration is finished.
   * @return 'True' if iteration is finished, 'False' alternatively.
   */
  public function isEnd()
  {
    return $this->_end;
  }
  /**
   * Reset the iterator to given node.
   * @param node The node to start from.
   */
  public function reset($node)
  {
    $this->_end = false;
    $this->_nodeList = array();
    $this->_nodeIdList = array();
    $this->_currentNode = &$node;
  }
  /**
   * Add nodes, only if they are not already in the internal processed node list.
   * @param nodeList An array of nodes.
   */
  //
  protected function addToSeenList($nodeList)
  {
    for ($i=sizeOf($nodeList)-1;$i>=0;$i--) {
      if ($nodeList[$i] instanceof Node) {
        if (!in_array($nodeList[$i]->getOID(), $this->_nodeIdList))
        {
          $this->_nodeList[sizeOf($this->_nodeList)] = &$nodeList[$i];
          array_push($this->_nodeIdList, $nodeList[$i]->getOID());
        }
      }
    }
  }
}
?>