<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2009 wemove digital solutions GmbH
 *
 * Licensed under the terms of any of the following licenses
 * at your choice:
 *
 * - GNU Lesser General Public License (LGPL)
 *   http://www.gnu.org/licenses/lgpl.html
 * - Eclipse Public License (EPL)
 *   http://www.eclipse.org/org/documents/epl-v10.php
 *
 * See the license.txt file distributed with this work for
 * additional information.
 *
 * $Id$
 */
namespace wcmf\lib\security;

use wcmf\lib\security\AuthUser;

/**
 * PermissionManager implementations are used to handle all authorization
 * requests.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface PermissionManager {

  const RIGHT_MODIFIER_ALLOW = '+';
  const RIGHT_MODIFIER_DENY = '-';

  /**
   * Get session variable name for the authenticated user.
   * @return The variable name.
   */
  public static function getAuthUserVarname();

  /**
   * Get authenticated user.
   * @return AuthUser object or null if not logged in.
   */
  public function getAuthUser();

  /**
   * See if the PermissionManager is working in anonymous mode. In anonymous mode all
   * authorization requests answered positive and AuthUser is an instance of AnonymousUser.
   * @return Boolean wether in anonymous mode
   */
  public function isAnonymous();

  /**
   * Deactivate rights checking by setting the anonymous confguration value.
   */
  public function deactivate();

  /**
   * (Re-)activate rights checking by unsetting the anonymous confguration value.
   */
  public function activate();

  /**
   * Authorize for given resource, context, action triple.
   * @param resource The resource to authorize (e.g. class name of the Controller or ObjectId).
   * @param context The context in which the action takes place.
   * @param action The action to process.
   * @return Boolean whether authorization succeded/failed.
   */
  public function authorize($resource, $context, $action);

  /**
   * Permission management
   */

  /**
   * Get the permission on a resource, context, action combination.
   * @param config The configuration to get the permission from.
   * @param resource The resource (e.g. class name of the Controller or OID).
   * @param context The context in which the action takes place.
   * @param action The action to process.
   * @return An assoziative array with keys 'default', 'allow', 'deny' and the attached roles as values.
   * @see AuthUser::parsePolicy
   */
  public function getPermission($config, $resource, $context, $action);

  /**
   * Create/Change a permission for a role on a resource, context, action combination.
   * @param config The configuration to create the permission in.
   * @param resource The resource (e.g. class name of the Controller or OID).
   * @param context The context in which the action takes place.
   * @param action The action to process.
   * @param role The role to authorize.
   * @param modifier One of the RIGHT_MODIFIER_ constants.
   * @return Boolean whether creation succeded/failed.
   */
  public function createPermission($config, $resource, $context, $action, $role, $modifier);

  /**
   * Remove a role from a permission on a resource, context, action combination.
   * @param config The configuration to remove the right from.
   * @param resource The resource (e.g. class name of the Controller or OID).
   * @param context The context in which the action takes place.
   * @param action The action to process.
   * @param role The role to remove.
   * @return Boolean whether removal succeded/failed.
   */
  public function removePermission($config, $resource, $context, $action, $role);
}
?>
