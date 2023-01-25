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

  const AUTH_USER_NAME = 'auth_user';

  private $cookiePrefix = '';

  /**
   * Constructor
   * @param $configuration
   */
  public function __construct(Configuration $configuration) {
    // NOTE: prevent "headers already sent" errors in phpunit tests
    if (!headers_sent() && session_status() == PHP_SESSION_NONE) {
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

      session_name($this->getCookieName());
    }
  }

  public function __destruct() {
    session_write_close();
  }

  /**
   * @see Session::isStarted()
   */
  public function isStarted() {
    return isset($_COOKIE[$this->getCookieName()]);
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
    $this->start();
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
    $this->start();
    $_SESSION[$key] = $value;
  }

  /**
   * @see Session::remove()
   */
  public function remove($key) {
    $this->start();
    unset($_SESSION[$key]);
  }

  /**
   * @see Session::exist()
   */
  public function exist($key) {
    $this->start();
    $result = isset($_SESSION[$key]);
    return $result;
  }

  /**
   * @see Session::clear()
   */
  public function clear() {
    $this->start();
    $_SESSION = [];
  }

  /**
   * @see Session::destroy()
   */
  public function destroy() {
    $this->start();
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
   * Start the session, if it is not started already
   */
  private function start() {
    if (session_status() == PHP_SESSION_NONE) {
      // NOTE: prevent "headers already sent" errors in phpunit tests
      if (!headers_sent()) {
        session_start();
      }
    }
  }

  /**
   * Get the cookie prefix
   * @return String
   */
  protected function getCookiePrefix() {
    return $this->cookiePrefix;
  }

  /**
   * Get the cookie name
   * @return String
   */
  private function getCookieName() {
    return $this->cookiePrefix.'-session';
  }
}