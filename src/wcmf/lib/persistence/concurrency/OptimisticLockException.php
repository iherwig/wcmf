<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2018 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\persistence\concurrency;

/**
 * OptimisticLockException signals an exception when trying to create an
 * optimistic lock.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class OptimisticLockException extends \Exception {

  private $currentState = null;

  /**
   * Constructor
   * @param $currentState PersistentObject instance representing the current object state
   *    or null, if the object is deleted
   */
  public function __construct($currentState) {
    $this->currentState = $currentState;

    $msg = '';
    if ($currentState == null) {
      $msg = 'The object was deleted by another user.';
    }
    else {
      $msg = 'The object was modified by another user.';
    }
    parent::__construct($msg);
  }

  /**
   * Get the current object
   * @return PersistentObject instance
   */
  public function getCurrentState() {
    return $this->currentState;
  }
}
?>
