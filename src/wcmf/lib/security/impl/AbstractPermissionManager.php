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
use wcmf\lib\core\LogManager;
use wcmf\lib\core\Session;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistenceAction;
use wcmf\lib\persistence\PersistenceFacade;
use wcmf\lib\security\PermissionManager;
use wcmf\lib\security\principal\impl\AnonymousUser;
use wcmf\lib\security\principal\User;
use wcmf\lib\util\StringUtil;

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

  private $_tempPermissions = array();

  private static $_logger = null;

  protected $_persistenceFacade = null;
  protected $_session = null;

  /**
   * Constructor
   * @param $persistenceFacade
   * @param $session
   */
  public function __construct(PersistenceFacade $persistenceFacade,
          Session $session) {
    $this->_persistenceFacade = $persistenceFacade;
    $this->_session = $session;
    if (self::$_logger == null) {
      self::$_logger = LogManager::getLogger(__CLASS__);
    }
  }

  /**
   * @see PermissionManager::authorize()
   */
  public function authorize($resource, $context, $action, User $user=null) {
    if ($user == null) {
      $user = $this->_session->getAuthUser();
    }
    if (self::$_logger->isDebugEnabled()) {
      self::$_logger->debug("Checking authorization for: '$resource?$context?$action' and user '".$user->getLogin()."'");
    }

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
    elseif ($this->_persistenceFacade->isKnownType($resourceStr)) {
      $resourceType = self::RESOURCE_TYPE_ENTITY_TYPE;
      $type = $resourceStr;
    }
    elseif ($this->_persistenceFacade->isKnownType($extensionRemoved)) {
      $resourceType = self::RESOURCE_TYPE_ENTITY_TYPE_PROPERTY;
      $type = $extensionRemoved;
      $typeProperty = $resourceStr;
    }
    else {
      // defaults to other
      $resourceType = self::RESOURCE_TYPE_OTHER;
    }
    if (self::$_logger->isDebugEnabled()) {
      self::$_logger->debug("Resource type: ".$resourceType);
    }

    // proceed by authorizing type depending resource
    // always start checking from most specific
    switch ($resourceType) {
      case (self::RESOURCE_TYPE_ENTITY_INSTANCE_PROPERTY):
        $authorized = $this->authorizeAction($oidProperty, $context, $action, $user);
        if ($authorized === null) {
          $authorized = $this->authorizeAction($typeProperty, $context, $action, $user);
          if ($authorized === null) {
            $authorized = $this->authorizeAction($oid, $context, $action, $user);
            if ($authorized === null) {
              $authorized = $this->authorizeAction($type, $context, $action, $user);
            }
          }
        }
        break;

      case (self::RESOURCE_TYPE_ENTITY_INSTANCE):
        $authorized = $this->authorizeAction($oid, $context, $action, $user);
        if ($authorized === null) {
          $authorized = $this->authorizeAction($type, $context, $action, $user);
        }
        break;

      case (self::RESOURCE_TYPE_ENTITY_TYPE_PROPERTY):
        $authorized = $this->authorizeAction($typeProperty, $context, $action, $user);
        if ($authorized === null) {
          $authorized = $this->authorizeAction($type, $context, $action, $user);
        }
        break;

      case (self::RESOURCE_TYPE_ENTITY_TYPE_PROPERTY):
        $authorized = $this->authorizeAction($type, $context, $action, $user);
        break;

      default:
        $authorized = $this->authorizeAction($resourceStr, $context, $action, $user);
        break;
    }

    // check parent entities in composite relations
    if ($authorized === null && $resourceType == self::RESOURCE_TYPE_ENTITY_INSTANCE) {
      if (self::$_logger->isDebugEnabled()) {
        self::$_logger->debug("Check parent objects");
      }
      $mapper = $this->_persistenceFacade->getMapper($type);
      $parentRelations = $mapper->getRelations('parent');
      if (sizeof($parentRelations) > 0) {

        $this->addTempPermission($oidObj, $context, PersistenceAction::READ);
        $object = $this->_persistenceFacade->load($oidObj);
        $this->removeTempPermission($oidObj, $context, PersistenceAction::READ);

        if ($object != null) {
          foreach ($parentRelations as $parentRelation) {
            if ($parentRelation->getThisAggregationKind() == 'composite') {
              $parentType = $parentRelation->getOtherType();

              $this->addTempPermission($parentType, $context, PersistenceAction::READ);
              $parents = $object->getValue($parentRelation->getOtherRole());
              $this->removeTempPermission($parentType, $context, PersistenceAction::READ);

              if ($parents != null) {
                if (!$parentRelation->isMultiValued()) {
                  $parents = array($parents);
                }
                foreach ($parents as $parent) {
                  $authorized = $this->authorize($parent->getOID(), $context, $action);
                  if (!$authorized) {
                    break;
                  }
                }
              }
            }
          }
        }
      }
    }

    if ($authorized === null) {
      $authorized = $this->getDefaultPolicy($user);
    }
    if (self::$_logger->isDebugEnabled()) {
      self::$_logger->debug("Result for $resource?$context?$action: ".(!$authorized ? "not " : "")."authorized");
    }

    return $authorized;
  }

  /**
   * Authorize the given resource, context, action triple using the
   * temporary permissions or the current user.
   * @param $resource The resource to authorize (e.g. class name of the Controller or ObjectId instance).
   * @param $context The context in which the action takes place.
   * @param $action The action to process.
   * @param $user User instance to use for authorization
   * @param $returnNullIfNoPermissionExists Optional, default: true
   * @return Boolean
   */
  protected function authorizeAction($resource, $context, $action, User $user, $returnNullIfNoPermissionExists=true) {
    if (self::$_logger->isDebugEnabled()) {
      self::$_logger->debug("Authorizing $resource?$context?$action");
    }
    $authorized = null;

    // check temporary permissions
    if ($this->hasTempPermission($resource, $context, $action)) {
      if (self::$_logger->isDebugEnabled()) {
        self::$_logger->debug("Has temporary permission");
      }
      $authorized = true;
    }
    else {
      // check other permissions
      $permissions = $this->getPermissions($resource, $context, $action);
      if (self::$_logger->isDebugEnabled()) {
        self::$_logger->debug("Permissions: ".StringUtil::getDump($permissions));
      }
      if ($permissions != null) {
        // matching permissions found, check user roles
        $authorized = $this->matchRoles($permissions, $user);
      }
      elseif (!$returnNullIfNoPermissionExists) {
        // no permission definied, check for user's default policy
        $authorized = $this->getDefaultPolicy($user);
      }
    }
    if (self::$_logger->isDebugEnabled()) {
      self::$_logger->debug("Result for $resource?$context?$action: ".(is_bool($authorized) ? ((!$authorized ? "not " : "")."authorized") : "not defined"));
    }
    return $authorized;
  }

  /**
   * Get the default policy that is used if no permission is set up
   * for a requested action.
   * @return Boolean
   */
  protected function getDefaultPolicy(User $user) {
    return ($user instanceof AnonymousUser) ? false : true;
  }

  /**
   * Parse a permissions string and return an associative array with the keys
   * 'default', 'allow', 'deny', where 'allow', 'deny' are arrays itselves holding roles
   * and 'default' is a boolean value derived from the wildcard policy (+* or -*).
   * @param $val A role string (+*, +administrators, -guest, entries without '+' or '-'
   *     prefix default to allow rules).
   * @return Associative array containing the permissions as an associative array with the keys
   *     'default', 'allow', 'deny' or null, if val is empty
   */
  protected function deserializePermissions($val) {
    if (strlen($val) == 0) {
      return null;
    }
    $result = array(
      'default' => null,
      'allow' => array(),
      'deny' => array(),
    );

    $roleValues = explode(" ", $val);
    foreach ($roleValues as $roleValue) {
      $roleValue = trim($roleValue);
      $matches = array();
      preg_match('/^([+-]?)(.+)$/', $roleValue, $matches);
      if (sizeof($matches) > 0) {
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
    }
    // if no wildcard policy is defined, set default to false
    if (!isset($result['default'])) {
      $result['default'] = false;
    }
    return $result;
  }

  /**
   * Convert an associative permissions array with keys 'default', 'allow', 'deny'
   * into a string.
   * @param $permissions Associative array with keys 'default', 'allow', 'deny',
   *     where 'allow', 'deny' are arrays itselves holding roles and 'default' is a
   *     boolean value derived from the wildcard policy (+* or -*).
   * @return A role string (+*, +administrators, -guest, entries without '+' or '-'
   *     prefix default to allow rules).
   */
  protected function serializePermissions($permissions) {
    $result = $permissions['default'] === true ? PermissionManager::PERMISSION_MODIFIER_ALLOW.'* ' :
        PermissionManager::PERMISSION_MODIFIER_DENY.'* ';
    if (isset($permissions['allow'])) {
      foreach ($permissions['allow'] as $role) {
        $result .= PermissionManager::PERMISSION_MODIFIER_ALLOW.$role.' ';
      }
    }
    if (isset($permissions['deny'])) {
      foreach ($permissions['deny'] as $role) {
        $result .= PermissionManager::PERMISSION_MODIFIER_DENY.$role.' ';
      }
    }
    return trim($result);
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
    if (self::$_logger->isDebugEnabled()) {
      self::$_logger->debug("Matching roles for ".$user->getLogin());
    }
    if (isset($permissions['allow'])) {
      foreach ($permissions['allow'] as $value) {
        if ($user->hasRole($value)) {
          if (self::$_logger->isDebugEnabled()) {
            self::$_logger->debug("Allowed because of role ".$value);
          }
          return true;
        }
      }
    }
    if (isset($permissions['deny'])) {
      foreach ($permissions['deny'] as $value) {
        if ($user->hasRole($value)) {
          if (self::$_logger->isDebugEnabled()) {
            self::$_logger->debug("Denied because of role ".$value);
          }
          return false;
        }
      }
    }
    if (self::$_logger->isDebugEnabled()) {
      self::$_logger->debug("Check default ".$permissions['default']);
    }
    return isset($permissions['default']) ? $permissions['default'] : false;
  }

  /**
   * @see PermissionManager::addTempPermission()
   */
  public function addTempPermission($resource, $context, $action) {
    $actionKey = ActionKey::createKey($resource, $context, $action);
    if (self::$_logger->isDebugEnabled()) {
      self::$_logger->debug("Adding temporary permission for '$actionKey'");
    }
    $this->_tempPermissions[$actionKey] = true;
  }

  /**
   * @see PermissionManager::removeTempPermission()
   */
  public function removeTempPermission($resource, $context, $action) {
    $actionKey = ActionKey::createKey($resource, $context, $action);
    if (self::$_logger->isDebugEnabled()) {
      self::$_logger->debug("Removing temporary permission for '$actionKey'");
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
