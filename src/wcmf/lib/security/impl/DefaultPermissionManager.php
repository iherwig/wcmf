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
namespace wcmf\lib\security\impl;

use wcmf\lib\config\ActionKey;
use wcmf\lib\config\impl\PersistenceActionKeyProvider;
use wcmf\lib\core\LogTrait;
use wcmf\lib\core\Session;
use wcmf\lib\model\ObjectQuery;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\Criteria;
use wcmf\lib\persistence\PersistenceFacade;
use wcmf\lib\security\PermissionManager;
use wcmf\lib\security\impl\AbstractPermissionManager;
use wcmf\lib\security\principal\PrincipalFactory;
use wcmf\lib\util\StringUtil;

/**
 * DefaultPermissionManager retrieves authorization rules the storage.
 * It is configured with an entity type that stores permissions and must
 * have the values 'resource', 'context', 'action', 'roles'.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultPermissionManager extends AbstractPermissionManager implements PermissionManager {
  use LogTrait;

  private $permissionType = null;
  private $actionKeyProvider = null;

  /**
   * Constructor
   * @param PersistenceFacade $persistenceFacade
   * @param Session $session
   */
  public function __construct(PersistenceFacade $persistenceFacade,
          Session $session,
          PrincipalFactory $principalFactory) {
    parent::__construct($persistenceFacade, $session, $principalFactory);
    $this->actionKeyProvider = new PersistenceActionKeyProvider();
    $this->actionKeyProvider->setValueMap([
        'resource' => 'resource',
        'context' => 'context',
        'action' => 'action',
        'value' => 'roles',
    ]);
  }

  /**
   * Set the entity type name of Permission instances.
   * @param string $permissionType
   */
  public function setPermissionType(string $permissionType): void {
    $this->permissionType = $permissionType;
    $this->actionKeyProvider->setEntityType($this->permissionType);
  }

  /**
   * @see PermissionManager::getPermissions()
   */
  public function getPermissions(string $resource, string $context, string $action): ?array {
    $result = null;
    $actionKey = ActionKey::getBestMatch($this->actionKeyProvider, $resource, $context, $action);
    if (strlen($actionKey) > 0) {
      $result = $this->deserializePermissions($this->actionKeyProvider->getKeyValue($actionKey));
    }
    if (self::logger()->isDebugEnabled()) {
      self::logger()->debug("Permissions for $resource?$context?$action (->$actionKey): ".trim(StringUtil::getDump($result)));
    }
    return $result;
  }

  /**
   * @see PermissionManager::setPermissions()
   */
  public function setPermissions(string $resource, string $context, string $action, ?array $permissions): void {
    $permissionInstance = $this->getPermissionInstance($resource, $context, $action);
    $isChanged = false;

    if ($permissions != null) {
      // set permissions
      $rolesStr = $this->serializePermissions($permissions);
      if (strlen($rolesStr)) {
        if (!$permissionInstance) {
          $this->createPermissionObject($resource, $context, $action, $rolesStr);
        }
        else {
          $permissionInstance->setValue('roles', $rolesStr);
        }
        $isChanged = true;
      }
    }
    else {
      // delete permissions
      if ($permissionInstance) {
        $permissionInstance->delete();
        $isChanged = true;
      }
    }
  }

  /**
   * @see PermissionManager::createPermission()
   */
  public function createPermission(string $resource, string $context, string $action, string $role, string $modifier): bool {
    return self::modifyPermission($resource, $context, $action, $role, $modifier);
  }

  /**
   * @see PermissionManager::removePermission()
   */
  public function removePermission(string $resource, string $context, string $action, string $role): bool {
    return self::modifyPermission($resource, $context, $action, $role, null);
  }

  /**
   * Modify a permission for the given role.
   * @param string $resource The resource (e.g. class name of the Controller or object id).
   * @param string $context The context in which the action takes place.
   * @param string $action The action to process.
   * @param string $role The role to authorize.
   * @param string $modifier One of the PERMISSION_MODIFIER_ constants, null, if the permission
   *    should be removed.
   * @return bool
   */
  protected function modifyPermission(string $resource, string $context, string $action, string $role, string $modifier) {
    // define roles value (empty if no modifier is given)
    $permVal = $modifier != null ? $modifier.$role : '';

    // check for existing permission
    $permission = $this->getPermissionInstance($resource, $context, $action);
    if (!$permission && $modifier != null) {
      // create the permission, if it does not exist yet
      $permission = $this->createPermissionObject($resource, $context, $action, $permVal);
    }
    elseif ($permission) {
      $value = $permission->getValue('roles');
      // remove role from value
      $newValue = preg_replace('/ +/', ' ', str_replace([PermissionManager::PERMISSION_MODIFIER_ALLOW.$role,
                    PermissionManager::PERMISSION_MODIFIER_DENY.$role], "", $value));
      if (strlen($newValue) > 0) {
        $permission->setValue('roles', trim($newValue." ".$permVal));
      }
      else {
        $permission->delete();
      }
    }
    return true;
  }

  /**
   * Get the permission object that matches the given parameters
   * @param string $resource Resource
   * @param string $context Context
   * @param string $action Action
   * @return mixed Instance of permissionType or null
   */
  protected function getPermissionInstance(string $resource, string $context, string $action) {
    $query = new ObjectQuery($this->permissionType, __CLASS__.__METHOD__);
    $tpl = $query->getObjectTemplate($this->permissionType);
    $tpl->setValue('resource', Criteria::asValue('=', $resource));
    $tpl->setValue('context', Criteria::asValue('=', $context));
    $tpl->setValue('action', Criteria::asValue('=', $action));
    $permissions = $query->execute(BuildDepth::SINGLE);
    return (sizeof($permissions) > 0) ? $permissions[0] : null;
  }

  /**
   * Create a permission object with the given parameters
   * @param string $resource Resource
   * @param string $context Context
   * @param string $action Action
   * @param string $roles String representing the permissions as returned from serializePermissions()
   * @return mixed Instance of permissionType
   */
  protected function createPermissionObject(string $resource, string $context, string $action, string $roles) {
    $permission = $this->persistenceFacade->create($this->permissionType);
    $permission->setValue('resource', $resource);
    $permission->setValue('context', $context);
    $permission->setValue('action', $action);
    $permission->setValue('roles', $roles);
    return $permission;
  }
}
?>
