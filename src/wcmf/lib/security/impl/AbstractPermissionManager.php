<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2018 wemove digital solutions GmbH
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
use wcmf\lib\security\principal\User;
use wcmf\lib\security\principal\impl\AnonymousUser;
use wcmf\lib\security\principal\PrincipalFactory;
use wcmf\lib\util\StringUtil;

/**
 * AbstractPermissionManager is the base class for concrete PermissionManager
 * implementations.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class AbstractPermissionManager implements PermissionManager {

  const RESOURCE_TYPE_ENTITY_TYPE = 'entity.type';
  const RESOURCE_TYPE_ENTITY_TYPE_PROPERTY = 'entity.type.property';
  const RESOURCE_TYPE_ENTITY_INSTANCE = 'entity.instance';
  const RESOURCE_TYPE_ENTITY_INSTANCE_PROPERTY = 'entity.instance.property';
  const RESOURCE_TYPE_OTHER = 'other';

  private $tempPermissions = [];
  private $tempPermissionIndex = 0;

  private static $logger = null;

  protected $persistenceFacade = null;
  protected $session = null;
  protected $dynamicRoles = [];
  protected $principalFactory = null;

  /**
   * Constructor
   * @param $persistenceFacade
   * @param $session
   */
  public function __construct(PersistenceFacade $persistenceFacade,
          Session $session,
          array $dynamicRoles=[]) {
    $this->persistenceFacade = $persistenceFacade;
    $this->session = $session;
    if (self::$logger == null) {
      self::$logger = LogManager::getLogger(__CLASS__);
    }
    $this->dynamicRoles = $dynamicRoles;
  }

  /**
   * Set the principal factory instances.
   * @param $principalFactory
   */
  public function setPrincipalFactory(PrincipalFactory $principalFactory) {
    $this->principalFactory = $principalFactory;
  }

  /**
   * @see PermissionManager::authorize()
   */
  public function authorize($resource, $context, $action, $login=null) {
    // get authenticated user, if no user is given
    if ($login == null) {
      $login = $this->session->getAuthUser();
    }
    if (self::$logger->isDebugEnabled()) {
      self::$logger->debug("Checking authorization for: '$resource?$context?$action' and user '".$login."'");
    }

    // normalize resource to string
    $resourceStr = ($resource instanceof ObjectId) ? $resource->__toString() : $resource;

    // determine the resource type and set entity type, oid and property if applicable
    $resourceDesc = $this->parseResource($resourceStr);
    $resourceType = $resourceDesc['resourceType'];
    $oid = $resourceDesc['oid'];
    $type = $resourceDesc['type'];
    $oidProperty = $resourceDesc['oidProperty'];
    $typeProperty = $resourceDesc['typeProperty'];
    if (self::$logger->isDebugEnabled()) {
      self::$logger->debug("Resource type: ".$resourceType);
    }

    // proceed by authorizing type depending resource
    // always start checking from most specific
    switch ($resourceType) {
      case (self::RESOURCE_TYPE_ENTITY_INSTANCE_PROPERTY):
        $authorized = $this->authorizeAction($oidProperty, $oidProperty, $context, $action, $login);
        if ($authorized === null) {
          $authorized = $this->authorizeAction($oidProperty, $typeProperty, $context, $action, $login);
          if ($authorized === null) {
            $authorized = $this->authorizeAction($oidProperty, $oid, $context, $action, $login);
            if ($authorized === null) {
              $authorized = $this->authorizeAction($oidProperty, $type, $context, $action, $login);
            }
          }
        }
        break;

      case (self::RESOURCE_TYPE_ENTITY_INSTANCE):
        $authorized = $this->authorizeAction($oid, $oid, $context, $action, $login);
        if ($authorized === null) {
          $authorized = $this->authorizeAction($oid, $type, $context, $action, $login);
        }
        break;

      case (self::RESOURCE_TYPE_ENTITY_TYPE_PROPERTY):
        $authorized = $this->authorizeAction($typeProperty, $typeProperty, $context, $action, $login);
        if ($authorized === null) {
          $authorized = $this->authorizeAction($typeProperty, $type, $context, $action, $login);
        }
        break;

      case (self::RESOURCE_TYPE_ENTITY_TYPE_PROPERTY):
        $authorized = $this->authorizeAction($type, $type, $context, $action, $login);
        break;

      default:
        $authorized = $this->authorizeAction($resourceStr, $resourceStr, $context, $action, $login);
        break;
    }

    // check parent entities in composite relations
    if ($authorized === null && $resourceType == self::RESOURCE_TYPE_ENTITY_INSTANCE) {
      if (self::$logger->isDebugEnabled()) {
        self::$logger->debug("Check parent objects");
      }
      $mapper = $this->persistenceFacade->getMapper($type);
      $parentRelations = $mapper->getRelations('parent');
      if (sizeof($parentRelations) > 0) {

        $oidObj = ObjectId::parse($oid);
        $tmpPerm = $this->addTempPermission($oidObj, $context, PersistenceAction::READ);
        $object = $this->persistenceFacade->load($oidObj);
        $this->removeTempPermission($tmpPerm);

        if ($object != null) {
          foreach ($parentRelations as $parentRelation) {
            if ($parentRelation->getThisAggregationKind() == 'composite') {
              $parentType = $parentRelation->getOtherType();

              $tmpPerm = $this->addTempPermission($parentType, $context, PersistenceAction::READ);
              $parents = $object->getValue($parentRelation->getOtherRole());
              $this->removeTempPermission($tmpPerm);

              if ($parents != null) {
                if (!$parentRelation->isMultiValued()) {
                  $parents = [$parents];
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
      $authorized = $this->getDefaultPolicy($login);
    }
    if (self::$logger->isDebugEnabled()) {
      self::$logger->debug("Result for $resource?$context?$action: ".(!$authorized ? "not " : "")."authorized");
    }

    return $authorized;
  }

  /**
   * Authorize a resource, context, action triple by using the permissions set
   * on another resource (e.g. authorize an action on an entity instance base
   * on the permissions defined for it's type).
   * @param $requestedResource The resource string to authorize.
   * @param $permissionResource The resource string to use for selecting permissions.
   * @param $context The context in which the action takes place.
   * @param $action The action to process.
   * @param $login The login of the user to use for authorization
   * @return Boolean or null if undefined
   */
  protected function authorizeAction($requestedResource, $permissionResource,
          $context, $action, $login) {
    if (self::$logger->isDebugEnabled()) {
      self::$logger->debug("Authorizing $requestedResource?$context?$action ".
              "using permissions of $permissionResource?$context?$action");
    }
    $authorized = null;

    // check temporary permissions
    if ($this->hasTempPermission($permissionResource, $context, $action)) {
      if (self::$logger->isDebugEnabled()) {
        self::$logger->debug("Has temporary permission");
      }
      $authorized = true;
    }
    else {
      // check other permissions
      $permissions = $this->getPermissions($permissionResource, $context, $action);
      if (self::$logger->isDebugEnabled()) {
        self::$logger->debug("Permissions: ".StringUtil::getDump($permissions));
      }
      if ($permissions != null) {
        // matching permissions found, check user roles
        $authorized = $this->matchRoles($requestedResource, $permissions, $login);
      }
    }
    if (self::$logger->isDebugEnabled()) {
      self::$logger->debug("Result: ".(is_bool($authorized) ? ((!$authorized ? "not " : "")."authorized") : "not defined"));
    }
    return $authorized;
  }

  /**
   * Get the default policy that is used if no permission is set up
   * for a requested action.
   * @param $login The login of the user to get the default policy for
   * @return Boolean
   */
  protected function getDefaultPolicy($login) {
    return ($login == AnonymousUser::USER_GROUP_NAME) ? false : true;
  }

  /**
   * Get the resource type and parameters (as applicable) from a resource
   * @param $resource The resource represented as string
   * @return Associative array with keys
   *   'resourceType' (one of the RESOURCE_TYPE_ constants),
   *   'oid' (object id),
   *   'type' (entity type),
   *   'oidProperty' (object id with instance property),
   *   'typeProperty' (type id with entity property)
   */
  protected function parseResource($resource) {
    $resourceType = null;
    $oid = null;
    $type = null;
    $oidProperty = null;
    $typeProperty = null;
    $extensionRemoved = preg_replace('/\.[^\.]*?$/', '', $resource);
    if (($oidObj = ObjectId::parse($resource)) !== null) {
      $resourceType = self::RESOURCE_TYPE_ENTITY_INSTANCE;
      $oid = $resource;
      $type = $oidObj->getType();
    }
    elseif (($oidObj = ObjectId::parse($extensionRemoved)) !== null) {
      $resourceType = self::RESOURCE_TYPE_ENTITY_INSTANCE_PROPERTY;
      $oid = $extensionRemoved;
      $type = $oidObj->getType();
      $oidProperty = $resource;
      $typeProperty = $type.substr($resource, strlen($extensionRemoved));
    }
    elseif ($this->persistenceFacade->isKnownType($resource)) {
      $resourceType = self::RESOURCE_TYPE_ENTITY_TYPE;
      $type = $resource;
    }
    elseif ($this->persistenceFacade->isKnownType($extensionRemoved)) {
      $resourceType = self::RESOURCE_TYPE_ENTITY_TYPE_PROPERTY;
      $type = $extensionRemoved;
      $typeProperty = $resource;
    }
    else {
      // defaults to other
      $resourceType = self::RESOURCE_TYPE_OTHER;
    }
    return [
      'resourceType' => $resourceType,
      'oid' => $oid,
      'type' => $type,
      'oidProperty' => $oidProperty,
      'typeProperty' => $typeProperty
    ];
  }

  /**
   * Parse a permissions string and return an associative array with the keys
   * 'default', 'allow', 'deny', where 'allow', 'deny' are arrays itself holding roles
   * and 'default' is a boolean value derived from the wildcard policy (+* or -*).
   * @param $value A role string (+*, +administrators, -guest, entries without '+' or '-'
   *     prefix default to allow rules).
   * @return Associative array containing the permissions as an associative array with the keys
   *     'default', 'allow', 'deny' or null, if value is empty
   */
  protected function deserializePermissions($value) {
    if (strlen($value) == 0) {
      return null;
    }
    $result = [
      'default' => null,
      'allow' => [],
      'deny' => [],
    ];

    $roleValues = explode(" ", $value);
    foreach ($roleValues as $roleValue) {
      $roleValue = trim($roleValue);
      $matches = [];
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
   *     where 'allow', 'deny' are arrays itself holding roles and 'default' is a
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
   * @param $resource The resource string to authorize.
   * @param $permissions An array containing permissions as an associative array
   *     with the keys 'default', 'allow', 'deny', where 'allow', 'deny' are arrays
   *     itself holding roles and 'default' is a boolean value derived from the
   *     wildcard policy (+* or -*). 'allow' overwrites 'deny' overwrites 'default'
   * @param $login the login of the user to match the roles for
   * @return Boolean whether the user is authorized according to the permissions
   */
  protected function matchRoles($resource, $permissions, $login) {
    if (self::$logger->isDebugEnabled()) {
      self::$logger->debug("Matching roles for ".$login);
    }
    $user = $this->principalFactory->getUser($login, true);
    if ($user != null) {
      foreach (['allow' => true, 'deny' => false] as $key => $result) {
        if (isset($permissions[$key])) {
          foreach ($permissions[$key] as $role) {
            if ($this->matchRole($user, $role, $resource)) {
              if (self::$logger->isDebugEnabled()) {
                self::$logger->debug($key." because of role ".$role);
              }
              return $result;
            }
          }
        }
      }
    }
    if (self::$logger->isDebugEnabled()) {
      self::$logger->debug("Check default ".$permissions['default']);
    }
    return (isset($permissions['default']) ? $permissions['default'] : false);
  }

  /**
   * Check if a user matches the role for a resource
   * @param $user The user instance.
   * @param $role The role name.
   * @param $resource The resource string to authorize.
   * @return Boolean
   */
  protected function matchRole(User $user, $role, $resource) {
    $isDynamicRole = isset($this->dynamicRoles[$role]);
    return (($isDynamicRole && $this->dynamicRoles[$role]->match($user, $resource) === true)
            || (!$isDynamicRole && $user->hasRole($role)));
  }

  /**
   * @see PermissionManager::addTempPermission()
   */
  public function addTempPermission($resource, $context, $action) {
    $this->tempPermissionIndex++;
    $actionKey = ActionKey::createKey($resource, $context, $action);
    if (self::$logger->isDebugEnabled()) {
      self::$logger->debug("Adding temporary permission for '$actionKey'");
    }
    $handle = $actionKey.'#'.$this->tempPermissionIndex;
    $this->tempPermissions[$handle] = $actionKey;
    return $handle;
  }

  /**
   * @see PermissionManager::removeTempPermission()
   */
  public function removeTempPermission($handle) {
    if (self::$logger->isDebugEnabled()) {
      self::$logger->debug("Removing temporary permission for '$handle'");
    }
    unset($this->tempPermissions[$handle]);
  }

  /**
   * @see PermissionManager::hasTempPermission()
   */
  public function hasTempPermission($resource, $context, $action) {
    if (sizeof($this->tempPermissions) == 0) {
      return false;
    }

    // check if the resource has a direct permission
    $permissions = array_flip($this->tempPermissions);
    $actionKey = ActionKey::createKey($resource, $context, $action);
    if (!isset($permissions[$actionKey])) {
      // if not and the resource belongs to an entity instance,
      // we might have a permission for the type
      $resourceDesc = $this->parseResource($resource);
      switch ($resourceDesc['resourceType']) {
        case self::RESOURCE_TYPE_ENTITY_INSTANCE:
          $typeResource = $resourceDesc['type'];
          break;
        case self::RESOURCE_TYPE_ENTITY_INSTANCE_PROPERTY:
          $typeResource = $resourceDesc['typeProperty'];
          break;
        default:
          $typeResource = null;
      }
      // set alternative action key
      if ($typeResource != null) {
        $actionKey = ActionKey::createKey($typeResource, $context, $action);
      }
    }
    return isset($permissions[$actionKey]);
  }

  /**
   * @see PermissionManager::clearTempPermissions()
   */
  public function clearTempPermissions() {
    $this->tempPermissions = [];
  }
}
?>
