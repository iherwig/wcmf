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
namespace wcmf\lib\persistence;

use wcmf\lib\core\Event;
use wcmf\lib\persistence\PersistentObject;

/**
 * ValueChangeEvent signals a change of a value of
 * a PersistentObject instance.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ValueChangeEvent extends Event {

  const NAME = __CLASS__;

  private $_object = null;
  private $_name = null;
  private $_oldValue = null;
  private $_newValue = null;

  /**
   * Constructor.
   * @param object A reference to the PersistentObject object that whose value has changed.
   * @param name The name of the item that has changed.
   * @param oldValue The old value of the item that has changed.
   * @param newValue The new value of the item that has changed.
   */
  public function __construct(PersistentObject $object, $name, $oldValue, $newValue) {
    $this->_object = $object;
    $this->_name = $name;
    $this->_oldValue = $oldValue;
    $this->_newValue = $newValue;
  }

  /**
   * Get the object whose value has changed.
   * @return PersistentObject instance
   */
  public function getObject() {
    return $this->_object;
  }

  /**
   * Get the name of the value that has changed.
   * @return String
   */
  public function getValueName() {
    return $this->_name;
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
