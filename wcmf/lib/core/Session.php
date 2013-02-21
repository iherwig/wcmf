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
namespace wcmf\lib\core;

/**
 * Session provides a unified access to session data.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class Session {

  private static $_instance = null;
  private static $ERROR_VARNAME = 'Session.errors';

  private function __construct() {}

  /**
   * Created a Session instance with given session id.
   * @param sessionId The session id to use (maybe null).
   * @note If session id is null an automatically generated session id will be used.
   */
  public static function init($sessionId) {
    // Set custom session id
    if (strlen($sessionId) > 0) {
      session_id($sessionId);
    }
    @session_start();
  }

  /**
   * Returns an instance of the class.
   * @note If called before init an automatically generated session id will be used.
   * @return A reference to the only instance of the Singleton object
   */
  public static function getInstance() {
    if (!isset(self::$_instance)) {
      self::$_instance = new Session();
    }
    return self::$_instance;
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
    if (isset($_SESSION) && isset($_SESSION[$key])) {
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
    if (isset($_SESSION)) {
      $_SESSION[$key] = $value;
    }
    else {
      throw new Exception("Cannot set session variable. A session is not started.");
    }
  }

  /**
   * Remove a session variable.
   * @param key The key (name) of the session variable.
   */
  public function remove($key) {
    if (isset($_SESSION)) {
      unset($_SESSION[$key]);
    }
  }

  /**
   * Tests, if a certain session variable is defined.
   * @param key The key (name) of the session variable.
   * @return A boolean flag. true if the session variable is set, false if not.
   */
  public function exist($key) {
    return isset($_SESSION) && isset($_SESSION[$key]);
  }

  /**
   * Clear the session data.
   */
  public function clear() {
    session_unset();
  }

  /**
   * Add an error to the session data.
   * @param key The identifier of the error
   * @param error The error message
   */
  public function addError($key, $error) {
    if (isset($_SESSION)) {
      if (!is_array($_SESSION[self::$ERROR_VARNAME])) {
        $_SESSION[self::$ERROR_VARNAME] = array();
      }
      $_SESSION[self::$ERROR_VARNAME][$key] = $error;
    }
  }

  /**
   * Get an error stored in the session data.
   * @param key The identifier of the error
   * @return The error message
   */
  public function getError($key) {
    if (isset($_SESSION)) {
      return $_SESSION[self::$ERROR_VARNAME][$key];
    }
    return null;
  }

  /**
   * Get all errors stored in the session data.
   * @return The error message
   */
  public function getErrors() {
    if (isset($_SESSION)) {
      return $_SESSION[self::$ERROR_VARNAME];
    }
    return null;
  }

  /**
   * Clear the session error data.
   */
  public function clearErrors() {
    if (isset($_SESSION)) {
      unset($_SESSION[self::$ERROR_VARNAME]);
    }
  }

  /**
   * Destroy the session.
   */
  public function destroy() {
    $_SESSION = array();
    if (strlen(session_id()) > 0) {
      session_destroy();
    }
  }

  /**
   * Get session variable name for the authenticated user.
   * @return The variable name.
   */
  private static function getAuthUserVarname() {
    return 'auth_user_'.Application::getId();
  }
}