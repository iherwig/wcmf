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
use wcmf\lib\core\impl\DefaultSession;
use wcmf\lib\presentation\Request;
use wcmf\lib\security\principal\impl\AnonymousUser;

/**
 * AuthTokenSession is a DefaultSession, but additionally requires
 * clients to send a token in the X-Auth-Token request header.
 * The token is created, when the authenticated user is associated
 * with the session and send to the client in a cookie named auth-token.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class AuthTokenSession extends DefaultSession implements Session {

  const TOKEN_NAME = 'auth-token';
  const TOKEN_HEADER = 'X-Auth-Token';

  private $isTokenValid = false;

  /**
   * Constructor
   * @param $request
   */
  public function __construct(Request $request) {
    parent::__construct();

    // check for valid auth-token in request
    $this->isTokenValid = $request->hasHeader(self::TOKEN_HEADER) &&
      $request->getHeader(self::TOKEN_HEADER) == $this->get(self::TOKEN_NAME);
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
      $this->set(self::TOKEN_NAME, $token);
      $domain = ($_SERVER['HTTP_HOST'] != 'localhost') ? $_SERVER['HTTP_HOST'] : false;
      setcookie(self::TOKEN_NAME, $token, 0, '/', $domain, false, false);
      $this->isTokenValid = true;
    }
  }

  /**
   * @see Session::getAuthUser()
   */
  public function getAuthUser() {
    $login = parent::getAuthUser();
    return $this->isTokenValid ? $login : AnonymousUser::USER_GROUP_NAME;
  }
}