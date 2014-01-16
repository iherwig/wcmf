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
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\ValidationException;
use wcmf\lib\security\principal\User;

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
  private static $_roleTypeName = null;
  private static $_roleRelationNames = null;

  /**
   * Constructor
   */
  public function __construct(ObjectId $oid=null) {
    parent::__construct($oid);

    // set role config
    if (self::$_roleConfig == null) {
      // load role config if existing
      $config = ObjectFactory::getConfigurationInstance();
      if (($roleConfig = $config->getSection('roleconfig')) !== false) {
        self::$_roleConfig = $roleConfig;
      }
    }
    // initialize role relation definition
    if (self::$_roleTypeName == null) {
      self::$_roleTypeName = ObjectFactory::getInstance('Role')->getType();
      self::$_roleRelationNames = array();
      $mapper = $this->getMapper();
      foreach ($mapper->getRelationsByType(self::$_roleTypeName) as $relation) {
        self::$_roleRelationNames[] = $relation->getOtherRole();
      }
    }
  }

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
    if (!$this->_hasOwnRolesLoaded) {
      // make sure that the roles are loaded

      // allow this in any case (prevent infinite loops when trying to authorize)
      $permissionManager = ObjectFactory::getInstance('permissionManager');
      $isAnonymous = $permissionManager->isAnonymous();
      if (!$isAnonymous) {
        $permissionManager->deactivate();
      }
      if (self::$_roleRelationNames) {
        foreach (self::$_roleRelationNames as $roleName) {
          $this->loadChildren($roleName);
        }
      }
      // reactivate the PermissionManager if necessary
      if (!$isAnonymous) {
        $permissionManager->activate();
      }
      $this->_hasOwnRolesLoaded = true;
    }
    // TODO add role nodes from addedNodes array
    return $this->getChildrenEx(null, null, self::$_roleTypeName, null);
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
      foreach ($this->getAddedNodes() as $role => $nodes) {
        if (in_array($role, self::$_roleRelationNames)) {
          foreach ($nodes as $role) {
            $rolename = $role->getName();
            if (self::$_roleConfig && isset(self::$_roleConfig[$rolename])) {
              $this->setConfig(self::$_roleConfig[$rolename]);
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
      $user = self::getByLogin($value);
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
}
?>
