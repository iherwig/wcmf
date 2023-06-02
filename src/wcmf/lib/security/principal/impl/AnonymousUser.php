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
namespace wcmf\lib\security\principal\impl;

use wcmf\lib\persistence\ObjectId;
use wcmf\lib\security\principal\User;
use wcmf\lib\security\principal\Role;

/**
 * Anonymous user
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class AnonymousUser implements User {

  const NAME = 'anonymous';

  private string $config = '';
  private ?ObjectId $oid = null;
  private static ?Role $role = null;

  /**
   * @see User::getOID()
   */
  public function getOID(): ObjectId {
    if ($this->oid == null) {
      $this->oid = ObjectId::parse(ObjectId::getDummyId());
    }
    return $this->oid;
  }

  /**
   * @see User::setLogin()
   */
  public function setLogin(string $login): void {}

  /**
   * @see User::getLogin()
   */
  public function getLogin(): string {
    return self::NAME;
  }

  /**
   * @see User::setPassword()
   */
  public function setPassword(string $password): void {}

  /**
   * @see User::getPassword()
   */
  public function getPassword(): string {
    return '';
  }

  /**
   * @see User::verifyPassword()
   */
  public function verifyPassword(string $password): bool {
    return false;
  }

  /**
   * @see User::setIsActive()
   */
  public function setIsActive(bool $isActive): void {}

  /**
   * @see User::isActive()
   */
  public function isActive(): bool {
    return true;
  }

  /**
   * @see User::setIsSuperUser()
   */
  public function setIsSuperUser(bool $isSuperUser): void {}

  /**
   * @see User::isSuperUser()
   */
  public function isSuperUser(): bool {
    return false;
  }

  /**
   * @see User::setConfig()
   */
  public function setConfig(string $config): void {
    $this->config = $config;
  }

  /**
   * @see User::getConfig()
   */
  public function getConfig(): string {
    return $this->config;
  }

  /**
   * @see User::hasRole()
   */
  public function hasRole(string $rolename): bool {
    return $rolename == self::NAME;
  }

  /**
   * @see User::getRoles()
   */
  public function getRoles(): array {
    if (self::$role == null) {
      self::$role = new AnonymousRole();
    }
    return [self::$role];
  }
}
?>
