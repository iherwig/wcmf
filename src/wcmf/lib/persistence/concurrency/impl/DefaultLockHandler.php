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
namespace wcmf\lib\persistence\concurrency\impl;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\ObjectQuery;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\Criteria;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistentObject;
use wcmf\lib\persistence\concurrency\LockHandler;
use wcmf\lib\persistence\concurrency\Lock;
use wcmf\lib\persistence\concurrency\PessimisticLockException;

/**
 * DefaultLockHandler implements the LockHandler interface for relational databases.
 * Locks are represented by the entity type 'Locktable' with attributes
 * 'sessionid', 'objectid', 'since'. Locktable instances are children of the user entity.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultLockHandler implements LockHandler {

  const SESSION_VARNAME = 'DefaultLockHandler.locks';
  const LOCKTYPE = 'Locktable';

  private $_userType = null;
  private $_lockUserRelationName = null;

  /**
   * @see LockHandler::aquireLock()
   */
  public function aquireLock(ObjectId $oid, $type, PersistentObject $currentState=null) {
    $currentUser = $this->getCurrentUser();
    if (!$currentUser) {
      return;
    }
    $session = ObjectFactory::getInstance('session');

    // check for existing locks
    $lock = $this->getLock($oid);
    if ($lock != null) {
      if ($lock->getType() == Lock::TYPE_PESSIMISTIC) {
        // if the existing lock is a pessimistic lock and it is owned by another
        // user, we throw an exception
        if ($lock->getUserOID() != $currentUser->getOID()) {
          throw new PessimisticLockException($lock);
        }
        // if the existing lock is a pessimistic lock and is owned by the user
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
   * @see LockHandler::releaseLock()
   */
  public function releaseLock(ObjectId $oid) {
    $currentUser = $this->getCurrentUser();
    if (!$currentUser) {
      return;
    }

    // delete locks for the given oid and current user
    $query = new ObjectQuery(self::LOCKTYPE);
    $tpl = $query->getObjectTemplate(self::LOCKTYPE);
    $tpl->setValue('objectid', Criteria::asValue("=", $oid));
    $userTpl = $query->getObjectTemplate($this->getUserType());
    $userTpl->setOID($currentUser->getOID());
    $userTpl->addNode($tpl);
    $locks = $query->execute(BuildDepth::SINGLE);
    foreach($locks as $lock) {
      // delete lock immediatly
      $lock->getMapper()->delete($lock);
    }
  }

  /**
   * @see LockHandler::releaseLocks()
   */
  public function releaseLocks(ObjectId $oid) {
    // delete locks for the given oid
    $query = new ObjectQuery(self::LOCKTYPE);
    $tpl = $query->getObjectTemplate(self::LOCKTYPE);
    $tpl->setValue('objectid', Criteria::asValue("=", $oid));
    $locks = $query->execute(BuildDepth::SINGLE);
    foreach($locks as $lock) {
      // delete lock immediatly
      $lock->getMapper()->delete($lock);
    }
  }

  /**
   * @see LockHandler::releaseAllLocks()
   */
  public function releaseAllLocks() {
    $currentUser = $this->getCurrentUser();
    if (!$currentUser) {
      return;
    }

    // delete locks for the current user
    $query = new ObjectQuery(self::LOCKTYPE);
    $tpl = $query->getObjectTemplate(self::LOCKTYPE);
    $userTpl = $query->getObjectTemplate($this->getUserType());
    $userTpl->setOID($currentUser->getOID());
    $userTpl->addNode($tpl);
    $locks = $query->execute(BuildDepth::SINGLE);
    foreach($locks as $lock) {
      // delete lock immediatly
      $lock->getMapper()->delete($lock);
    }
  }

  /**
   * @see LockHandler::getLock()
   */
  public function getLock(ObjectId $oid) {
    // check if a lock is stored in the session (maybe optimistic
    // or pessimistic)
    $locks = $this->getSessionLocks();
    $oidStr = $oid->__toString();
    $sessionLock = null;
    if (isset($locks[$oidStr])) {
      $sessionLock = $locks[$oidStr];
    }

    // if the session lock is pessimistic (exclusive), return it
    if ($sessionLock != null && $sessionLock->getType() == Lock::TYPE_PESSIMISTIC) {
      return $sessionLock;
    }
    else {
      // otherwise we need to check for a pessimistic lock in the store
      $query = new ObjectQuery(self::LOCKTYPE);
      $tpl = $query->getObjectTemplate(self::LOCKTYPE);
      $tpl->setValue('objectid', Criteria::asValue('=', $oid));
      $locks = $query->execute(BuildDepth::SINGLE);
      if (sizeof($locks) > 0) {
        $lockObj = $locks[0];
        $user = $lockObj->getValue($this->getLockUserRelationName());
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
    }

    return $sessionLock;
  }

  /**
   * @see LockHandler::updateLock()
   */
  public function updateLock(ObjectId $oid, PersistentObject $object) {
    $lock = $this->getLock($oid);
    if ($lock) {
      if ($lock->getType() == Lock::TYPE_OPTIMISTIC) {
        $currentUser = $this->getCurrentUser();
        if ($currentUser && $lock->getUserOID() == $currentUser->getOID()) {
          $lock->setCurrentState($object);
          $this->storeLock($lock);
        }
      }
    }
  }

  /**
   * Store the given Lock instance for later retrieval
   * @param lock Lock instance
   */
  protected function storeLock(Lock $lock) {
    if ($lock->getType() == Lock::TYPE_PESSIMISTIC) {
      // pessimistic locks must be stored in the database in order
      // to be seen by other users
      $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
      $lockObj = $persistenceFacade->create(self::LOCKTYPE, BuildDepth::REQUIRED);
      $lockObj->setValue('sessionid', $lock->getSessionID());
      $lockObj->setValue('objectid', $lock->getOID());
      $lockObj->setValue('since', $lock->getCreated());
      $userClass = get_class($persistenceFacade->create($this->getUserType(), BuildDepth::REQUIRED));
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
  protected function getCurrentUser() {
    $permissionManager = ObjectFactory::getInstance('permissionManager');
    return $permissionManager->getAuthUser();
  }

  /**
   * Get the Lock instances stored in the session
   * @return Associative array with the serialized ObjectId instances
   * as keys and the Lock instances as values
   */
  protected function getSessionLocks() {
    $session = ObjectFactory::getInstance('session');
    if ($session->exist(self::SESSION_VARNAME)) {
      return $session->get(self::SESSION_VARNAME);
    }
    return array();
  }

  /**
   * Add a given Lock instance to the session
   * @param lock Lock instance
   */
  protected function addSessionLock(Lock $lock) {
    $session = ObjectFactory::getInstance('session');
    $locks = $this->getSessionLocks();
    $locks[$lock->getOID()->__toString()] = $lock;
    $session->set(self::SESSION_VARNAME, $locks);
  }

  /**
   * Remove a given Lock instance from the session
   * @param lock Lock instance
   */
  protected function removeSessionLock(Lock $lock) {
    $session = ObjectFactory::getInstance('session');
    $locks = $this->getSessionLocks();
    unset($locks[$lock->getOID->__toString()]);
    $session->set(self::SESSION_VARNAME, $locks);
  }

  /**
   * Get the user type.
   * @return String
   */
  protected function getUserType() {
    if ($this->_userType == null) {
      $this->_userType = ObjectFactory::getInstance('User')->getType();
    }
    return $this->_userType;
  }

  /**
   * Get the name of the relation between the lock type and the user type.
   * @return String
   */
  protected function getLockUserRelationName() {
    if ($this->_lockUserRelationName == null) {
      $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
      $mapper = $persistenceFacade->getMapper(self::LOCKTYPE);
      $relDescs = $mapper->getRelationsByType($this->getUserType());
      $this->_lockUserRelationName = $relDescs[0]->getOtherRole();
    }
    return $this->_lockUserRelationName;
  }
}
?>
