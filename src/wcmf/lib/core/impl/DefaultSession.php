<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2015 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\core\impl;

use wcmf\lib\core\Session;
use wcmf\lib\security\principal\impl\AnonymousUser;

// session configuration
ini_set('session.cookie_lifetime', 0);
ini_set('session.use_cookies', 'On');
ini_set('session.use_only_cookies', 'On');
ini_set('session.use_strict_mode', 'On');
ini_set('session.cookie_httponly', 'On');
ini_set('session.use_trans_sid', 'Off');
ini_set('session.cache_limiter', 'nocache');
ini_set('session.hash_function', 'sha256');

/**
 * Default session implementation.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultSession implements Session {

  private static $ERROR_VARNAME = 'Session.errors';

  private $_authUserVarName = null;

  /**
   * Constructor
   */
  public function __construct() {
    $this->_authUserVarName = 'auth_user_'.md5(__FILE__);

    $sessionName = 'wcmf'.md5(__FILE__);
    session_name($sessionName);
    // NOTE: prevent "headers already sent" errors in phpunit tests
    if (!headers_sent()) {
      session_start();
      // regenerate session for authenticated sessions
      if (isset($_SESSION[$this->_authUserVarName])) {
        session_regenerate_id();
      }
    }
  }

  public function __destruct() {
    session_write_close();
  }

  /**
   * @see Session::getID()
   */
  public function getID() {
    return session_id();
  }

  /**
   * @see Session::get()
   */
  public function get($key) {
    $value = null;
    if (isset($_SESSION[$key])) {
      $value = $_SESSION[$key];
    }
    return $value;
  }

  /**
   * @see Session::set()
   */
  public function set($key, $value) {
    $_SESSION[$key] = $value;
  }

  /**
   * @see Session::remove()
   */
  public function remove($key) {
    unset($_SESSION[$key]);
  }

  /**
   * @see Session::exist()
   */
  public function exist($key) {
    $result = isset($_SESSION[$key]);
    return $result;
  }

  /**
   * @see Session::clear()
   */
  public function clear() {
    $_SESSION = array();
  }

  /**
   * @see Session::destroy()
   */
  public function destroy() {
    $_SESSION = array();
    @session_destroy();
  }

  /**
   * @see Session::setAuthUser()
   */
  public function setAuthUser($login) {
    $this->set($this->_authUserVarName, $login);
  }

  /**
   * @see Session::getAuthUser()
   */
  public function getAuthUser() {
    $login = AnonymousUser::USER_GROUP_NAME;
    // check for auth user in session
    if ($this->exist($this->_authUserVarName)) {
      $login = $this->get($this->_authUserVarName);
    }
    return $login;
  }

  /**
   * @see Session::addError()
   */
  public function addError($key, $error) {
    if (isset($_SESSION[self::$ERROR_VARNAME])) {
      $_SESSION[self::$ERROR_VARNAME] = array();
    }
    $_SESSION[self::$ERROR_VARNAME][$key] = $error;
  }

  /**
   * @see Session::getError()
   */
  public function getError($key) {
    $error = null;
    if (isset($_SESSION[self::$ERROR_VARNAME])) {
      $error = $_SESSION[self::$ERROR_VARNAME][$key];
    }
    return $error;
  }

  /**
   * @see Session::getErrors()
   */
  public function getErrors() {
    $errors = $_SESSION[self::$ERROR_VARNAME];
    return $errors;
  }

  /**
   * @see Session::clearErrors()
   */
  public function clearErrors() {
    unset($_SESSION[self::$ERROR_VARNAME]);
  }
}