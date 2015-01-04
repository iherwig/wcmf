<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\persistence\impl;

use \Exception;
use wcmf\lib\core\Log;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\Transaction;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistentObject;
use wcmf\lib\persistence\StateChangeEvent;

/**
 * Default implementation of Transaction.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultTransaction implements Transaction {

  private static $isInfoEnabled = false;
  private static $isDebugEnabled = false;

  private $_id = '';
  private $_isActive = false;
  private $_observedObjects = array();

  protected $_newObjects = array();
  protected $_dirtyObjects = array();
  protected $_deletedObjects = array();
  protected $_detachedObjects = array();

  /**
   * Contains all loaded objects no matter which state they have
   */
  protected $_loadedObjects = array();

  /**
   * Constructor.
   */
  public function __construct() {
    $this->_id = __CLASS__.'_'.ObjectId::getDummyId();
    ObjectFactory::getInstance('eventManager')->addListener(StateChangeEvent::NAME,
      array($this, 'stateChanged'));
    self::$isInfoEnabled = Log::isInfoEnabled(__CLASS__);
    self::$isDebugEnabled = Log::isDebugEnabled(__CLASS__);
  }

  /**
   * Destructor.
   */
  public function __destruct() {
    ObjectFactory::getInstance('eventManager')->removeListener(StateChangeEvent::NAME,
      array($this, 'stateChanged'));
  }

  /**
   * @see Transaction::registerNew()
   */
  public function registerNew(PersistentObject $object) {
    if (!$this->_isActive) {
      return;
    }
    $key = $object->getOID()->__toString();
    if (self::$isDebugEnabled) {
      Log::debug("Register new object: ".$key, __CLASS__);
    }
    $this->_newObjects[$key] = $object;
    $this->_observedObjects[$key] = $object;
  }

  /**
   * @see Transaction::registerDirty()
   */
  public function registerDirty(PersistentObject $object) {
    if (!$this->_isActive) {
      return;
    }
    $key = $object->getOID()->__toString();
    // if it was a new or deleted object, we return immediatly
    if (isset($this->_newObjects[$key]) || isset($this->_deletedObjects[$key])) {
      return;
    }
    if (self::$isDebugEnabled) {
      Log::debug("Register dirty object: ".$key, __CLASS__);
    }
    $this->_dirtyObjects[$key] = $object;
  }

  /**
   * @see Transaction::registerDeleted()
   */
  public function registerDeleted(PersistentObject $object) {
    if (!$this->_isActive) {
      return;
    }
    $key = $object->getOID()->__toString();
    // if it was a new object, we remove it from the registry and
    // return immediatly
    if (isset($this->_newObjects[$key])) {
      unset($this->_newObjects[$key]);
      return;
    }
    // if it was a dirty object, we remove it from the registry
    if (isset($this->_dirtyObjects[$key])) {
      unset($this->_dirtyObjects[$key]);
    }
    if (self::$isDebugEnabled) {
      Log::debug("Register deleted object: ".$key, __CLASS__);
    }
    $this->_deletedObjects[$key] = $object;
  }

  /**
   * @see Transaction::begin()
   */
  public function begin() {
    if (self::$isInfoEnabled) {
      Log::info("Starting transaction", __CLASS__);
    }
    $this->_isActive = true;
  }

  /**
   * @see Transaction::commit()
   */
  public function commit() {
    if (self::$isInfoEnabled) {
      Log::info("Commit transaction", __CLASS__);
    }
    $changedOids = array();
    if ($this->_isActive) {
      $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
      $knowTypes = $persistenceFacade->getKnownTypes();
      try {
        // start transaction for each mapper
        foreach ($knowTypes as $type) {
          $mapper = $persistenceFacade->getMapper($type);
          $mapper->beginTransaction();
        }
        // process the recorded object changes, since new
        // object changes may occure during the commit, we
        // loop until all queues are empty
        $commitDone = false;
        while (!$commitDone) {
          $changedOids = array_merge($changedOids, $this->processInserts());
          $this->processUpdates();
          $this->processDeletes();
          // check if all queues are empty
          $commitDone = (sizeof($this->_newObjects) == 0 &&
                  sizeof($this->_dirtyObjects) == 0 &&
                          sizeof($this->_deletedObjects) == 0);
        }
        // commit transaction for each mapper
        if (self::$isInfoEnabled) {
          Log::info("Committing transaction", __CLASS__);
        }
        foreach ($knowTypes as $type) {
          $mapper = $persistenceFacade->getMapper($type);
          $mapper->commitTransaction();
        }
      }
      catch (Exception $ex) {
        // rollback transaction for each mapper
        Log::error("Rolling back transaction. Exception: ".$ex->__toString(), __CLASS__);
        foreach ($knowTypes as $type) {
          $mapper = $persistenceFacade->getMapper($type);
          $mapper->rollbackTransaction();
        }
        $this->rollback();
        throw $ex;
      }
    }
    // forget changes
    $this->rollback();
    return $changedOids;
  }

  /**
   * @see Transaction::rollback()
   */
  public function rollback() {
    if (self::$isInfoEnabled) {
      Log::info("Rollback transaction", __CLASS__);
    }
    // forget changes
    $this->clear();
    $this->_isActive = false;
  }

  /**
   * @see Transaction::isActive()
   */
  public function isActive() {
    return $this->_isActive;
  }

  /**
   * @see Transaction::registerLoaded()
   */
  public function registerLoaded(PersistentObject $object) {
    $oid = $object->getOID();
    $key = $oid->__toString();
    if (self::$isDebugEnabled) {
      Log::debug("New Data:\n".$object->dump(), __CLASS__);
      Log::debug("Registry before:\n".$this->dump(), __CLASS__);
    }
    // register the object if it is newly loaded or
    // merge the attributes, if it is already loaded
    $registeredObject = null;
    if (isset($this->_loadedObjects[$key])) {
      $registeredObject = $this->_loadedObjects[$key];
      // merge existing attributes with new attributes
      if (self::$isDebugEnabled) {
        Log::debug("Merging data of ".$key, __CLASS__);
      }
      $registeredObject->mergeValues($object);
    }
    else {
      if (self::$isDebugEnabled) {
        Log::debug("Register loaded object: ".$key, __CLASS__);
      }
      $this->_loadedObjects[$key] = $object;
      // start to listen to changes if the transaction is active
      if ($this->_isActive) {
        if (self::$isDebugEnabled) {
          Log::debug("Start listening to: ".$key, __CLASS__);
        }
        $this->_observedObjects[$key] = $object;
      }
      $registeredObject = $object;
    }
    if (self::$isDebugEnabled) {
      Log::debug("Registry after:\n".$this->dump(), __CLASS__);
    }
    return $registeredObject;
  }

  /**
   * Dump the registry content into a string
   * @return String
   */
  protected function dump() {
    $str = '';
    foreach (array_values($this->_loadedObjects) as $curObject) {
      $str .= $curObject->dump();
    }
    return $str;
  }

  /**
   * @see Transaction::getLoaded()
   */
  public function getLoaded(ObjectId $oid) {
    $registeredObject = null;
    $key = $oid->__toString();
    if (isset($this->_loadedObjects[$key])) {
      $registeredObject = $this->_loadedObjects[$key];
    }
    return $registeredObject;
  }

  /**
   * @see Transaction::detach()
   */
  public function detach(ObjectId $oid) {
    $key = $oid->__toString();
    if (isset($this->_newObjects[$key])) {
      unset($this->_newObjects[$key]);
    }
    if (isset($this->_dirtyObjects[$key])) {
      unset($this->_dirtyObjects[$key]);
    }
    if (isset($this->_deletedObjects[$key])) {
      unset($this->_deletedObjects[$key]);
    }
    if (isset($this->_loadedObjects[$key])) {
      unset($this->_loadedObjects[$key]);
    }
    unset($this->_observedObjects[$key]);
    $this->_detachedObjects[$key] = $oid;
  }

  /**
   * Clear all internal
   */
  protected function clear() {
    foreach ($this->_newObjects as $object) {
      unset($this->_observedObjects[$object->getOID()->__toString()]);
    }
    $this->_newObjects = array();

    foreach ($this->_dirtyObjects as $object) {
      unset($this->_observedObjects[$object->getOID()->__toString()]);
    }
    $this->_dirtyObjects = array();

    foreach ($this->_deletedObjects as $object) {
      unset($this->_observedObjects[$object->getOID()->__toString()]);
    }
    $this->_deletedObjects = array();

    foreach ($this->_loadedObjects as $object) {
      unset($this->_observedObjects[$object->getOID()->__toString()]);
    }
    $this->_loadedObjects = array();

    $this->_detachedObjects = array();
  }

  /**
   * Process the new objects queue
   * @return Map of oid changes (key: oid string before commit, value: oid string after commit)
   */
  protected function processInserts() {
    $changedOids = array();
    $pendingInserts = array();
    $insertOids = array_keys($this->_newObjects);
    while (sizeof($insertOids) > 0) {
      $key = array_shift($insertOids);
      if (self::$isDebugEnabled) {
        Log::debug("Process insert on object: ".$key, __CLASS__);
      }
      $object = $this->_newObjects[$key];
      // postpone insert, if the object has required objects that are
      // not persisted yet
      $canInsert = true;
      $requiredObjects = $object->getIndispensableObjects();
      foreach ($requiredObjects as $requiredObject) {
        if ($requiredObject->getState() == PersistentObject::STATE_NEW) {
          if (self::$isDebugEnabled) {
            Log::debug("Postpone insert of object: ".$key.". Required objects are not saved yet.", __CLASS__);
          }
          $pendingInserts[] = $object;
          $canInsert = false;
          break;
        }
      }
      if ($canInsert) {
        $mapper = $object->getMapper();
        if ($mapper) {
          $oldOid = $object->getOID();
          $mapper->save($object);
          $changedOids[$oldOid->__toString()] = $object->getOID()->__toString();
        }
      }
      unset($this->_newObjects[$key]);
      $insertOids = array_keys($this->_newObjects);
    }
    // re-add pending inserts
    foreach ($pendingInserts as $object) {
      $key = $object->getOID()->__toString();
      $this->_newObjects[$key] = $object;
    }
    return $changedOids;
  }

  /**
   * Process the dirty objects queue
   */
  protected function processUpdates() {
    $updateOids = array_keys($this->_dirtyObjects);
    while (sizeof($updateOids) > 0) {
      $key = array_shift($updateOids);
      if (self::$isDebugEnabled) {
        Log::debug("Process update on object: ".$key, __CLASS__);
      }
      $object = $this->_dirtyObjects[$key];
      $mapper = $object->getMapper();
      if ($mapper) {
        $mapper->save($object);
      }
      unset($this->_dirtyObjects[$key]);
      $updateOids = array_keys($this->_dirtyObjects);
    }
  }

  /**
   * Process the deleted objects queue
   */
  protected function processDeletes() {
    $deleteOids = array_keys($this->_deletedObjects);
    while (sizeof($deleteOids) > 0) {
      $key = array_shift($deleteOids);
      if (self::$isDebugEnabled) {
        Log::debug("Process delete on object: ".$key, __CLASS__);
      }
      $object = $this->_deletedObjects[$key];
      $mapper = $object->getMapper();
      if ($mapper) {
        $mapper->delete($object);
      }
      unset($this->_deletedObjects[$key]);
      $deleteOids = array_keys($this->_deletedObjects);
    }
  }

  /**
   * Listen to StateChangeEvents
   * @param $event StateChangeEvent instance
   */
  public function stateChanged(StateChangeEvent $event) {
    $object = $event->getObject();

    // don't listen to detached object changes
    $key = $object->getOID()->__toString();
    if (isset($this->_detachedObjects[$key])) {
      return;
    }
    //if (isset($this->_observedObjects[$object->getOID()->__toString()])) {
      $oldState = $event->getOldValue();
      $newState = $event->getNewValue();
      if (self::$isDebugEnabled) {
        Log::debug("State changed: ".$object->getOID()." old:".$oldState." new:".$newState, __CLASS__);
      }
      switch ($newState) {
        case PersistentObject::STATE_NEW:
          $this->registerNew($object);
          break;

        case PersistentObject::STATE_DIRTY:
          $this->registerDirty($object);
          break;

        case PersistentObject::STATE_DELETED:
          $this->registerDeleted($object);
          break;
      }
    //}
  }

  /**
   * @see Transaction::getObjects()
   */
  public function getObjects() {
    return $this->_observedObjects;
  }
}
?>