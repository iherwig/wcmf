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
namespace wcmf\lib\core\impl;

use wcmf\lib\config\Configuration;
use wcmf\lib\core\Session;
use wcmf\lib\security\principal\impl\AnonymousUser;
use wcmf\lib\util\StringUtil;
use wcmf\lib\util\URIUtil;

/**
 * DefaultSession uses the default PHP session implementation:
 * - server side storage
 * - session id sent as a cookie to the client
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultSession implements Session {

  CONST AUTH_USER_NAME = 'auth_user';

  private $cookiePrefix = '';

  /**
   * Constructor
   * @param $configuration
   */
  public function __construct(Configuration $configuration) {
    // session configuration
    ini_set('session.cookie_lifetime', 0);
    ini_set('session.use_cookies', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', (URIUtil::isHttps() ? 1 : 0));
    ini_set('session.use_trans_sid', 0);
    ini_set('session.cache_limiter', 'nocache');
    ini_set('session.hash_function', 1);
    if (in_array('sha256', hash_algos())) {
      ini_set('session.hash_function', 'sha256');
    }
    $this->cookiePrefix = strtolower(StringUtil::slug($configuration->getValue('title', 'application')));

    session_name($this->cookiePrefix.'-session');
    // NOTE: prevent "headers already sent" errors in phpunit tests
    if (!headers_sent()) {
      session_start();
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
    $_SESSION = [];
  }

  /**
   * @see Session::destroy()
   */
  public function destroy() {
    $_SESSION = [];
    @session_destroy();
  }

  /**
   * @see Session::setAuthUser()
   */
  public function setAuthUser($login) {
    $this->set(self::AUTH_USER_NAME, $login);
    // NOTE: prevent "headers already sent" errors in phpunit tests
    if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
      session_regenerate_id(true);
    }
  }

  /**
   * @see Session::getAuthUser()
   */
  public function getAuthUser() {
    $login = AnonymousUser::USER_GROUP_NAME;
    // check for auth user in session
    if ($this->exist(self::AUTH_USER_NAME)) {
      $login = $this->get(self::AUTH_USER_NAME);
    }
    return $login;
  }

  /**
   * Get the cookie prefix
   * @return String
   */
  protected function getCookiePrefix() {
    return $this->cookiePrefix;
  }
}