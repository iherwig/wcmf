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
namespace wcmf\lib\persistence;

use \Exception;
use wcmf\lib\core\EventManager;
use wcmf\lib\core\Log;
use wcmf\lib\persistence\ITransaction;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistenceFacade;
use wcmf\lib\persistence\PersistentObject;
use wcmf\lib\persistence\StateChangeEvent;
use wcmf\lib\persistence\concurrency\ConcurrencyManager;

/**
 * Default implementation of ITransaction.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class Transaction implements ITransaction {

  private $_id = '';
  private $_isActive = false;
  private $_observedObjects = array();

  protected $_newObjects = array();
  protected $_dirtyObjects = array();
  protected $_deletedObjects = array();

  /**
   * Contains all loaded objects no matter which state they have
   */
  protected $_loadedObjects = array();

  /**
   * Constructor.
   */
  public function __construct() {
    $this->_id = __CLASS__.'_'.ObjectId::getDummyId();
    EventManager::getInstance()->addListener(StateChangeEvent::NAME,
      array($this, 'stateChanged'));
  }

  /**
   * Destructor.
   */
  public function __destruct() {
    EventManager::getInstance()->removeListener(StateChangeEvent::NAME,
      array($this, 'stateChanged'));
  }

  /**
   * @see ITransaction::registerNew()
   */
  public function registerNew(PersistentObject $object) {
    if (!$this->_isActive) {
      return;
    }
    $key = $object->getOID()->__toString();
    Log::info("Register new object: ".$key, __CLASS__);
    $this->_newObjects[$key] = $object;
    $this->_observedObjects[$key] = $object;
  }

  /**
   * @see ITransaction::registerDirty()
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
    Log::info("Register dirty object: ".$key, __CLASS__);
    $this->_dirtyObjects[$key] = $object;
  }

  /**
   * @see ITransaction::registerDeleted()
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
    Log::info("Register deleted object: ".$key, __CLASS__);
    $this->_deletedObjects[$key] = $object;
  }

  /**
   * @see ITransaction::begin()
   */
  public function begin() {
    Log::info("Starting transaction", __CLASS__);
    $this->_isActive = true;
  }

  /**
   * @see ITransaction::commit()
   */
  public function commit() {
    if ($this->_isActive) {
      $persistenceFacade = PersistenceFacade::getInstance();
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
          $this->processInserts();
          $this->processUpdates();
          $this->processDeletes();
          // check if all queues are empty
          $commitDone = (sizeof($this->_newObjects) == 0 &&
                  sizeof($this->_dirtyObjects) == 0 &&
                          sizeof($this->_deletedObjects) == 0);
        }
        // commit transaction for each mapper
        Log::info("Committing transaction", __CLASS__);
        foreach ($knowTypes as $type) {
          $mapper = $persistenceFacade->getMapper($type);
          $mapper->commitTransaction();
        }
      }
      catch (Exception $ex) {
        // rollback transaction for each mapper
        Log::error("Rolling back transaction. Exception: ".$ex->getMessage(), __CLASS__);
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
  }

  /**
   * @see ITransaction::rollback()
   */
  public function rollback() {
    // forget changes
    $this->clear();
    $this->_isActive = false;
  }

  /**
   * @see ITransaction::isActive()
   */
  public function isActive() {
    return $this->_isActive;
  }

  /**
   * @see ITransaction::registerLoaded()
   */
  public function registerLoaded(PersistentObject $object) {
    $oid = $object->getOID();
    $key = $oid->__toString();
    if (Log::isDebugEnabled(__CLASS__)) {
      Log::debug("New Data:\n".$object->dump(), __CLASS__);
      Log::debug("Registry before:\n".$this->dump(), __CLASS__);
    }
    // register the object if it is newly loaded or
    // merge the attributes, if it is already loaded
    $registeredObject = null;
    if (isset($this->_loadedObjects[$key])) {
      $registeredObject = $this->_loadedObjects[$key];
      // merge existing attributes with new attributes
      Log::debug("Merging data of ".$key, __CLASS__);
      $registeredObject->mergeValues($object);
    }
    else {
      Log::info("Register loaded object: ".$key, __CLASS__);
      $this->_loadedObjects[$key] = $object;
      // start to listen to changes if the transaction is active
      if ($this->_isActive) {
        Log::debug("Start listening to: ".$key, __CLASS__);
        $this->_observedObjects[$key] = $object;
      }
      $registeredObject = $object;
    }
    if (Log::isDebugEnabled(__CLASS__)) {
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
   * @see ITransaction::getLoaded()
   */
  public function getLoaded(ObjectId $oid, $buildAttribs=null) {
    $registeredObject = null;
    $key = $oid->__toString();
    if (isset($this->_loadedObjects[$key])) {
      $registeredObject = $this->_loadedObjects[$key];
      // check requested attributes
      if (!$registeredObject->isComplete()) {
        if ($buildAttribs == null) {
          // all attributes are expected, but the object is not complete
          return null;
        }
        else {
          // compare existing attributes with requested ones
          foreach ($buildAttribs as $attributeName) {
            if (!$registeredObject->hasValue($attributeName)) {
              // immediatly return, if buildAttrib does not exist
              Log::debug("Build attribute constraint not fullfilled for: ".$key.".".$attributeName, __CLASS__);
              return null;
            }
          }
        }
      }
    }
    return $registeredObject;
  }

  /**
   * @see ITransaction::detach()
   */
  public function detach(PersistentObject $object) {
    $key = $object->getOID()->__toString();
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
  }

  /**
   * Process the new objects queue
   */
  protected function processInserts() {
    $insertOids = array_keys($this->_newObjects);
    while (sizeof($insertOids) > 0) {
      $key = array_shift($insertOids);
      Log::info("Process insert on object: ".$key, __CLASS__);
      $object = $this->_newObjects[$key];
      $mapper = $object->getMapper();
      if ($mapper) {
        $mapper->save($object);
      }
      unset($this->_newObjects[$key]);
      $insertOids = array_keys($this->_newObjects);
    }
  }

  /**
   * Process the dirty objects queue
   */
  protected function processUpdates() {
    $updateOids = array_keys($this->_dirtyObjects);
    while (sizeof($updateOids) > 0) {
      $key = array_shift($updateOids);
      Log::info("Process update on object: ".$key, __CLASS__);
      $object = $this->_dirtyObjects[$key];
      ConcurrencyManager::getInstance()->checkPersist($object);
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
      Log::info("Process delete on object: ".$key, __CLASS__);
      $object = $this->_deletedObjects[$key];
      ConcurrencyManager::getInstance()->checkPersist($object);
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
   * @param event StateChangeEvent instance
   */
  public function stateChanged(StateChangeEvent $event) {
    $object = $event->getObject();
    if (isset($this->_observedObjects[$object->getOID()->__toString()])) {
      $oldState = $event->getOldValue();
      $newState = $event->getNewValue();
      Log::debug("State changed: ".$object->getOID()." old:".$oldState." new:".$newState, __CLASS__);
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
    }
  }
}
?>