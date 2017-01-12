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

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\ValidationData;
use wcmf\lib\config\Configuration;
use wcmf\lib\core\Session;
use wcmf\lib\presentation\Request;
use wcmf\lib\security\principal\impl\AnonymousUser;
use wcmf\lib\util\StringUtil;
use wcmf\lib\util\URIUtil;

/**
 * ClientSideSession has no server state as it stores the data in cookies.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ClientSideSession implements Session {

  const TOKEN_HEADER = 'Authorization';
  const AUTH_TYPE = 'Bearer';
  const AUTH_USER_NAME = 'user';

  private $cookiePrefix = '';

  private $token = null;
  private $key = null;

  /**
   * Constructor
   * @param $configuration
   * @param $request
   */
  public function __construct(Configuration $configuration, Request $request) {
    $this->cookiePrefix = strtolower(StringUtil::slug($configuration->getValue('title', 'application')));
    $this->key = $configuration->getValue('secret', 'application');

    // check for token in request
    $this->token = $request->hasHeader(self::TOKEN_HEADER) ?
      trim(str_replace(self::AUTH_TYPE, '', $request->getHeader(self::TOKEN_HEADER))) : null;
  }

  /**
   * @see Session::getID()
   */
  public function getID() {
    return null;
  }

  /**
   * @see Session::get()
   */
  public function get($key, $default=null) {
    $value = $default;
    if (isset($_COOKIE[$key])) {
      $value = $_COOKIE[$key];
    }
    return $value;
  }

  /**
   * @see Session::set()
   */
  public function set($key, $value) {
    if (!headers_sent()) {
      setcookie($key, $value, 0, '/', '', URIUtil::isHttps(), false);
    }
    $_COOKIE[$key] = $value;
  }

  /**
   * @see Session::remove()
   */
  public function remove($key) {
    if (!headers_sent()) {
      setcookie($key, false, 0, '/', '', URIUtil::isHttps(), false);
    }
    unset($_COOKIE[$key]);
  }

  /**
   * @see Session::exist()
   */
  public function exist($key) {
    $result = isset($_COOKIE[$key]);
    return $result;
  }

  /**
   * @see Session::clear()
   */
  public function clear() {
    foreach(array_keys($_COOKIE) as $key) {
      $this->remove($key);
    }
  }

  /**
   * @see Session::destroy()
   */
  public function destroy() {
    // TODO invalidate jwt
    $this->clear();
  }

  /**
   * @see Session::setAuthUser()
   */
  public function setAuthUser($login) {
    $token = $this->createToken($login);
    $this->set($this->getCookiePrefix().'-token', $token);
  }

  /**
   * @see Session::getAuthUser()
   */
  public function getAuthUser() {
    $login = AnonymousUser::USER_GROUP_NAME;
    // check for auth user in token
    if (($data = $this->getTokenData()) !== null) {
      $login = $data[self::AUTH_USER_NAME];
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

  /**
   * Create the token for the given login
   * @param $login
   * @return String
   */
  protected function createToken($login) {
    $jwt = (new Builder())
            ->setIssuer($this->getTokenIssuer())
            ->setIssuedAt(time())
            ->setExpiration(time()+3600)
            ->set(self::AUTH_USER_NAME, $login)
            ->sign($this->getTokenSigner(), $this->key)
            ->getToken();
    return $jwt->__toString();
  }

  /**
   * Get the token issuer
   * @return String
   */
  protected function getTokenIssuer() {
    return URIUtil::getProtocolStr().$_SERVER['HTTP_HOST'];
  }

  /**
   * Get the token issuer
   * @return String
   */
  protected function getTokenSigner() {
    return new Sha256();
  }

  /**
   * Get the claims stored in the JWT
   * @return Associative array
   */
  protected function getTokenData() {
    $data = null;
    if ($this->token !== null) {
      $jwt = (new Parser())->parse((string)$this->token);

      // validate
      $data = new ValidationData();
      $data->setIssuer($this->getTokenIssuer());
      if ($jwt->validate($data) && $jwt->verify($this->getTokenSigner(), $this->key)) {
        $data = $jwt->getClaims();
      }
    }
    return $data;
  }
}