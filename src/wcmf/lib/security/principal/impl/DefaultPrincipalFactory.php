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
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\Criteria;
use wcmf\lib\persistence\PersistenceAction;
use wcmf\lib\security\principal\PrincipalFactory;
use wcmf\lib\security\principal\User;

/**
 * Default implementation of PrincipalFactory.
 * Retrieves users and roles from the storage.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultPrincipalFactory implements PrincipalFactory {

  private $_userType = null;
  private $_roleType = null;

  private $_roleRelationNames = null;

  /**
   * Set the entity type name of User instances.
   * @param $userType String
   */
  public function setUserType($userType) {
    $this->_userType = $userType;
  }

  /**
   * Set the entity type name of Role instances.
   * @param $roleType String
   */
  public function setRoleType($roleType) {
    $this->_roleType = $roleType;
  }

  /**
   * @see PrincipalFactory::getUser()
   */
  public function getUser($login, $useTempPermission=false) {
    $permissionManager = ObjectFactory::getInstance('permissionManager');
    if ($useTempPermission) {
      $permissionManager->addTempPermission($this->_userType, '', PersistenceAction::READ);
    }

    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $user = $persistenceFacade->loadFirstObject($this->_userType, BuildDepth::SINGLE,
                array(
                    new Criteria($this->_userType, 'login', '=', $login)
                ), null);

    if ($useTempPermission) {
      $permissionManager->removeTempPermission($this->_userType, '', PersistenceAction::READ);
    }
    return $user;
  }

  /**
   * @see PrincipalFactory::getUserRoles()
   */
  public function getUserRoles(User $user, $useTempPermission=false) {
    $permissionManager = ObjectFactory::getInstance('permissionManager');
    if ($useTempPermission) {
      $permissionManager->addTempPermission($this->_roleType, '', PersistenceAction::READ);
    }

    // initialize role relation definition
    if ($this->_roleRelationNames == null) {
      $this->_roleRelationNames = array();
      $mapper = $user->getMapper();
      foreach ($mapper->getRelationsByType($this->_roleType) as $relation) {
        $this->_roleRelationNames[] = $relation->getOtherRole();
      }
    }

    foreach ($this->_roleRelationNames as $roleName) {
      $user->loadChildren($roleName);
    }

    if ($useTempPermission) {
      $permissionManager->removeTempPermission($this->_roleType, '', PersistenceAction::READ);
    }

    // TODO add role nodes from addedNodes array
    // use getChildrenEx, because we are interessted in the type
    return $user->getChildrenEx(null, null, $this->_roleType, null);
  }

  /**
   * @see PrincipalFactory::getRole()
   */
  public function getRole($name, $useTempPermission=false) {
    $permissionManager = ObjectFactory::getInstance('permissionManager');
    if ($useTempPermission) {
      $permissionManager->addTempPermission($this->_roleType, '', PersistenceAction::READ);
    }

    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $role = $persistenceFacade->loadFirstObject($this->_roleType, BuildDepth::SINGLE,
                array(
                    new Criteria($this->_roleType, 'name', '=', $name)
                ), null);

    if ($useTempPermission) {
      $permissionManager->removeTempPermission($this->_roleType, '', PersistenceAction::READ);
    }
    return $role;
  }
}
?>
