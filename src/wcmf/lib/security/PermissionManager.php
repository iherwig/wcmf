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
   * @param $resource The resource to authorize (e.g. class name of the Controller or ObjectId instance).
   * @param $context The context in which the action takes place.
   * @param $action The action to process.
   * @param $login The login of the user to use for authorization (optional,
   *              default: the value of Session::getAuthUser())
   * @return Boolean whether authorization succeeded/failed.
   */
  public function authorize($resource, $context, $action, $login=null);

  /**
   * Add a temporary permission for the current user. The permission
   * is valid only until end of execution or a call to
   * PermissionManager::removeTempPermission() or PermissionManager::clearTempPermissions().
   * @param $resource The resource to authorize (e.g. class name of the Controller or ObjectId).
   * @param $context The context in which the action takes place.
   * @param $action The action to process.
   * @return String handle, to be used when calling PermissionManager::removeTempPermission()
   */
  public function addTempPermission($resource, $context, $action);

  /**
   * Remove a temporary permission for the current user.
   * @param $handle The handle obtained from PermissionManager::addTempPermission()
   */
  public function removeTempPermission($handle);

  /**
   * Check if a temporary permission for the current user exists.
   * @param $resource The resource to authorize (e.g. class name of the Controller or ObjectId).
   * @param $context The context in which the action takes place.
   * @param $action The action to process.
   * @return Boolean
   */
  public function hasTempPermission($resource, $context, $action);

  /**
   * Reset all temporary permissions
   */
  public function clearTempPermissions();

  /**
   * Permission management
   */

  /**
   * Get the permissions on a resource, context, action combination.
   * @param $resource The resource (e.g. class name of the Controller or ObjectId).
   * @param $context The context in which the action takes place.
   * @param $action The action to process.
   * @return Assoziative array with keys 'default' (boolean) and 'allow', 'deny'
   * (arrays of role names) or null, if no permissions are defined.
   */
  public function getPermissions($resource, $context, $action);

  /**
   * Set the permissions on a resource, context, action combination.
   * @param $resource The resource (e.g. class name of the Controller or ObjectId).
   * @param $context The context in which the action takes place.
   * @param $action The action to process.
   * @param $permissions Assoziative array with keys 'default' (boolean) and
   * 'allow', 'deny' (arrays of role names) or null if all permissions should be deleted.
   */
  public function setPermissions($resource, $context, $action, $permissions);

  /**
   * Create/Change a permission for a role on a resource, context, action combination.
   * @param $resource The resource (e.g. class name of the Controller or ObjectId).
   * @param $context The context in which the action takes place.
   * @param $action The action to process.
   * @param $role The role to authorize.
   * @param $modifier One of the PERMISSION_MODIFIER constants.
   * @return Boolean whether creation succeded/failed.
   */
  public function createPermission($resource, $context, $action, $role, $modifier);

  /**
   * Remove a role from a permission on a resource, context, action combination.
   * @param $resource The resource (e.g. class name of the Controller or ObjectId).
   * @param $context The context in which the action takes place.
   * @param $action The action to process.
   * @param $role The role to remove.
   * @return Boolean whether removal succeded/failed.
   */
  public function removePermission($resource, $context, $action, $role);
}
?>
