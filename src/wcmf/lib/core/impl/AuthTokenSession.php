<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2018 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\core\impl;

use wcmf\lib\config\Configuration;
use wcmf\lib\core\TokenBasedSession;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\security\principal\impl\AnonymousUser;
use wcmf\lib\util\URIUtil;

/**
 * AuthTokenSession is a DefaultSession, but additionally requires
 * clients to send a token in the X-Auth-Token request header (Double Submit Cookie).
 * The token is created, when the authenticated user is associated
 * with the session and send to the client in a cookie named
 * <em>application-title</em>-token.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class AuthTokenSession extends DefaultSession implements TokenBasedSession {

  const TOKEN_HEADER = 'X-Auth-Token';

  private $tokenName = '';
  private $isTokenValid = false;

  /**
   * Constructor
   * @param $configuration
   */
  public function __construct(Configuration $configuration) {
    parent::__construct($configuration);

    $this->tokenName = $this->getCookiePrefix().'-auth-token';
  }

  /**
   * @see TokenBasedSession::getHeaderName()
   */
  public function getHeaderName() {
    return self::TOKEN_HEADER;
  }

  /**
   * @see TokenBasedSession::getCookieName()
   */
  public function getCookieName() {
    return $this->tokenName;
  }

  /**
   * @see Session::setAuthUser()
   */
  public function setAuthUser($login) {
    parent::setAuthUser($login);

    // set auth-token cookie for authenticated user
    if ($login !== AnonymousUser::USER_GROUP_NAME) {
      // generate a token, set it in the session and send it to the client
      $token = base64_encode(openssl_random_pseudo_bytes(32));
      $this->isTokenValid = true;
      // NOTE: prevent "headers already sent" errors in phpunit tests
      if (!headers_sent()) {
        setcookie($this->tokenName, $token, 0, '/', '', URIUtil::isHttps(), true);
      }
    }
  }

  /**
   * @see Session::getAuthUser()
   */
  public function getAuthUser() {
    $login = parent::getAuthUser();
    return $this->isTokenValid() ? $login : AnonymousUser::USER_GROUP_NAME;
  }

  /**
   * Check if the request contains a valid token
   */
  protected function isTokenValid() {
    if ($this->isTokenValid) {
      // already validated
      return true;
    }
    $request = ObjectFactory::getInstance('request');
    $token = $request->hasHeader(self::TOKEN_HEADER) ? $request->getHeader(self::TOKEN_HEADER) :
        $request->getValue(self::TOKEN_HEADER, null);
    $this->isTokenValid = $token != null && $token == $_COOKIE[$this->tokenName];
    return $this->isTokenValid;
  }
}