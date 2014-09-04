<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\security\principal\impl;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\i18n\Message;
use wcmf\lib\model\Node;
use wcmf\lib\persistence\ValidationException;
use wcmf\lib\security\principal\Role;
use wcmf\lib\security\principal\User;

/**
 * Default implementation of a user that is persistent.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class AbstractUser extends Node implements User {

  private $_roles = null;

  private static $_roleConfig = null;

  /**
   * @see User::setLogin()
   */
  public function setLogin($login) {
    $this->setValue('login', $login);
  }

  /**
   * @see User::getLogin()
   */
  public function getLogin() {
    return $this->getValue('login');
  }

  /**
   * @see User::setPassword()
   */
  public function setPassword($password) {
    $this->setValue('password', $password);
  }

  /**
   * @see User::getPassword()
   */
  public function getPassword() {
    return $this->getValue('password');
  }

  /**
   * @see User::hashPassword()
   */
  public function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
  }

  /**
   * @see User::verifyPassword()
   */
  public function verifyPassword($password, $passwordHash) {
    return password_verify($password, $passwordHash);
  }

  /**
   * @see User::setConfig()
   */
  public function setConfig($config) {
    $this->setValue('config', $config);
  }

  /**
   * @see User::getConfig()
   */
  public function getConfig() {
    return $this->getValue('config');
  }

  /**
   * @see User::hasRole()
   */
  public function hasRole($roleName) {
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
  public function getRoles() {
    if (!$this->_roles) {
      $principalFactory = ObjectFactory::getInstance('principalFactory');
      $this->_roles = $principalFactory->getUserRoles($this, true);
    }
    return $this->_roles;
  }

  /**
   * @see PersistentObject::beforeInsert()
   */
  public function beforeInsert() {
    $this->ensureHashedPassword();
  }

  /**
   * @see PersistentObject::beforeUpdate()
   */
  public function beforeUpdate() {
    $this->ensureHashedPassword();
    $this->setRoleConfig();
  }

  /**
   * Hash password property if not done already.
   */
  protected function ensureHashedPassword() {
    // the password is expected to be stored in the 'password' value
    $password = $this->getValue('password');
    if (strlen($password) > 0) {
      $info = password_get_info($password);
      if ($info['algo'] != PASSWORD_BCRYPT) {
        $this->setValue('password', $this->hashPassword($password));
      }
    }
  }

  /**
   * Set the configuration of the currently associated role, if no
   * configuration is set already.
   */
  protected function setRoleConfig() {
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
  public function setValue($name, $value, $forceSet=false, $trackChange=true) {
    // prevent overwriting the password with an empty value
    // the password is expected to be stored in the 'password' value
    if (!($name == 'password' && strlen(trim($value)) == 0)) {
      parent::setValue($name, $value, $forceSet, $trackChange);
    }
  }

  /**
   * @see PersistentObject::validateValue()
   */
  public function validateValue($name, $value) {
    parent::validateValue($name, $value);

    // validate the login property
    // the login is expected to be stored in the 'login' value
    if ($name == 'login') {
      if (strlen(trim($value)) == 0) {
        throw new ValidationException(Message::get("The user requires a login name"));
      }
      $principalFactory = ObjectFactory::getInstance('principalFactory');
      $user = $principalFactory->getUser($value);
      if ($user != null && $user->getOID() != $this->getOID()) {
        throw new ValidationException(Message::get("The login '%0%' already exists", array($value)));
      }
    }

    // validate the password property if the user is newly created
    // the password is expected to be stored in the 'password' value
    if ($name == 'password') {
      if ($this->getState() == self::STATE_NEW && strlen(trim($value)) == 0) {
        throw new ValidationException(Message::get("The password can't be empty"));
      }
    }
  }

  /**
   * Get the role configurations from the application configuration
   * @return Array with role names as keys and config file names as values
   */
  protected static function getRoleConfigs() {
    if (self::$_roleConfig == null) {
      // load role config if existing
      $config = ObjectFactory::getConfigurationInstance();
      if (($roleConfig = $config->getSection('roleconfig')) !== false) {
        self::$_roleConfig = $roleConfig;
      }
    }
    return self::$_roleConfig;
  }
}
?>
