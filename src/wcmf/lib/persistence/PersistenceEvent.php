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

  private $_object = null;
  private $_action = null;

  /**
   * Constructor.
   * @param $object A reference to the involved PersistentObject instance.
   * @param $action One of the PersistenceAction values.
   */
  public function __construct(PersistentObject $object, $action) {
    $this->_object = $object;
    $this->_action = $action;
  }

  /**
   * Get the object involved.
   * @return PersistentObject instance
   */
  public function getObject() {
    return $this->_object;
  }

  /**
   * Get the action.
   * @return PersistenceAction value
   */
  public function getAction() {
    return $this->_action;
  }
}
?>
