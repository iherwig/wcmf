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
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(WCMF_BASE."wcmf/lib/model/class.ObjectQuery.php");
require_once(WCMF_BASE."wcmf/lib/util/class.InifileParser.php");

/**
 * @class LockManagerRDB
 * @ingroup Persistence
 * @brief LockManagerRDB implements the ILockHandler interface for relational databases.
 * Locks are represented by the entity type 'Locktable' with attributes
 * 'sessionid', 'objectid', 'since'. Locktable instances are children of the user entity.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class RDBLockHandler implements ILockHandler
{
  /**
   * Load the user with the given oid
   */
  public function getUserByOID(ObjectId $useroid)
  {
    // get the user with the given oid
    $persistenceFacade = PersistenceFacade::getInstance();
    return $persistenceFacade->load($useroid, BUILDDEPTH_SINGLE);
  }
  /**
   * @see ILockHandler::aquireLock();
   */
  public function aquireLock(ObjectId $useroid, $sessid, ObjectId $oid, $lockDate)
  {
    $persistenceFacade = PersistenceFacade::getInstance();
    $lock = $persistenceFacade->create('Locktable', BUILDDEPTH_REQUIRED);
    $lock->setValue('sessionid', $sessid);
    $lock->setValue('objectid', $oid);
    $lock->setValue('since', $lockDate);
    $user = $this->getUserByOID($useroid);
    $user->addNode($lock);
    $lock->save();
  }
  /**
   * @see ILockHandler::releaseLock();
   */
  public function releaseLock(ObjectId $useroid=null, $sessid=null, ObjectId $oid=null)
  {
    $query = new ObjectQuery('Locktable');
    $tpl = $query->getObjectTemplate('Locktable');
    if ($sessid != null) {
      $tpl->setValue('sessionid', Criteria::asValue("=", $sessid));
    }
    if ($oid != null) {
      $tpl->setValue('objectid', Criteria::asValue("=", $oid));
    }
    if ($useroid != null)
    {
      $user = $this->getUserByOID($useroid);
      if ($user != null)
      {
        $userTpl = $query->getObjectTemplate(UserManager::getUserClassName());
        $userTpl->setOID($useroid);
        $userTpl->addNode($tpl);
      }
    }
    $locks = $query->execute(BUILDDEPTH_SINGLE);
    foreach($locks as $lock) {
      $lock->delete();
    }
  }
  /**
   * @see ILockHandler::releaseAllLocks();
   */
  public function releaseAllLocks(ObjectId $useroid, $sessid)
  {
    $this->releaseLock($useroid, $sessid, null);
  }
  /**
   * @see ILockHandler::getLock();
   */
  public function getLock(ObjectId $oid)
  {
    // deactivate locking
    $parser = InifileParser::getInstance();
    $oldLocking = $parser->getValue('locking', 'cms');
    $parser->setValue('locking', false, 'cms');

    // load locks
    $query = new ObjectQuery(UserManager::getUserClassName());
    $tpl1 = $query->getObjectTemplate(UserManager::getUserClassName());
    $tpl2 = $query->getObjectTemplate('Locktable');
    $tpl2->setValue('objectid', "= '".$oid."'");
    $tpl1->addNode($tpl2);
    $users = $query->execute(1);

    // reactivate locking
    $parser->setValue('locking', $oldLocking, 'cms');

    if (sizeof($users) == 0) {
      return null;
    }
    else
    {
      $user = $users[0];
      $locks = $user->getChildrenEx(null, 'Locktable', array('objectid' => $oid), null);
      if (sizeof($locks) > 0) {
        $lock = &$locks[0];
      }
      else {
        return null;
      }
      return new Lock($oid, $user->getOID(), $user->getLogin(),
                  $lock->getValue('sessionid'),
                  $lock->getValue('since'));
    }
  }
}
?>
