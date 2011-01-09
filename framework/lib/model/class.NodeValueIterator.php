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
 * @class NodeValueIterator
 * @ingroup Model
 * @brief NodeValueIterator is used to iterate over all persistent values of a Node.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NodeValueIterator
{
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
  public function __construct(Node $node, $recursive)
  {
    $this->_recursive = $recursive;
    $this->_nodeIterator = new NodeIterator($node);
    $this->_currentAttributes = $node->getPersistentValueNames();
    $this->_currentAttribute = current($this->_currentAttributes);
    $this->_end = sizeof($this->_currentAttributes) == 0;
  }
  /**
   * Proceed to next attribute.
   * Subclasses may override this method to implement spezial traversion algorithms.
   * @return A reference to the NodeValueIterator.
   */
  public function proceed()
  {
    $next = next($this->_currentAttributes);
    if ($next !== false) {
      $this->_currentAttribute = $next;
    }
    else
    {
      if ($this->_recursive)
      {
        $this->_nodeIterator->proceed();
        if ($this->_nodeIterator->isEnd()) {
          $this->_end = true;
        }
        else {
          $nextNode = $this->_nodeIterator->getCurrentNode();
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
   * Get the current node.
   * Subclasses may override this method to return only special attributes.
   * @return The current node.
   */
  public function getCurrentNode()
  {
    return $this->_nodeIterator->getCurrentNode();
  }
  /**
   * Get the current attribute name.
   * Subclasses may override this method to return only special attributes.
   * @return The current attribute name.
   */
  public function getCurrentAttribute()
  {
    return $this->_currentAttribute;
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
   * @param recursive True/False wether the iterator should also process child nodes
   */
  public function reset($node, $recursive)
  {
    $this->_recursive = $recursive;
    $this->_nodeIterator = new NodeIterator($node);
    $this->_currentAttributes = $node->getPersistentValueNames();
    $this->_currentAttribute = current($this->_currentAttributes);
    $this->_end = sizeof($this->_currentAttributes) == 0;
  }
}
?>