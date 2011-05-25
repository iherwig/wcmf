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
require_once(WCMF_BASE."wcmf/lib/persistence/class.ITransaction.php");
// TODO make SearchUtil a ChangeListener at PersistenceFacade
require_once(WCMF_BASE."wcmf/lib/util/class.SearchUtil.php");

/**
 * @class Transaction
 * @ingroup Persistence
 * @brief Default implementation of ITransaction.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class Transaction implements ITransaction
{
  private $_id = '';
  private $_isActive = false;

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
  public function __construct()
  {
    $this->_id = __CLASS__.'_'.ObjectId::getDummyId();
  }
  /**
   * @see ITransaction::registerNew()
   */
  public function registerNew(PersistentObject $object)
  {
    if (!$this->_isActive) {
      return;
    }
    $key = $object->getOID()->__toString();
    Log::debug("Register new object: ".$key, __CLASS__);
    $this->_newObjects[$key] = $object;
  }
  /**
   * @see ITransaction::registerDirty()
   */
  public function registerDirty(PersistentObject $object)
  {
    if (!$this->_isActive) {
      return;
    }
    $key = $object->getOID()->__toString();
    // if it was a new or deleted object, we return immediatly
    if (isset($this->_newObjects[$key]) || isset($this->_deletedObjects[$key])) {
      return;
    }
    Log::debug("Register dirty object: ".$key, __CLASS__);
    $this->_dirtyObjects[$key] = $object;
  }
  /**
   * @see ITransaction::registerDeleted()
   */
  public function registerDeleted(PersistentObject $object)
  {
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
    Log::debug("Register deleted object: ".$key, __CLASS__);
    $this->_deletedObjects[$key] = $object;
  }
  /**
   * @see ITransaction::begin()
   */
  public function begin()
  {
    Log::debug("Starting transaction", __CLASS__);
    $this->_isActive = true;
  }
  /**
   * @see ITransaction::commit()
   */
  public function commit()
  {
    if ($this->_isActive)
    {
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
        while (!$commitDone)
        {
          $this->processInserts();
          $this->processUpdates();
          $this->processDeletes();
          // check if all queues are empty
          $commitDone = (sizeof($this->_newObjects) == 0 &&
                  sizeof($this->_dirtyObjects) == 0 &&
                          sizeof($this->_deletedObjects) == 0);
        }
        // commit transaction for each mapper
        Log::debug("Committing transaction", __CLASS__);
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
      }
    }
    // forget changes
    $this->clear();
    $this->_isActive = false;
  }
  /**
   * @see ITransaction::rollback()
   */
  public function rollback()
  {
    // forget changes
    $this->clear();
    $this->_isActive = false;
  }
  /**
   * @see ITransaction::isActive()
   */
  public function isActive()
  {
    return $this->_isActive;
  }
  /**
   * @see ITransaction::registerLoaded()
   */
  public function registerLoaded(PersistentObject $object)
  {
    $oid = $object->getOID();
    $key = $oid->__toString();
    if (Log::isDebugEnabled(__CLASS__)) {
      Log::debug("Register loaded object: ".$key, __CLASS__);
      Log::debug("New Data:\n".$object->dump(), __CLASS__);
      Log::debug("Registry before:\n".$this->dump(), __CLASS__);
    }
    // register the object if it is newly loaded or
    // merge the attributes, if it is already loaded
    $registeredObject = $this->getLoaded($oid);
    if ($registeredObject != null) {
      // merge existing attributes with new attributes
      Log::debug("Merging data of ".$key, __CLASS__);
      $registeredObject->mergeValues($object);
    }
    else {
      $this->_loadedObjects[$key] = $object;
      // start to listen to changes if the transaction is active
      if ($this->_isActive) {
        Log::debug("Start listening to: ".$key, __CLASS__);
        $object->addChangeListener($this);
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
  protected function dump()
  {
    $str = '';
    foreach (array_values($this->_loadedObjects) as $curObject) {
      $str .= $curObject->dump();
    }
    return $str;
  }
  /**
   * @see ITransaction::getLoaded()
   */
  public function getLoaded(ObjectId $oid)
  {
    $key = $oid->__toString();
    if (isset($this->_loadedObjects[$key])) {
      return $this->_loadedObjects[$key];
    }
    return null;
  }
  /**
   * @see ITransaction::detach()
   */
  public function detach(PersistentObject $object)
  {
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
    $object->removeChangeListener($this);
  }
  /**
   * Clear all internal
   */
  protected function clear()
  {
    foreach ($this->_newObjects as $object) {
      $object->removeChangeListener($this);
    }
    $this->_newObjects = array();

    foreach ($this->_dirtyObjects as $object) {
      $object->removeChangeListener($this);
    }
    $this->_dirtyObjects = array();

    foreach ($this->_deletedObjects as $object) {
      $object->removeChangeListener($this);
    }
    $this->_deletedObjects = array();

    foreach ($this->_loadedObjects as $object) {
      $object->removeChangeListener($this);
    }
    $this->_loadedObjects = array();
  }
  /**
   * Process the new objects queue
   */
  protected function processInserts()
  {
    $insertOids = array_keys($this->_newObjects);
    while (sizeof($insertOids) > 0)
    {
      $key = array_shift($insertOids);
      Log::debug("Process insert on object: ".$key, __CLASS__);
      $object = $this->_newObjects[$key];
      $mapper = $object->getMapper();
      if ($mapper) {
        $mapper->save($object);
        // update search index
        SearchUtil::indexInSearch($object);
      }
      unset($this->_newObjects[$key]);
      $insertOids = array_keys($this->_newObjects);
    }
  }
  /**
   * Process the dirty objects queue
   */
  protected function processUpdates()
  {
    $updateOids = array_keys($this->_dirtyObjects);
    while (sizeof($updateOids) > 0)
    {
      $key = array_shift($updateOids);
      Log::debug("Process update on object: ".$key, __CLASS__);
      $object = $this->_dirtyObjects[$key];
      $mapper = $object->getMapper();
      if ($mapper) {
        $mapper->save($object);
        // update search index
        SearchUtil::indexInSearch($object);
      }
      unset($this->_dirtyObjects[$key]);
      $updateOids = array_keys($this->_dirtyObjects);
    }
  }
  /**
   * Process the deleted objects queue
   */
  protected function processDeletes()
  {
    $deleteOids = array_keys($this->_deletedObjects);
    while (sizeof($deleteOids) > 0)
    {
      $key = array_shift($deleteOids);
      Log::debug("Process delete on object: ".$key, __CLASS__);
      $object = $this->_deletedObjects[$key];
      $mapper = $object->getMapper();
      if ($mapper) {
        $object->beforeDelete();
        // remove from search index
        SearchUtil::deleteFromSearch($object);
      }
      unset($this->_deletedObjects[$key]);
      $deleteOids = array_keys($this->_deletedObjects);
    }
  }

  /**
   * ChangeListener interface implementation
   */

  /**
   * @see IChangeListener::getId()
   */
  public function getId()
  {
    return $this->_id;
  }
  /**
   * @see IChangeListener::valueChanged()
   */
  public function valueChanged(PersistentObject $object, $name, $oldValue, $newValue) {}
  /**
   * @see IChangeListener::propertyChanged()
   */
  public function propertyChanged(PersistentObject $object, $name, $oldValue, $newValue) {}
  /**
   * @see IChangeListener::stateChanged()
   */
  public function stateChanged(PersistentObject $object, $oldValue, $newValue)
  {
    Log::debug("State changed: ".$object->getOID()." old:".$oldValue." new:".$newValue, __CLASS__);
    switch ($newValue)
    {
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
?>