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
require_once(WCMF_BASE."wcmf/lib/visitor/class.Visitor.php");
/**
 * @class CommitVisitor
 * @ingroup Visitor
 * @brief The CommitVisitor is used to commit the object's changes to the object storage.
 * The objects must implement the PersistentObject interface.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class CommitVisitor extends Visitor
{
  private $_result = array();

  /**
   * Visit the current object in iteration and commit changes.
   * @param obj A reference to the current object.
   */
  public function visit($obj)
  {
    $oldState = $obj->getState();
    switch($oldState)
    {
      case STATE_DIRTY:
      case STATE_NEW:
        // save changes / insert
        $obj->save();
        break;

      case STATE_DELETED:
        // delete object
        $obj->delete();
        break;
    }

    // store commit in result array
    if (!isset($this->_result[$oldState])) {
      $this->_result[$oldState] = array();
    }
    $this->_result[$oldState][sizeof($this->_result[$oldState])] = $obj->getOID();
  }

  /**
   * Get the last commit result.
   * @return An associative array with the persistent states as keys and
   *         arrays of oids (after commit) as values
   */
  public function getResult()
  {
    return $this->_result;
  }

  /**
   * Clear the commit result.
   */
  public function clearResult()
  {
    $this->_result = array();
  }
}
?>
