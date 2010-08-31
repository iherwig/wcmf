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
require_once(BASE."wcmf/lib/security/class.UserManager.php");
require_once(BASE."wcmf/lib/security/class.User.php");
require_once(BASE."wcmf/lib/security/class.Role.php");
require_once(BASE."wcmf/lib/persistence/class.PersistenceFacade.php");

/**
 * @class UserManagerRDB
 * @ingroup Security
 * @brief UserManagerRDB is a UserManager that stores user and role information in a database
 * using RDBMappers. The User and Role implementation classes are defined by the configuration
 * keys 'User' and 'Role' in the [implementation] section.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class UserManagerRDB extends UserManager
{
  /**
   * @see UserManager::initialize()
   */
  function initialize($params)
  {
    $userRepository = array();

    // load the user/role instances
    $query = PersistenceFacade::createObjectQuery(UserManager::getUserClassName());
    $users = $query->execute(1);
    $query = PersistenceFacade::createObjectQuery(UserManager::getRoleClassName());
    $roles = $query->execute(1);

    // add the user/role instances to the repository
    $userRepository['users'] = array();
    for ($i=0; $i<sizeof($users); $i++) {
      if ($users[$i] != null) {
        $userRepository['users'][sizeof($userRepository['users'])] = &$users[$i];
      }
    }
    for ($i=0; $i<sizeof($roles); $i++) {
      if ($roles[$i] != null) {
        $userRepository['roles'][sizeof($userRepository['roles'])] = &$roles[$i];
      }
    }
  	return $userRepository;
  }

  /**
   * @see UserManager::createUserImpl()
   */
  protected function createUserImpl($name, $firstname, $login, $password)
  {
    $persistenceFacade = PersistenceFacade::getInstance();
    $user = $persistenceFacade->create(UserManager::getUserClassName(), BUILDDEPTH_REQUIRED);
    $user->setName($name);
    $user->setFirstname($firstname);
    $user->setLogin($login);
    $user->setPassword($password);
    $user->save();

    return $user;
  }

  /**
   * @see UserManager::removeUserImpl()
   */
  protected function removeUserImpl(User $user)
  {
    $user->delete();
  }

  /**
   * @see UserManager::setUserPropertyImpl()
   */
  protected function setUserPropertyImpl(User $user, $property, $value)
  {
    $user->setValue($property, $value, DATATYPE_ATTRIBUTE);
    $user->save();
  }

  /**
   * @see UserManager::createRoleImpl()
   */
  protected function createRoleImpl($name)
  {
    $persistenceFacade = &PersistenceFacade::getInstance();
    $role = &$persistenceFacade->create($this->getRoleClassName(), BUILDDEPTH_REQUIRED);
    $role->setName($name);
    $role->save();

    return $role;
  }

  /**
   * @see UserManager::removeRoleImpl()
   */
  protected function removeRoleImpl(Role $role)
  {
    $role->delete();
  }

  /**
   * @see UserManager::setRolePropertyImpl()
   */
  protected function setRolePropertyImpl(Role $role, $property, $value)
  {
    $role->setValue($property, $value, DATATYPE_ATTRIBUTE);
    $role->save();
  }

  /**
   * @see UserManager::addUserToRoleImpl()
   */
  protected function addUserToRoleImpl(Role $role, User $user)
  {
    $user->addRole($role->getName(), true);
  }

  /**
   * @see UserManager::removeUserFromRoleImpl()
   */
  protected function removeUserFromRoleImpl(Role $role, User $user)
  {
    $user->removeRole($role->getName(), true);
  }
}
?>
