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
namespace wcmf\lib\core\impl;

use wcmf\lib\core\Session;

/**
 * Default session implementation.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultSession implements Session {

  private static $ERROR_VARNAME = 'Session.errors';

  public function __construct() {
    @session_start();
    // regenerate session id if cookie is lost
    $sessionName = ini_get('session.name');
    if (!isset($_COOKIE[$sessionName]) || strlen($_COOKIE[$sessionName]) == 0) {
      session_regenerate_id();
    }
  }

  /**
   * Get the id of the session.
   * @return The id of the current session.
   */
  public function getID() {
    return session_id();
  }

  /**
   * Returns the value of an session variable
   * @param key The key (name) of the session vaiable.
   * @return The session var or null if it doesn't exist.
   */
  public function get($key) {
    $value = null;
    if (isset($_SESSION[$key])) {
      $value = $_SESSION[$key];
    }
    return $value;
  }

  /**
   * Sets the value of an session variable.
   * @param key The key (name) of the session vaiable.
   * @param value The value of the session variable.
   */
  public function set($key, $value) {
    $_SESSION[$key] = $value;
  }

  /**
   * Remove a session variable.
   * @param key The key (name) of the session variable.
   */
  public function remove($key) {
    unset($_SESSION[$key]);
  }

  /**
   * Tests, if a certain session variable is defined.
   * @param key The key (name) of the session variable.
   * @return A boolean flag. true if the session variable is set, false if not.
   */
  public function exist($key) {
    return isset($_SESSION[$key]);
  }

  /**
   * Clear the session data.
   */
  public function clear() {
    $_SESSION = array();
  }

  /**
   * Destroy the session.
   */
  public function destroy() {
    $_SESSION = array();
    @session_destroy();
  }

  /**
   * Add an error to the session data.
   * @param key The identifier of the error
   * @param error The error message
   */
  public function addError($key, $error) {
    if (isset($_SESSION[self::$ERROR_VARNAME])) {
      $_SESSION[self::$ERROR_VARNAME] = array();
    }
    $_SESSION[self::$ERROR_VARNAME][$key] = $error;
  }

  /**
   * Get an error stored in the session data.
   * @param key The identifier of the error
   * @return The error message
   */
  public function getError($key) {
    if (isset($_SESSION[self::$ERROR_VARNAME])) {
      return $_SESSION[self::$ERROR_VARNAME][$key];
    }
    return null;
  }

  /**
   * Get all errors stored in the session data.
   * @return The error message
   */
  public function getErrors() {
    return $_SESSION[self::$ERROR_VARNAME];
  }

  /**
   * Clear the session error data.
   */
  public function clearErrors() {
    unset($_SESSION[self::$ERROR_VARNAME]);
  }
}