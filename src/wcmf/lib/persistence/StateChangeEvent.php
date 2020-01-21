<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2020 wemove digital solutions GmbH
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

  private $object = null;
  private $oldValue = null;
  private $newValue = null;

  /**
   * Constructor.
   * @param $object PersistentObject instance
   * @param $oldValue The old value of the state.
   * @param $newValue The new value of the state.
   */
  public function __construct(PersistentObject $object, $oldValue, $newValue) {
    $this->object = $object;
    $this->oldValue = $oldValue;
    $this->newValue = $newValue;
  }

  /**
   * Get the object whose state has changed.
   * @return PersistentObject instance
   */
  public function getObject() {
    return $this->object;
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
