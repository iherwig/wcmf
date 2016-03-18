<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2015 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\persistence\concurrency;

/**
 * Lock represents a lock on an object.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class Lock implements \Serializable {

  const TYPE_OPTIMISTIC = 'optimistic';
  const TYPE_PESSIMISTIC = 'pessimistic'; // pessimistic write lock

  private $_objectId = null;
  private $_login = "";
  private $_created = "";
  private $_currentState = null;

  /**
   * Creates a lock on a given object.
   * @param $type One of the Lock::Type constants
   * @param $oid ObjectId of the object to lock
   * @param $login Login name of the user who holds the lock
   * @param $created Creation date of the lock. If omitted the current date will be taken.
   */
  public function __construct($type, $oid, $login, $created='') {
    $this->_type = $type;
    $this->_objectId = $oid;
    $this->_login = $login;
    if ($created == '') {
      $this->_created = date("Y-m-d H:i:s");
    }
    else {
      $this->_created = $created;
    }
  }

  /**
   * Get the type of the lock.
   * @return One of the Lock::Type constants.
   */
  public function getType() {
    return $this->_type;
  }

  /**
   * Get the oid of the locked object.
   * @return ObjectId of the locked object.
   */
  public function getObjectId() {
    return $this->_objectId;
  }

  /**
   * Get the login of the user who holds the lock.
   * @return The login of the user.
   */
  public function getLogin() {
    return $this->_login;
  }

  /**
   * Get the creation date/time of the lock.
   * @return The creation date/time of the lock.
   */
  public function getCreated() {
    return $this->_created;
  }

  /**
   * Set the original state of the object in case of an
   * optimistic lock.
   * @param $currentState PersistentObject instance or null
   */
  public function setCurrentState($currentState) {
    $this->_currentState = serialize($currentState);
  }

  /**
   * Get the original state of the object in case of an
   * optimistic lock.
   * @return PersistentObject instance or null
   */
  public function getCurrentState() {
    return unserialize($this->_currentState);
  }

  public function serialize() {
    return serialize(array(serialize($this->_objectId),
        $this->_login, $this->_created, serialize($this->_currentState)));
  }

  public function unserialize($data) {
    $parts = unserialize($data);
    $this->_objectId = unserialize($parts[0]);
    $this->_login = $parts[1];
    $this->_created = $parts[2];
    $this->_currentState = unserialize($parts[3]);
  }
}
?>
