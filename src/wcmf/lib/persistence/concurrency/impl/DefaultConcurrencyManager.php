<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2020 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\persistence\concurrency\impl;

use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\core\LogManager;
use wcmf\lib\core\Session;
use wcmf\lib\model\NodeValueIterator;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\concurrency\ConcurrencyManager;
use wcmf\lib\persistence\concurrency\Lock;
use wcmf\lib\persistence\concurrency\LockHandler;
use wcmf\lib\persistence\concurrency\OptimisticLockException;
use wcmf\lib\persistence\concurrency\PessimisticLockException;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistenceFacade;
use wcmf\lib\persistence\PersistentObject;
use wcmf\lib\persistence\ReferenceDescription;
use wcmf\lib\persistence\TransientAttributeDescription;

/**
 * Default ConcurrencyManager implementation.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultConcurrencyManager implements ConcurrencyManager {

  private $persistenceFacade = null;
  private $lockHandler = null;
  private $session = null;

  private static $logger = null;

  /**
   * Constructor
   * @param $persistenceFacade
   * @param $lockHandler
   * @param $session
   */
  public function __construct(PersistenceFacade $persistenceFacade,
          LockHandler $lockHandler,
          Session $session) {
    $this->persistenceFacade = $persistenceFacade;
    $this->lockHandler = $lockHandler;
    $this->session = $session;
    if (self::$logger == null) {
      self::$logger = LogManager::getLogger(__CLASS__);
    }
  }

  /**
   * @see ConcurrencyManager::aquireLock()
   */
  public function aquireLock(ObjectId $oid, $type, PersistentObject $currentState=null) {
    if (!ObjectId::isValid($oid) || ($type != Lock::TYPE_OPTIMISTIC &&
            $type != Lock::TYPE_PESSIMISTIC)) {
      throw new IllegalArgumentException("Invalid object id or locktype given");
    }

    // load the current state if not provided
    if ($type == Lock::TYPE_OPTIMISTIC && $currentState == null) {
      $currentState = $this->persistenceFacade->load($oid, BuildDepth::SINGLE);
    }

    $this->lockHandler->aquireLock($oid, $type, $currentState);
  }

  /**
   * @see ConcurrencyManager::releaseLock()
   */
  public function releaseLock(ObjectId $oid, $type=null) {
    if (!ObjectId::isValid($oid)) {
      throw new IllegalArgumentException("Invalid object id given");
    }
    $this->lockHandler->releaseLock($oid, $type);
  }

  /**
   * @see ConcurrencyManager::releaseLocks()
   */
  public function releaseLocks(ObjectId $oid) {
    if (!ObjectId::isValid($oid)) {
      throw new IllegalArgumentException("Invalid object id given");
    }
    $this->lockHandler->releaseLocks($oid);
  }

  /**
   * @see ConcurrencyManager::releaseAllLocks()
   */
  public function releaseAllLocks() {
    $this->lockHandler->releaseAllLocks();
  }

  /**
   * @see ConcurrencyManager::getLock()
   */
  public function getLock(ObjectId $oid) {
    return $this->lockHandler->getLock($oid);
  }

  /**
   * @see ConcurrencyManager::checkPersist()
   */
  public function checkPersist(PersistentObject $object) {
    $oid = $object->getOID();
    $lock = $this->lockHandler->getLock($oid);
    if ($lock != null) {
      $type = $lock->getType();

      // if there is a pessimistic lock on the object and it's not
      // owned by the current user, throw a PessimisticLockException
      if ($type == Lock::TYPE_PESSIMISTIC) {
        $authUserLogin = $this->session->getAuthUser();
        if ($lock->getLogin() != $authUserLogin) {
            throw new PessimisticLockException($lock);
        }
      }

      // if there is an optimistic lock on the object and the object was updated
      // in the meantime, throw a OptimisticLockException
      if ($type == Lock::TYPE_OPTIMISTIC) {
        $originalState = $lock->getCurrentState();
        // temporarily detach the object from the transaction in order to get
        // the latest version from the store
        $transaction = $this->persistenceFacade->getTransaction();
        $transaction->detach($object->getOID());
        $currentState = $this->persistenceFacade->load($oid, BuildDepth::SINGLE);
        // check for deletion
        if ($currentState == null) {
          throw new OptimisticLockException(null);
        }
        // check for modifications
        $mapper = $this->persistenceFacade->getMapper($object->getType());
        $it = new NodeValueIterator($originalState, false);
        foreach($it as $valueName => $originalValue) {
          $attribute = $mapper->hasAttribute($valueName) ? $mapper->getAttribute($valueName) : null;
          // ignore references and transient values
          if ($attribute && !($attribute instanceof ReferenceDescription) && !($attribute instanceof TransientAttributeDescription)) {
            $currentValue = $currentState->getValue($valueName);
            if (strval($currentValue) != strval($originalValue)) {
              if (self::$logger->isDebugEnabled()) {
                self::$logger->debug("Current state is different to original state: ".$object->getOID()."-".$valueName.": current[".
                        serialize($currentValue)."], original[".serialize($originalValue)."]");
              }
              throw new OptimisticLockException($currentState);
            }
          }
        }
        // if there was no concurrent update, attach the object again
        $transaction->attach($object);
      }
    }
    // everything is ok
  }

  /**
   * @see ConcurrencyManager::updateLock()
   */
  public function updateLock(ObjectId $oid, PersistentObject $object) {
    return $this->lockHandler->updateLock($oid, $object);
  }
}
?>
