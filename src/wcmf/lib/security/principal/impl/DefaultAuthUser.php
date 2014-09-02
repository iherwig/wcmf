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
use wcmf\lib\persistence\PersistenceAction;
use wcmf\lib\security\Policy;
use wcmf\lib\security\principal\AuthUser;

/**
 * Default AuthUser implementation. The class holds an internal User
 * User that is retrieved after login and delegates most of the
 * functionality to that.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultAuthUser implements AuthUser {

  private $_login_time = "";
  private $_policies = array();
  private $_defaulPolicy = true;
  private $_user = null;

  /**
   * @see AuthUser::login()
   */
  public function login($login, $password) {
    $config = ObjectFactory::getConfigurationInstance();
    $userTypeInst = ObjectFactory::getInstance('User');

    // because there is no authorized user already, we have to add a temporary permission to the
    // PermissionManager for this operation to allow user retrieval from the persistent storage
    $permissionManager = ObjectFactory::getInstance('permissionManager');
    $permissionManager->addTempPermission($userTypeInst->getType(), '', PersistenceAction::READ);
    // try to receive the user with given credentials
    $user = $userTypeInst::getByLogin($login);
    // remove the temporary permission
    $permissionManager->removeTempPermission($userTypeInst->getType(), '', PersistenceAction::READ);

    // check if user exists
    $loginOk = false;
    if ($user != null) {
      // check password
      $loginOk = $user->verifyPassword($password, $user->getPassword());
      if ($loginOk) {
        // login succeeded, store the user instance
        $this->_user = clone $user;

        // load user config initially
        $userConfig = $this->getConfig();
        if (strlen($userConfig) > 0) {
          $config->addConfiguration($userConfig);
        }

        $this->_login_time = strftime("%c", time());
      }
    }

    return $loginOk;
  }

  /**
   * @see AuthUser::setDefaultPolicy()
   */
  public function setDefaultPolicy($val) {
    $this->_defaulPolicy = $val;
  }

  /**
   * @see AuthUser::getDefaultPolicy()
   */
  public function getDefaultPolicy() {
    return $this->_defaulPolicy;
  }

  /**
   * @see AuthUser::getLoginTime()
   */
  public function getLoginTime() {
    return $this->_login_time;
  }

  /**
   * Implementation of abstract base class methods.
   * Delegates to internal user instance.
   */

  /**
   * @see User::getUser()
   */
  public function getUser($login, $password) {
    if ($this->_user != null) {
      return $this->_user->getUser($login, $password);
    }
    return null;
  }

  /**
   * @see User::getUserId()
   */
  public function getUserId() {
    if ($this->_user != null) {
      return $this->_user->getUserId();
    }
    return null;
  }

  /**
   * @see User::setLogin()
   */
  public function setLogin($login) {
    if ($this->_user != null) {
      $this->_user->setLogin($login);
    }
  }

  /**
   * @see User::getLogin()
   */
  public function getLogin() {
    if ($this->_user != null) {
      return $this->_user->getLogin();
    }
    return null;
  }

  /**
   * @see User::setPassword()
   */
  public function setPassword($password) {
    if ($this->_user != null) {
      $this->_user->setPassword($password);
    }
  }

  /**
   * @see User::getPassword()
   */
  public function getPassword() {
    if ($this->_user != null) {
      return $this->_user->getPassword();
    }
    return null;
  }

  /**
   * @see User::hashPassword
   */
  public function hashPassword($password) {
    if ($this->_user != null) {
      return $this->_user->hashPassword($password);
    }
    return $password;
  }

  /**
   * @see User::verifyPassword
   */
  public function verifyPassword($password, $passwordHash) {
    if ($this->_user != null) {
      return $this->_user->verifyPassword($password, $passwordHash);
    }
    return false;
  }

  /**
   * @see User::setConfig()
   */
  public function setConfig($config) {
    if ($this->_user != null) {
      $this->_user->setConfig($config);
    }
  }

  /**
   * @see User::getConfig()
   */
  public function getConfig() {
    if ($this->_user != null) {
      return $this->_user->getConfig();
    }
    return null;
  }

  /**
   * @see User::hasRole()
   */
  public function hasRole($rolename) {
    if ($this->_user != null) {
      return $this->_user->hasRole($rolename);
    }
    return false;
  }

  /**
   * @see User::getRoles()
   */
  public function getRoles() {
    if ($this->_user != null) {
      return $this->_user->getRoles();
    }
    return array();
  }

  /**
   * @see User::getByLogin()
   */
  public static function getByLogin($login) {
    if ($this->_user != null) {
      $this->_user->getByLogin($login);
    }
    return null;
  }

  /**
   * Delegate everything else to internal User instance
   */
  public function __call($name, array $arguments) {
    if ($this->_user != null) {
      return call_user_func_array(array($this->_user, $name), $arguments);
    }
    return null;
  }
}
?>
