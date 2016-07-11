<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2016 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\persistence\impl;

use wcmf\lib\core\ErrorHandler;
use wcmf\lib\core\EventManager;
use wcmf\lib\core\LogManager;
use wcmf\lib\i18n\Message;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\concurrency\ConcurrencyManager;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\output\OutputStrategy;
use wcmf\lib\persistence\PagingInfo;
use wcmf\lib\persistence\PersistenceAction;
use wcmf\lib\persistence\PersistenceEvent;
use wcmf\lib\persistence\PersistenceFacade;
use wcmf\lib\persistence\PersistenceMapper;
use wcmf\lib\persistence\PersistentObject;
use wcmf\lib\security\AuthorizationException;
use wcmf\lib\security\PermissionManager;

/**
 * AbstractMapper provides a basic implementation for other mapper classes.
 * It handles authorization on entity level and calls the lifecycle callcacks
 * of PersistentObject instances. Authorization on attribute level has to be
 * implemented by subclasses.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class AbstractMapper implements PersistenceMapper {

  private $logStrategy = null;

  private $attributeNames = array();
  private $relationNames = array();

  private static $logger = null;

  protected $persistenceFacade = null;
  protected $permissionManager = null;
  protected $concurrencyManager = null;
  protected $eventManager = null;
  protected $message = null;

  /**
   * Constructor
   * @param $persistenceFacade
   * @param $permissionManager
   * @param $concurrencyManager
   * @param $eventManager
   * @param $message
   */
  public function __construct(PersistenceFacade $persistenceFacade,
          PermissionManager $permissionManager,
          ConcurrencyManager $concurrencyManager,
          EventManager $eventManager,
          Message $message) {
    if (self::$logger == null) {
      self::$logger = LogManager::getLogger(__CLASS__);
    }
    $this->persistenceFacade = $persistenceFacade;
    $this->permissionManager = $permissionManager;
    $this->concurrencyManager = $concurrencyManager;
    $this->eventManager = $eventManager;
    $this->message = $message;
  }

  /**
   * Set the OutputStrategy used for logging persistence actions.
   * @param $logStrategy
   */
  public function setLogStrategy(OutputStrategy $logStrategy) {
    $this->logStrategy = $logStrategy;
  }

  /**
   * @see PersistenceMapper::hasRelation()
   */
  public function hasRelation($roleName) {
    if (isset($this->relationNames[$roleName])) {
      return true;
    }
    $relations = $this->getRelations();
    foreach ($relations as $relation) {
      if ($relation->getOtherRole() == $roleName) {
        $this->relationNames[$roleName] = true;
        return true;
      }
    }
    return false;
  }

  /**
   * @see PersistenceMapper::hasAttribute()
   */
  public function hasAttribute($name) {
    if (isset($this->attributeNames[$name])) {
      return true;
    }
    $attributes = $this->getAttributes();
    foreach ($attributes as $attribute) {
      if ($attribute->getName() === $name) {
        $this->attributeNames[$name] = true;
        return true;
      }
    }
    return false;
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
    if ($isDirty) {
      // check permissions for changed attributes, because this
      // also includes instance and type checks
      $oidStr = $object->getOID()->__toString();
      foreach ($object->getChangedValues() as $valueName) {
        $resource = $oidStr.'.'.$valueName;
        if (!$this->checkAuthorization($resource, PersistenceAction::UPDATE)) {
          $this->authorizationFailedError($resource, PersistenceAction::UPDATE);
          return;
        }
      }
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
    $object->validateValues($this->message);

    // check concurrency
    $this->concurrencyManager->checkPersist($object);

    // save object
    $this->saveImpl($object);

    // update lock
    $this->concurrencyManager->updateLock($oid, $object);

    // call lifecycle callback
    if ($isDirty) {
      $object->afterUpdate();
      $this->eventManager->dispatch(PersistenceEvent::NAME,
              new PersistenceEvent($object, PersistenceAction::UPDATE));
    }
    elseif ($isNew) {
      $object->afterInsert();
      $this->eventManager->dispatch(PersistenceEvent::NAME,
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
    $this->concurrencyManager->checkPersist($object);

    // delete object
    $result = $this->deleteImpl($object);
    if ($result === true) {
      // call lifecycle callback
      $object->afterDelete();
      $this->eventManager->dispatch(PersistenceEvent::NAME,
              new PersistenceEvent($object, PersistenceAction::DELETE));

      // release any locks on the object
      $this->concurrencyManager->releaseLocks($oid);
    }
    return $result;
  }

  /**
   * @see PersistenceMapper::getOIDs()
   */
  public function getOIDs($type, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null) {
    $oids = $this->getOIDsImpl($type, $criteria, $orderby, $pagingInfo);
    $tx = $this->persistenceFacade->getTransaction();

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
    $tx = $this->persistenceFacade->getTransaction();

    // remove objects for which the user is not authorized
    $result = array();
    for ($i=0, $count=sizeof($objects); $i<$count; $i++) {
      $object = $objects[$i];
      if ($this->checkAuthorization($object->getOID(), PersistenceAction::READ)) {
        // call lifecycle callback
        $object->afterLoad();
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
    $tx = $this->persistenceFacade->getTransaction();

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
   * Log the state of the given object
   * @param $obj PersistentObject instance
   */
  protected function logAction(PersistentObject $obj) {
    if ($this->logStrategy) {
      $this->logStrategy->writeObject($obj);
    }
  }

  /**
   * Check authorization on a resource (type/instance/instance property) and a given action.
   * @param $resource Resource to authorize
   * @param $action Action to authorize
   * @return Boolean depending on success of authorization
   */
  protected function checkAuthorization($resource, $action) {
    return $this->permissionManager->authorize($resource, '', $action);
  }

  /**
   * Handle an authorization error.
   * @param $resource
   * @param $action
   * @throws AuthorizationException
   */
  protected function authorizationFailedError($resource, $action) {
    // when reading only log the error to avoid errors on the display
    $msg = $this->message->getText("Authorization failed for action '%0%' on '%1%'.", array($action, $resource));
    if ($action == PersistenceAction::READ) {
      self::$logger->error($msg."\n".ErrorHandler::getStackTrace());
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
