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
namespace wcmf\lib\core;

/**
 * Session is the interface for session implementations
 * and defines access to session variables.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface Session {

  /**
   * Get the id of the session.
   * @return The id of the current session.
   */
  public function getID();

  /**
   * Returns the value of a session variable
   * @param $key The key (name) of the session vaiable.
   * @param $default The default value if the key is not defined (optional, default: _null_)
   * @return The session var or null if it doesn't exist.
   */
  public function get($key, $default=null);

  /**
   * Sets the value of a session variable.
   * @param $key The key (name) of the session vaiable.
   * @param $value The value of the session variable.
   */
  public function set($key, $value);

  /**
   * Remove a session variable.
   * @param $key The key (name) of the session variable.
   */
  public function remove($key);

  /**
   * Tests, if a certain session variable is defined.
   * @param $key The key (name) of the session variable.
   * @return Boolean whether the session variable is set or not.
   */
  public function exist($key);

  /**
   * Clear the session data.
   */
  public function clear();

  /**
   * Destroy the session.
   */
  public function destroy();

  /**
   * Set the login of authenticated user.
   * @param $login Login name of the user
   */
  public function setAuthUser($login);

  /**
   * Get the login of the authenticated user.
   * @return String
   */
  public function getAuthUser();
}