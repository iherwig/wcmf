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
namespace wcmf\lib\security\principal;

/**
 * PrincipalFactory implementations are used to retrieve User and
 * Role instances.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface PrincipalFactory {

  /**
   * Get a User instance by login.
   * @param $login The user's login
   * @param $useTempPermission Boolean whether to set a temporary permission
   *   for reading or not (uselfull in the login process, where no authenticated
   *   user exists yet) (optional, default: false)
   * @return User instance
   */
  public function getUser($login, $useTempPermission=false);

  /**
   * Get the Role instances associated with the given User instance.
   * @param $user The User instance
   * @param $useTempPermission Boolean whether to set a temporary permission
   *   for reading or not (uselfull in the login process, where no authenticated
   *   user exists yet) (optional, default: false)
   * @return Array of Role instances
   */
  public function getUserRoles(User $user, $useTempPermission=false);

  /**
   * Get a Role instance by name.
   * @param $name The role's name
   * @param $useTempPermission Boolean whether to set a temporary permission
   *   for reading or not (uselfull in the login process, where no authenticated
   *   user exists yet) (optional, default: false)
   * @return Role instance
   */
  public function getRole($name, $useTempPermission=false);
}
?>
