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

use wcmf\lib\core\ErrorHandler;
use wcmf\lib\core\LogManager;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\i18n\Message;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\output\OutputStrategy;
use wcmf\lib\persistence\PagingInfo;
use wcmf\lib\persistence\PersistenceAction;
use wcmf\lib\persistence\PersistenceFacade;
use wcmf\lib\persistence\PersistenceMapper;
use wcmf\lib\persistence\PersistenceEvent;
use wcmf\lib\persistence\PersistentObject;
use wcmf\lib\security\AuthorizationException;

/**
 * AbstractMapper provides a basic implementation for other mapper classes.
 * It handles authorization on entity level and calls the lifecycle callcacks
 * of PersistentObject instances. Authorization on attribute level has to be
 * implemented by subclasses.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class AbstractMapper implements PersistenceMapper {

  private $_logging = false;
  private $_logStrategy = null;

  private $_attributeNames = array();
  private $_relationNames = array();

  private static $_logger = null;

  /**
   * Constructor
   */
  public function __construct() {
    if (self::$_logger == null) {
      self::$_logger = LogManager::getLogger(__CLASS__);
    }
  }

  /**
   * @see PersistenceMapper::hasRelation()
   */
  public function hasRelation($roleName) {
    if (isset($this->_relationNames[$roleName])) {
      return true;
    }
    $relations = $this->getRelations();
    foreach ($relations as $relation) {
      if ($relation->getOtherRole() == $roleName) {
        $this->_relationNames[$roleName] = true;
        return true;
      }
    }
    return false;
  }

  /**
   * @see PersistenceMapper::hasAttribute()
   */
  public function hasAttribute($name) {
    if (isset($this->_attributeNames[$name])) {
      return true;
    }
    $attributes = $this->getAttributes();
    foreach ($attributes as $attribute) {
      if ($attribute->getName() == $name) {
        $this->_attributeNames[$name] = true;
        return true;
      }
    }
    return false;
  }

  /**
   * @see PersistenceMapper::enableLogging()
   */
  public function enableLogging(OutputStrategy $logStrategy) {
    $this->_logStrategy = $logStrategy;
    $this->_logging = true;
  }

  /**
   * @see PersistenceMapper::disableLogging()
   */
  public function disableLogging() {
    $this->_logging = false;
  }

  /**
   * @see PersistenceMapper::isLogging()
   */
  public function isLogging() {
    return $this->_logging;
  }

  /**
   * @see PersistenceMapper::logAction()
   */
  public function logAction(PersistentObject $obj) {
    if ($this->isLogging()) {
      $this->_logStrategy->writeObject($obj);
    }
  }

  /**
   * @see PersistenceMapper::load()
   */
  public function load(ObjectId $oid, $buildDepth=BuildDepth::SINGLE) {
    if (!$this->checkAuthorization($oid, PersistenceAction::READ)) {
      $this->authorizationFailedError($oid, PersistenceAction::READ);
      return;
    }

    if (!ObjectId::isValid($oid) || $oid->containsDummyIds()) {
      return null;
    }
    // load object
    $object = $this->loadImpl($oid, $buildDepth);
    if ($object != null) {
      // call lifecycle callback
      $object->afterLoad();
    }
    return $object;
  }

  /**
   * @see PersistenceMapper::create()
   */
  public function create($type, $buildDepth=BuildDepth::SINGLE) {
    // Don't check rights here, because object creation may be needed
    // for internal purposes. That newly created objects may not be saved
    // to the storage unless they are valid and the user is authorized
    // is assured by the save method.
    $object = $this->createImpl($type, $buildDepth);

    // call lifecycle callback
    $object->afterCreate();

    return $object;
  }

  /**
   * @see PersistenceMapper::save()
   */
  public function save(PersistentObject $object) {
    $isDirty = ($object->getState() == PersistentObject::STATE_DIRTY);
    $isNew = ($object->getState() == PersistentObject::STATE_NEW);

    $oid = $object->getOID();
    // check permissions for changed attributes first, because this
    // also includes instance and type checks
    $oidStr = $object->getOID()->__toString();
    foreach ($object->getChangedValues() as $valueName) {
      $resource = $oidStr.'.'.$valueName;
      if (!$this->checkAuthorization($resource, PersistenceAction::UPDATE)) {
        $this->authorizationFailedError($resource, PersistenceAction::UPDATE);
        return;
      }
    }
    if ($isDirty && !$this->checkAuthorization($oid, PersistenceAction::UPDATE)) {
      $this->authorizationFailedError($oid, PersistenceAction::UPDATE);
      return;
    }
    elseif ($isNew && !$this->checkAuthorization($oid, PersistenceAction::CREATE)) {
      $this->authorizationFailedError($oid, PersistenceAction::CREATE);
      return;
    }

    // call lifecycle callback
    if ($isDirty) {
      $object->beforeUpdate();
    }
    elseif ($isNew) {
      $object->beforeInsert();
    }

    // validate object
    $object->validateValues();

    // check concurrency
    $concurrencyManager = ObjectFactory::getInstance('concurrencyManager');
    $concurrencyManager->checkPersist($object);

    // save object
    $this->saveImpl($object);

    // update lock
    $concurrencyManager->updateLock($oid, $object);

    // call lifecycle callback
    if ($isDirty) {
      $object->afterUpdate();
      ObjectFactory::getInstance('eventManager')->dispatch(PersistenceEvent::NAME,
              new PersistenceEvent($object, PersistenceAction::UPDATE));
    }
    elseif ($isNew) {
      $object->afterInsert();
      ObjectFactory::getInstance('eventManager')->dispatch(PersistenceEvent::NAME,
              new PersistenceEvent($object, PersistenceAction::CREATE));
    }
  }

  /**
   * @see PersistenceMapper::delete()
   */
  public function delete(PersistentObject $object) {
    $oid = $object->getOID();
    if (!$this->checkAuthorization($oid, PersistenceAction::DELETE)) {
      $this->authorizationFailedError($oid, PersistenceAction::DELETE);
      return;
    }

    if (!ObjectId::isValid($oid)) {
      return false;
    }
    // call lifecycle callback
    $object->beforeDelete();

    // check concurrency
    $concurrencyManager = ObjectFactory::getInstance('concurrencyManager');
    $concurrencyManager->checkPersist($object);

    // delete object
    $result = $this->deleteImpl($object);
    if ($result === true) {
      // call lifecycle callback
      $object->afterDelete();
      ObjectFactory::getInstance('eventManager')->dispatch(PersistenceEvent::NAME,
              new PersistenceEvent($object, PersistenceAction::DELETE));

      // release any locks on the object
      $concurrencyManager->releaseLocks($oid);
    }
    return $result;
  }

  /**
   * @see PersistenceMapper::getOIDs()
   */
  public function getOIDs($type, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null) {
    $oids = $this->getOIDsImpl($type, $criteria, $orderby, $pagingInfo);
    $tx = ObjectFactory::getInstance('persistenceFacade')->getTransaction();

    // remove oids for which the user is not authorized
    $result = array();
    for ($i=0, $count=sizeof($oids); $i<$count; $i++) {
      $oid = $oids[$i];
      if ($this->checkAuthorization($oid, PersistenceAction::READ)) {
        $result[] = $oid;
      }
      else {
        $tx->detach($oid);
      }
    }
    return $result;
  }

  /**
   * @see PersistenceMapper::loadObjects()
   */
  public function loadObjects($type, $buildDepth=BuildDepth::SINGLE, $criteria=null, $orderby=null,
    PagingInfo $pagingInfo=null) {
    $objects = $this->loadObjectsImpl($type, $buildDepth, $criteria, $orderby, $pagingInfo);
    $tx = ObjectFactory::getInstance('persistenceFacade')->getTransaction();

    // remove objects for which the user is not authorized
    $result = array();
    for ($i=0, $count=sizeof($objects); $i<$count; $i++) {
      $object = $objects[$i];
      if ($this->checkAuthorization($object->getOID(), PersistenceAction::READ)) {
        $result[] = $object;
      }
      else {
        $tx->detach($object->getOID());
      }
      // TODO remove attribute values for which the user is not authorized
      // should use some pre-check if restrictions on the entity type exist
    }
    return $result;
  }

  /**
   * @see PersistenceMapper::loadRelation()
   */
  public function loadRelation(array $objects, $role, $buildDepth=BuildDepth::SINGLE, $criteria=null, $orderby=null,
    PagingInfo $pagingInfo=null) {
    $relatedObjects = $this->loadRelationImpl($objects, $role, $buildDepth, $criteria, $orderby, $pagingInfo);
    $tx = ObjectFactory::getInstance('persistenceFacade')->getTransaction();

    // remove objects for which the user is not authorized
    if ($relatedObjects != null) {
      $result = array();
      foreach ($relatedObjects as $oidStr => $curObjects) {
        $curResult = array();
        for ($i=0, $count=sizeof($curObjects); $i<$count; $i++) {
          $object = $curObjects[$i];
          if ($this->checkAuthorization($object->getOID(), PersistenceAction::READ)) {
            $curResult[] = $object;
          }
          else {
            $tx->detach($object->getOID());
          }
        }
        $result[$oidStr] = $curObjects;
      }
      return $result;
    }
    return null;
  }

  /**
   * Check authorization on a resource (type/instance/instance property) and a given action.
   * @param $resource Resource to authorize
   * @param $action Action to authorize
   * @return Boolean depending on success of authorization
   */
  protected function checkAuthorization($resource, $action) {
    $permissionManager = ObjectFactory::getInstance('permissionManager');
    if (!$permissionManager->authorize($resource, '', $action)) {
      return false;
    }
    else {
      return true;
    }
  }

  /**
   * Handle an authorization error.
   * @param $resource
   * @param $action
   * @throws AuthorizationException
   */
  protected function authorizationFailedError($resource, $action) {
    // when reading only log the error to avoid errors on the display
    $msg = Message::get("Authorization failed for action '%0%' on '%1%'.", array($action, $resource));
    if ($action == PersistenceAction::READ) {
      self::$_logger->error($msg."\n".ErrorHandler::getStackTrace());
    }
    else {
      throw new AuthorizationException($msg);
    }
  }

  /**
   * @see PersistenceFacade::load()
   * @note Precondition: Object rights have been checked already
   *
   */
  abstract protected function loadImpl(ObjectId $oid, $buildDepth=BuildDepth::SINGLE);

  /**
   * @see PersistenceFacade::create()
   * @note Precondition: Object rights have been checked already
   */
  abstract protected function createImpl($type, $buildDepth=BuildDepth::SINGLE);

  /**
   * @see PersistenceMapper::save()
   * @note Precondition: Object rights have been checked already
   */
  abstract protected function saveImpl(PersistentObject $object);

  /**
   * @see PersistenceMapper::delete()
   * @note Precondition: Object rights have been checked already
   */
  abstract protected function deleteImpl(PersistentObject $object);

  /**
   * @see PersistenceMapper::getOIDs()
   */
  abstract protected function getOIDsImpl($type, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null);

  /**
   * @see PersistenceMapper::loadObjects()
   */
  abstract protected function loadObjectsImpl($type, $buildDepth=BuildDepth::SINGLE, $criteria=null, $orderby=null,
    PagingInfo $pagingInfo=null);

  /**
   * @see PersistenceMapper::loadRelation()
   */
  abstract protected function loadRelationImpl(array $objects, $role, $buildDepth=BuildDepth::SINGLE, $criteria=null,
    $orderby=null, PagingInfo $pagingInfo=null);
}
?>
