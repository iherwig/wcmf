<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2016 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\security\principal;

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
  public function getOID();

  /**
   * Set the login of the user.
   * @param $login The login of the user.
   */
  public function setLogin($login);

  /**
   * Get the login of the user.
   * @return The login of the user.
   */
  public function getLogin();

  /**
   * Set the password of the user. Implementations of User must
   * hash the password before persisting it.
   * @param $password The plaintext password of the user.
   */
  public function setPassword($password);

  /**
   * Get the password of the user. The result is expected to
   * be hashed, if the user was persisted already. If not
   * persisted, the result may be the plaintext password.
   * @return The password of the user,
   */
  public function getPassword();

  /**
   * Verify the given password against the password of the user.
   * @param $password The plaintext password to verify
   * @return Boolean.
   */
  public function verifyPassword($password);

  /**
   * Set the configuration file of the user.
   * @param $config The configuration file of the user.
   */
  public function setConfig($config);

  /**
   * Get the configuration file of the user.
   * @return The configuration file of the user.
   */
  public function getConfig();

  /**
   * Check for a certain role in the user roles.
   * @param $roleName The role name to check for. e.g. "administrators"
   * @return Boolean whether the user has the role
   */
  public function hasRole($roleName);

  /**
   * Get the roles of a user.
   * @return Array of role names
   */
  public function getRoles();
}
?>
