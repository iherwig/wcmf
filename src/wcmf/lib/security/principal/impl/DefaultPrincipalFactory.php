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

use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\model\Node;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\Criteria;
use wcmf\lib\persistence\PersistenceAction;
use wcmf\lib\persistence\PersistenceFacade;
use wcmf\lib\security\PermissionManager;
use wcmf\lib\security\principal\PrincipalFactory;
use wcmf\lib\security\principal\User;
use wcmf\lib\security\principal\Role;
use wcmf\lib\security\principal\impl\AnonymousUser;

/**
 * Default implementation of PrincipalFactory.
 * Retrieves users and roles from the storage.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultPrincipalFactory implements PrincipalFactory {

  private ?PersistenceFacade $persistenceFacade = null;
  private ?PermissionManager $permissionManager = null;
  private ?string $userType = null;
  private ?string $roleType = null;
  private array $users = [];

  private $roleRelationNames = null;

  /**
   * Constructor
   * @param PersistenceFacade $persistenceFacade
   * @param PermissionManager $permissionManager
   * @param string $userType Entity type name of User instances
   * @param string $roleType Entity type name of Role instances
   */
  public function __construct(PersistenceFacade $persistenceFacade,
          PermissionManager $permissionManager, string $userType, string $roleType) {
    $this->persistenceFacade = $persistenceFacade;
    $this->permissionManager = $permissionManager;
    $this->userType = $userType;
    $this->roleType = $roleType;
  }

  /**
   * @see PrincipalFactory::getUser()
   */
  public function getUser(string $login, bool $useTempPermission=false): ?User {
    // load user if not done before
    if ($login != AnonymousUser::NAME && !isset($this->users[$login])) {
      $loadUser = function() use ($login) {
        return $this->persistenceFacade->loadFirstObject($this->userType, BuildDepth::SINGLE,
          [new Criteria($this->userType, 'login', '=', $login)], null);
      };

      $this->users[$login] = $useTempPermission ?
        $this->permissionManager->withTempPermissions($loadUser, [$this->userType, '', PersistenceAction::READ]) :
        $loadUser();
    }
    return $login == AnonymousUser::NAME ? new AnonymousUser() : $this->users[$login];
  }

  /**
   * @see PrincipalFactory::getUserRoles()
   */
  public function getUserRoles(User $user, bool $useTempPermission=false): array {
    if (!($user instanceof Node)) {
      throw new IllegalArgumentException('Node instance expected');
    }

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
  public function getRole(string $name, bool $useTempPermission=false): ?Role {
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
