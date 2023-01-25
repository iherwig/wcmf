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
  public function authorize($resource, $context, $action, $login=null, $applyDefaultPolicy=true) {
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
  public function hasTempPermission($resource, $context, $action) {
    return true;
  }

  /**
   * @see PermissionManager::getPermissions()
   */
  public function getPermissions($resource, $context, $action) {
    return self::$policies;
  }

  /**
   * @see PermissionManager::setPermissions()
   */
  public function setPermissions($resource, $context, $action, $permissions) {
  }

  /**
   * @see PermissionManager::createPermission()
   */
  public function createPermission($resource, $context, $action, $role, $modifier) {
    return false;
  }

  /**
   * @see PermissionManager::removePermission()
   */
  public function removePermission($resource, $context, $action, $role) {
    return false;
  }
}
?>
