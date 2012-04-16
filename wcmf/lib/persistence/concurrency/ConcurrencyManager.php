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

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\NodeValueIterator;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistenceFacade;
use wcmf\lib\persistence\PersistentObject;
use wcmf\lib\persistence\concurrency\LockHandler;
use wcmf\lib\persistence\concurrency\OptimisticLockException;
use wcmf\lib\persistence\concurrency\PessimisticLockException;
use wcmf\lib\security\RightsManager;

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
class ConcurrencyManager {

  private static $_instance = null;
  private function __construct() {}

  private $_lockHandlerImpl = null;

  /**
   * Returns an instance of the class.
   * @return A reference to the only instance of the Singleton object
   */
  public static function getInstance() {
    if (!isset(self::$_instance) ) {
      self::$_instance = new ConcurrencyManager();

      $impl = ObjectFactory::getInstance('lockHandler');
      if (!($impl instanceof LockHandler)) {
        throw new ConfigurationException("The configured LockHandler does not implement LockHandler.");
      }
      self::$_instance->_lockHandlerImpl = $impl;
    }
    return self::$_instance;
  }

  /**
   * Aquire a lock on an ObjectId for the current user. Throws an exception if
   * locking fails.
   * @param oid The object id of the object to lock.
   * @param type One of the Lock::Type constants.
   * @param currentState PersistentObject instance defining the current state
   *    for an optimistic lock (optional, if not given, the current state will
   *    be loaded from the store)
   */
  public function aquireLock(ObjectId $oid, $type, PersistentObject $currentState=null) {
    if (!ObjectId::isValid($oid) || ($type != Lock::TYPE_OPTIMISTIC &&
            $type != Lock::TYPE_PESSIMISTIC)) {
      throw new IllegalArgumentException("Invalid object id or locktype given");
    }

    // load the current state if not provided
    if ($type == Lock::TYPE_OPTIMISTIC && $currentState == null) {
      $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
      $currentState = $persistenceFacade->load($oid, BuildDepth::SINGLE);
    }

    $this->_lockHandlerImpl->aquireLock($oid, $type, $currentState);
  }

  /**
   * Release a lock on an ObjectId for the current user.
   * @param oid object id of the object to release.
   */
  public function releaseLock(ObjectId $oid) {
    if (!ObjectId::isValid($oid)) {
      throw new IllegalArgumentException("Invalid object id given");
    }
    $this->_lockHandlerImpl->releaseLock($oid);
  }

  /**
   * Release all locks on an ObjectId regardless of the user.
   * @param oid object id of the object to release.
   */
  public function releaseLocks(ObjectId $oid) {
    if (!ObjectId::isValid($oid)) {
      throw new IllegalArgumentException("Invalid object id given");
    }
    $this->_lockHandlerImpl->releaseLocks($oid);
  }

  /**
   * Release all locks for the current user.
   */
  public function releaseAllLocks() {
    $this->_lockHandlerImpl->releaseAllLocks();
  }

  /**
   * Check if the given object can be persisted. Throws an exception if not.
   */
  public function checkPersist(PersistentObject $object) {
    $oid = $object->getOID();
    $lock = $this->_lockHandlerImpl->getLock($oid);
    if ($lock != null) {
      $type = $lock->getType();

      // if there is a pessimistic lock on the object and it's not
      // owned by the current user, throw a PessimisticLockException
      if ($type == Lock::TYPE_PESSIMISTIC) {
        $rightsManager = RightsManager::getInstance();
        $currentUser = $rightsManager->getAuthUser();
        if ($lock->getUserOID() != $currentUser->getOID()) {
            throw new PessimisticLockException($lock);
        }
      }

      // if there is an optimistic lock on the object and the object was updated
      // in the meantime, throw a OptimisticLockException
      if ($type == Lock::TYPE_OPTIMISTIC) {
        $originalState = $lock->getCurrentState();
        // temporarily detach the object from the transaction in order to get
        // the latest version from the store
        $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
        $transaction = $persistenceFacade->getTransaction();
        $transaction->detach($object);
        $currentState = $persistenceFacade->load($oid, BuildDepth::SINGLE);
        // check for deletion
        if ($currentState == null) {
          throw new OptimisticLockException(null);
        }
        // check for modifications
        $it = new NodeValueIterator($originalState, false);
        foreach($it as $valueName => $originalValue) {
          $currentValue = $currentState->getValue($valueName);
          if ($currentValue != $originalValue) {
            throw new OptimisticLockException($currentState);
          }
        }
        // if there was no concurrent update, attach the object again
        if ($object->getState() == PersistentObject::STATE_DIRTY) {
          $transaction->registerDirty($object);
        }
        elseif ($object->getState() == PersistentObject::STATE_DELETED) {
          $transaction->registerDeleted($object);
        }
      }
    }
    // everything is ok
  }
}
?>
