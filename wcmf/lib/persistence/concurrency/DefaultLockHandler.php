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
require_once(WCMF_BASE."wcmf/lib/persistence/PersistenceFacade.php");
require_once(WCMF_BASE."wcmf/lib/model/ObjectQuery.php");
require_once(WCMF_BASE."wcmf/lib/util/InifileParser.php");

/**
 * @class DefaultLockHandler
 * @ingroup Persistence
 * @brief DefaultLockHandler implements the ILockHandler interface for relational databases.
 * Locks are represented by the entity type 'Locktable' with attributes
 * 'sessionid', 'objectid', 'since'. Locktable instances are children of the user entity.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultLockHandler implements ILockHandler
{
  const SESSION_VARNAME = 'DefaultLockHandler.locks';

  /**
   * @see ILockHandler::aquireLock()
   */
  public function aquireLock(ObjectId $oid, $type, PersistentObject $currentState=null)
  {
    $currentUser = $this->getCurrentUser();
    if (!$currentUser) {
      return;
    }
    $session = SessionData::getInstance();

    // check for existing locks
    $lock = $this->getLock($oid);
    if ($lock != null)
    {
      if ($lock->getType() == Lock::TYPE_PESSIMISTIC) {
        // if the existing lock is a pessimistic lock and it is owned by another
        // user, we throw an exception
        if ($lock->getUserOID() != $currentUser->getOID()) {
          throw new PessimisticLockException($lock);
        }
        // if the existing lock is a pessimistic lock and owned by the user
        // there is no need to aquire a optimistic lock
        if ($type == Lock::TYPE_OPTIMISTIC) {
          return;
        }
      }
    }

    // create the lock instance
    $lock = new Lock($type, $oid, $currentUser->getOID(), $currentUser->getLogin(),
            $session->getID());

    // set the current state for optimistic locks
    if ($type == Lock::TYPE_OPTIMISTIC) {
      $lock->setCurrentState($currentState);
    }

    // store the lock
    $this->storeLock($lock);
  }

  /**
   * @see ILockHandler::releaseLock()
   */
  public function releaseLock(ObjectId $oid)
  {
    $currentUser = $this->getCurrentUser();
    if (!$currentUser) {
      return;
    }
    $session = SessionData::getInstance();

    // delete locks for the given oid, current user and session
    $query = new ObjectQuery('Locktable');
    $tpl = $query->getObjectTemplate('Locktable');
    $tpl->setValue('sessionid', Criteria::asValue("=", $session->getID()));
    $tpl->setValue('objectid', Criteria::asValue("=", $oid->__toString()));
    $userTpl = $query->getObjectTemplate(UserManager::getUserClassName());
    $userTpl->setOID($currentUser->getOID());
    $userTpl->addNode($tpl);
    $locks = $query->execute(BUILDDEPTH_SINGLE);
    foreach($locks as $lock) {
      // delete lock immediatly
      $lock->getMapper()->delete($lock);
    }
  }

  /**
   * @see ILockHandler::releaseLocks()
   */
  public function releaseLocks(ObjectId $oid)
  {
    // delete locks for the given oid
    $query = new ObjectQuery('Locktable');
    $tpl = $query->getObjectTemplate('Locktable');
    $tpl->setValue('objectid', Criteria::asValue("=", $oid->__toString()));
    $locks = $query->execute(BUILDDEPTH_SINGLE);
    foreach($locks as $lock) {
      // delete lock immediatly
      $lock->getMapper()->delete($lock);
    }
  }

  /**
   * @see ILockHandler::releaseAllLocks()
   */
  public function releaseAllLocks()
  {
    $currentUser = $this->getCurrentUser();
    if (!$currentUser) {
      return;
    }
    $session = SessionData::getInstance();

    // delete locks for the current user and session
    $query = new ObjectQuery('Locktable');
    $tpl = $query->getObjectTemplate('Locktable');
    $tpl->setValue('sessionid', Criteria::asValue("=", $session->getID()));
    $userTpl = $query->getObjectTemplate(UserManager::getUserClassName());
    $userTpl->setOID($currentUser->getOID());
    $userTpl->addNode($tpl);
    $locks = $query->execute(BUILDDEPTH_SINGLE);
    foreach($locks as $lock) {
      // delete lock immediatly
      $lock->getMapper()->delete($lock);
    }
  }

  /**
   * Get an existing Lock instance if existing
   * @param oid ObjectId of the locked object
   * @return Lock instance or null
   */
  public function getLock(ObjectId $oid)
  {
    // check if a lock is stored in the session (maybe optimistic
    // or pessimistic)
    $locks = $this->getSessionLocks();
    $oidStr = $oid->__toString();
    if (isset($locks[$oidStr])) {
      return $locks[$oidStr];
    }

    // check if a lock is stored in the database (only pessimistic)
    $query = new ObjectQuery('Locktable');
    $tpl = $query->getObjectTemplate('Locktable');
    $tpl->setValue('objectid', "= '".$oid."'");
    $locks = $query->execute(BUILDDEPTH_SINGLE);
    if (sizeof($locks) > 0) {
      $lockObj = $locks[0];
      $user = $lockObj->getFirstParent(UserManager::getUserClassName());
      $lock = new Lock(Lock::TYPE_PESSIMISTIC, $oid, $user->getOID(), $user->getLogin(),
              $lockObj->getValue('sessionid'), $lockObj->getValue('since'));

      // if the lock belongs to the current user, we store
      // it in the session for later retrieval
      $currentUser = $this->getCurrentUser();
      if ($currentUser && $user->getOID() == $currentUser->getOID()) {
        $this->addSessionLock($lock);
      }
      return $lock;
    }

    return null;
  }

  /**
   * Store the given Lock instance for later retrieval
   * @param lock Lock instance
   */
  protected function storeLock(Lock $lock)
  {
    if ($lock->getType() == Lock::TYPE_PESSIMISTIC) {
      // pessimistic locks must be stored in the database in order
      // to be seen by other users
      $persistenceFacade = PersistenceFacade::getInstance();
      $lockObj = $persistenceFacade->create('Locktable', BUILDDEPTH_REQUIRED);
      $lockObj->setValue('sessionid', $lock->getSessionID());
      $lockObj->setValue('objectid', $lock->getOID());
      $lockObj->setValue('since', $lock->getCreated());
      $userClass = UserManager::getUserClassName();
      $user = new $userClass($lock->getUserOID());
      $user->addNode($lockObj);
      // save lock immediatly
      $lockObj->getMapper()->save($lockObj);
    }
    // store the lock in the session for faster retrieval
    $this->addSessionLock($lock);
  }

  /**
   * Get the current user
   * @return User instance
   */
  protected function getCurrentUser()
  {
    $rightsManager = RightsManager::getInstance();
    return $rightsManager->getAuthUser();
  }

  /**
   * Get the Lock instances stored in the session
   * @return Associative array with the serialized ObjectId instances
   * as keys and the Lock instances as values
   */
  protected function getSessionLocks()
  {
    $session = SessionData::getInstance();
    if ($session->exist(self::SESSION_VARNAME)) {
      return $session->get(self::SESSION_VARNAME);
    }
    return array();
  }

  /**
   * Add a given Lock instance to the session
   * @param lock Lock instance
   */
  protected function addSessionLock(Lock $lock)
  {
    $session = SessionData::getInstance();
    $locks = $this->getSessionLocks();
    $locks[$lock->getOID()->__toString()] = $lock;
    $session->set(self::SESSION_VARNAME, $locks);
  }

  /**
   * Remove a given Lock instance from the session
   * @param lock Lock instance
   */
  protected function removeSessionLock(Lock $lock)
  {
    $session = SessionData::getInstance();
    $locks = $this->getSessionLocks();
    unset($locks[$lock->getOID->__toString()]);
    $session->set(self::SESSION_VARNAME, $locks);
  }
}
?>
