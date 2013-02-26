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
use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\security\Role;
use wcmf\lib\security\User;

/**
 * Some constants describing user properties
 */
define("USER_PROPERTY_LOGIN", "login");
define("USER_PROPERTY_NAME", "name");
define("USER_PROPERTY_FIRSTNAME", "firstname");
define("USER_PROPERTY_CONFIG", "config");
define("ROLE_PROPERTY_NAME", "name");

/**
 * UserManager is used to edit users and roles.
 * UserManager supports the following operations:
 * - create/remove a user
 * - create/remove a role
 * - add/remove a user to/from a role
 * - change a users password
 *
 * This class defines abstract methods that subclasses must implement to support
 * different user repositories. The UserManager implementation class is defined by
 * the configuration key 'UserManager' in the [implementation] section.
 *
 * @todo - Use RightsManager to restrict access to methods (except changePassword)
 *       - Transaction support
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class UserManager {

  /**
   * Associative array mapping configuration files to role names
   */
  private $_roleConfig = null;

  /**
   * Associative array with keys 'users' and 'roles' and
   * array values containing the user and role instances.
   */
  private $_userRepository = null;

  /**
   * The type used as User class.
   */
  private $_userType = null;

  /**
   * The type used as Role class.
   */
  private $_roleType = null;

  /**
   * Creates a UserManager Object.
   */
  public function __construct() {
    // load role config if existing
    $configuration = ObjectFactory::getInstance('configuration');
    if (($roleConfig = $configuration->getSection('roleconfig')) !== false) {
      $this->_roleConfig = $roleConfig;
    }
  }

  /**
   * Set the user type.
   * @param userType
   */
  public function setUserType($userType) {
    $this->_userType = $userType;
  }

  /**
   * Set the role type.
   * @param roleType
   */
  public function setRoleType($roleType) {
    $this->_roleType = $roleType;
  }

  /**
   * Encrypt a password using the md5 algorithm.
   * @param password The password to encrypt
   * @return The encrypted password.
   */
  public static function encryptPassword($password) {
    return md5($password);
  }

  /**
   * Create a user login with a given password.
   * @param name The name of the user
   * @param firstname The first name of the user
   * @param login The login of the user
   * @param password The password of the user
   * @param passwordRepeated The password of the user again
   * @return A reference to the created user.
   */
  public function createUser($name, $firstname, $login, $password, $passwordRepeated) {
    if ($this->_userRepository == null) {
      $this->_userRepository = $this->getUsersAndRoles();
    }

    if ($password != $passwordRepeated) {
      throw new IllegalArgumentException(Message::get("The given passwords don't match"));
    }
    if ($login == '') {
      $this->rollbackTransaction();
      throw new IllegalArgumentException(Message::get("The user requires a login name"));
    }
    if ($this->getUser($login) != null) {
      throw new IllegalArgumentException(Message::get("The login '%1%' already exists", array($login)));
    }
    // encrypt password
    $password = $this->encryptPassword($password);

    $user = $this->createUserImpl($name, $firstname, $login, $password);

    // update user repository
    $this->_userRepository['users'][sizeof($this->_userRepository['users'])] = &$user;

    return $user;
  }

  /**
   * Remove a user login.
   * @param login The login of the user
   */
  public function removeUser($login) {
    if ($this->_userRepository == null) {
      $this->_userRepository = $this->getUsersAndRoles();
    }

    if (($user = $this->getUser($login)) == null) {
      throw new IllegalArgumentException(Message::get("The login '%1%' does not exist", array($login)));
    }
    $this->removeUserImpl($user);

    // update user repository
    for($i=0, $count=sizeof($this->_userRepository['users']); $i<$count; $i++) {
      if ($this->_userRepository['users'][$i]->getLogin() == $login) {
        array_splice($this->_userRepository['users'][$i], $i, 1);
      }
    }
  }

  /**
   * Set a user property.
   * @param login The login of the user
   * @param property One of the USER_PROPERTY constants
   * @param value The value to set this property to
   */
  public function setUserProperty($login, $property, $value) {
    if (!in_array($property, array(USER_PROPERTY_LOGIN, USER_PROPERTY_NAME, USER_PROPERTY_FIRSTNAME, USER_PROPERTY_CONFIG))) {
      throw new IllegalArgumentException("Unknown user property: '%1%'", array($property));
    }
    if (($property == USER_PROPERTY_LOGIN) && ($value == '')) {
      throw new IllegalArgumentException(Message::get("The user requires a login name"));
    }
    if (($property == USER_PROPERTY_LOGIN) && ($this->getUser($value) != null)) {
      throw new IllegalArgumentException(Message::get("The login '%1%' already exists", array($value)));
    }
    if (($user = $this->getUser($login)) == null) {
      throw new IllegalArgumentException(Message::get("The login '%1%' does not exist", array($login)));
    }
    // get the repository user first, because the login may change
    $user = $this->getUser($login);

    // validate the value
    $validationMsg = $user->validateValue($property, $value);
    if (strlen($validationMsg) > 0) {
      $this->onError($validationMsg, __FILE__, __LINE__);
    }
    else {
      $this->setUserPropertyImpl($user, $property, $value);
    }

    // update user repository
    $user->setValue($property, $value);
  }

  /**
   * Reset a users password.
   * @param login The login of the user
   * @param newPassword The new password for the user
   * @param newPasswordRepeated The new password of the user again
   */
  public function resetPassword($login, $newPassword, $newPasswordRepeated) {
    if (($user = $this->getUser($login)) == null) {
      throw new IllegalArgumentException(Message::get("The login '%1%' does not exist", array($login)));
    }

    if ($newPassword != $newPasswordRepeated) {
      throw new IllegalArgumentException(Message::get("The given passwords don't match"));
    }
    // encrypt password
    $newPassword = $this->encryptPassword($newPassword);

    $this->setUserPropertyImpl($user, 'password', $newPassword);

    // update user repository
    $user = $this->getUser($login);
    $user->setPassword($newPassword);
  }

  /**
   * Change a users password.
   * @param login The login of the user
   * @param oldPassword The old password of the user
   * @param newPassword The new password for the user
   * @param newPasswordRepeated The new password of the user again
   */
  public function changePassword($login, $oldPassword, $newPassword, $newPasswordRepeated) {
    if (($user = $this->getUser($login)) == null) {
      throw new IllegalArgumentException(Message::get("The login '%1%' does not exist", array($login)));
    }
    // encrypt password
    $oldPassword = $this->encryptPassword($oldPassword);

    if ($user->getPassword() != $oldPassword) {
      throw new IllegalArgumentException(Message::get("The old password is incorrect"));
    }
    if ($newPassword != $newPasswordRepeated) {
      throw new IllegalArgumentException(Message::get("The given passwords don't match"));
    }
    // encrypt password
    $newPassword = $this->encryptPassword($newPassword);

    $this->setUserPropertyImpl($user, 'password', $newPassword);

    // update user repository
    $user = $this->getUser($login);
    $user->setPassword($newPassword);
  }

  /**
   * Create a role.
   * @param name The name of the role
   * @return A reference to the created role.
   */
  public function createRole($name) {
    if ($this->_userRepository == null) {
      $this->_userRepository = $this->getUsersAndRoles();
    }

    if ($name == '') {
      throw new IllegalArgumentException(Message::get("The role requires a name"));
    }
    if ($this->getRole($name) != null) {
      throw new IllegalArgumentException(Message::get("The role '%1%' already exists", array($name)));
    }
    $role = $this->createRoleImpl($name);

    // update user repository
    $this->_userRepository['roles'][sizeof($this->_userRepository['roles'])] = &$role;

    return $role;
  }

  /**
   * Remove a role.
   * @param name The name of the role
   */
  public function removeRole($name) {
    if ($this->_userRepository == null) {
      $this->_userRepository = $this->getUsersAndRoles();
    }

    if (($role = $this->getRole($name)) == null) {
      throw new IllegalArgumentException(Message::get("The role '%1%' does not exist", array($name)));
    }
    $this->removeRoleImpl($role);

    // update user repository
    for($i=0; $i<sizeof($this->_userRepository['roles']); $i++) {
      if ($this->_userRepository['roles'][$i]->getName() == $name) {
        array_splice($this->_userRepository['roles'][$i], $i, 1);
      }
    }
  }

  /**
   * Set a role property.
   * @param name The name of the role
   * @param property One of the ROLE_PROPERTY constants
   * @param value The value to set this property to
   */
  public function setRoleProperty($name, $property, $value) {
    if (!in_array($property, array(ROLE_PROPERTY_NAME))) {
      throw new IllegalArgumentException(Message::get("Unknown role property: '%1%'", array($property)));
    }
    if (($property == ROLE_PROPERTY_NAME) && ($value == '')) {
      throw new IllegalArgumentException(Message::get("The role requires a name"));
    }
    if (($property == ROLE_PROPERTY_NAME) && ($this->getRole($value) != null)) {
      throw new IllegalArgumentException(Message::get("The role '%1%' already exists", array($value)));
    }
    if (($role = $this->getRole($name)) == null) {
      throw new IllegalArgumentException(Message::get("The role '%1%' does not exist", array($name)));
    }
    // get the repository role first, because the name may change
    $role = $this->getRole($name);

    // validate the value
    $validationMsg = $role->validateValue($property, $value);
    if (strlen($validationMsg) > 0) {
      $this->onError($validationMsg, __FILE__, __LINE__);
    }
    else {
      $this->setRolePropertyImpl($role, $property, $value);
    }

    // update user repository
    $role->setValue($property, $value);
  }

  /**
   * Add a user to a role.
   * @param rolename The name of the role
   * @param login The login of the user
   */
  public function addUserToRole($rolename, $login) {
    if (($role = $this->getRole($rolename)) == null) {
      throw new IllegalArgumentException(Message::get("The role '%1%' does not exist", array($rolename)));
    }
    if (($user = $this->getUser($login)) == null) {
      throw new IllegalArgumentException(Message::get("The login '%1%' does not exist", array($login)));
    }
    if ($user != null && $user->hasRole($rolename)) {
      throw new IllegalArgumentException(Message::get("The user '%1%' already has the role '%2%'", array($login, $rolename)));
    }
    $this->addUserToRoleImpl($role, $user);

    // set role config
    if ($this->_roleConfig && isset($this->_roleConfig[$rolename])) {
      $this->setUserProperty($login, USER_PROPERTY_CONFIG, $this->_roleConfig[$rolename]);
    }
  }

  /**
   * Remove a user from a role.
   * @param rolename The name of the role
   * @param login The login of the user
   */
  public function removeUserFromRole($rolename, $login) {
    if (($role = $this->getRole($rolename)) == null) {
      throw new IllegalArgumentException(Message::get("The role '%1%' does not exist", array($rolename)));
    }
    if (($user = $this->getUser($login)) == null) {
      throw new IllegalArgumentException(Message::get("The login '%1%' does not exist", array($login)));
    }
    if ($user != null && !$user->hasRole($rolename)) {
      throw new IllegalArgumentException(Message::get("The user '%1%' does not have the role '%2%'", array($login, $rolename)));
    }
    $this->removeUserFromRoleImpl($role, $user);

    // remove role config
    if ($this->_roleConfig && isset($this->_roleConfig[$rolename])) {
      $this->setUserProperty($login, USER_PROPERTY_CONFIG, '');
    }
  }

  /**
   * Get list of all users.
   * @return An array containing all login names
   */
  public function listUsers() {
    if ($this->_userRepository == null) {
      $this->_userRepository = $this->getUsersAndRoles();
    }

    $result = array();
    for($i=0, $count=sizeof($this->_userRepository['users']); $i<$count; $i++) {
      array_push($result, $this->_userRepository['users'][$i]->getLogin());
    }
    return $result;
  }

  /**
   * Get list of all roles.
   * @return An array containing all role names
   */
  public function listRoles() {
    if ($this->_userRepository == null) {
      $this->_userRepository = $this->getUsersAndRoles();
    }

    $result = array();
    for($i=0, $count=sizeof($this->_userRepository['roles']); $i<$count; $i++) {
      array_push($result, $this->_userRepository['roles'][$i]->getName());
    }
    return $result;
  }

  /**
   * Get list of all roles a user has.
   * @return An array containing all role names of the user
   */
  public function listUserRoles($login) {
    if (($user = $this->getUser($login)) == null) {
      throw new IllegalArgumentException(Message::get("The login '%1%' does not exist", array($login)));
    }
    $roles = $user->getRoles();
    $result = array();
    for($i=0, $count=sizeof($roles); $i<$count; $i++) {
      array_push($result, $roles[$i]->getName());
    }
    return $result;
  }

  /**
   * Get list of all users that have a role.
   * @return An array containing all login names of the role members
   */
  public function listRoleMembers($rolename) {
    if ($this->_userRepository == null) {
      $this->_userRepository = $this->getUsersAndRoles();
    }

    if (($role = &$this->getRole($rolename)) == null) {
      throw new IllegalArgumentException(Message::get("The role '%1%' does not exist", array($rolename)));
    }
    $result = array();
    for($i=0, $count=sizeof($this->_userRepository['users']); $i<$count; $i++) {
      $curUser = $this->_userRepository['users'][$i];
      $roles = $this->listUserRoles($curUser->getLogin());
      if (in_array($rolename, $roles)) {
        array_push($result, $curUser->getLogin());
      }
    }
    return $result;
  }

  /**
   * Get a user from the repository.
   * @param login The login of the user
   * @return A reference to the matching User object or null if the user does not exist
   */
  public function getUser($login) {
    if ($this->_userRepository == null) {
      $this->_userRepository = $this->getUsersAndRoles();
    }

    for($i=0, $count=sizeof($this->_userRepository['users']); $i<$count; $i++) {
      $curUser = $this->_userRepository['users'][$i];
      if ($curUser->getLogin() == $login) {
        return $curUser;
      }
    }
    return null;
  }

  /**
   * Get a role from the repository.
   * @param name The name of the role
   * @return A reference to the matching Role object or null if the role does not exist
   */
  public function getRole($name) {
    if ($this->_userRepository == null) {
      $this->_userRepository = $this->getUsersAndRoles();
    }

    for($i=0, $count=sizeof($this->_userRepository['roles']); $i<$count; $i++) {
      $curRole = $this->_userRepository['roles'][$i];
      if ($curRole->getName() == $name) {
        return $curRole;
      }
    }
    return null;
  }

  /**
   * Get a principal (type: user/role) from the repository.
   * @param oid The oid of the principal
   * @return A reference to the matching User/Role object or null if the principal does not exist
   */
  public function getPrincipal(ObjectId $oid) {
    $principal = null;
    $type = $oid->getType();
    if ($type == self::getUserType()) {
      $principalArray = $this->_userRepository['users'];
    }
    elseif ($type == self::getRoleType()) {
      $principalArray = $this->_userRepository['roles'];
    }
    else {
      throw new IllegalArgumentException(Message::get("Unknown object type: '%1%'", array($type)));
    }
    for($i=0, $count=sizeof($principalArray); $i<$count; $i++) {
      $curPrincipal = $principalArray[$i];
      if ($curPrincipal->getOID() == $oid) {
        $principal = $curPrincipal;
        break;
      }
    }
    return $principal;
  }

  /**
   * Remove a principal (type: user/role) from the repository.
   * @param oid The oid of the principal
   */
  public function removePrincipal(ObjectId $oid) {
    $type = $oid->getType();
    $principal = $this->getPrincipal($oid);
    if ($principal != null) {
      if ($type == self::getUserType()) {
        $this->removeUser($principal->getLogin());
      }
      elseif ($type == self::getRoleType()) {
        $this->removeRole($principal->getName());
      }
      else {
        throw new IllegalArgumentException(Message::get("Unknown object type: '%1%'", array($type)));
      }
    }
    else {
      throw new IllegalArgumentException(Message::get("The principal does not exist: '%1%'", array($oid)));
    }
  }

  /**
   * Get the user type.
   * @return String
   */
  public function getUserType() {
    if ($this->_userType == null) {
      throw new ConfigurationException("The userType is not set.");
    }
    return $this->_userType;
  }

  /**
   * Get the role type.
   * @return String
   */
  public function getRoleType() {
    if ($this->_roleType == null) {
      throw new ConfigurationException("The roleType is not set.");
    }
    return $this->_roleType;
  }

  /**
   * Methods to be implemented by subclasses.
   */

  /**
   * Get the user and role instances from the store.
   * @return Associative array with keys 'users' and 'roles' and
   * array values containing the instances.
   */
  protected abstract function getUsersAndRoles();

  /**
   * Create a user login with a given password.
   * @param name The name of the user
   * @param firstname The first name of the user
   * @param login The login of the user
   * @param password The encrypted password of the user
   * @return A reference to the created user
   * @note Precondition: The login does not exist
   */
  protected abstract function createUserImpl($name, $firstname, $login, $password);

  /**
   * Remove a user.
   * @param user A reference to the user
   * @note This method is responsible for removing the user from all roles it has
   * @note Precondition: The login does exist
   */
  protected abstract function removeUserImpl(User $user);

  /**
   * Set a user property.
   * @param user A reference to the user
   * @param property One of the USER_PROPERTY constants or 'password'
   * @param value The value to set this property to
   */
  protected abstract function setUserPropertyImpl(User $user, $property, $value);

  /**
   * Create a role.
   * @param name The name of the role
   * @return A reference to the created role
   * @note Precondition: The role does not exist
   */
  protected abstract function createRoleImpl($name);

  /**
   * Remove a role.
   * @param role A reference to the role
   * @note This method is responsible for removing the role from all users it is attached to
   * @note Precondition: The role does exist
   */
  protected abstract function removeRoleImpl(Role $role);

  /**
   * Set a role property.
   * @param role A reference to the role
   * @param property One of the ROLE_PROPERTY constants
   * @param value The value to set this property to
   */
  protected abstract function setRolePropertyImpl(Role $role, $property, $value);

  /**
   * Add a user to a role.
   * @param role A reference to the role
   * @param user A reference to the user
   * @note Precondition: user and role do exist and the user does not have the role
   */
  protected abstract function addUserToRoleImpl(Role $role, User $user);

  /**
   * Remove a user from a role.
   * @param role A reference to the role
   * @param user A reference to the user
   * @note Precondition: user and role do exist and the user does have the role
   */
  protected abstract function removeUserFromRoleImpl(Role $role, User $user);
}
?>
