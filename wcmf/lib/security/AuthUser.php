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
namespace wcmf\lib\security;

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\config\InifileParser;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\PersistenceFacade;
use wcmf\lib\persistence\PersistentObject;
use wcmf\lib\security\RightsManager;
use wcmf\lib\security\User;
use wcmf\lib\security\UserManager;

/**
 * AuthUser provides a storage and methods for user data used for
 * authentication/authorization purposes.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class AuthUser extends User {

  private $_login_time = "";
  private $_policies = array();
  private $_defaulPolicy = true;
  private $_user = null;

  /**
   * Log a user into the application.
   * @param login The login string of the user
   * @param password The password string of the user
   * @param isPasswordEncrypted True/False wether the password is encrypted or not [default: false]
   * @return True/False whether login succeeded.
   */
  public function login($login, $password, $isPasswordEncrypted=false) {
    $parser = InifileParser::getInstance();

    // encrypt password if not done already
    if (!$isPasswordEncrypted) {
      $password = UserManager::encryptPassword($password);
    }
    // because there is no authorized user already, we propably have to deactivate the
    // RightsManager for this operation to allow user retrieval from the persistent storage
    $rightsManager = RightsManager::getInstance();
    $isAnonymous = $rightsManager->isAnonymous();
    if (!$isAnonymous) {
      $rightsManager->deactivate();
    }
    // try to receive the user with given credentials
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $userInstance = $persistenceFacade->create(UserManager::getUserClassName(), BuildDepth::SINGLE);
    $user = $userInstance->getUser($login, $password);

    // check if user exists
    $loginOk = false;
    if ($user != null) {
      // login succeeded, store the user instance
      $this->_user = clone $user;
      $this->setOID($user->getOID());

      // load user config initially
      $config = $this->getConfig();
      if (strlen($config) > 0) {
        $parser->parseIniFile($GLOBALS['CONFIG_PATH'].$config, true);
      }

      // add policies
      if (($policies = $parser->getSection('authorization')) === false) {
        throw new ConfigurationException($parser->getErrorMsg());
      }
      else {
        $this->addPolicies($policies);
      }
      $this->_login_time = strftime("%c", time());
      $loginOk = true;
    }

    // reactivate the RightsManager if necessary
    if (!$isAnonymous) {
      $rightsManager->activate();
    }
    return $loginOk;
  }

  /**
   * Adds one ore more policies to the policy repository of the user.
   * @param policies An associative array with the policy information (key=action, value=policy string).
   * @note A policy string looks like this "+*, -guest, +admin"
   */
  protected function addPolicies(array $policies) {
    foreach ($policies AS $key => $value) {
      if (!isset($this->_policies[$key])) {
        $parsedPolicies = $this->parsePolicy($value);
        $this->_policies[$key] = $parsedPolicies;
      }
    }
  }

  /**
   * Checks, if the user is authorized for this action.
   * Returns defaulPolicy if action key is not defined.
   * @param actionKey An action key string
   * @return True/False whether authorization succeeded
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
   * Matches the roles of the user and the roles for a certain key
   * @param val An array containing policy information as an associative array with the keys ('default', 'allow', 'deny'). Where
   * 	      'allow', 'deny' are arrays itselves holding roles. 'allow' overwrites 'deny' overwrites 'default'
   * @return True/False whether the user has access right according to this policy.
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
   * Parse an policy string and returns an associative array with the keys ('default', 'allow', 'deny'). Where
   * 'allow', 'deny' are arrays itselves holding roles. 'deny' overwrites 'allow' overwrites 'default'
   * @param val An role string (+*, +admin, -guest, entries without '+' or '-' prefix default to allow rules).
   * @return An array containing the policy data as an associative array with the keys 'default', 'allow', 'deny'.
   */
  protected function parsePolicy($val) {
    $rtn = array();

    $roles = explode(" ", $val);
    foreach ($roles as $value) {
      $value=trim($value);
      if (strlen($value)==2 && substr($value,1,1) == "*") {
        if (substr($value,0,1)=="+") {
          $rtn['default'] = true;
        }
        else if (substr($value,0,1)=="-") {
          $rtn['default'] = false;
        }
      }
      else {
        if (substr($value,0,1)=="+") {
          $rtn['allow'][] = substr($value,1);
        }
        else if (substr($value,0,1)=="-") {
          $rtn['deny'][] = substr($value,1);
        }
        else {
          // entries without '+' or '-' prefix default to allow rules
          $rtn['allow'][] = $value;
        }
      }
    }
    return $rtn;
  }

  /**
   * Assign the default policy.
   * @param val A boolean value.
   */
  public function setDefaultPolicy($val) {
    $this->_defaulPolicy = $val;
  }

  /**
   * Get login time of the user.
   * @return A formatted time string.
   */
  public function getLoginTime() {
    return $this->_login_time;
  }

  /**
   * Get a string representation of the user.
   * @return The string
   */
  public function __toString() {
    if ($this->_user != null) {
      return $this->_user->__toString();
    }
    return "";
  }

  /**
   * Implementation of abstract base class methods.
   * Delegates to internal user instance.
   */

  /**
   * @see PersistentObject::getType()
   */
  function getType() {
    return UserManager::getUserClassName();
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
    return "";
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
    return "";
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
      return $this->_user->getName();
    }
    return "";
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
      return $this->_user->getFirstname();
    }
    return "";
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
    // strip path from config name for compatibility with old versions,
    // where the path was stored
    if ($this->_user != null) {
      return basename($this->_user->getConfig());
    }
    return "";
  }

  /**
   * @see User::addRole()
   */
  public function addRole($rolename) {
    // not supported by AuthUser
  }

  /**
   * @see User::removeRole()
   */
  public function removeRole($rolename) {
    // not supported by AuthUser
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
}
?>
