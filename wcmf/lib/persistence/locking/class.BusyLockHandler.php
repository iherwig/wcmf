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
require_once(WCMF_BASE."wcmf/lib/persistence/locking/class.ILockHandler.php");

/**
 * @class BusyLockManager
 * @ingroup Persistence
 * @brief BusyLockManager acts as if half of the resources are locked.
 * Use this to simulate heavy concurrency.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class BusyLockManager implements ILockHandler
{
  /**
   * @see ILockHandler::aquireLockImpl();
   */
  public function aquireLock(ObjectId $useroid, $session, ObjectId $oid, $lockDate) {}
  /**
   * @see ILockHandler::releaseLockImpl();
   */
  public function releaseLock(ObjectId $useroid=null, $sessid=null, ObjectId $oid=null) {}
  /**
   * @see ILockHandler::releaseAllLocksImpl();
   */
  public function releaseAllLocks(ObjectId $useroid, $sessid) {}
  /**
   * @see ILockHandler::getLockImpl();
   */
  public function getLock(ObjectId $oid)
  {
    if (rand(0,1) > 0.5) {
    	return null;
    }
    else
    {
      $lockDate = date("Y-m-d H:i:s", mktime(date("H"), date("i")-1, date("s"), date("m"), date("d"), date("Y"))); // one minute ago
    	return array('oid' => $oid, 'userid' => 0, 'login' => 'BusyLockManager', 'since' => $lockDate, 'sessid' => uniqid(""));
    }
  }
}
?>
