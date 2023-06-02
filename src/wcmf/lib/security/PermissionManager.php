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
namespace wcmf\lib\security;

/**
 * PermissionManager implementations are used to handle all authorization
 * requests. PermissionManager instances are configured with an AuthUser
 * instance, against which authorization requests are processed.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface PermissionManager {

  const PERMISSION_MODIFIER_ALLOW = '+';
  const PERMISSION_MODIFIER_DENY = '-';

  /**
   * Authorize for given resource, context, action triple.
   * A resource could be one of the following:
   * - Controller class name (e.g. `wcmf\application\controller\SaveController`)
   * - Type name (e.g. `app.src.model.wcmf.User`)
   * - Type and property name (e.g. `app.src.model.wcmf.User.login`)
   * - Object id (e.g. `app.src.model.wcmf.User:123`)
   * - Object id and property name (e.g. `app.src.model.wcmf.User:123.login`)
   *
   * @param string $resource The resource to authorize (e.g. class name of the Controller or ObjectId instance).
   * @param string $context The context in which the action takes place.
   * @param string $action The action to process.
   * @param string $login The login of the user to use for authorization (optional, default: the value of Session::getAuthUser())
   * @param bool $applyDefaultPolicy Boolean whether to apply a default policy, if no authorization rule is set for this request (optional, default: true)
   * @return bool whether authorization succeeded/failed or null, if no rule is set and no default policy is applied
   */
  public function authorize(string $resource, string $context, string $action, string $login=null, bool $applyDefaultPolicy=true): bool;

  /**
   * Execute a function with a temporary permission for the current user. The permission
   * is valid only until end of execution of the function.
   * @param callable $callable The function to execute.
   * @param array $permissions Array of permission definition arrays containing
   *      - The resource to authorize (e.g. class name of the Controller or ObjectId) at index 0,
   *      - The context in which the action takes place at index 1,
   *      - The action to process at index 2
   * @return mixed The result of the call to the function
   */
  public function withTempPermissions(callable $callable, array ...$permissions);

  /**
   * Check if a temporary permission for the current user exists.
   * @param string $resource The resource to authorize (e.g. class name of the Controller or ObjectId).
   * @param string $context The context in which the action takes place.
   * @param string $action The action to process.
   * @return bool
   */
  public function hasTempPermission(string $resource, string $context, string $action): bool;

  /**
   * Permission management
   */

  /**
   * Get the permissions on a resource, context, action combination.
   * @param string $resource The resource (e.g. class name of the Controller or ObjectId).
   * @param string $context The context in which the action takes place.
   * @param string $action The action to process.
   * @return array{'default': bool, 'allow': array<string>, 'deny': array<string>}
   *     with allow and deny arrays containing role names or null, if no permissions are defined.
   */
  public function getPermissions(string $resource, string $context, string $action): ?array;

  /**
   * Set the permissions on a resource, context, action combination.
   * @param string $resource The resource (e.g. class name of the Controller or ObjectId).
   * @param string $context The context in which the action takes place.
   * @param string $action The action to process.
   * @param array{'default': bool, 'allow': array<string>, 'deny': array<string>} $permissions
   *     with allow and deny arrays containing role names or null, if all permissions should be deleted.
   */
  public function setPermissions(string $resource, string $context, string $action, ?array $permissions): void;

  /**
   * Create/Change a permission for a role on a resource, context, action combination.
   * @param string $resource The resource (e.g. class name of the Controller or ObjectId).
   * @param string $context The context in which the action takes place.
   * @param string $action The action to process.
   * @param string $role The role to authorize.
   * @param string $modifier One of the PERMISSION_MODIFIER constants.
   * @return bool whether creation succeded/failed.
   */
  public function createPermission(string $resource, string $context, string $action, string $role, string $modifier): bool;

  /**
   * Remove a role from a permission on a resource, context, action combination.
   * @param string $resource The resource (e.g. class name of the Controller or ObjectId).
   * @param string $context The context in which the action takes place.
   * @param string $action The action to process.
   * @param string $role The role to remove.
   * @return bool whether removal succeded/failed.
   */
  public function removePermission(string $resource, string $context, string $action, string $role): bool;
}
?>
