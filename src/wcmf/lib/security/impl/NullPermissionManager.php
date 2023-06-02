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
 * NullPermissionManager acts like an absent PermissionManager.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NullPermissionManager implements PermissionManager {

  private static $policies = [
    'allow' => [],
    'deny' => [],
    'default' => true
  ];

  /**
   * @see PermissionManager::authorize()
   */
  public function authorize(string $resource, string $context, string $action, string $login=null, bool $applyDefaultPolicy=true): bool {
    return true;
  }

  /**
   * @see PermissionManager::withTempPermissions()
   */
  public function withTempPermissions(callable $callable, array ...$permissions) {
    return $callable();
  }

  /**
   * @see PermissionManager::hasTempPermission()
   */
  public function hasTempPermission(string $resource, string $context, string $action): bool {
    return true;
  }

  /**
   * @see PermissionManager::getPermissions()
   */
  public function getPermissions(string $resource, string $context, string $action): ?array {
    return self::$policies;
  }

  /**
   * @see PermissionManager::setPermissions()
   */
  public function setPermissions(string $resource, string $context, string $action, ?array $permissions): void {
  }

  /**
   * @see PermissionManager::createPermission()
   */
  public function createPermission(string $resource, string $context, string $action, string $role, string $modifier): bool {
    return false;
  }

  /**
   * @see PermissionManager::removePermission()
   */
  public function removePermission(string $resource, string $context, string $action, string $role): bool {
    return false;
  }
}
?>
