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
 * @brief NodeIterator is used to iterate over a tree/list build of objects
 * using a Depth-First-Algorithm. Classes used with the NodeIterator must
 * implement the getChildren() and getOID() methods.
 * NodeIterator implements the 'Iterator Pattern'.
 * The base class NodeIterator defines the interface for all
 * specialized Iterator classes.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NodeIterator
{
  var $_end;        // indicates if the iteration is ended
  var $_objList;    // the list of seen objects
  var $_objIdList;  // the list of seen object ids
  var $_currentObj; // the object the iterator points to
  /**
   * Constructor.
   * @param obj The object to start from.
   */
  function NodeIterator(&$obj)
  {
    $this->_end = false;
    $this->_objList = array();
    $this->_objIdList = array();
    $this->_currentObj = &$obj;
  }
  /**
   * Proceed to next object.
   * Subclasses may override this method to implement spezial traversion algorithms.
   * @return A reference to the NodeIterator.
   */
  function &proceed()
  {
    $childrenArray = $this->_currentObj->getChildren();
    $this->addToSeenList($childrenArray);

    if (sizeOf($this->_objList) != 0)
    {
      // array_pop destroys the reference to the object
      $this->_currentObj = & $this->_objList[sizeOf($this->_objList)-1];
      array_pop($this->_objList);
    }
    else
    {
        $this->_end = true;
    }
    return $this;
  }
  /**
   * Get the current object.
   * Subclasses may override this method to return only special objects.
   * @return A reference to the current object.
   */
  function &getCurrentObject()
  {
    return $this->_currentObj;
  }
  /**
   * Find out whether iteration is finished.
   * @return 'True' if iteration is finished, 'False' alternatively.
   */
  function isEnd()
  {
    return $this->_end;
  }
  /**
   * Reset the iterator to given object.
   * @param obj The object to start from.
   */
  function reset(&$obj)
  {
    $this->_end = false;
    $this->_objList = array();
    $this->_objIdList = array();
    $this->_currentObj = &$obj;
  }
  /**
   * Add objects, only if they are not already in the internal processed object list.
   * @attention Internal use only.
   * @param objList An array of objects.
   */
  //
  function addToSeenList($objList)
  {
    for ($i=sizeOf($objList)-1;$i>=0;$i--)
    {
      if ($objList[$i] instanceof Node)
      {
        if (!in_array($objList[$i]->getOID(), $this->_objIdList))
        {
          $this->_objList[sizeOf($this->_objList)] = &$objList[$i];
          array_push($this->_objIdList, $objList[$i]->getOID());
        }
      }
    }
  }
}
?>