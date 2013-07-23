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

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\Node;
use wcmf\lib\i18n\Message;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\Criteria;
use wcmf\lib\persistence\ValidationException;
use wcmf\lib\security\principal\User;
use wcmf\lib\security\principal\Role;

require_once(WCMF_BASE."wcmf/vendor/password_compat/lib/password.php");

/**
 * Default implementation of a user.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class AbstractUser extends Node implements User {

  private $_cachedRoles = array();
  private $_hasOwnRolesLoaded = false;

  private static $_roleConfig = null;

  /**
   * @see User::getUserId()
   */
  public function getUserId() {
    return $this->getOID()->getFirstId();
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
   * @see User::addRole()
   */
  public function addRole(Role $role) {
    $rolename = $role->getName();
    if ($this->hasRole($rolename)) {
      return;
    }
    $this->addNode($role);

    // set role config
    if (self::$_roleConfig == null) {
      // load role config if existing
      $config = ObjectFactory::getConfigurationInstance();
      if (($roleConfig = $config->getSection('roleconfig')) !== false) {
        self::$_roleConfig = $roleConfig;
      }
    }
    if (self::$_roleConfig && isset(self::$_roleConfig[$rolename])) {
      $this->setConfig(self::$_roleConfig[$rolename]);
    }
  }

  /**
   * @see User::removeRole()
   */
  public function removeRole($rolename) {
    if (!$this->hasRole($rolename)) {
      return;
    }
    // remove the role if existing
    $role = $this->getRoleByName($rolename);
    if ($role != null) {
      $this->deleteNode($role);
    }
  }

  /**
   * @see User::hasRole()
   */
  public function hasRole($rolename) {
    $roles = $this->getRoles();
    for ($i=0; $i<sizeof($roles); $i++) {
      if ($roles[$i]->getName() == $rolename) {
        return true;
      }
    }
    return false;
  }

  /**
   * @see User::getRoles()
   */
  public function getRoles() {
    $roleTypeName = ObjectFactory::getInstance('Role')->getType();
    if (!$this->_hasOwnRolesLoaded) {
      // make sure that the roles are loaded

      // allow this in any case (prevent infinite loops when trying to authorize)
      $permissionManager = ObjectFactory::getInstance('permissionManager');
      $isAnonymous = $permissionManager->isAnonymous();
      if (!$isAnonymous) {
        $permissionManager->deactivate();
      }
      $mapper = $this->getMapper();
      foreach ($mapper->getRelationsByType($roleTypeName) as $relation) {
        $this->loadChildren($relation->getOtherRole());
      }
      // reactivate the PermissionManager if necessary
      if (!$isAnonymous) {
        $permissionManager->activate();
      }
      $this->_hasOwnRolesLoaded = true;
    }
    return $this->getChildrenEx(null, null, $roleTypeName, null);
  }

  /**
   * Get a Role instance whose name is given
   * @param rolename The name of the role
   * @return A reference to the role or null if nor existing
   */
  protected function getRoleByName($rolename) {
    if (!isset($this->_cachedRoles[$rolename])) {
      // load the role
      $roleType = ObjectFactory::getInstance('Role');
      $role = $roleType::getByName($rolename);
      if ($role != null) {
        $this->_cachedRoles[$rolename] = $role;
      }
      else {
        return null;
      }
    }
    return $this->_cachedRoles[$rolename];
  }

  /**
   * @see User::resetRoleCache()
   */
  public function resetRoleCache() {
    $this->_cachedRoles = array();
    $this->_hasOwnRolesLoaded = false;
  }


  /**
   * @see User::getByLogin()
   */
  public static function getByLogin($login) {
    $userTypeName = ObjectFactory::getInstance('User')->getType();
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $user = $persistenceFacade->loadFirstObject($userTypeName, BuildDepth::SINGLE,
                array(
                    new Criteria($userTypeName, 'login', '=', $login)
                ), null);
    return $user;
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
      $user = self::getByLogin($value);
      if ($user != null && $user->getOID() != $this->getOID()) {
        throw new ValidationException(Message::get("The login '%0%' already exists", array($value)));
      }
    }
  }
}
?>
