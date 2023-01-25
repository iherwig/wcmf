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
namespace wcmf\lib\persistence\impl;

use wcmf\lib\core\ErrorHandler;
use wcmf\lib\core\EventManager;
use wcmf\lib\core\LogTrait;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\i18n\Message;
use wcmf\lib\persistence\AttributeDescription;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\concurrency\ConcurrencyManager;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\output\OutputStrategy;
use wcmf\lib\persistence\PagingInfo;
use wcmf\lib\persistence\PersistenceAction;
use wcmf\lib\persistence\PersistenceEvent;
use wcmf\lib\persistence\PersistenceException;
use wcmf\lib\persistence\PersistenceFacade;
use wcmf\lib\persistence\PersistenceMapper;
use wcmf\lib\persistence\PersistentObject;
use wcmf\lib\persistence\ReferenceDescription;
use wcmf\lib\persistence\RelationDescription;
use wcmf\lib\security\AuthorizationException;
use wcmf\lib\security\PermissionManager;

/**
 * AbstractMapper provides a basic implementation for other mapper classes.
 * It handles authorization on entity level and calls the lifecycle callbacks
 * of PersistentObject instances. Authorization on attribute level has to be
 * implemented by subclasses.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class AbstractMapper implements PersistenceMapper {
  use LogTrait;

  private ?OutputStrategy $logStrategy = null;

  private array $attributes = [];
  private array $relations = [];

  protected PersistenceFacade $persistenceFacade;
  protected PermissionManager $permissionManager;
  protected ConcurrencyManager $concurrencyManager;
  protected EventManager $eventManager;

  /**
   * Constructor
   * @param $persistenceFacade
   * @param $permissionManager
   * @param $concurrencyManager
   * @param $eventManager
   */
  public function __construct(PersistenceFacade $persistenceFacade,
          PermissionManager $permissionManager,
          ConcurrencyManager $concurrencyManager,
          EventManager $eventManager) {
    $this->persistenceFacade = $persistenceFacade;
    $this->permissionManager = $permissionManager;
    $this->concurrencyManager = $concurrencyManager;
    $this->eventManager = $eventManager;
  }

  /**
   * @see PersistenceMapper::setLogStrategy()
   */
  public function setLogStrategy(OutputStrategy $logStrategy): void {
    $this->logStrategy = $logStrategy;
  }

  /**
   * @see PersistenceMapper::getTypeDisplayName()
   */
  public function getTypeDisplayName(Message $message): string {
    return $message->getText($this->getType());
  }

  /**
   * @see PersistenceMapper::getTypeDescription()
   */
  public function getTypeDescription(Message $message): string {
    return $message->getText("");
  }

  /**
   * @see PersistenceMapper::hasRelation()
   */
  public function hasRelation(string $roleName): bool {
    $this->initRelations();
    return isset($this->relations['byrole'][$roleName]);
  }

  /**
   * @see PersistenceMapper::getRelation()
   */
  public function getRelation(string $roleName): ?RelationDescription {
    if ($this->hasRelation($roleName)) {
      return $this->relations['byrole'][$roleName];
    }
    throw new PersistenceException("No relation to '".$roleName."' exists in '".$this->getType()."'");
  }

  /**
   * @see PersistenceMapper::getRelationsByType()
   */
  public function getRelationsByType(string $type): array {
    $this->initRelations();
    if (isset($this->relations['bytype'][$type])) {
      return $this->relations['bytype'][$type];
    }
    throw new PersistenceException("No relation to '".$type."' exists in '".$this->getType()."'");
  }

  /**
   * @see PersistenceMapper::hasAttribute()
   */
  public function hasAttribute(string $name): bool {
    $this->initAttributes();
    return isset($this->attributes['byname'][$name]);
  }

  /**
   * @see PersistenceMapper::getAttribute()
   */
  public function getAttribute(string $name): AttributeDescription {
    if ($this->hasAttribute($name)) {
      return $this->attributes['byname'][$name];
    }
    throw new PersistenceException("No attribute '".$name."' exists in '".$this->getType()."'");
  }

  /**
   * @see PersistenceMapper::getReferences()
   */
  public function getReferences(): array {
    $this->initAttributes();
    return $this->attributes['refs'];
  }

  /**
   * @see PersistenceMapper::getAttributeDisplayName()
   */
  public function getAttributeDisplayName(string $name, Message $message): string {
    return $message->getText($name);
  }

  /**
   * @see PersistenceMapper::getAttributeDescription()
   */
  public function getAttributeDescription(string $name, Message $message): string {
    return $message->getText("");
  }

  /**
   * @see PersistenceMapper::getProperties()
   */
  public function getProperties(): array {
    return [];
  }

  /**
   * @see PersistenceMapper::load()
   */
  public function load(ObjectId $oid, ?int $buildDepth=BuildDepth::SINGLE): ?PersistentObject {
    if (!$this->checkAuthorization($oid, PersistenceAction::READ)) {
      $this->authorizationFailedError($oid, PersistenceAction::READ);
      return null;
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
  public function create(string $type, ?int $buildDepth=BuildDepth::SINGLE): PersistentObject {
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
  public function save(PersistentObject $object): void {
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
    $object->validateValues();

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
              new PersistenceEvent($object, PersistenceAction::UPDATE, $oid));
    }
    elseif ($isNew) {
      $object->afterInsert();
      $this->eventManager->dispatch(PersistenceEvent::NAME,
              new PersistenceEvent($object, PersistenceAction::CREATE, $oid));
    }
  }

  /**
   * @see PersistenceMapper::delete()
   */
  public function delete(PersistentObject $object): bool {
    $oid = $object->getOID();
    if (!$this->checkAuthorization($oid, PersistenceAction::DELETE)) {
      $this->authorizationFailedError($oid, PersistenceAction::DELETE);
      return false;
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
            new PersistenceEvent($object, PersistenceAction::DELETE, $oid));

      // release any locks on the object
      $this->concurrencyManager->releaseLocks($oid);
    }
    return $result;
  }

  /**
   * @see PersistenceMapper::getOIDs()
   */
  public function getOIDs(string $type, ?array $criteria=null, ?array $orderby=null, ?PagingInfo $pagingInfo=null): array {
    $oids = $this->getOIDsImpl($type, $criteria, $orderby, $pagingInfo);
    $tx = $this->persistenceFacade->getTransaction();

    // remove oids for which the user is not authorized
    $result = [];
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
  public function loadObjects($typeOrTypes, int $buildDepth=BuildDepth::SINGLE, array $criteria=null, array $orderby=null, PagingInfo $pagingInfo=null): array {
    $objects = $this->loadObjectsImpl($typeOrTypes, $buildDepth, $criteria, $orderby, $pagingInfo);
    $tx = $this->persistenceFacade->getTransaction();

    // remove objects for which the user is not authorized
    $result = [];
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
  public function loadRelation(array $objects, string $role, int $buildDepth=BuildDepth::SINGLE, ?array $criteria=null, ?array $orderby=null,
    ?PagingInfo $pagingInfo=null): array {
    $relatedObjects = $this->loadRelationImpl($objects, $role, $buildDepth, $criteria, $orderby, $pagingInfo);
    $tx = $this->persistenceFacade->getTransaction();

    // remove objects for which the user is not authorized
    if ($relatedObjects != null) {
      $result = [];
      foreach ($relatedObjects as $oidStr => $curObjects) {
        $curResult = [];
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
  protected function logAction(PersistentObject $obj): void {
    if ($this->logStrategy) {
      $this->logStrategy->writeObject($obj);
    }
  }

  /**
   * Check authorization on a resource (type/instance/instance property) and a given action.
   * @param $resource Resource to authorize
   * @param $action Action to authorize
   * @return bool depending on success of authorization
   */
  protected function checkAuthorization(string $resource, string $action): bool {
    return $this->permissionManager->authorize($resource, '', $action);
  }

  /**
   * Handle an authorization error.
   * @param $resource
   * @param $action
   * @throws AuthorizationException
   */
  protected function authorizationFailedError(string $resource, string $action): void {
    // when reading only log the error to avoid errors on the display
    $msg = ObjectFactory::getInstance('message')->
            getText("Authorization failed for action '%0%' on '%1%'.", [$action, $resource]);
    if ($action == PersistenceAction::READ) {
      self::logger()->error($msg."\n".ErrorHandler::getStackTrace());
    }
    else {
      throw new AuthorizationException($msg);
    }
  }

  /**
   * Initialize relations.
   */
  private function initRelations(): void {
    if ($this->relations == null) {
      $this->relations = [];
      $this->relations['byrole'] = [];
      $this->relations['bytype'] = [];

      $relations = $this->getRelations();
      foreach ($relations as $relation) {
        $this->relations['byrole'][$relation->getOtherRole()] = $relation;
        $otherType = $relation->getOtherType();
        if (!isset($this->relations['bytype'][$otherType])) {
          $this->relations['bytype'][$otherType] = [];
        }
        $this->relations['bytype'][$otherType][] = $relation;
        $this->relations['bytype'][$this->persistenceFacade->getSimpleType($otherType)][] = $relation;
      }
    }
  }

  /**
   * Initialize attributes.
   */
  private function initAttributes(): void {
    if ($this->attributes == null) {
      $this->attributes = [];
      $this->attributes['byname'] = [];
      $this->attributes['refs'] = [];

      $attributes = $this->getAttributes();
      foreach ($attributes as $attribute) {
        $this->attributes['byname'][$attribute->getName()] = $attribute;
        if ($attribute instanceof ReferenceDescription) {
          $this->attributes['refs'][] = $attribute;
        }
      }
    }
  }

  /**
   * @see PersistenceFacade::load()
   * @note Precondition: Object rights have been checked already
   */
  abstract protected function loadImpl(ObjectId $oid, int $buildDepth=BuildDepth::SINGLE): ?PersistentObject;

  /**
   * @see PersistenceFacade::create()
   * @note Precondition: Object rights have been checked already
   */
  abstract protected function createImpl(string $type, int $buildDepth=BuildDepth::SINGLE): PersistentObject;

  /**
   * @see PersistenceMapper::save()
   * @note Precondition: Object rights have been checked already
   */
  abstract protected function saveImpl(PersistentObject $object): void;

  /**
   * @see PersistenceMapper::delete()
   * @note Precondition: Object rights have been checked already
   */
  abstract protected function deleteImpl(PersistentObject $object): bool;

  /**
   * @see PersistenceMapper::getOIDs()
   */
  abstract protected function getOIDsImpl(string $type, ?array $criteria=null, ?array $orderby=null, ?PagingInfo $pagingInfo=null): array;

  /**
   * @see PersistenceMapper::loadObjects()
   */
  abstract protected function loadObjectsImpl(string $type, int $buildDepth=BuildDepth::SINGLE, ?array $criteria=null, ?array $orderby=null,
    ?PagingInfo $pagingInfo=null): array;

  /**
   * @see PersistenceMapper::loadRelation()
   */
  abstract protected function loadRelationImpl(array $objects, string $role, int $buildDepth=BuildDepth::SINGLE, ?array $criteria=null,
  ?array $orderby=null, ?PagingInfo $pagingInfo=null): array;
}
?>
