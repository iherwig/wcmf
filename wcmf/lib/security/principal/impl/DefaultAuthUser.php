<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2009 wemove digital solutions GmbH
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
 *
 * $Id$
 */
namespace wcmf\lib\security\principal\impl;

use \ReflectionClass;

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\Criteria;
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
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $userManager = ObjectFactory::getInstance('userManager');
    $userType = $userManager->getUserType();
    $user = $persistenceFacade->loadFirstObject($userType, BuildDepth::SINGLE,
                  array(
                      new Criteria($userType, 'login', '=', $login)
                  ), null);

    // check if user exists
    $loginOk = false;
    if ($user != null) {
      $uRC = new ReflectionClass($user);
      if ($uRC->implementsInterface('wcmf\lib\security\principal\User')) {
        // check password
        $loginOk = $userManager->verifyPassword($password, $user->getPassword());
        if ($loginOk) {
          // login succeeded, store the user instance
          $this->_user = clone $user;

          // load user config initially
          $userConfig = $this->getConfig();
          if (strlen($userConfig) > 0) {
            $config->addConfiguation($userConfig);
          }

          // add policies
          $policies = $config->getSection('authorization');
          $this->addPolicies($policies);
          $this->_login_time = strftime("%c", time());
        }
      }
      else {
        throw new ConfigurationException($userType.' does not implement wcmf\lib\security\principal\User');
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
    return $val['default'];
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
      $this->_user->getUser($login, $password);
    }
  }

  /**
   * @see User::getUserId()
   */
  public function getUserId() {
    if ($this->_user != null) {
      $this->_user->getUserId();
    }
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
      $this->_user->getPassword();
    }
  }

  /**
   * @see User::setName()
   */
  public function setName($name) {
    if ($this->_user != null) {
      $this->_user->setName($name);
    }
  }

  /**
   * @see User::getName()
   */
  public function getName() {
    if ($this->_user != null) {
      $this->_user->getName();
    }
  }

  /**
   * @see User::setFirstname()
   */
  public function setFirstname($firstname) {
    if ($this->_user != null) {
      $this->_user->setFirstname($firstname);
    }
  }

  /**
   * @see User::getFirstname()
   */
  public function getFirstname() {
    if ($this->_user != null) {
      $this->_user->getFirstname();
    }
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
      $this->_user->getConfig();
    }
  }

  /**
   * @see User::addRole()
   */
  public function addRole($rolename) {
    if ($this->_user != null) {
      $this->_user->addRole($rolename);
    }
  }

  /**
   * @see User::removeRole()
   */
  public function removeRole($rolename) {
    if ($this->_user != null) {
      $this->_user->removeRole($rolename);
    }
  }

  /**
   * @see User::hasRole()
   */
  public function hasRole($rolename) {
    if ($this->_user != null) {
      $this->_user->hasRole($rolename);
    }
  }

  /**
   * @see User::getRoles()
   */
  public function getRoles() {
    if ($this->_user != null) {
      $this->_user->getRoles();
    }
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
   * Delegate everything else to internal User instance
   */
  public function __call($name, array $arguments) {
    if ($this->_user != null) {
      return call_user_func_array(array($this->_user, $name), $arguments);
    }
  }
}
?>
