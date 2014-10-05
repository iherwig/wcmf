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
use wcmf\lib\security\PermissionManager;

/**
 * DefaultPermissionManager retrieves authorization rules the storage.
 * It is configures with an entity type that stores permissions and must
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
    $actionKey = ActionKey::getBestMatch($this->_actionKeyProvider, $resource, $context, $action);
    if (strlen($actionKey) > 0) {
      return $this->parsePermissions($this->_actionKeyProvider->getKeyValue($actionKey));
    }
    return null;
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
    $permVal = '';
    if ($modifier != null) {
      $permVal = $modifier.$role;
    }

    // check for existing permission
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    $query = new ObjectQuery($this->_permissionType, __CLASS__.__METHOD__);
    $tpl = $query->getObjectTemplate($this->_permissionType);
    $tpl->setValue($this->_valueMap['resource'], Criteria::asValue('=', $resource));
    $tpl->setValue($this->_valueMap['context'], Criteria::asValue('=', $context));
    $tpl->setValue($this->_valueMap['action'], Criteria::asValue('=', $action));
    $permissions = $query->execute(BuildDepth::SINGLE);
    if (sizeof($permissions) == 0 && $modifier != null) {
      // create the permission, if it does not exist yet
      $permission = $persistenceFacade->create($this->_permissionType);
      $permission->setValue('resource', $resource);
      $permission->setValue('context', $context);
      $permission->setValue('action', $action);
      $permission->setValue('roles', $permVal);
    }
    else {
      $permission = $permissions[0];
      $value = $permission->getValue('roles');
      // remove role from value
      $newValue = str_replace(array(PermissionManager::PERMISSION_MODIFIER_ALLOW.$role,
                    PermissionManager::PERMISSION_MODIFIER_DENY.$role), "", $value);
      if (strlen($newValue) > 0) {
        $permission->setValue('roles', $newValue." ".$permVal);
      }
      else {
        $permission->delete();
      }
    }

    $persistenceFacade->getTransaction()->commit();
    return true;
  }
}
?>
