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
namespace wcmf\lib\security\impl;

use wcmf\lib\security\PermissionManager;

/**
 * ChainedPermissionManager retrieves authorization rules included managers.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ChainedPermissionManager extends AbstractPermissionManager implements PermissionManager {

  private $managers = [];

  /**
   * Set the PermissionManager instances to delegate to.
   * @param array<PermissionManager> $managers Array of PermissionManager instances
   */
  public function setManagers(array $managers): void {
    $this->managers = $managers;
  }

  /**
   * @see PermissionManager::getPermissions()
   */
  public function getPermissions(string $resource, string $context, string $action): ?array {
    foreach ($this->managers as $manager) {
      $permissions = $manager->getPermissions($resource, $context, $action);
      if ($permissions != null) {
        return $permissions;
      }
    }
    return null;
  }

  /**
   * @see PermissionManager::setPermissions()
   */
  public function setPermissions(string $resource, string $context, string $action, ?array $permissions): void {
    if (sizeof($this->managers) > 0) {
      $this->managers[0]->setPermissions($resource, $context, $action, $permissions);
    }
  }

  /**
   * @see PermissionManager::createPermission()
   */
  public function createPermission(string $resource, string $context, string $action, string $role, string $modifier): bool {
    if (sizeof($this->managers) > 0) {
      return $this->managers[0]->createPermission($resource, $context, $action, $role, $modifier);
    }
    return false;
  }

  /**
   * @see PermissionManager::removePermission()
   */
  public function removePermission(string $resource, string $context, string $action, string $role): bool {
    if (sizeof($this->managers) > 0) {
      return $this->managers[0]->removePermission($resource, $context, $action, $role);
    }
    return false;
  }
}
?>
