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

  private $_dataConverter = null; // a DataConverter object that converts data before writing and after reading from storage
  private $_logging = false;
  private $_logStrategy = null;

  /**
   * @see PersistenceMapper::hasRelation()
   */
  public function hasRelation($roleName) {
    $relations = $this->getRelations();
    foreach ($relations as $relation) {
      if ($relation->getOtherRole() == $roleName) {
        return true;
      }
    }
    return false;
  }

  /**
   * @see PersistenceMapper::hasAttribute()
   */
  public function hasAttribute($name) {
    $attributes = $this->getAttributes();
    foreach ($attributes as $attribute) {
      if ($attribute->getName() == $name) {
        return true;
      }
    }
    return false;
  }

  /**
   * @see PersistenceMapper::setDataConverter()
   */
  public function setDataConverter(DataConverter $dataConverter) {
    $this->_dataConverter = $dataConverter;
  }

  /**
   * @see PersistenceMapper::getDataConverter()
   */
  public function getDataConverter() {
    return $this->_dataConverter;
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
  public function load(ObjectId $oid, $buildDepth=BuildDepth::SINGLE, $buildAttribs=null, $buildTypes=null) {
    if (!$this->checkAuthorization($oid, PersistenceAction::READ)) {
      $this->authorizationFailedError($oid, PersistenceAction::READ);
      return;
    }

    if (!ObjectId::isValid($oid) || $oid->containsDummyIds()) {
      return null;
    }
    // load object
    $object = $this->loadImpl($oid, $buildDepth, $buildAttribs, $buildTypes);
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
    return $this->saveImpl($object);

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
    PagingInfo $pagingInfo=null, $buildAttribs=null, $buildTypes=null) {
    $objects = $this->loadObjectsImpl($type, $buildDepth, $criteria, $orderby, $pagingInfo, $buildAttribs, $buildTypes);

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
  public function loadRelation(PersistentObject $object, $role, $buildDepth=BuildDepth::SINGLE, $criteria=null, $orderby=null,
    PagingInfo $pagingInfo=null, $buildAttribs=null, $buildTypes=null) {
    $objects = $this->loadRelationImpl($object, $role, $buildDepth, $criteria, $orderby, $pagingInfo, $buildAttribs, $buildTypes);

    // remove objects for which the user is not authorized
    if ($objects != null) {
      if (is_array($objects)) {
        // multivalued
        $result = array();
        for ($i=0, $count=sizeof($objects); $i<$count; $i++) {
          $object = $objects[$i];
          if ($this->checkAuthorization($object->getOID(), PersistenceAction::READ)) {
            $result[] = $object;
          }
        }
        return $result;
      }
      else {
        // singlevalued
        if ($this->checkAuthorization($objects->getOID(), PersistenceAction::READ)) {
          return $objects;
        }
      }
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
  abstract protected function loadImpl(ObjectId $oid, $buildDepth=BuildDepth::SINGLE, $buildAttribs=null, $buildTypes=null);

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
    PagingInfo $pagingInfo=null, $buildAttribs=null, $buildTypes=null);

  /**
   * @see PersistenceMapper::loadRelation()
   */
  abstract protected function loadRelationImpl(PersistentObject $object, $role, $buildDepth=BuildDepth::SINGLE, $criteria=null, $orderby=null,
    PagingInfo $pagingInfo=null, $buildAttribs=null, $buildTypes=null);
}
?>
