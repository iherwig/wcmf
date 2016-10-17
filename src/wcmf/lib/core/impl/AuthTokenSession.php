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
namespace wcmf\lib\core\impl;

use wcmf\lib\core\Session;
use wcmf\lib\presentation\Request;
use wcmf\lib\security\principal\impl\AnonymousUser;

// session configuration
ini_set('session.cookie_lifetime', 0);
ini_set('session.use_cookies', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.use_trans_sid', 0);
ini_set('session.cache_limiter', 'nocache');
ini_set('session.hash_function', 1);
if (in_array('sha256', hash_algos())) {
  ini_set('session.hash_function', 'sha256');
}

/**
 * AuthToken session implementation.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class AuthTokenSession implements Session {

  const TOKEN_NAME = 'auth-token';
  const TOKEN_HEADER = 'X-Auth-Token';

  private $authUserVarName = null;
  private $isAuthorized = false;
  private $request = null;

  /**
   * Constructor
   * @param $request
   */
  public function __construct(Request $request) {
    $this->authUserVarName = 'auth_user';
    $this->request = $request;

    $sessionName = 'wcmf'.md5(__FILE__);
    session_name($sessionName);
    // NOTE: prevent "headers already sent" errors in phpunit tests
    if (!headers_sent()) {
      session_start();
    }

    // check for valid auth-token in request
    $this->isAuthorized = $this->request->hasHeader(self::TOKEN_HEADER) &&
      $this->request->getHeader(self::TOKEN_HEADER) == $this->getID();
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
  public function get($key, $default=null) {
    $value = $default;
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
    $this->set($this->authUserVarName, $login);
    // NOTE: prevent "headers already sent" errors in phpunit tests
    if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
      session_regenerate_id(true);
    }
    // set auth-token cookie for authenticated user
    if ($login !== AnonymousUser::USER_GROUP_NAME) {
      $domain = ($_SERVER['HTTP_HOST'] != 'localhost') ? $_SERVER['HTTP_HOST'] : false;
      setcookie(self::TOKEN_NAME, $this->getID(), 0, '/', $domain, false, false);
      $this->isAuthorized = true;
    }
  }

  /**
   * @see Session::getAuthUser()
   */
  public function getAuthUser() {
    $login = AnonymousUser::USER_GROUP_NAME;
    // check for auth user in session
    if ($this->exist($this->authUserVarName) && $this->isAuthorized) {
      $login = $this->get($this->authUserVarName);
    }
    return $login;
  }
}