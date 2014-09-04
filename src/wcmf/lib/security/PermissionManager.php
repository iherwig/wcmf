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
   * Set the authenticated user.
   * @param $authUser AuthUser instance.
   */
  public function setAuthUser($authUser);

  /**
   * Get the authenticated user.
   * @return AuthUser instance.
   */
  public function getAuthUser();

  /**
   * See if the PermissionManager is working in anonymous mode. In anonymous mode all
   * authorization requests answered positive and AuthUser is an instance of AnonymousUser.
   * @return Boolean whether in anonymous mode
   */
  public function isAnonymous();

  /**
   * Deactivate permission checking by setting the anonymous confguration value.
   */
  public function deactivate();

  /**
   * (Re-)activate permission checking by unsetting the anonymous confguration value.
   */
  public function activate();

  /**
   * Authorize for given resource, context, action triple.
   * A resource could be one of the following:
   * - Controller class name (e.g. `wcmf\application\controller\SaveController`)
   * - Type name (e.g. `app.src.model.wcmf.User`)
   * - Type and propery name (e.g. `app.src.model.wcmf.User.login`)
   * - Object id (e.g. `app.src.model.wcmf.User:123`)
   * - Object id and propery name (e.g. `app.src.model.wcmf.User:123.login`)
   *
   * @param $resource The resource to authorize (e.g. class name of the Controller or ObjectId instance).
   * @param $context The context in which the action takes place.
   * @param $action The action to process.
   * @return Boolean whether authorization succeded/failed.
   */
  public function authorize($resource, $context, $action);

  /**
   * Add a temporary permission for the current user. The permission
   * is valid only until end of execution or a call to
   * PermissionManager::removeTempPermission() or PermissionManager::clearTempPermissions().
   * @param $resource The resource to authorize (e.g. class name of the Controller or ObjectId).
   * @param $context The context in which the action takes place.
   * @param $action The action to process.
   */
  public function addTempPermission($resource, $context, $action);

  /**
   * Remove a temporary permission for the current user.
   * @param $resource The resource to authorize (e.g. class name of the Controller or ObjectId).
   * @param $context The context in which the action takes place.
   * @param $action The action to process.
   */
  public function removeTempPermission($resource, $context, $action);

  /**
   * Reset all temporary permissions
   */
  public function clearTempPermissions();

  /**
   * Permission management
   */

  /**
   * Get the permission on a resource, context, action combination.
   * @param $resource The resource (e.g. class name of the Controller or OID).
   * @param $context The context in which the action takes place.
   * @param $action The action to process.
   * @return An assoziative array with keys 'default', 'allow', 'deny' and the attached roles as values.
   * @see AuthUser::parsePolicy
   */
  public function getPermission($resource, $context, $action);

  /**
   * Create/Change a permission for a role on a resource, context, action combination.
   * @param $resource The resource (e.g. class name of the Controller or OID).
   * @param $context The context in which the action takes place.
   * @param $action The action to process.
   * @param $role The role to authorize.
   * @param $modifier One of the PERMISSION_MODIFIER_ constants.
   * @return Boolean whether creation succeded/failed.
   */
  public function createPermission($resource, $context, $action, $role, $modifier);

  /**
   * Remove a role from a permission on a resource, context, action combination.
   * @param $resource The resource (e.g. class name of the Controller or OID).
   * @param $context The context in which the action takes place.
   * @param $action The action to process.
   * @param $role The role to remove.
   * @return Boolean whether removal succeded/failed.
   */
  public function removePermission($resource, $context, $action, $role);
}
?>
