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
use wcmf\lib\security\principal\User;
use wcmf\lib\security\principal\impl\AnonymousUser;

/**
 * AbstractPermissionManager is the base class for concrete PermissionManager
 * implementations.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class AbstractPermissionManager {

  const RESOURCE_TYPE_ENTITY_TYPE = 'entity.type';
  const RESOURCE_TYPE_ENTITY_TYPE_PROPERTY = 'entity.type.property';
  const RESOURCE_TYPE_ENTITY_INSTANCE = 'entity.instance';
  const RESOURCE_TYPE_ENTITY_INSTANCE_PROPERTY = 'entity.instance.property';
  const RESOURCE_TYPE_OTHER = 'other';

  private $_anonymousUser = null;
  private $_authUserVarName = null;
  private $_tempPermissions = array();

  /**
   * Constructor
   */
  public function __construct() {
    $this->_anonymousUser = new AnonymousUser();
    $this->_authUserVarName = 'auth_user_'.Application::getId();
  }

  /**
   * @see PermissionManager::setAuthUser()
   */
  public function setAuthUser($authUser) {
    $session = ObjectFactory::getInstance('session');
    $session->set($this->_authUserVarName, $authUser);
  }

  /**
   * @see PermissionManager::getAuthUser()
   */
  public function getAuthUser() {
    $user = $this->_anonymousUser;
    // check for auth user in session
    $session = ObjectFactory::getInstance('session');
    if ($session->exist($this->_authUserVarName)) {
      $user = $session->get($this->_authUserVarName);
    }
    return $user;
  }

  /**
   * @see PermissionManager::authorize()
   */
  public function authorize($resource, $context, $action) {
    if (Log::isDebugEnabled(__CLASS__)) {
      Log::debug("Checking authorization for: $resource?$context?$action", __CLASS__);
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
        $authorized = $this->authorizeAction($oidProperty, $context, $action);
        if ($authorized === null) {
          $authorized = $this->authorizeAction($typeProperty, $context, $action);
          if ($authorized === null) {
            $authorized = $this->authorizeAction($oid, $context, $action);
            if ($authorized === null) {
              $authorized = $this->authorizeAction($type, $context, $action);
            }
          }
        }
        break;

      case (self::RESOURCE_TYPE_ENTITY_INSTANCE):
        $authorized = $this->authorizeAction($oid, $context, $action);
        if ($authorized === null) {
          $authorized = $this->authorizeAction($type, $context, $action);
        }
        break;

      case (self::RESOURCE_TYPE_ENTITY_TYPE_PROPERTY):
        $authorized = $this->authorizeAction($typeProperty, $context, $action);
        if ($authorized === null) {
          $authorized = $this->authorizeAction($type, $context, $action);
        }
        break;

      case (self::RESOURCE_TYPE_ENTITY_TYPE_PROPERTY):
        $authorized = $this->authorizeAction($type, $context, $action);
        break;

      default:
        $authorized = $this->authorizeAction($resourceStr, $context, $action);
        break;
    }

    if ($authorized === null) {
      $authUser = $this->getAuthUser();
      $authorized = $authUser && $this->getDefaultPolicy($authUser);
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
  protected function authorizeAction($resource, $context, $action, $returnNullIfNoPermissionExists=true) {
    if (Log::isDebugEnabled(__CLASS__)) {
      Log::debug("Authorizing $resource?$context?$action", __CLASS__);
    }
    $authorized = null;

    // check temporary permissions
    if ($this->hasTempPermission($resource, $context, $action)) {
      if (Log::isDebugEnabled(__CLASS__)) {
        Log::debug("Has temporary permission", __CLASS__);
      }
      $authorized = true;
    }
    else {
      // check other permissions
      $authUser = $this->getAuthUser();
      $permisssions = $this->getPermissions($resource, $context, $action);
      if ($permisssions != null) {
        // mathing permissions found, check user roles
        $authorized = $this->matchRoles($permisssions, $authUser);
      }
      elseif (!$returnNullIfNoPermissionExists) {
        // no permission definied, check for user's default policy
        $authorized = $this->getDefaultPolicy($authUser);
      }
    }
    if (Log::isDebugEnabled(__CLASS__)) {
      Log::debug("Result for $resource?$context?$action: ".(is_bool($authorized) ? ((!$authorized ? "not " : "")."authorized") : "not defined"), __CLASS__);
    }
    return $authorized;
  }

  /**
   * Get the default policy that is used if no permission is set up
   * for a requested action.
   * @return Boolean
   */
  public function getDefaultPolicy(User $user) {
    return ($user instanceof AnonymousUser) ? false : true;
  }

  /**
   * Parse a permissions string and return an associative array with the keys
   * 'default', 'allow', 'deny', where 'allow', 'deny' are arrays itselves holding roles
   * and 'default' is a boolean value derived from the wildcard policy (+* or -*).
   * @param $val A role string (+*, +administrators, -guest, entries without '+' or '-'
   *     prefix default to allow rules).
   * @return An array containing the permissions as an associative array with the keys
   *     'default', 'allow', 'deny'.
   */
  protected function parsePermissions($val) {
    $result = array();

    $roleValues = explode(" ", $val);
    foreach ($roleValues as $roleValue) {
      $roleValue = trim($roleValue);
      $matches = array();
      preg_match('/^([+-]?)(.+)$/', $roleValue, $matches);
      $prefix = $matches[1];
      $role = $matches[2];
      if ($role === '*') {
        $result['default'] = $prefix == '-' ? false : true;
      }
      else {
        if ($prefix === '-') {
          $result['deny'][] = $role;
        }
        else {
          // entries without '+' or '-' prefix default to allow rules
          $result['allow'][] = $role;
        }
      }
    }
    // if no wildcard policy is defined, set default to false
    if (!isset($result['default'])) {
      $result['default'] = false;
    }
    return $result;
  }

  /**
   * Matches the roles of the user and the roles in the given permissions
   * @param $permissions An array containing permissions as an associative array
   *     with the keys 'default', 'allow', 'deny', where 'allow', 'deny' are arrays
   *     itselves holding roles and 'default' is a boolean value derived from the
   *     wildcard policy (+* or -*). 'allow' overwrites 'deny' overwrites 'default'
   * @param $user AuthUser instance
   * @return Boolean whether the user has access right according to the permissions.
   */
  protected function matchRoles($permissions, User $user) {
    if (Log::isDebugEnabled(__CLASS__)) {
      Log::debug("Matching roles for ".$user->getLogin(), __CLASS__);
    }
    if (isset($permissions['allow'])) {
      foreach ($permissions['allow'] as $value) {
        if ($user->hasRole($value)) {
          return true;
        }
      }
    }
    if (isset($permissions['deny'])) {
      foreach ($permissions['deny'] as $value) {
        if ($user->hasRole($value)) {
          return false;
        }
      }
    }
    return isset($permissions['default']) ? $permissions['default'] : false;
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
   * @see PermissionManager::hasTempPermission()
   */
  public function hasTempPermission($resource, $context, $action) {
    $actionKey = ActionKey::createKey($resource, $context, $action);
    return isset($this->_tempPermissions[$actionKey]);
  }

  /**
   * @see PermissionManager::clearTempPermissions()
   */
  public function clearTempPermissions() {
    $this->_tempPermissions = array();
  }
}
?>
