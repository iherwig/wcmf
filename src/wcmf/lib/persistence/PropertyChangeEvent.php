<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2016 wemove digital solutions GmbH
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

  private $object = null;
  private $name = null;
  private $oldValue = null;
  private $newValue = null;

  /**
   * Constructor.
   * @param $object PersistentObject instance that whose property has changed.
   * @param $name The name of the property that has changed.
   * @param $oldValue The old value of the property that has changed.
   * @param $newValue The new value of the property that has changed.
   */
  public function __construct(PersistentObject $object, $name, $oldValue, $newValue) {
    $this->object = $object;
    $this->name = $name;
    $this->oldValue = $oldValue;
    $this->newValue = $newValue;
  }

  /**
   * Get the object whose property has changed.
   * @return PersistentObject instance
   */
  public function getObject() {
    return $this->object;
  }

  /**
   * Get the name of the property that has changed.
   * @return String
   */
  public function getPropertyName() {
    return $this->name;
  }

  /**
   * Get the old value.
   * @return Mixed
   */
  public function getOldValue() {
    return $this->oldValue;
  }

  /**
   * Get the new value.
   * @return Mixed
   */
  public function getNewValue() {
    return $this->newValue;
  }
}
?>
