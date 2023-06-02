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
   * @param string $login The user's login
   * @param bool $useTempPermission Boolean whether to set a temporary permission
   *   for reading or not (uselfull in the login process, where no authenticated
   *   user exists yet) (optional, default: false)
   * @return User instance or null
   */
  public function getUser(string $login, bool $useTempPermission=false): ?User;

  /**
   * Get the Role instances associated with the given User instance.
   * @param User $user The User instance
   * @param bool $useTempPermission Boolean whether to set a temporary permission
   *   for reading or not (uselfull in the login process, where no authenticated
   *   user exists yet) (optional, default: false)
   * @return array<Role>
   */
  public function getUserRoles(User $user, bool $useTempPermission=false): array;

  /**
   * Get a Role instance by name.
   * @param string $name The role's name
   * @param bool $useTempPermission Boolean whether to set a temporary permission
   *   for reading or not (uselfull in the login process, where no authenticated
   *   user exists yet) (optional, default: false)
   * @return Role instance or null
   */
  public function getRole(string $name, bool $useTempPermission=false): ?Role;
}
?>
