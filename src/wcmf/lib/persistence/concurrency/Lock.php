<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2017 wemove digital solutions GmbH
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

  private $type = null;
  private $objectId = null;
  private $login = "";
  private $created = "";
  private $currentState = null;

  /**
   * Creates a lock on a given object.
   * @param $type One of the Lock::Type constants
   * @param $oid ObjectId of the object to lock
   * @param $login Login name of the user who holds the lock
   * @param $created Creation date of the lock. If omitted the current date will be taken.
   */
  public function __construct($type, $oid, $login, $created='') {
    $this->type = $type;
    $this->objectId = $oid;
    $this->login = $login;
    if ($created == '') {
      $this->created = date("Y-m-d H:i:s");
    }
    else {
      $this->created = $created;
    }
  }

  /**
   * Get the type of the lock.
   * @return One of the Lock::Type constants.
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Get the oid of the locked object.
   * @return ObjectId of the locked object.
   */
  public function getObjectId() {
    return $this->objectId;
  }

  /**
   * Get the login of the user who holds the lock.
   * @return The login of the user.
   */
  public function getLogin() {
    return $this->login;
  }

  /**
   * Get the creation date/time of the lock.
   * @return The creation date/time of the lock.
   */
  public function getCreated() {
    return $this->created;
  }

  /**
   * Set the original state of the object in case of an
   * optimistic lock.
   * @param $currentState PersistentObject instance or null
   */
  public function setCurrentState($currentState) {
    $this->currentState = serialize($currentState);
  }

  /**
   * Get the original state of the object in case of an
   * optimistic lock.
   * @return PersistentObject instance or null
   */
  public function getCurrentState() {
    return unserialize($this->currentState);
  }

  public function serialize() {
    return serialize([$this->type, serialize($this->objectId),
        $this->login, $this->created, serialize($this->currentState)]);
  }

  public function unserialize($data) {
    $parts = unserialize($data);
    $this->type = $parts[0];
    $this->objectId = unserialize($parts[1]);
    $this->login = $parts[2];
    $this->created = $parts[3];
    $this->currentState = unserialize($parts[4]);
  }
}
?>
