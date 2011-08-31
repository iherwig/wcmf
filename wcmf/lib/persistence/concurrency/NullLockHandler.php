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
require_once(WCMF_BASE."wcmf/lib/persistence/concurrency/ILockHandler.php");

/**
 * @class NullLockHandler
 * @ingroup Persistence
 * @brief NullLockHandler acts as if no LockHandler was installed.
 * Use this to disable locking.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NullLockHandler implements ILockHandler
{
  /**
   * @see ILockHandler::aquireLock()
   */
  public function aquireLock(ObjectId $oid, $type, PersistentObject $currentState=null) {}
  /**
   * @see ILockHandler::releaseLock()
   */
  public function releaseLock(ObjectId $oid) {}
  /**
   * @see ILockHandler::releaseLocks()
   */
  public function releaseLocks(ObjectId $oid) {}
  /**
   * @see ILockHandler::releaseAllLocks()
   */
  public function releaseAllLocks() {}
  /**
   * @see ILockHandler::getLocks()
   */
  public function getLock(ObjectId $oid) {
    return null;
  }
}
?>
