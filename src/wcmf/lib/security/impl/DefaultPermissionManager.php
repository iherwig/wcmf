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
 * DefaultPermissionManager retrieves authorization rules the storage.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultPermissionManager extends AbstractPermissionManager implements PermissionManager {

  /**
   * @see PermissionManager::getPermissions()
   */
  public function getPermissions($resource, $context, $action) {
    // TODO
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
