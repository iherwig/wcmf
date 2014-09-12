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
namespace wcmf\lib\security\impl;

use wcmf\lib\config\ActionKey;
use wcmf\lib\config\impl\PersistenceActionKeyProvider;
use wcmf\lib\security\PermissionManager;

/**
 * DefaultPermissionManager retrieves authorization rules the storage.
 * It is configures with an entity type that stores permissions and must
 * have the values 'resource', 'context', 'action', 'roles'.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultPermissionManager extends AbstractPermissionManager implements PermissionManager {

  private $_permissionType = null;
  private $_actionKeyProvider = null;

  /**
   * Constructor
   */
  public function __construct() {
    $this->_actionKeyProvider = new PersistenceActionKeyProvider();
    $this->_actionKeyProvider->setValueMap(array(
        'resource' => 'resource',
        'context' => 'context',
        'action' => 'action',
        'value' => 'roles',
    ));
  }

  /**
   * Set the entity type name of Permission instances.
   * @param $permissionType String
   */
  public function setPermissionType($permissionType) {
    $this->_permissionType = $permissionType;
    $this->_actionKeyProvider->setEntityType($this->_permissionType);
  }

  /**
   * @see PermissionManager::getPermissions()
   */
  public function getPermissions($resource, $context, $action) {
    $actionKey = ActionKey::getBestMatch($this->_actionKeyProvider, $resource, $context, $action);
    if (strlen($actionKey) > 0) {
      return $this->parsePermissions($this->_actionKeyProvider->getKeyValue($actionKey));
    }
    return null;
  }

  /**
   * @see PermissionManager::createPermission()
   */
  public function createPermission($resource, $context, $action, $role, $modifier) {
    // TODO
  }

  /**
   * @see PermissionManager::removePermission()
   */
  public function removePermission($resource, $context, $action, $role) {
    // TODO
  }
}
?>
