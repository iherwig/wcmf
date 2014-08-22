<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
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
    // regenerate session id if cookie is lost
    $sessionName = 'wcmf'.md5(__FILE__);
    session_name($sessionName);
    if (!isset($_COOKIE[$sessionName]) || strlen($_COOKIE[$sessionName]) == 0) {
      session_regenerate_id();
    }
    session_start();
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