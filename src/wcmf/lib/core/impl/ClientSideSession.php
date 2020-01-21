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

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\ValidationData;
use wcmf\lib\config\Configuration;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\core\Session;
use wcmf\lib\core\TokenBasedSession;
use wcmf\lib\security\principal\impl\AnonymousUser;
use wcmf\lib\util\StringUtil;
use wcmf\lib\util\URIUtil;

/**
 * ClientSideSession has no server state as it stores the data in cookies.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ClientSideSession implements TokenBasedSession {

  const TOKEN_HEADER = 'Authorization';
  const AUTH_TYPE = 'Bearer';
  const AUTH_USER_NAME = 'auth_user';

  private $cookiePrefix = '';
  private $tokenName = '';
  private $token = null;

  private $key = null;

  /**
   * Constructor
   * @param $configuration
   */
  public function __construct(Configuration $configuration) {
    $this->cookiePrefix = strtolower(StringUtil::slug($configuration->getValue('title', 'application')));
    $this->tokenName = $this->getCookiePrefix().'-auth-token';
    $this->key = $configuration->getValue('secret', 'application');
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
   * @see Session::isStarted()
   */
  public function isStarted() {
    return sizeof(array_filter(array_keys($_COOKIE), function($key) {
      return strpos($key, $this->getCookiePrefix()) === 0;
    })) > 0;
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
    if ($key === self::AUTH_USER_NAME) {
      // auth user is stored in the token cookie
      $value = $this->getAuthUser();
    }
    elseif (isset($_COOKIE[$key])) {
      $value = $this->unserializeValue($_COOKIE[$key]);
    }
    return $value;
  }

  /**
   * @see Session::set()
   */
  public function set($key, $value) {
    // don't encode auth token value
    $encodedValue = ($key !== $this->getCookieName())  ? $this->serializeValue($value) : $value;
    if (!headers_sent()) {
      setcookie($key, $encodedValue, 0, '/', '', URIUtil::isHttps(), true);
    }
    $_COOKIE[$key] = $encodedValue;
  }

  /**
   * @see Session::remove()
   */
  public function remove($key) {
    if (!headers_sent()) {
      setcookie($key, false, 0, '/', '', URIUtil::isHttps(), true);
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
    $this->token = $this->createToken($login);
    $this->set($this->tokenName, $this->token);
  }

  /**
   * @see Session::getAuthUser()
   */
  public function getAuthUser() {
    $login = AnonymousUser::USER_GROUP_NAME;
    // check for auth user in token
    if (($data = $this->getTokenData()) !== null && isset($data[self::AUTH_USER_NAME])) {
      $login = $data[self::AUTH_USER_NAME]->getValue();
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
            ->issueBy($this->getTokenIssuer())
            ->issuedAt(time())
            ->expiresAt(time()+3600)
            ->withClaim(self::AUTH_USER_NAME, $login)
            ->getToken($this->getTokenSigner(), $this->key);
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
    $result = null;

    $request = ObjectFactory::getInstance('request');
    $token = $request->hasHeader(self::TOKEN_HEADER) ?
        trim(str_replace(self::AUTH_TYPE, '', $request->getHeader(self::TOKEN_HEADER))) : $this->token;
    if ($token !== null) {
      $jwt = (new Parser())->parse((string)$token);

      // validate
      $data = new ValidationData();
      $data->setIssuer($this->getTokenIssuer());
      if ($jwt->validate($data) && $jwt->verify($this->getTokenSigner(), $this->key)) {
        $result = $jwt->getClaims();
      }
    }
    return $result;
  }

  /**
   * Serialize a value to be used in a cookie
   * @param $value
   * @return String
   */
  protected function serializeValue($value) {
    return json_encode($value);
  }

  /**
   * Unserialize a value used in a cookie
   * @param $value
   * @return String
   */
  protected function unserializeValue($value) {
    return json_decode($value, true);
  }
}