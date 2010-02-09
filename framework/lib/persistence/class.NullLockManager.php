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
require_once(BASE."wcmf/lib/persistence/class.LockManager.php");

/**
 * @class NullLockManager
 * @ingroup Persistence
 * @brief NullLockManager acts as if no LockManager was installed.
 * Use this to disable locking.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NullLockManager extends LockManager
{
  /**
   * @see LockManager::aquireLockImpl();
   */
  function aquireLockImpl($authUser, $session, $oid, $lockDate) {}
  /**
   * @see LockManager::releaseLockImpl();
   */
  function releaseLockImpl($authUser, $session, $oid) {}
  /**
   * @see LockManager::releaseAllLocksImpl();
   */
  function releaseAllLocksImpl($authUser, $session) {}
  /**
   * @see LockManager::getLockImpl();
   */
  function getLockImpl($oid)
  {
    return null;
  }
}
?>
