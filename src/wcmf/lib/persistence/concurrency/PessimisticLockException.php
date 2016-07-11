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
namespace wcmf\lib\persistence\concurrency;

use wcmf\lib\persistence\concurrency\Lock;

/**
 * PessimisticLockException signals an exception when trying to create an
 * pessimistic lock.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PessimisticLockException extends \Exception {

  private $lock = null;

  /**
   * Constructor
   * @param $lock Lock instance that cause the exception
   */
  public function __construct(Lock $lock) {
    $this->lock = $lock;

    parent::__construct("The object is currently locked by another user.");
  }

  /**
   * Get the lock
   * @return Lock instance
   */
  public function getLock() {
    return $this->lock;
  }
}
?>
