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
namespace wcmf\lib\persistence;

use wcmf\lib\core\ErrorHandler;
use wcmf\lib\core\Log;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\i18n\Message;
use wcmf\lib\persistence\PersistenceAction;
use wcmf\lib\persistence\PersistenceFacade;
use wcmf\lib\persistence\PersistenceMapper;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistentObject;
use wcmf\lib\persistence\output\OutputStrategy;
use wcmf\lib\security\AuthorizationException;

/**
 * AbstractMapper provides a basic implementation for other mapper classes.
 * It handles authorization and calls the lifecycle callcacks of PersistentObject
 * instances.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class AbstractMapper implements PersistenceMapper {

  private $_logging = false;
  private $_logStrategy = null;

  private $_attributeNames = array();
  private $_relationNames = array();

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
    if ($isDirty && !$this->checkAuthorization($oid, PersistenceAction::MODIFY)) {
      $this->authorizationFailedError($oid, PersistenceAction::MODIFY);
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

    // save object
    $this->saveImpl($object);

    // call lifecycle callback
    if ($isDirty) {
      $object->afterUpdate();
    }
    elseif ($isNew) {
      $object->afterInsert();
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

    // delete object
    $result = $this->deleteImpl($object);
    if ($result === true) {
      // call lifecycle callback
      $object->afterDelete();

      // release any locks on the object
      $concurrencyManager = ObjectFactory::getInstance('concurrencyManager');
      $concurrencyManager->releaseLocks($oid);
    }
    return $result;
  }

  /**
   * @see PersistenceMapper::getOIDs()
   */
  public function getOIDs($type, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null) {
    $oids = $this->getOIDsImpl($type, $criteria, $orderby, $pagingInfo);

    // remove oids for which the user is not authorized
    $result = array();
    for ($i=0, $count=sizeof($oids); $i<$count; $i++) {
      $oid = $oids[$i];
      if ($this->checkAuthorization($oid, PersistenceAction::READ)) {
        $result[] = $oid;
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

    // remove objects for which the user is not authorized
    $result = array();
    for ($i=0, $count=sizeof($objects); $i<$count; $i++) {
      $object = $objects[$i];
      if ($this->checkAuthorization($object->getOID(), PersistenceAction::READ)) {
        $result[] = $object;
      }
    }
    return $result;
  }

  /**
   * @see PersistenceMapper::loadRelation()
   */
  public function loadRelation(array $objects, $role, $buildDepth=BuildDepth::SINGLE, $criteria=null, $orderby=null,
    PagingInfo $pagingInfo=null) {
    $relatedObjects = $this->loadRelationImpl($objects, $role, $buildDepth, $criteria, $orderby, $pagingInfo);

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
        }
        $result[$oidStr] = $curObjects;
      }
      return $result;
    }
    return null;
  }

  /**
   * Check authorization on an type/OID and a given action.
   * @param oid The object id of the Object to authorize (its type will be checked too)
   * @param action Action to authorize
   * @return True/False depending on success of authorization
   */
  protected function checkAuthorization(ObjectId $oid, $action) {
    $permissionManager = ObjectFactory::getInstance('permissionManager');
    if (!$permissionManager->authorize($oid, '', $action)) {
      return false;
    }
    else {
      return true;
    }
  }

  /**
   * Handle an authorization error.
   * @param ObjectId oid
   * @param type action
   * @throws AuthorizationException
   */
  protected function authorizationFailedError(ObjectId $oid, $action) {
    // when reading only log the error to avoid errors on the display
    $msg = Message::get("Authorization failed for action '%0%' on '%1%'.", array($action, $oid));
    if ($action == PersistenceAction::READ) {
      Log::error($msg."\n".ErrorHandler::getStackTrace(), __CLASS__);
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
