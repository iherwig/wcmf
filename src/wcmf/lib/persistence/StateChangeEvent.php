<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\persistence;

use wcmf\lib\core\Event;
use wcmf\lib\persistence\PersistentObject;

/**
 * StateChangeEvent signals a change of the state of
 * a PersistentObject instance.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class StateChangeEvent extends Event {

  const NAME = __CLASS__;

  private $_object = null;
  private $_oldValue = null;
  private $_newValue = null;

  /**
   * Constructor.
   * @param $object A reference to the PersistentObject object that whose state has changed.
   * @param $oldValue The old value of the state.
   * @param $newValue The new value of the state.
   */
  public function __construct(PersistentObject $object, $oldValue, $newValue) {
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
