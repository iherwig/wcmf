<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2015 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\security\principal\impl;

use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\Criteria;
use wcmf\lib\persistence\PersistenceAction;
use wcmf\lib\persistence\PersistenceFacade;
use wcmf\lib\security\PermissionManager;
use wcmf\lib\security\principal\PrincipalFactory;
use wcmf\lib\security\principal\User;

/**
 * Default implementation of PrincipalFactory.
 * Retrieves users and roles from the storage.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultPrincipalFactory implements PrincipalFactory {

  private $_persistenceFacade = null;
  private $_permissionManager = null;
  private $_userType = null;
  private $_roleType = null;

  private $_roleRelationNames = null;

  /**
   * Constructor
   * @param $persistenceFacade
   * @param $permissionManager
   * @param $userType Entity type name of User instances
   * @param $roleType Entity type name of Role instances
   */
  public function __construct(PersistenceFacade $persistenceFacade,
          PermissionManager $permissionManager, $userType, $roleType) {
    $this->_persistenceFacade = $persistenceFacade;
    $this->_permissionManager = $permissionManager;
    $this->_userType = $userType;
    $this->_roleType = $roleType;
  }

  /**
   * @see PrincipalFactory::getUser()
   */
  public function getUser($login, $useTempPermission=false) {
    if ($useTempPermission) {
      $this->_permissionManager->addTempPermission($this->_userType, '', PersistenceAction::READ);
    }

    $user = $this->_persistenceFacade->loadFirstObject($this->_userType, BuildDepth::SINGLE,
                array(
                    new Criteria($this->_userType, 'login', '=', $login)
                ), null);

    if ($useTempPermission) {
      $this->_permissionManager->removeTempPermission($this->_userType, '', PersistenceAction::READ);
    }
    return $user;
  }

  /**
   * @see PrincipalFactory::getUserRoles()
   */
  public function getUserRoles(User $user, $useTempPermission=false) {
    if ($useTempPermission) {
      $this->_permissionManager->addTempPermission($this->_roleType, '', PersistenceAction::READ);
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
      $this->_permissionManager->removeTempPermission($this->_roleType, '', PersistenceAction::READ);
    }

    // TODO add role nodes from addedNodes array
    // use getChildrenEx, because we are interessted in the type
    return $user->getChildrenEx(null, null, $this->_roleType, null);
  }

  /**
   * @see PrincipalFactory::getRole()
   */
  public function getRole($name, $useTempPermission=false) {
    if ($useTempPermission) {
      $this->_permissionManager->addTempPermission($this->_roleType, '', PersistenceAction::READ);
    }

    $role = $this->_persistenceFacade->loadFirstObject($this->_roleType, BuildDepth::SINGLE,
                array(
                    new Criteria($this->_roleType, 'name', '=', $name)
                ), null);

    if ($useTempPermission) {
      $this->_permissionManager->removeTempPermission($this->_roleType, '', PersistenceAction::READ);
    }
    return $role;
  }
}
?>
