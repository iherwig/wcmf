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
namespace wcmf\lib\persistence\concurrency;

use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistentObject;

/**
 * ConcurrencyManager is used to handle concurrency for objects.
 * Depending on the lock type, locking has different semantics:
 * - Optimistic locks can be aquired on the same object by different users.
 *   This lock quarantees that an exception is thrown, if the user tries
 *   to persist an object, which another used has updated, since the user
 *   retrieved it.
 * - Pessimistic (write) locks can be aquired on the same object only by one
 *   user. This lock quarantees that no other user can modify the object
 *   until the lock is released.
 * A user can only aquire one lock on each object. An exeption is thrown,
 * if a user tries to aquire a lock on an object on which another user
 * already owns a pessimistic lock.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface ConcurrencyManager {

  /**
   * Aquire a lock on an ObjectId for the current user. Throws an exception if
   * locking fails.
   * @param oid The object id of the object to lock.
   * @param type One of the Lock::Type constants.
   * @param currentState PersistentObject instance defining the current state
   *    for an optimistic lock (optional, if not given, the current state will
   *    be loaded from the store)
   */
  public function aquireLock(ObjectId $oid, $type, PersistentObject $currentState=null);

  /**
   * Release a lock on an ObjectId for the current user.
   * @param oid object id of the object to release.
   */
  public function releaseLock(ObjectId $oid);

  /**
   * Release all locks on an ObjectId regardless of the user.
   * @param oid object id of the object to release.
   */
  public function releaseLocks(ObjectId $oid);

  /**
   * Release all locks for the current user.
   */
  public function releaseAllLocks();

  /**
   * Check if the given object can be persisted. Throws an exception if not.
   */
  public function checkPersist(PersistentObject $object);
}
?>
