<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2015 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\persistence\concurrency\impl;

use wcmf\lib\core\LogManager;
use wcmf\lib\core\Session;
use wcmf\lib\model\ObjectQuery;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\concurrency\Lock;
use wcmf\lib\persistence\concurrency\LockHandler;
use wcmf\lib\persistence\concurrency\PessimisticLockException;
use wcmf\lib\persistence\Criteria;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistenceFacade;
use wcmf\lib\persistence\PersistentObject;

/**
 * DefaultLockHandler implements the LockHandler interface for relational
 * databases. It relies on an entity type that implements the PersistentLock
 * interface.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultLockHandler implements LockHandler {

  const SESSION_VARNAME = 'DefaultLockHandler.locks';

  private $_persistenceFacade = null;
  private $_session = null;
  private $_lockType = null;

  private static $_logger = null;

  /**
   * Constructor
   * @param $persistenceFacade
   * @param $session
   * @param $lockType Entity type name of PersistentLock instances
   */
  public function __construct(PersistenceFacade $persistenceFacade,
          Session $session,
          $lockType) {
    $this->_persistenceFacade = $persistenceFacade;
    $this->_session = $session;
    $this->_lockType = $lockType;
    if (self::$_logger == null) {
      self::$_logger = LogManager::getLogger(__CLASS__);
    }
  }

  /**
   * @see LockHandler::aquireLock()
   */
  public function aquireLock(ObjectId $oid, $type, PersistentObject $currentState=null) {
    $authUserLogin = $this->getAuthUser();

    // check for existing locks
    $lock = $this->getLock($oid);
    if ($lock != null) {
      if ($lock->getType() == Lock::TYPE_PESSIMISTIC) {
        // if the existing lock is a pessimistic lock and it is owned by another
        // user, we throw an exception
        if ($lock->getLogin() != $authUserLogin) {
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
    $lock = new Lock($type, $oid, $authUserLogin, $this->_session->getID());

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
  public function releaseLock(ObjectId $oid, $type=null) {
    $authUserLogin = $this->getAuthUser();

    // delete locks for the given oid and current user
    $query = new ObjectQuery($this->_lockType, __CLASS__.__METHOD__);
    $tpl = $query->getObjectTemplate($this->_lockType);
    $tpl->setValue('objectid', Criteria::asValue("=", $oid));
    $tpl->setValue('login', Criteria::asValue("=", $authUserLogin));
    $locks = $query->execute(BuildDepth::SINGLE);
    foreach($locks as $lock) {
      // delete lock immediatly
      $lock->getMapper()->delete($lock);
      $this->removeSessionLock($oid, $type);
    }
  }

  /**
   * @see LockHandler::releaseLocks()
   */
  public function releaseLocks(ObjectId $oid) {
    // delete locks for the given oid
    $query = new ObjectQuery($this->_lockType, __CLASS__.__METHOD__);
    $tpl = $query->getObjectTemplate($this->_lockType);
    $tpl->setValue('objectid', Criteria::asValue("=", $oid));
    $locks = $query->execute(BuildDepth::SINGLE);
    foreach($locks as $lock) {
      // delete lock immediatly
      $lock->getMapper()->delete($lock);
      $this->removeSessionLock($oid);
    }
  }

  /**
   * @see LockHandler::releaseAllLocks()
   */
  public function releaseAllLocks() {
    $authUserLogin = $this->getAuthUser();

    // delete locks for the current user
    $query = new ObjectQuery($this->_lockType, __CLASS__.__METHOD__);
    $tpl = $query->getObjectTemplate($this->_lockType);
    $tpl->setValue('login', Criteria::asValue("=", $authUserLogin));
    $locks = $query->execute(BuildDepth::SINGLE);
    foreach($locks as $lock) {
      // delete lock immediatly
      $lock->getMapper()->delete($lock);
      $this->removeSessionLock($lock->getValue('objectid'));
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
      $query = new ObjectQuery($this->_lockType, __CLASS__.__METHOD__);
      $tpl = $query->getObjectTemplate($this->_lockType);
      $tpl->setValue('objectid', Criteria::asValue('=', $oid));
      $locks = $query->execute(BuildDepth::SINGLE);
      if (sizeof($locks) > 0) {
        $lockObj = $locks[0];
        $lock = new Lock(Lock::TYPE_PESSIMISTIC, $oid, $lockObj->getValue('login'),
                $lockObj->getValue('sessionid'), $lockObj->getValue('created'));

        // if the lock belongs to the current user, we store
        // it in the session for later retrieval
        $authUserLogin = $this->getAuthUser();
        if ($lockObj->getValue('login') == $authUserLogin) {
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
        $authUserLogin = $this->getAuthUser();
        if ($lock->getLogin() == $authUserLogin) {
          $lock->setCurrentState($object);
          $this->storeLock($lock);
        }
      }
    }
  }

  /**
   * Store the given Lock instance for later retrieval
   * @param $lock Lock instance
   */
  protected function storeLock(Lock $lock) {
    if ($lock->getType() == Lock::TYPE_PESSIMISTIC) {
      // pessimistic locks must be stored in the database in order
      // to be seen by other users
      $lockObj = $this->_persistenceFacade->create($this->_lockType, BuildDepth::REQUIRED);
      $lockObj->setValue('objectid', $lock->getObjectId());
      $lockObj->setValue('login', $lock->getLogin());
      $lockObj->setValue('created', $lock->getCreated());
      // save lock immediatly
      $lockObj->getMapper()->save($lockObj);
    }
    // store the lock in the session for faster retrieval
    $this->addSessionLock($lock);
  }

  /**
   * Get the login of the current user
   * @return String
   */
  protected function getAuthUser() {
    return $this->_session->getAuthUser();
  }

  /**
   * Get the Lock instances stored in the session
   * @return Associative array with the serialized ObjectId instances
   * as keys and the Lock instances as values
   */
  protected function getSessionLocks() {
    if ($this->_session->exist(self::SESSION_VARNAME)) {
      return $this->_session->get(self::SESSION_VARNAME);
    }
    return array();
  }

  /**
   * Add a given Lock instance to the session
   * @param $lock Lock instance
   */
  protected function addSessionLock(Lock $lock) {
    $locks = $this->getSessionLocks();
    $locks[$lock->getObjectId()->__toString()] = $lock;
    $this->_session->set(self::SESSION_VARNAME, $locks);
  }

  /**
   * Remove a given Lock instance from the session
   * @param $oid The locked oid
   * @param $type One of the Lock::Type constants or null for all types (default: _null_)
   */
  protected function removeSessionLock(ObjectId $oid, $type) {
    $locks = $this->getSessionLocks();
    if (isset($locks[$oid->__toString()])) {
      $lock = $locks[$oid->__toString()];
      if ($type == null || $type != null && $lock->getType() == $type) {
        unset($locks[$oid->__toString()]);
        $this->_session->set(self::SESSION_VARNAME, $locks);
      }
    }
  }
}
?>
