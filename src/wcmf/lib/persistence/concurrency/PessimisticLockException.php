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
namespace wcmf\lib\persistence\concurrency;

use wcmf\lib\persistence\concurrency\Lock;

/**
 * @class PessimisticLockException
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PessimisticLockException extends \Exception {

  private $_lock = null;

  /**
   * Constructor
   * @param $lock Lock instance that cause the exception
   */
  public function __construct(Lock $lock) {
    $this->_lock = $lock;

    parent::__construct("The object is currently locked by another user.");
  }

  /**
   * Get the lock
   * @return Lock instance
   */
  public function getLock() {
    return $this->_lock;
  }
}
?>
