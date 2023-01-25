<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2020 wemove digital solutions GmbH
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
use wcmf\lib\security\principal\impl\AnonymousUser;

/**
 * Default implementation of PrincipalFactory.
 * Retrieves users and roles from the storage.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultPrincipalFactory implements PrincipalFactory {

  private $persistenceFacade = null;
  private $permissionManager = null;
  private $userType = null;
  private $roleType = null;
  private $users = [];

  private $roleRelationNames = null;

  /**
   * Constructor
   * @param $persistenceFacade
   * @param $permissionManager
   * @param $userType Entity type name of User instances
   * @param $roleType Entity type name of Role instances
   */
  public function __construct(PersistenceFacade $persistenceFacade,
          PermissionManager $permissionManager, $userType, $roleType) {
    $this->persistenceFacade = $persistenceFacade;
    $this->permissionManager = $permissionManager;
    $this->userType = $userType;
    $this->roleType = $roleType;
  }

  /**
   * @see PrincipalFactory::getUser()
   */
  public function getUser($login, $useTempPermission=false) {
    // load user if not done before
    if ($login != AnonymousUser::USER_GROUP_NAME && !isset($this->users[$login])) {
      $loadUser = function() use ($login) {
        return $this->persistenceFacade->loadFirstObject($this->userType, BuildDepth::SINGLE,
          [new Criteria($this->userType, 'login', '=', $login)], null);
      };

      $this->users[$login] = $useTempPermission ?
        $this->permissionManager->withTempPermissions($loadUser, [$this->userType, '', PersistenceAction::READ]) :
        $loadUser();
    }
    return $login == AnonymousUser::USER_GROUP_NAME ? new AnonymousUser() : $this->users[$login];
  }

  /**
   * @see PrincipalFactory::getUserRoles()
   */
  public function getUserRoles(User $user, $useTempPermission=false) {
    $loadUserRoles = function() use ($user) {
      // initialize role relation definition
      if ($this->roleRelationNames == null) {
        $this->roleRelationNames = [];
        $mapper = $user->getMapper();
        foreach ($mapper->getRelationsByType($this->roleType) as $relation) {
          $this->roleRelationNames[] = $relation->getOtherRole();
        }
      }

      foreach ($this->roleRelationNames as $roleName) {
        $user->loadChildren($roleName);
      }
    };

    $useTempPermission ?
      $this->permissionManager->withTempPermissions($loadUserRoles, [$this->roleType, '', PersistenceAction::READ]) :
      $loadUserRoles();

    // TODO add role nodes from addedNodes array
    // use getChildrenEx, because we are interessted in the type
    return $user->getChildrenEx(null, null, $this->roleType, null);
  }

  /**
   * @see PrincipalFactory::getRole()
   */
  public function getRole($name, $useTempPermission=false) {
    $loadRole = function() use ($name) {
      return $this->persistenceFacade->loadFirstObject($this->roleType, BuildDepth::SINGLE,
        [new Criteria($this->roleType, 'name', '=', $name)], null);
    };

    $role = $useTempPermission ?
      $this->permissionManager->withTempPermissions($loadRole, [$this->roleType, '', PersistenceAction::READ]) :
      $loadRole();

    return $role;
  }
}
?>
