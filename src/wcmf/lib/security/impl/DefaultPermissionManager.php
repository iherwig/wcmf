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
namespace wcmf\lib\security\impl;

use wcmf\lib\config\ActionKey;
use wcmf\lib\config\impl\PersistenceActionKeyProvider;
use wcmf\lib\core\Log;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\ObjectQuery;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\Criteria;
use wcmf\lib\security\impl\AbstractPermissionManager;
use wcmf\lib\security\PermissionManager;
use wcmf\lib\util\StringUtil;

/**
 * DefaultPermissionManager retrieves authorization rules the storage.
 * It is configured with an entity type that stores permissions and must
 * have the values 'resource', 'context', 'action', 'roles'.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultPermissionManager extends AbstractPermissionManager implements PermissionManager {

  private $_permissionType = null;
  private $_actionKeyProvider = null;

  /**
   * Constructor
   */
  public function __construct() {
    $this->_actionKeyProvider = new PersistenceActionKeyProvider();
    $this->_actionKeyProvider->setValueMap(array(
        'resource' => 'resource',
        'context' => 'context',
        'action' => 'action',
        'value' => 'roles',
    ));
  }

  /**
   * Set the entity type name of Permission instances.
   * @param $permissionType String
   */
  public function setPermissionType($permissionType) {
    $this->_permissionType = $permissionType;
    $this->_actionKeyProvider->setEntityType($this->_permissionType);
  }

  /**
   * @see PermissionManager::getPermissions()
   */
  public function getPermissions($resource, $context, $action) {
    $result = null;
    $actionKey = ActionKey::getBestMatch($this->_actionKeyProvider, $resource, $context, $action);
    if (strlen($actionKey) > 0) {
      $result = $this->deserializePermissions($this->_actionKeyProvider->getKeyValue($actionKey));
    }
    if (Log::isDebugEnabled(__CLASS__)) {
      Log::debug("Permissions for $resource?$context?$action (->$actionKey): ".trim(StringUtil::getDump($result)), __CLASS__);
    }
    return $result;
  }

  /**
   * @see PermissionManager::setPermissions()
   */
  public function setPermissions($resource, $context, $action, $permissions) {
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
  public function createPermission($resource, $context, $action, $role, $modifier) {
    return self::modifyPermission($resource, $context, $action, $role, $modifier);
  }

  /**
   * @see PermissionManager::removePermission()
   */
  public function removePermission($resource, $context, $action, $role) {
    return self::modifyPermission($resource, $context, $action, $role, null);
  }

  /**
   * Modify a permission for the given role.
   * @param $resource The resource (e.g. class name of the Controller or object id).
   * @param $context The context in which the action takes place.
   * @param $action The action to process.
   * @param $role The role to authorize.
   * @param $modifier One of the PERMISSION_MODIFIER_ constants, null, if the permission
   *    should be removed.
   * @return boolean
   */
  protected function modifyPermission($resource, $context, $action, $role, $modifier) {
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
      $newValue = preg_replace('/ +/', ' ', str_replace(array(PermissionManager::PERMISSION_MODIFIER_ALLOW.$role,
                    PermissionManager::PERMISSION_MODIFIER_DENY.$role), "", $value));
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
   * @param $resource Resource
   * @param $context Context
   * @param $action Action
   * @return Instance of _permissionType or null
   */
  protected function getPermissionInstance($resource, $context, $action) {
    $query = new ObjectQuery($this->_permissionType, __CLASS__.__METHOD__);
    $tpl = $query->getObjectTemplate($this->_permissionType);
    $tpl->setValue('resource', Criteria::asValue('=', $resource));
    $tpl->setValue('context', Criteria::asValue('=', $context));
    $tpl->setValue('action', Criteria::asValue('=', $action));
    $permissions = $query->execute(BuildDepth::SINGLE);
    return (sizeof($permissions) > 0) ? $permissions[0] : null;
  }

  /**
   * Create a permission object with the given parameters
   * @param $resource Resource
   * @param $context Context
   * @param $action Action
   * @param $roles String representing the permissions as returned from serializePermissions()
   * @return Instance of _permissionType
   */
  protected function createPermissionObject($resource, $context, $action, $roles) {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $permission = $persistenceFacade->create($this->_permissionType);
    $permission->setValue('resource', $resource);
    $permission->setValue('context', $context);
    $permission->setValue('action', $action);
    $permission->setValue('roles', $roles);
    return $permission;
  }
}
?>
