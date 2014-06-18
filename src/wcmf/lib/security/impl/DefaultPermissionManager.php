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
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\presentation\Application;
use wcmf\lib\security\PermissionManager;
use wcmf\lib\security\Policy;
use wcmf\lib\security\principal\impl\AnonymousUser;

/**
 * DefaultPermissionManager retrieves authorization rules from the
 * application configuration.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultPermissionManager implements PermissionManager {

  const AUTHORIZATION_SECTION = 'authorization';

  private $_anonymousUser = null;
  private $_tempPermissions = array();

  /**
   * Constructor
   */
  public function __construct() {
    $this->_anonymousUser = new AnonymousUser();
  }

  /**
   * @see PermissionManager::getAuthUserVarname()
   */
  public static function getAuthUserVarname() {
    return 'auth_user_'.Application::getId();
  }

  /**
   * @see PermissionManager::getAuthUser()
   */
  public function getAuthUser() {
    $user = $this->_anonymousUser;
    // check for auth user in session
    $session = ObjectFactory::getInstance('session');
    $userVarname = self::getAuthUserVarname();
    if ($session->exist($userVarname)) {
      $user = $session->get($userVarname);
      $user->resetRoleCache();
    }
    return $user;
  }

  /**
   * @see PermissionManager::isAnonymous()
   * The mode is set in configuration section 'application' key 'anonymous'
   */
  public function isAnonymous() {
    $config = ObjectFactory::getConfigurationInstance();
    return $config->getBooleanValue('anonymous', 'application');
  }

  /**
   * @see PermissionManager::deactivate()
   */
  public function deactivate() {
    $config = ObjectFactory::getConfigurationInstance();
    $config->setValue('anonymous', true, 'application');
  }

  /**
   * @see PermissionManager::activate()
   */
  public function activate() {
    $config = ObjectFactory::getConfigurationInstance();
    $config->setValue('anonymous', false, 'application');
  }

  /**
   * @see PermissionManager::authorize()
   */
  public function authorize($resource, $context, $action) {
    if ($this->isAnonymous()) {
      return true;
    }
    // if authorization is requested for an oid, we check the type first
    $oid = null;
    if ($resource instanceof ObjectId) {
      $oid = $resource;
    }
    else if (ObjectId::isValid($resource)) {
      $oid = ObjectId::parse($resource);
    }
    if ($oid && !$this->authorize($oid->getType(), $context, $action)) {
      return false;
    }

    $actionKey = ActionKey::getBestMatch(self::AUTHORIZATION_SECTION, $resource, $context, $action);

    // check temporary permissions
    if (in_array($actionKey, $this->_tempPermissions)) {
      return true;
    }

    $authUser = $this->getAuthUser();
    if ($authUser && $authUser->authorize($actionKey)) {
      return true;
    }
    return false;
  }

  /**
   * @see PermissionManager::addTempPermission()
   */
  public function addTempPermission($resource, $context, $action) {
    $actionKey = ActionKey::getBestMatch(self::AUTHORIZATION_SECTION, $resource, $context, $action);
    $this->_tempPermissions[] = $actionKey;
  }

  /**
   * @see PermissionManager::clearTempPermissions()
   */
  public function clearTempPermissions() {
    $this->_tempPermissions = array();
  }

  /**
   * @see PermissionManager::getPermission()
   */
  public function getPermission($resource, $context, $action) {
    $config = ObjectFactory::getConfigurationInstance();
    $permDef = ActionKey::createKey($resource, $context, $action);
    if ($config->getValue($permDef, self::AUTHORIZATION_SECTION) !== false) {
      return Policy::parse($config->getValue($permDef, self::AUTHORIZATION_SECTION));
    }
    else {
      return array();
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
   * @param resource The resource (e.g. class name of the Controller or OID).
   * @param context The context in which the action takes place.
   * @param action The action to process.
   * @param role The role to authorize.
   * @param modifier One of the PERMISSION_MODIFIER_ constants, null, if the permission
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
