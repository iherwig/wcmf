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
class PersistentObjectProxy {

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
   * Get the object id of the PersistentObject.
   * @return ObjectId
   */
  public function getOID() {
    return $this->_oid;
  }

  /**
   * Get the type of the PersistentObject.
   * @return String
   */
  public function getType() {
    return $this->_oid->getType();
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
    return $this->__call('getValue', array($name));
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
   *        [default: BuildDepth::SINGLE)]
   */
  public function resolve($buildDepth=BuildDepth::SINGLE) {
    $this->_realSubject = ObjectFactory::getInstance('persistenceFacade')->load($this->_oid, $buildDepth);
    if ($this->_realSubject == null) {
      throw new PersistenceException("Could not resolve oid: ".$this->_oid);
    }
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