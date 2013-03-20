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
namespace wcmf\lib\security\principal;

/**
 * User is the interface for users.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface User {

  /**
   * Get the id of the user.
   * @return The id.
   */
  public function getUserId();

  /**
   * Set the login of the user.
   * @param login The login of the user.
   */
  public function setLogin($login);

  /**
   * Get the login of the user.
   * @return The login of the user.
   */
  public function getLogin();

  /**
   * Set the password of the user.
   * @param password The unencrypted password of the user.
   */
  public function setPassword($password);

  /**
   * Get the password of the user.
   * @return The encrypted password of the user.
   */
  public function getPassword();

  /**
   * Set the name of the user.
   * @param name The name of the user.
   */
  public function setName($name);

  /**
   * Get name of the user.
   * @return The name of the user.
   */
  public function getName();

  /**
   * Set the firstname of the user.
   * @param firstname The firstname of the user.
   */
  public function setFirstname($firstname);

  /**
   * Get the firstname of the user.
   * @return The firstname of the user.
   */
  public function getFirstname();

  /**
   * Set the configuration file of the user.
   * @param config The configuration file of the user.
   */
  public function setConfig($config);

  /**
   * Get the configuration file of the user.
   * @return The configuration file of the user.
   */
  public function getConfig();

  /**
   * Assign a role to the user.
   * @param rolename The role name. e.g. "administrators"
   */
  public function addRole($rolename);

  /**
   * Remove a role from the user.
   * @param rolename The role name. e.g. "administrators"
   */
  public function removeRole($rolename);

  /**
   * Check for a certain role in the user roles.
   * @param rolename The role name to check for. e.g. "administrators"
   * @return True/False whether the user has the role
   */
  public function hasRole($rolename);

  /**
   * Get the roles of a user.
   * @return An array holding the role names
   */
  public function getRoles();

  /**
   * If implementations cache loaded roles for performance reasons, they
   * must invalidate the cache when this method is called. This might be
   * necessary after retrieving an instance from the session.
   */
  public function resetRoleCache();
}
?>
