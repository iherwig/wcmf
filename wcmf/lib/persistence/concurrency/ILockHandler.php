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

/**
 * @class ILockHandler
 * @ingroup Persistence
 * @brief Interface for LockHandler implementations.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface ILockHandler
{
  /**
   * Aquire a lock on an object id for the current user and throw
   * an exception, if another user already owns a pessimistic lock on
   * the object.
   * @param oid ObjectId of the object to lock.
   * @param type One of the Lock::Type constants.
   * @param currentState PersistentObject instance defining the current state
   *    for an optimistic lock (optional, only given if type is Lock::TYPE_OPTIMISTIC)
   */
  function aquireLock(ObjectId $oid, $type, PersistentObject $currentState=null);
  /**
   * Release the lock the current user owns on an object id.
   * @param oid ObjectId of the object to release.
   */
  function releaseLock(ObjectId $oid);
  /**
   * Release all locks on an object id regardless of the user.
   * @param oid ObjectId of the object to release.
   */
  function releaseLocks(ObjectId $oid);
  /**
   * Release all locks owned by the current user.
   */
  function releaseAllLocks();
  /**
   * Get the lock for an object id.
   * @param oid object id of the object to get the lock data for.
   * @return Lock instance or null
   */
  function getLock(ObjectId $oid);
}
?>
