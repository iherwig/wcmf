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
use wcmf\lib\core\Log;
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

  const RESOURCE_TYPE_ENTITY_TYPE = 'entity.type';
  const RESOURCE_TYPE_ENTITY_TYPE_PROPERTY = 'entity.type.property';
  const RESOURCE_TYPE_ENTITY_INSTANCE = 'entity.instance';
  const RESOURCE_TYPE_ENTITY_INSTANCE_PROPERTY = 'entity.instance.property';
  const RESOURCE_TYPE_OTHER = 'other';

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
    if (Log::isDebugEnabled(__CLASS__)) {
      Log::debug("Checking authorization for: $resource?$context?$action", __CLASS__);
    }
    if ($this->isAnonymous()) {
      if (Log::isDebugEnabled(__CLASS__)) {
        Log::debug("Permissions deactivated -> authorized", __CLASS__);
      }
      return true;
    }

    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    // normalize resource to string
    $resourceStr = ($resource instanceof ObjectId) ? $resource->__toString() : $resource;

    // determine the resource type and set entity type, oid and property if applying
    $extensionRemoved = preg_replace('/\.[^\.]*?$/', '', $resourceStr);
    $resourceType = null;
    $oid = null;
    $type = null;
    $oidProperty = null;
    $typeProperty = null;
    if (($oidObj = ObjectId::parse($resourceStr)) !== null) {
      $resourceType = self::RESOURCE_TYPE_ENTITY_INSTANCE;
      $oid = $resourceStr;
      $type = $oidObj->getType();
    }
    elseif (($oidObj = ObjectId::parse($extensionRemoved)) !== null) {
      $resourceType = self::RESOURCE_TYPE_ENTITY_INSTANCE_PROPERTY;
      $oid = $extensionRemoved;
      $type = $oidObj->getType();
      $oidProperty = $resourceStr;
      $typeProperty = $type.substr($resourceStr, strlen($extensionRemoved));
    }
    elseif ($persistenceFacade->isKnownType($resourceStr)) {
      $resourceType = self::RESOURCE_TYPE_ENTITY_TYPE;
      $type = $resourceStr;
    }
    elseif ($persistenceFacade->isKnownType($extensionRemoved)) {
      $resourceType = self::RESOURCE_TYPE_ENTITY_TYPE_PROPERTY;
      $type = $extensionRemoved;
      $typeProperty = $resourceStr;
    }
    else {
      // defaults to other
      $resourceType = self::RESOURCE_TYPE_OTHER;
    }
    if (Log::isDebugEnabled(__CLASS__)) {
      Log::debug("Resource type: ".$resourceType, __CLASS__);
    }

    // proceed by authorizing type depending resource
    // always start checking from most specific
    switch ($resourceType) {
      case (self::RESOURCE_TYPE_ENTITY_INSTANCE_PROPERTY):
        $authorized = $this->authorizeResource($oidProperty, $context, $action);
        if ($authorized === null) {
          $authorized = $this->authorizeResource($typeProperty, $context, $action);
          if ($authorized === null) {
            $authorized = $this->authorizeResource($oid, $context, $action);
            if ($authorized === null) {
              $authorized = $this->authorizeResource($type, $context, $action);
            }
          }
        }
        break;

      case (self::RESOURCE_TYPE_ENTITY_INSTANCE):
        $authorized = $this->authorizeResource($oid, $context, $action);
        if ($authorized === null) {
          $authorized = $this->authorizeResource($type, $context, $action);
        }
        break;

      case (self::RESOURCE_TYPE_ENTITY_TYPE_PROPERTY):
        $authorized = $this->authorizeResource($typeProperty, $context, $action);
        if ($authorized === null) {
          $authorized = $this->authorizeResource($type, $context, $action);
        }
        break;

      case (self::RESOURCE_TYPE_ENTITY_TYPE_PROPERTY):
        $authorized = $this->authorizeResource($type, $context, $action);
        break;

      default:
        $authorized = $this->authorizeResource($resourceStr, $context, $action);
        break;
    }

    if ($authorized === null) {
      $authUser = $this->getAuthUser();
      $authorized = $authUser && $authUser->getDefaultPolicy();
    }
    if (Log::isDebugEnabled(__CLASS__)) {
      Log::debug("Result for $resource?$context?$action: ".(!$authorized ? "not " : "")."authorized", __CLASS__);
    }

    return $authorized;
  }

  /**
   * Authorize the given resource, context, action triple using the
   * temporary permissions or the current user.
   * @param $resource The resource to authorize (e.g. class name of the Controller or ObjectId instance).
   * @param $context The context in which the action takes place.
   * @param $action The action to process.
   * @param $returnNullIfNoPermissionExists Optional, default: true
   * @return Boolean
   */
  protected function authorizeResource($resource, $context, $action, $returnNullIfNoPermissionExists=true) {
    $actionKey = ActionKey::getBestMatch(self::AUTHORIZATION_SECTION, $resource, $context, $action);
    if (Log::isDebugEnabled(__CLASS__)) {
      Log::debug("Action key for $resource?$context?$action: '$actionKey'", __CLASS__);
    }
    $authorized = null;
    if (strlen($actionKey) > 0 || !$returnNullIfNoPermissionExists) {
      // mathing permission definition found

      // check temporary permissions
      if (isset($this->_tempPermissions[$actionKey])) {
        if (Log::isDebugEnabled(__CLASS__)) {
          Log::debug("Has temporary permission", __CLASS__);
        }
        $authorized = true;
      }
      else {
        // check with authorized user
        if (Log::isDebugEnabled(__CLASS__)) {
          Log::debug("Authorizing with user...", __CLASS__);
        }
        $authUser = $this->getAuthUser();
        if ($authUser) {
          $config = ObjectFactory::getConfigurationInstance();
          $policies = Policy::parse($config->getValue($actionKey, self::AUTHORIZATION_SECTION));
          $authorized = $this->matchRoles($policies, $authUser);
        }
        else {
          $authorized = false;
        }
      }
    }
    elseif(!$returnNullIfNoPermissionExists) {
      // no permission definied, check for users default policy
      $authUser = $this->getAuthUser();
      if ($authUser) {
        $authorized = $authUser->getDefaultPolicy();
      }
    }
    if (Log::isDebugEnabled(__CLASS__)) {
      Log::debug("Result for action key '$actionKey': ".(is_bool($authorized) ? ((!$authorized ? "not " : "")."authorized") : "not defined"), __CLASS__);
    }
    return $authorized;
  }

  /**
   * Matches the roles of the user and the roles in the given policies
   * @param $policies An array containing policy information as an associative array
   *     with the keys ('default', 'allow', 'deny'). Where 'allow', 'deny' are arrays
   *     itselves holding roles. 'allow' overwrites 'deny' overwrites 'default'
   * @param $user AuthUser instance
   * @return Boolean whether the user has access right according to this policy.
   */
  protected function matchRoles($policies, $user) {
    if (isset($policies['allow'])) {
      foreach ($policies['allow'] as $value) {
        if ($user->hasRole($value)) {
          return true;
        }
      }
    }
    if (isset($policies['deny'])) {
      foreach ($policies['deny'] as $value) {
        if ($user->hasRole($value)) {
          return false;
        }
      }
    }
    return isset($policies['default']) ? $policies['default'] : false;
  }

  /**
   * @see PermissionManager::addTempPermission()
   */
  public function addTempPermission($resource, $context, $action) {
    $actionKey = ActionKey::createKey($resource, $context, $action);
    if (Log::isDebugEnabled(__CLASS__)) {
      Log::debug("Adding temporary permission for '$actionKey'", __CLASS__);
    }
    $this->_tempPermissions[$actionKey] = true;
  }

  /**
   * @see PermissionManager::removeTempPermission()
   */
  public function removeTempPermission($resource, $context, $action) {
    $actionKey = ActionKey::createKey($resource, $context, $action);
    if (Log::isDebugEnabled(__CLASS__)) {
      Log::debug("Removing temporary permission for '$actionKey'", __CLASS__);
    }
    unset($this->_tempPermissions[$actionKey]);
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
