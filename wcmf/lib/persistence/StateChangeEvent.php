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
require_once(WCMF_BASE."wcmf/lib/core/Event.php");

/**
 * @class StateChangeEvent
 * @ingroup Event
 * @brief StateChangeEvent signals a change of the state of
 * a PersistentObject instance.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class StateChangeEvent extends Event
{
  const NAME = __CLASS__;

  private $_object = null;
  private $_oldValue = null;
  private $_newValue = null;

  /**
   * Constructor.
   * @param object A reference to the PersistentObject object that whose state has changed.
   * @param oldValue The old value of the state.
   * @param newValue The new value of the state.
   */
  public function __construct(PersistentObject $object, $oldValue, $newValue)
  {
    $this->_object = $object;
    $this->_oldValue = $oldValue;
    $this->_newValue = $newValue;
  }
  /**
   * Get the object whose state has changed.
   * @return PersistentObject instance
   */
  public function getObject() {
    return $this->_object;
  }
  /**
   * Get the old value.
   * @return Mixed
   */
  public function getOldValue() {
    return $this->_oldValue;
  }
  /**
   * Get the new value.
   * @return Mixed
   */
  public function getNewValue() {
    return $this->_newValue;
  }
}
?>
