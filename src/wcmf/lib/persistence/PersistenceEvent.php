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
namespace wcmf\lib\persistence;

use wcmf\lib\core\Event;
use wcmf\lib\persistence\PersistentObject;

/**
 * PersistentEvent signals create/update/delete operations
 * on a persistent entity.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PersistenceEvent extends Event {

  const NAME = __CLASS__;

  private $object = null;
  private $action = null;
  private $oldOid = null;

  /**
   * Constructor.
   * @param $object PersistentObject instance.
   * @param $action One of the PersistenceAction values.
   * @param $oldOid The object id of the object before the action was performed (optional)
   */
  public function __construct(PersistentObject $object, $action, $oldOid=null) {
    $this->object = $object;
    $this->action = $action;
    $this->oldOid = $oldOid == null ? $object->getOID() : $oldOid;
  }

  /**
   * Get the object involved.
   * @return PersistentObject instance
   */
  public function getObject() {
    return $this->object;
  }

  /**
   * Get the action.
   * @return PersistenceAction value
   */
  public function getAction() {
    return $this->action;
  }

  /**
   * Get the old object id.
   * @return ObjectId
   */
  public function getOldOid() {
    return $this->oldOid;
  }
}
?>
