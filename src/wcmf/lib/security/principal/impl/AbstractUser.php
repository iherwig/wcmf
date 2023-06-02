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

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\Node;
use wcmf\lib\security\principal\PasswordService;
use wcmf\lib\security\principal\Role;
use wcmf\lib\security\principal\User;
use wcmf\lib\validation\ValidationException;

/**
 * Default implementation of a user that is persistent.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class AbstractUser extends Node implements User {

  private ?array $roles = null;

  private static ?array $roleConfig = null;

  /**
   * @see User::setLogin()
   */
  public function setLogin(string $login): void {
    $this->setValue('login', $login);
  }

  /**
   * @see User::getLogin()
   */
  public function getLogin(): string {
    return $this->getValue('login');
  }

  /**
   * @see User::setPassword()
   */
  public function setPassword(string $password): void {
    $this->setValue('password', $password);
  }

  /**
   * @see User::getPassword()
   */
  public function getPassword(): string {
    return $this->getValue('password');
  }

  /**
   * @see User::verifyPassword()
   */
  public function verifyPassword(string $password): bool {
    return PasswordService::verify($password, $this->getPassword());
  }

  /**
   * @see User::setIsActive()
   */
  public function setIsActive(bool $isActive): void {
    $this->setValue('active', $isActive);
  }

  /**
   * @see User::isActive()
   */
  public function isActive(): bool {
    return intval($this->getValue('active')) === 1;
  }

  /**
   * @see User::setIsSuperUser()
   */
  public function setIsSuperUser(bool $isSuperUser): void {
    $this->setValue('super_user', $isSuperUser);
  }

  /**
   * @see User::isSuperUser()
   */
  public function isSuperUser(): bool {
    return intval($this->getValue('super_user')) === 1;
  }

  /**
   * @see User::setConfig()
   */
  public function setConfig(string $config): void {
    $this->setValue('config', $config);
  }

  /**
   * @see User::getConfig()
   */
  public function getConfig(): string {
    return $this->getValue('config');
  }

  /**
   * @see User::hasRole()
   */
  public function hasRole(string $roleName): bool {
    $roles = $this->getRoles();
    for ($i=0, $count=sizeof($roles); $i<$count; $i++) {
      if ($roles[$i]->getName() == $roleName) {
        return true;
      }
    }
    return false;
  }

  /**
   * @see User::getRoles()
   */
  public function getRoles(): array {
    if (!$this->roles) {
      $principalFactory = ObjectFactory::getInstance('principalFactory');
      $this->roles = $principalFactory->getUserRoles($this, true);
    }
    return $this->roles;
  }

  /**
   * @see PersistentObject::beforeInsert()
   */
  public function beforeInsert(): void {
    $this->ensureHashedPassword();
  }

  /**
   * @see PersistentObject::beforeUpdate()
   */
  public function beforeUpdate(): void {
    $this->ensureHashedPassword();
    $this->setRoleConfig();
  }

  /**
   * @see PersistentObject::beforeDelete()
   */
  public function beforeDelete(): void {
    if ($this->isSuperUser()) {
      $message = ObjectFactory::getInstance("message");
      throw new \Exception($message->getText("Super users cannot be deleted"));
    }
  }

  /**
   * Hash password property if not done already.
   */
  protected function ensureHashedPassword(): void {
    // the password is expected to be stored in the 'password' value
    $password = $this->getValue('password');
    if (strlen($password) > 0 && !PasswordService::isHashed($password)) {
      $this->setValue('password', PasswordService::hash($password));
    }
  }

  /**
   * Set the configuration of the currently associated role, if no
   * configuration is set already.
   */
  protected function setRoleConfig(): void {
    if (strlen($this->getConfig()) == 0) {
      // check added nodes for Role instances
      foreach ($this->getAddedNodes() as $relationName => $nodes) {
        foreach ($nodes as $node) {
          if ($node instanceof Role) {
            $roleName = $node->getName();
            $roleConfigs = self::getRoleConfigs();
            if (isset($roleConfigs[$roleName])) {
              $this->setConfig($roleConfigs[$roleName]);
              break;
            }
          }
        }
      }
    }
  }

  /**
   * @see PersistentObject::setValue()
   */
  public function setValue(string $name, $value, bool $forceSet=false, bool $trackChange=true): bool {
    // prevent overwriting the password with an empty value
    // the password is expected to be stored in the 'password' value
    if (!($name == 'password' && strlen(trim($value)) == 0)) {
      return parent::setValue($name, $value, $forceSet, $trackChange);
    }
    return false;
  }

  /**
   * @see PersistentObject::validateValue()
   */
  public function validateValue(string $name, $value): void {
    parent::validateValue($name, $value);
    $message = ObjectFactory::getInstance('message');

    // validate the login property
    if ($name == 'login') {
      if (strlen(trim($value)) == 0) {
        throw new ValidationException($name, $value, $message->getText("The user requires a login name"));
      }
      if ($value == AnonymousUser::NAME) {
        throw new ValidationException($name, $value, $message->getText("The login '%0%' is not allowed",
                [AnonymousUser::NAME]));
      }
      $principalFactory = ObjectFactory::getInstance('principalFactory');
      $user = $principalFactory->getUser($value);
      if ($user != null && $user->getLogin() == $value && $user->getOID() != $this->getOID()) {
        throw new ValidationException($name, $value, $message->getText("The login '%0%' already exists", [$value]));
      }
    }

    // validate the password property if the user is newly created
    if ($name == 'password') {
      if ($this->getState() == self::STATE_NEW && strlen(trim($value)) == 0) {
        throw new ValidationException($name, $value, $message->getText("The password can't be empty"));
      }
    }
  }

  /**
   * Get the role configurations from the application configuration
   * @return array with role names as keys and config file names as values
   */
  protected static function getRoleConfigs(): array {
    if (self::$roleConfig == null) {
      // load role config if existing
      $config = ObjectFactory::getInstance('configuration');
      if (($roleConfig = $config->getSection('roleconfig')) !== false) {
        self::$roleConfig = $roleConfig;
      }
    }
    return self::$roleConfig;
  }

  /**
   * Get the currently authenticated user
   * @return User
   */
  protected function getAuthUser(): User {
    $principalFactory = ObjectFactory::getInstance('principalFactory');
    $session = ObjectFactory::getInstance('session');
    return $principalFactory->getUser($session->getAuthUser());
  }
}
?>
