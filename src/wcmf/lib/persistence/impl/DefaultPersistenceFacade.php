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

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\core\EventManager;
use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\impl\DefaultUnionQueryProvider;
use wcmf\lib\persistence\ObjectComparator;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\output\OutputStrategy;
use wcmf\lib\persistence\PagingInfo;
use wcmf\lib\persistence\PersistenceFacade;
use wcmf\lib\persistence\PersistenceMapper;
use wcmf\lib\persistence\PersistentObject;
use wcmf\lib\persistence\StateChangeEvent;
use wcmf\lib\persistence\UnionQuery;
use wcmf\lib\persistence\UnionQueryProvider;

/**
 * Default PersistenceFacade implementation.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultPersistenceFacade implements PersistenceFacade {

  private $mappers = [];
  private $simpleToFqNames = [];
  private $createdOIDs = [];
  private $eventManager = null;
  private $currentTransaction = null;
  private $logStrategy = null;

  /**
   * Constructor
   * @param $eventManager
   * @param $logStrategy OutputStrategy used for logging persistence actions.
   */
  public function __construct(EventManager $eventManager,
    OutputStrategy $logStrategy) {
      $this->eventManager = $eventManager;
      $this->logStrategy = $logStrategy;
      // register as change listener to track the created oids, after save
      $this->eventManager->addListener(StateChangeEvent::NAME,
        [$this, 'stateChanged']);
  }

  /**
   * Destructor
   */
  public function __destruct() {
    $this->eventManager->removeListener(StateChangeEvent::NAME,
      [$this, 'stateChanged']);
  }

  /**
   * Set the PersistentMapper instances.
   * @param $mappers Associative array with the fully qualified
   *   mapped class names as keys and the mapper instances as values
   */
  public function setMappers($mappers) {
    $this->mappers = $mappers;
    foreach ($mappers as $fqName => $mapper) {
      // register simple type names
      $name = $this->calculateSimpleType($fqName);
      if (!isset($this->mappers[$name])) {
        $this->mappers[$name] = $mapper;
        if (!isset($this->simpleToFqNames[$name])) {
          $this->simpleToFqNames[$name] = $fqName;
        }
        else {
          // if the simple type name already exists, we remove
          // it in order to prevent collisions with the new type
          unset($this->simpleToFqNames[$name]);
        }
      }
      // set logging strategy
      $mapper->setLogStrategy($this->logStrategy);
    }
  }

  /**
   * @see PersistenceFacade::getKnownTypes()
   */
  public function getKnownTypes() {
    return array_values($this->simpleToFqNames);
  }

  /**
   * @see PersistenceFacade::isKnownType()
   */
  public function isKnownType($type) {
    return (isset($this->mappers[$type]));
  }

  /**
   * @see PersistenceFacade::getFullyQualifiedType()
   */
  public function getFullyQualifiedType($type) {
    if (isset($this->simpleToFqNames[$type])) {
      return $this->simpleToFqNames[$type];
    }
    if ($this->isKnownType($type)) {
      return $type;
    }
    throw new ConfigurationException("Type '".$type."' is unknown.");
  }

  /**
   * @see PersistenceFacade::getSimpleType()
   */
  public function getSimpleType($type) {
    $simpleType = $this->calculateSimpleType($type);
    // if there is a entry for the type name but not for the simple type name,
    // the type is ambiquous and we return the type name
    return (isset($this->mappers[$type]) && !isset($this->simpleToFqNames[$simpleType])) ?
    $type : $simpleType;
  }

  /**
   * @see PersistenceFacade::load()
   */
  public function load(ObjectId $oid, $buildDepth=BuildDepth::SINGLE) {
    if ($buildDepth < 0 && !in_array($buildDepth, [BuildDepth::INFINITE, BuildDepth::SINGLE])) {
      throw new IllegalArgumentException("Build depth not supported: $buildDepth");
    }
    // check if the object is already part of the transaction
    $transaction = $this->getTransaction();
    $obj = $transaction->getLoaded($oid);

    // if not cached or build depth requested, load
    if ($obj == null || $buildDepth != BuildDepth::SINGLE) {
      $mapper = $this->getMapper($oid->getType());
      $obj = $mapper->load($oid, $buildDepth);
    }
    return $obj;
  }

  /**
   * @see PersistenceFacade::create()
   */
  public function create($type, $buildDepth=BuildDepth::SINGLE) {
    if ($buildDepth < 0 && !in_array($buildDepth, [BuildDepth::INFINITE, BuildDepth::SINGLE, BuildDepth::REQUIRED])) {
      throw new IllegalArgumentException("Build depth not supported: $buildDepth");
    }

    $mapper = $this->getMapper($type);
    $obj = $mapper->create($type, $buildDepth);

    // attach the object to the transaction
    $attachedObjec = $this->getTransaction()->attach($obj);

    return $attachedObjec;
  }

  /**
   * @see PersistenceFacade::getLastCreatedOID()
   */
  public function getLastCreatedOID($type) {
    $fqType = $this->getFullyQualifiedType($type);
    if (isset($this->createdOIDs[$fqType]) && sizeof($this->createdOIDs[$fqType]) > 0) {
      return $this->createdOIDs[$fqType][sizeof($this->createdOIDs[$fqType])-1];
    }
    return null;
  }

  /**
   * @see PersistenceFacade::getOIDs()
   */
  public function getOIDs($type, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null) {
    $this->checkArrayParameter($criteria, 'criteria', 'wcmf\lib\persistence\Criteria');
    $this->checkArrayParameter($orderby, 'orderby');

    $mapper = $this->getMapper($type);
    $result = $mapper->getOIDs($type, $criteria, $orderby, $pagingInfo);
    return $result;
  }

  /**
   * @see PersistenceFacade::getFirstOID()
   */
  public function getFirstOID($type, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null) {
    if ($pagingInfo == null) {
      $pagingInfo = new PagingInfo(1, true);
    }
    $oids = $this->getOIDs($type, $criteria, $orderby, $pagingInfo);
    if (sizeof($oids) > 0) {
      return $oids[0];
    }
    else {
      return null;
    }
  }

  /**
   * @see PersistenceFacade::loadObjects()
   */
  public function loadObjects($typeOrTypes, $buildDepth=BuildDepth::SINGLE, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null) {
    $this->checkArrayParameter($criteria, 'criteria', 'wcmf\lib\persistence\Criteria');
    $this->checkArrayParameter($orderby, 'orderby');

    if (!is_array($typeOrTypes)) {
      // single type
      $mapper = $this->getMapper($typeOrTypes);
      $result = $mapper->loadObjects($typeOrTypes, $buildDepth, $criteria, $orderby, $pagingInfo);
    }
    else {
      $queryProvider = new DefaultUnionQueryProvider($typeOrTypes, $criteria);
      $result = UnionQuery::execute($queryProvider, $buildDepth, $orderby, $pagingInfo);
    }
    return $result;
  }

  /**
   * @see PersistenceFacade::loadFirstObject()
   */
  public function loadFirstObject($typeOrTypes, $buildDepth=BuildDepth::SINGLE, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null) {
    if ($pagingInfo == null) {
      $pagingInfo = new PagingInfo(1, true);
    }
    $objects = $this->loadObjects($typeOrTypes, $buildDepth, $criteria, $orderby, $pagingInfo);
    if (sizeof($objects) > 0) {
      return $objects[0];
    }
    else {
      return null;
    }
  }

  /**
   * @see PersistenceFacade::getTransaction()
   */
  public function getTransaction() {
    if ($this->currentTransaction == null) {
      $this->currentTransaction = ObjectFactory::getInstance('transaction');
    }
    return $this->currentTransaction;
  }

  /**
   * @see PersistenceFacade::getMapper()
   */
  public function getMapper($type) {
    if ($this->isKnownType($type)) {
      $mapper = $this->mappers[$type];
      return $mapper;
    }
    else {
      throw new ConfigurationException("No PersistenceMapper found for type '".$type."'");
    }
  }

  /**
   * @see PersistenceFacade::setMapper()
   */
  public function setMapper($type, PersistenceMapper $mapper) {
    $this->mappers[$type] = $mapper;
  }

  /**
   * Check if the given value is either null or an array and
   * throw an exception if not
   * @param $param The parameter
   * @param $paramName The name of the parameter (used in the exception text)
   * @param $className Class name to match if, instances of a specific type are expected (optional)
   */
  private function checkArrayParameter($param, $paramName, $className=null) {
    if ($param == null) {
      return;
    }
    if (!is_array($param)) {
      throw new IllegalArgumentException("The parameter '".$paramName.
        "' is expected to be null or an array");
    }
    if ($className != null) {
      foreach ($param as $instance) {
        if (!($instance instanceof $className)) {
          throw new IllegalArgumentException("The parameter '".$paramName.
            "' is expected to contain only instances of '".$className."'");
        }
      }
    }
  }

  /**
   * Listen to StateChangeEvents
   * @param $event StateChangeEvent instance
   */
  public function stateChanged(StateChangeEvent $event) {
    $oldState = $event->getOldValue();
    $newState = $event->getNewValue();
    // store the object id in the internal registry if the object was saved after creation
    if ($oldState == PersistentObject::STATE_NEW && $newState == PersistentObject::STATE_CLEAN) {
      $object = $event->getObject();
      $type = $object->getType();
      if (!array_key_exists($type, $this->createdOIDs)) {
        $this->createdOIDs[$type] = [];
      }
      $this->createdOIDs[$type][] = $object->getOID();
    }
  }

  /**
   * Calculate the simple type name for a given fully qualified type name.
   * @param $type Type name with namespace
   * @return Simple type name (without namespace)
   */
  protected function calculateSimpleType($type) {
    $pos = strrpos($type, '.');
    if ($pos !== false) {
      return substr($type, $pos+1);
    }
    return $type;
  }
}
?>
