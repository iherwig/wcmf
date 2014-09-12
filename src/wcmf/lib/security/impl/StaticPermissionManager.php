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
use wcmf\lib\config\impl\ConfigActionKeyProvider;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\security\PermissionManager;

/**
 * StaticPermissionManager retrieves authorization rules from the
 * application configuration.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class StaticPermissionManager extends AbstractPermissionManager implements PermissionManager {

  const AUTHORIZATION_SECTION = 'authorization';

  private $_actionKeyProvider = null;

  /**
   * Constructor
   */
  public function __construct() {
    $this->_actionKeyProvider = new ConfigActionKeyProvider();
    $this->_actionKeyProvider->setConfigSection(self::AUTHORIZATION_SECTION);
  }

  /**
   * @see PermissionManager::getPermissions()
   */
  public function getPermissions($resource, $context, $action) {
    $actionKey = ActionKey::getBestMatch($this->_actionKeyProvider, $resource, $context, $action);
    if (strlen($actionKey) > 0) {
      $config = ObjectFactory::getConfigurationInstance();
      return $this->parsePermissions($config->getValue($actionKey, self::AUTHORIZATION_SECTION));
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
   * @param $resource The resource (e.g. class name of the Controller or OID).
   * @param $context The context in which the action takes place.
   * @param $action The action to process.
   * @param $role The role to authorize.
   * @param $modifier One of the PERMISSION_MODIFIER_ constants, null, if the permission
   *    should be removed.
   * @return boolean
   */
  protected function modifyPermission($resource, $context, $action, $role, $modifier) {
    // get config file to modify
    $appConfig = ObjectFactory::getConfigurationInstance();
    $configFiles = $appConfig->getConfigurations();
    if (sizeof($configFiles) == 0) {
      return false;
    }

    // create a writable configuration and modify the permission
    $mainConfig = $configFiles[0];
    $newConfig = new IniFileConfiguration(dirname($mainConfig));
    $newConfig->addConfiguration(basename($mainConfig));

    $permDef = ActionKey::createKey($resource, $context, $action);
    $permVal = '';
    if ($modifier != null) {
      $permVal = $modifier.$role;
    }
    if ($newConfig->getValue($permDef, self::AUTHORIZATION_SECTION) === false && $modifier != null) {
      $newConfig->setValue($permDef, $permVal, self::AUTHORIZATION_SECTION, true);
    }
    else {
      $value = $newConfig->getValue($permDef, self::AUTHORIZATION_SECTION);
      // remove role from value
      $newValue = str_replace(array(PermissionManager::PERMISSION_MODIFIER_ALLOW.$role,
                    PermissionManager::PERMISSION_MODIFIER_DENY.$role), "", $value);
      if ($newValue != '') {
        $newConfig->setValue($permDef, $newValue." ".$permVal, self::AUTHORIZATION_SECTION, false);
      }
      else {
        $newConfig->removeKey($permDef, self::AUTHORIZATION_SECTION);
      }
    }

    $newConfig->writeConfiguration(basename($mainConfig));
    return true;
  }
}
?>
