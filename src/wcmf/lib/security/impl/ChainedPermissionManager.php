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

use wcmf\lib\security\PermissionManager;

/**
 * ChainedPermissionManager retrieves authorization rules included managers.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ChainedPermissionManager extends AbstractPermissionManager implements PermissionManager {

  private $_managers = array();

  /**
   * Set the PermissionManager instances to delegate to.
   * @param $managers Array of PermissionManager instances
   */
  public function setManagers($managers) {
    $this->_managers = $managers;
  }

  /**
   * @see PermissionManager::getPermissions()
   */
  public function getPermissions($resource, $context, $action) {
    foreach ($this->_managers as $manager) {
      $permissions = $manager->getPermissions($resource, $context, $action);
      if ($permissions != null) {
        return $permissions;
      }
    }
    return null;
  }

  /**
   * @see PermissionManager::createPermission()
   */
  public function createPermission($resource, $context, $action, $role, $modifier) {}

  /**
   * @see PermissionManager::removePermission()
   */
  public function removePermission($resource, $context, $action, $role) {}
}
?>
