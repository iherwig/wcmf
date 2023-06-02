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

use wcmf\lib\persistence\ObjectId;

/**
 * User is the interface for users.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface User {

  /**
   * Get the object id of the user.
   * @return ObjectId
   */
  public function getOID(): ObjectId;

  /**
   * Set the login of the user.
   * @param string $login The login of the user.
   */
  public function setLogin(string $login): void;

  /**
   * Get the login of the user.
   * @return string
   */
  public function getLogin(): string;

  /**
   * Set the password of the user. Implementations of User must
   * hash the password before persisting it.
   * @param string $password The plaintext password of the user.
   */
  public function setPassword(string $password): void;

  /**
   * Get the password of the user. The result is expected to
   * be hashed, if the user was persisted already. If not
   * persisted, the result may be the plaintext password.
   * @return string
   */
  public function getPassword(): string;

  /**
   * Verify the given password against the password of the user.
   * @param $password The plaintext password to verify
   * @return bool
   */
  public function verifyPassword(string $password): bool;

  /**
   * Set if the user is active.
   * @param bool $isActive Boolean whether the user is active or not
   */
  public function setIsActive(bool $isActive): void;

  /**
   * Check if the user is active.
   * @return bool
   */
  public function isActive(): bool;

  /**
   * Set if the user is super user (can't be inactive).
   * @param bool $isSuperUser Boolean whether the user is super user or not
   */
  public function setIsSuperUser(bool $isSuperUser): void;

  /**
   * Check if the user is super user (can't be inactive).
   * @return bool
   */
  public function isSuperUser(): bool;

  /**
   * Set the configuration file of the user.
   * @param string $config The configuration file of the user.
   */
  public function setConfig(string $config): void;

  /**
   * Get the configuration file of the user.
   * @return string
   */
  public function getConfig(): string;

  /**
   * Check for a certain role in the user roles.
   * @param string $roleName The role name to check for. e.g. "administrators"
   * @return bool
   */
  public function hasRole(string $roleName): bool;

  /**
   * Get the roles of a user.
   * @return array<Role>
   */
  public function getRoles(): array;
}
?>
