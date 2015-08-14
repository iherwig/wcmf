<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2015 wemove digital solutions GmbH
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
 * PropertyChangeEvent signals a change of a property of
 * a PersistentObject instance.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PropertyChangeEvent extends Event {

  const NAME = __CLASS__;

  private $_object = null;
  private $_name = null;
  private $_oldValue = null;
  private $_newValue = null;

  /**
   * Constructor.
   * @param $object A reference to the PersistentObject object that whose property has changed.
   * @param $name The name of the property that has changed.
   * @param $oldValue The old value of the property that has changed.
   * @param $newValue The new value of the property that has changed.
   */
  public function __construct(PersistentObject $object, $name, $oldValue, $newValue) {
    $this->_object = $object;
    $this->_name = $name;
    $this->_oldValue = $oldValue;
    $this->_newValue = $newValue;
  }

  /**
   * Get the object whose property has changed.
   * @return PersistentObject instance
   */
  public function getObject() {
    return $this->_object;
  }

  /**
   * Get the name of the property that has changed.
   * @return String
   */
  public function getPropertyName() {
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
