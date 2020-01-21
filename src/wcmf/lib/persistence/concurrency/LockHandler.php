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
namespace wcmf\lib\persistence\concurrency;

use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistentObject;

/**
 * LockHandler defines the interface for LockHandler implementations.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface LockHandler {

  /**
   * Aquire a lock on an object id for the current user and throw
   * an exception, if another user already owns a pessimistic lock on
   * the object.
   * @param $oid ObjectId of the object to lock.
   * @param $type One of the Lock::Type constants.
   * @param $currentState PersistentObject instance defining the current state
   *    for an optimistic lock (optional, only given if type is Lock::TYPE_OPTIMISTIC)
   */
  public function aquireLock(ObjectId $oid, $type, PersistentObject $currentState=null);

  /**
   * Release the lock the current user owns on an object id.
   * @param $oid ObjectId of the object to release.
   * @param $type One of the Lock::Type constants or null for all types (default: _null_)
   */
  public function releaseLock(ObjectId $oid, $type=null);

  /**
   * Release all locks on an object id regardless of the user.
   * @param $oid ObjectId of the object to release.
   */
  public function releaseLocks(ObjectId $oid);

  /**
   * Release all locks owned by the current user.
   */
  public function releaseAllLocks();

  /**
   * Get the lock for an object id.
   * @param $oid object id of the object to get the lock data for.
   * @return Lock instance or null
   */
  public function getLock(ObjectId $oid);

  /**
   * Update the current state of the lock belonging to the given object
   * if existing and owned by the current.
   * @param $oid The object id.
   * @param $object The updated object data.
   */
  public function updateLock(ObjectId $oid, PersistentObject $object);
}
?>
