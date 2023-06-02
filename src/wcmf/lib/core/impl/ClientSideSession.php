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

use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Configuration as JwtConfiguration;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use wcmf\lib\config\Configuration;
use wcmf\lib\core\ObjectFactory;
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

  private string $cookiePrefix = '';
  private string $tokenName = '';
  private string $tokenIssuer = '';

  private ?JwtConfiguration $tokenConfig = null;

  private ?string $token = null;

  /**
   * Constructor
   * @param $configuration
   */
  public function __construct(Configuration $configuration) {
    $this->cookiePrefix = strtolower(StringUtil::slug($configuration->getValue('title', 'application')));
    $this->tokenName = $this->getCookiePrefix().'-auth-token';
    $this->tokenIssuer = URIUtil::getProtocolStr().$_SERVER['HTTP_HOST'];
    $this->tokenConfig = JwtConfiguration::forSymmetricSigner(
        new Sha256(),
        InMemory::plainText($configuration->getValue('secret', 'application'))
    );
    $this->tokenConfig->setValidationConstraints(
        new IssuedBy($this->tokenIssuer),
        new LooseValidAt(SystemClock::fromSystemTimezone()),
        new SignedWith($this->tokenConfig->signer(), $this->tokenConfig->signingKey()),
    );
  }

  /**
   * @see TokenBasedSession::getHeaderName()
   */
  public function getHeaderName(): string {
    return self::TOKEN_HEADER;
  }

  /**
   * @see TokenBasedSession::getCookieName()
   */
  public function getCookieName(): string {
    return $this->tokenName;
  }

  /**
   * @see Session::isStarted()
   */
  public function isStarted(): bool {
    return sizeof(array_filter(array_keys($_COOKIE), function($key) {
      return strpos($key, $this->getCookiePrefix()) === 0;
    })) > 0;
  }

  /**
   * @see Session::getID()
   */
  public function getID(): string {
    return '';
  }

  /**
   * @see Session::get()
   */
  public function get(string $key, mixed $default=null): mixed {
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
  public function set(string $key, mixed $value): void {
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
  public function remove(string $key): void {
    if (!headers_sent()) {
      setcookie($key, false, 0, '/', '', URIUtil::isHttps(), true);
    }
    unset($_COOKIE[$key]);
  }

  /**
   * @see Session::exist()
   */
  public function exist(string $key): bool {
    $result = isset($_COOKIE[$key]);
    return $result;
  }

  /**
   * @see Session::clear()
   */
  public function clear(): void {
    foreach(array_keys($_COOKIE) as $key) {
      $this->remove($key);
    }
  }

  /**
   * @see Session::destroy()
   */
  public function destroy(): void {
    // TODO invalidate jwt
    $this->clear();
  }

  /**
   * @see Session::setAuthUser()
   */
  public function setAuthUser(string $login): void {
    $this->token = $this->createToken($login);
    $this->set($this->tokenName, $this->token);
  }

  /**
   * @see Session::getAuthUser()
   */
  public function getAuthUser(): string {
    $login = AnonymousUser::NAME;
    // check for auth user in token
    if (($data = $this->getTokenData()) !== null && isset($data[self::AUTH_USER_NAME])) {
      $login = $data[self::AUTH_USER_NAME]->getValue();
    }
    return $login;
  }

  /**
   * Get the cookie prefix
   * @return string
   */
  protected function getCookiePrefix(): string {
    return $this->cookiePrefix;
  }

  /**
   * Create the token for the given login
   * @param string $login
   * @return string
   */
  protected function createToken(string $login): string {
    $now = new \DateTimeImmutable('now', new \DateTimeZone(date_default_timezone_get()));
    $exp = $now->add(\DateInterval::createFromDateString('1 hour'));
    $jwt = (new Builder())
            ->issuedBy($this->tokenIssuer)
            ->issuedAt($now)
            ->expiresAt($exp)
            ->withClaim(self::AUTH_USER_NAME, $login)
            ->getToken($this->tokenConfig->signer(), $this->tokenConfig->signingKey());
    return $jwt->__toString();
  }

  /**
   * Get the claims stored in the JWT
   * @return array
   */
  protected function getTokenData(): array {
    $result = null;

    $request = ObjectFactory::getInstance('request');
    $token = $request->hasHeader(self::TOKEN_HEADER) ?
        trim(str_replace(self::AUTH_TYPE, '', $request->getHeader(self::TOKEN_HEADER))) : $this->token;
    if ($token !== null) {
      $jwt = (new Parser())->parse((string)$token);

      // validate
      if ($this->tokenConfig->validator()->validate($jwt)) {
        $result = $jwt->headers()->all();
      }
    }
    return $result;
  }

  /**
   * Serialize a value to be used in a cookie
   * @param mixed $value
   * @return string
   */
  protected function serializeValue(mixed $value): string {
    return json_encode($value);
  }

  /**
   * Unserialize a value used in a cookie
   * @param string $value
   * @return mixed
   */
  protected function unserializeValue(string $value): mixed {
    return json_decode($value, true);
  }
}