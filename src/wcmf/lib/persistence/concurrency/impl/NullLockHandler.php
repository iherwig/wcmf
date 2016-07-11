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
namespace wcmf\lib\persistence\concurrency\impl;

use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistentObject;
use wcmf\lib\persistence\concurrency\LockHandler;

/**
 * NullLockHandler acts as if no LockHandler was installed.
 * Use this to disable locking.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NullLockHandler implements LockHandler {

  /**
   * @see LockHandler::aquireLock()
   */
  public function aquireLock(ObjectId $oid, $type, PersistentObject $currentState=null) {}

  /**
   * @see LockHandler::releaseLock()
   */
  public function releaseLock(ObjectId $oid, $type=null) {}

  /**
   * @see LockHandler::releaseLocks()
   */
  public function releaseLocks(ObjectId $oid) {}

  /**
   * @see LockHandler::releaseAllLocks()
   */
  public function releaseAllLocks() {}

  /**
   * @see LockHandler::getLocks()
   */
  public function getLock(ObjectId $oid) {
    return null;
  }

  /**
   * @see LockHandler::updateLock()
   */
  public function updateLock(ObjectId $oid, PersistentObject $object) {}
}
?>
