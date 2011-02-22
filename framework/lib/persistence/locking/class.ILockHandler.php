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
   * Aquire a lock on an OID for a given user.
   * @param useroid The oid of the user.
   * @param sessid The id of the session of the user.
   * @param oid object id of the object to lock.
   * @param lockDate date of the lock.
   */
  function aquireLock(ObjectId $useroid, $sessid, ObjectId $oid, $lockDate);
  /**
   * Release a lock on an ObjectId for a given user or all locks for that user or all locks for the ObjectId.
   * The behaviour depends on the given parameters. A null means that this parameter should be ignored
   * @param useroid The oid of the user or null to ignore the userid.
   * @param sessid The id of the session of the user or null to ignore the session id.
   * @param oid object id of the object to release or null top ignore the oid.
   */
  function releaseLock(ObjectId $useroid=null, $sessid=null, ObjectId $oid=null);
  /**
   * Release all lock for a given user.
   * @param useroid The oid of the user.
   * @param sessid The id of the session of the user.
   */
  function releaseAllLocks(ObjectId $useroid, $sessid);
  /**
   * Get lock data for an OID. This method may also be used to check for an lock.
   * @param oid object id of the object to get the lock data for.
   * @return A Lock instance or null if no lock exists/or in case of an invalid oid.
   */
  function getLock(ObjectId $oid);
  }
?>
