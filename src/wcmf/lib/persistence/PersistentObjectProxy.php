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

use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistenceException;
use wcmf\lib\persistence\PersistentObject;

/**
 * PersistentObjectProxy is proxy for an PersistentObject instance.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PersistentObjectProxy implements PersistentObject {

  protected $_oid = null;                // object identifier
  protected $_realSubject = null;        // the PersistentObject instance

  /**
   * Constructor.
   * @param $oid The object id of the PersistentObject instance.
   */
  public function __construct(ObjectId $oid) {
    $this->_oid = $oid;
  }

  /**
   * Create a PersistenceProxy instance from a PersistentObject. This is useful
   * if you want to prevent automatic loading of the subject if it is already loaded.
   * Returns the argument, if already an PersistentObjectProxy instance.
   * @param $object The PersistentObject or PersistentObjectProxy
   * @return PersistentObjectProxy
   */
  public static function fromObject($object) {
    if ($object instanceof PersistentObjectProxy) {
      return $object;
    }
    else if ($object instanceof PersistentObject) {
      $proxy = new PersistentObjectProxy($object->getOID());
      $proxy->_realSubject = $object;
      return $proxy;
    }
    else {
      throw new IllegalArgumentException("Cannot create proxy from unknown object");
    }
  }

  /**
   * Get the PersistentObject instance.
   * @return PersistentObject
   */
  public function getRealSubject() {
    if ($this->_realSubject == null) {
      $this->resolve();
    }
    return $this->_realSubject;
  }

  /**
   * Delegate method call to the instance.
   */
  public function __call($name, array $arguments) {
    if ($this->_realSubject == null) {
      $this->resolve();
    }
    return call_user_func_array(array($this->_realSubject, $name), $arguments);
  }

  /**
   * Load the PersistentObject instance. Use this method if the subject should be loaded
   * with a depth greater than BuildDepth::SINGLE
   * @param $buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        (default: _BuildDepth::SINGLE_)
   */
  public function resolve($buildDepth=BuildDepth::SINGLE) {
    $this->_realSubject = ObjectFactory::getInstance('persistenceFacade')->load($this->_oid, $buildDepth);
    if ($this->_realSubject == null) {
      throw new PersistenceException("Could not resolve oid: ".$this->_oid);
    }
  }

  /**
   * @see PersistentObject::initialize()
   */
  public function initialize(array $data) {
    return $this->__call(__FUNCTION__, $data);
  }

  /**
   * Get the type of the PersistentObject.
   * @return String
   */
  public function getType() {
    return $this->_oid->getType();
  }

  /**
   * @see PersistentObject::getMapper()
   */
  public function getMapper() {
    return $this->__call(__FUNCTION__, array());
  }

  /**
   * Get the object id of the PersistentObject.
   * @return ObjectId
   */
  public function getOID() {
    return $this->_oid;
  }

  /**
   * @see PersistentObject::setOID()
   */
  public function setOID(ObjectId $oid) {
    return $this->__call(__FUNCTION__, array($oid));
  }

  /**
   * @see PersistentObject::getState()
   */
  public function getState() {
    return $this->__call(__FUNCTION__, array());
  }

  /**
   * @see PersistentObject::setState()
   */
  public function setState($state) {
    return $this->__call(__FUNCTION__, array($state));
  }

  /**
   * @see PersistentObject::delete()
   */
  public function delete() {
    return $this->__call(__FUNCTION__, array());
  }

  /**
   * @see PersistentObject::__clone()
   */
  public function __clone() {
    return $this->__call(__FUNCTION__, array());
  }

  /**
   * @see PersistentObject::copyValues()
   */
  public function copyValues(PersistentObject $object, $copyPkValues=true) {
    return $this->__call(__FUNCTION__, array($object, $copyPkValues));
  }

  /**
   * @see PersistentObject::mergeValues()
   */
  public function mergeValues(PersistentObject $object) {
    return $this->__call(__FUNCTION__, array($object));
  }

  /**
   * @see PersistentObject::clearValues()
   */
  public function clearValues() {
    return $this->__call(__FUNCTION__, array());
  }

  /**
   * @see PersistentObject::afterCreate()
   */
  public function afterCreate() {
    return $this->__call(__FUNCTION__, array());
  }

  /**
   * @see PersistentObject::beforeInsert()
   */
  public function beforeInsert() {
    return $this->__call(__FUNCTION__, array());
  }

  /**
   * @see PersistentObject::afterInsert()
   */
  public function afterInsert() {
    return $this->__call(__FUNCTION__, array());
  }

  /**
   * @see PersistentObject::afterLoad()
   */
  public function afterLoad() {
    return $this->__call(__FUNCTION__, array());
  }

  /**
   * @see PersistentObject::beforeUpdate()
   */
  public function beforeUpdate() {
    return $this->__call(__FUNCTION__, array());
  }

  /**
   * @see PersistentObject::afterUpdate()
   */
  public function afterUpdate() {
    return $this->__call(__FUNCTION__, array());
  }

  /**
   * @see PersistentObject::beforeDelete()
   */
  public function beforeDelete() {
    return $this->__call(__FUNCTION__, array());
  }

  /**
   * @see PersistentObject::afterDelete()
   */
  public function afterDelete() {
    return $this->__call(__FUNCTION__, array());
  }

  /**
   * Get the value of a named item.
   * @param $name The name of the item to query.
   * @return The value of the item / null if it doesn't exits.
   */
  public function getValue($name) {
    // return pk values as they are parts of the oid
    $mapper = ObjectFactory::getInstance('persistenceFacade')->getMapper($this->getType());
    $pkNames = $mapper->getPkNames();
    for ($i=0, $count=sizeof($pkNames); $i<$count; $i++) {
      if ($name == $pkNames[$i]) {
        $ids = $this->_oid->getId();
        return $ids[$i];
      }
    }
    return $this->__call(__FUNCTION__, array($name));
  }

  /**
   * @see PersistentObject::setValue()
   */
  public function setValue($name, $value, $forceSet=false, $trackChange=true) {
    return $this->__call(__FUNCTION__, array($name, $value, $forceSet, $trackChange));
  }

  /**
   * @see PersistentObject::hasValue()
   */
  public function hasValue($name) {
    return $this->__call(__FUNCTION__, array($name));
  }

  /**
   * @see PersistentObject::removeValue()
   */
  public function removeValue($name) {
    return $this->__call(__FUNCTION__, array($name));
  }

  /**
   * @see PersistentObject::validateValues()
   */
  public function validateValues() {
    return $this->__call(__FUNCTION__, array());
  }

  /**
   * @see PersistentObject::validateValue()
   */
  public function validateValue($name, $value) {
    return $this->__call(__FUNCTION__, array($name, $value));
  }

  /**
   * @see PersistentObject::getChangedValues()
   */
  public function getChangedValues() {
    return $this->__call(__FUNCTION__, array());
  }

  /**
   * @see PersistentObject::getOriginalValues()
   */
  public function getOriginalValues() {
    return $this->__call(__FUNCTION__, array());
  }

  /**
   * @see PersistentObject::getIndispensableObjects()
   */
  public function getIndispensableObjects() {
    return $this->__call(__FUNCTION__, array());
  }

  /**
   * @see PersistentObject::getProperty()
   */
  public function getProperty($name) {
    return $this->__call(__FUNCTION__, array($name));
  }

  /**
   * @see PersistentObject::setProperty()
   */
  public function setProperty($name, $value) {
    return $this->__call(__FUNCTION__, array($name, $value));
  }

  /**
   * @see PersistentObject::getPropertyNames()
   */
  public function getPropertyNames() {
    return $this->__call(__FUNCTION__, array());
  }

  /**
   * @see PersistentObject::getValueProperty()
   */
  public function getValueProperty($name, $property) {
    return $this->__call(__FUNCTION__, array($name, $property));
  }

  /**
   * @see PersistentObject::setValueProperty()
   */
  public function setValueProperty($name, $property, $value) {
    return $this->__call(__FUNCTION__, array($name, $property, $value));
  }

  /**
   * @see PersistentObject::getValuePropertyNames()
   */
  public function getValuePropertyNames($name) {
    return $this->__call(__FUNCTION__, array($name));
  }

  /**
   * @see PersistentObject::getValueNames()
   */
  public function getValueNames($excludeTransient=false) {
    return $this->__call(__FUNCTION__, array($excludeTransient));
  }

  /**
   * @see PersistentObject::getDisplayValue()
   * @note Subclasses will override this for special application requirements
   */
  public function getDisplayValue() {
    return $this->getOID()->__toString();
  }

  /**
   * @see PersistentObject::getObjectDisplayName()
   * @note Subclasses will override this for special application requirements
   */
  public function getObjectDisplayName() {
    return Message::get($this->getType());
  }

  /**
   * @see PersistentObject::getObjectDescription()
   * @note Subclasses will override this for special application requirements
   */
  public function getObjectDescription() {
    return Message::get($this->getType());
  }

  /**
   * @see PersistentObject::getValueDisplayName()
   * @note Subclasses will override this for special application requirements
   */
  public function getValueDisplayName($name) {
    return Message::get($name);
  }

  /**
   * @see PersistentObject::getValueDescription()
   * @note Subclasses will override this for special application requirements
   */
  public function getValueDescription($name) {
    return Message::get($name);
  }

  /**
   * @see PersistentObject::dump()
   */
  public function dump() {
    return $this->__call(__FUNCTION__, array());
  }

  /**
   * Get a string representation of the instance.
   * @return String
   */
  function __toString() {
    return 'Proxy_'.$this->_oid->__toString();
  }
}
?>