<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
 *
 * Licensed under the terms of any of the following licenses
 * at your choice:
 *
 * - GNU Lesser General Public License (LGPL)
 *   http://www.gnu.org/licenses/lgpl.html
 * - Eclipse Public License (EPL)
 *   http://www.eclipse.org/org/documents/epl-v10.php
 *
 * See the license.txt file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\security\principal\impl;

use wcmf\lib\core\ObjectFactory;
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

    // because there is no authorized user already, we propably have to deactivate the
    // PermissionManager for this operation to allow user retrieval from the persistent storage
    $permissionManager = ObjectFactory::getInstance('permissionManager');
    $isAnonymous = $permissionManager->isAnonymous();
    if (!$isAnonymous) {
      $permissionManager->deactivate();
    }
    // try to receive the user with given credentials
    $userTypeInst = ObjectFactory::getInstance('User');
    $user = $userTypeInst::getByLogin($login);

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

        // add policies
        $policies = $config->getSection('authorization');
        $this->addPolicies($policies);
        $this->_login_time = strftime("%c", time());
      }
    }

    // reactivate the PermissionManager if necessary
    if (!$isAnonymous) {
      $permissionManager->activate();
    }
    return $loginOk;
  }

  /**
   * @see AuthUser::authorize()
   */
  public function authorize($actionKey) {
    if ($actionKey == '') {
      return $this->_defaulPolicy;
    }
    if (isset($this->_policies[$actionKey])) {
      return $this->matchRoles($this->_policies[$actionKey]);
    }
    return $this->_defaulPolicy;
  }

  /**
   * @see AuthUser::setDefaultPolicy()
   */
  public function setDefaultPolicy($val) {
    $this->_defaulPolicy = $val;
  }

  /**
   * @see AuthUser::getLoginTime()
   */
  public function getLoginTime() {
    return $this->_login_time;
  }

  /**
   * Adds one ore more policies to the policy repository of the user.
   * @param policies An associative array with the policy information
   *    (key=action, value=policy string).
   * @note A policy string looks like this "+*, -guest, +admin"
   */
  protected function addPolicies(array $policies) {
    foreach ($policies AS $key => $value) {
      if (!isset($this->_policies[$key])) {
        $parsedPolicies = Policy::parse($value);
        $this->_policies[$key] = $parsedPolicies;
      }
    }
  }

  /**
   * Matches the roles of the user and the roles for a certain key
   * @param val An array containing policy information as an associative array
   *     with the keys ('default', 'allow', 'deny'). Where 'allow', 'deny' are arrays
   *     itselves holding roles. 'allow' overwrites 'deny' overwrites 'default'
   * @return Boolean whether the user has access right according to this policy.
   */
  protected function matchRoles($val) {
    if (isset($val['allow'])) {
      foreach ($val['allow'] as $value) {
        if ($this->hasRole($value)) {
          return true;
        }
      }
    }
    if (isset($val['deny'])) {
      foreach ($val['deny'] as $value) {
        if ($this->hasRole($value)) {
          return false;
        }
      }
    }
    return isset($val['default']) ? $val['default'] : false;
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
   * @see User::resetRoleCache()
   */
  public function resetRoleCache() {
    if ($this->_user != null) {
      $this->_user->resetRoleCache();
    }
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
