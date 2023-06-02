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
use wcmf\lib\core\LogTrait;
use wcmf\lib\core\Session;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistenceAction;
use wcmf\lib\persistence\PersistenceFacade;
use wcmf\lib\security\PermissionManager;
use wcmf\lib\security\principal\PrincipalFactory;
use wcmf\lib\security\principal\User;
use wcmf\lib\security\principal\DynamicRole;
use wcmf\lib\security\principal\impl\AnonymousUser;
use wcmf\lib\util\StringUtil;

/**
 * AbstractPermissionManager is the base class for concrete PermissionManager
 * implementations.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class AbstractPermissionManager implements PermissionManager {
  use LogTrait;

  const RESOURCE_TYPE_ENTITY_TYPE = 'entity.type';
  const RESOURCE_TYPE_ENTITY_TYPE_PROPERTY = 'entity.type.property';
  const RESOURCE_TYPE_ENTITY_INSTANCE = 'entity.instance';
  const RESOURCE_TYPE_ENTITY_INSTANCE_PROPERTY = 'entity.instance.property';
  const RESOURCE_TYPE_OTHER = 'other';

  private array $tempPermissions = [];
  private int $tempPermissionIndex = 0;

  protected ?PersistenceFacade $persistenceFacade = null;
  protected ?Session $session = null;
  protected ?PrincipalFactory $principalFactory = null;
  protected array $dynamicRoles = [];

  /**
   * Constructor
   * @param PersistenceFacade $persistenceFacade
   * @param Session $session
   * @param array<DynamicRole>
   */
  public function __construct(PersistenceFacade $persistenceFacade,
          Session $session,
          PrincipalFactory $principalFactory,
          array $dynamicRoles=[]) {
    $this->persistenceFacade = $persistenceFacade;
    $this->session = $session;
    $this->principalFactory = $principalFactory;
    $this->dynamicRoles = $dynamicRoles;
  }

  /**
   * @see PermissionManager::authorize()
   */
  public function authorize(string $resource, string $context, string $action, string $login=null, bool $applyDefaultPolicy=true): bool {
    // get authenticated user, if no user is given
    if ($login == null) {
      $login = $this->session->getAuthUser();
    }
    if (self::logger()->isDebugEnabled()) {
      self::logger()->debug("Checking authorization for: '$resource?$context?$action' and user '".$login."'");
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
    if (self::logger()->isDebugEnabled()) {
      self::logger()->debug("Resource type: ".$resourceType);
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

      default:
        $authorized = $this->authorizeAction($resourceStr, $resourceStr, $context, $action, $login);
        break;
    }

    // check parent entities in composite relations
    if ($authorized === null && $resourceType == self::RESOURCE_TYPE_ENTITY_INSTANCE) {
      if (self::logger()->isDebugEnabled()) {
        self::logger()->debug("Check parent objects");
      }
      $mapper = $this->persistenceFacade->getMapper($type);
      $parentRelations = $mapper->getRelations('parent');
      if (sizeof($parentRelations) > 0) {

        $oidObj = ObjectId::parse($oid);
        $object = $this->withTempPermissions(function() use ($oidObj) {
          return $this->persistenceFacade->load($oidObj);
        }, [$oidObj, $context, PersistenceAction::READ]);

        if ($object != null) {
          foreach ($parentRelations as $parentRelation) {
            if ($parentRelation->getThisAggregationKind() == 'composite') {
              $parentType = $parentRelation->getOtherType();
              $parentRole = $parentRelation->getOtherRole();

              $parents = $this->withTempPermissions(function() use ($object, $parentRole) {
                return $object->getValue($parentRole);
              }, [$parentType, $context, PersistenceAction::READ]);

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

    if ($authorized === null && $applyDefaultPolicy) {
      $authorized = $this->getDefaultPolicy($login);
    }
    if (self::logger()->isDebugEnabled()) {
      self::logger()->debug("Result for $resource?$context?$action: ".(!$authorized ? "not " : "")."authorized");
    }

    return $authorized;
  }

  /**
   * Authorize a resource, context, action triple by using the permissions set
   * on another resource (e.g. authorize an action on an entity instance base
   * on the permissions defined for it's type).
   * @param string $requestedResource The resource string to authorize.
   * @param string $permissionResource The resource string to use for selecting permissions.
   * @param string $context The context in which the action takes place.
   * @param string $action The action to process.
   * @param string $login The login of the user to use for authorization
   * @return bool or null if undefined
   */
  protected function authorizeAction(string $requestedResource, string $permissionResource, string $context, string $action, string $login): ?bool {
    if (self::logger()->isDebugEnabled()) {
      self::logger()->debug("Authorizing $requestedResource?$context?$action ".
              "using permissions of $permissionResource?$context?$action");
    }
    $authorized = null;

    // check temporary permissions
    if ($this->hasTempPermission($permissionResource, $context, $action)) {
      if (self::logger()->isDebugEnabled()) {
        self::logger()->debug("Has temporary permission");
      }
      $authorized = true;
    }
    else {
      // check other permissions
      $permissions = $this->getPermissions($permissionResource, $context, $action);
      if (self::logger()->isDebugEnabled()) {
        self::logger()->debug("Permissions: ".StringUtil::getDump($permissions));
      }
      if ($permissions != null) {
        // matching permissions found, check user roles
        $authorized = $this->matchRoles($requestedResource, $permissions, $login);
      }
    }
    if (self::logger()->isDebugEnabled()) {
      self::logger()->debug("Result: ".(is_bool($authorized) ? ((!$authorized ? "not " : "")."authorized") : "not defined"));
    }
    return $authorized;
  }

  /**
   * Get the default policy that is used if no permission is set up
   * for a requested action.
   * @param string $login The login of the user to get the default policy for
   * @return bool
   */
  protected function getDefaultPolicy(string $login): bool {
    return ($login == AnonymousUser::NAME) ? false : true;
  }

  /**
   * Get the resource type and parameters (as applicable) from a resource
   * @param string $resource The resource represented as string
   * @return array{'resourceType': string, 'oid': string, 'type': string, 'oidProperty': string, 'typeProperty': string}
   */
  protected function parseResource(string $resource): array {
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
   * @param string $value A role string (+*, +administrators, -guest, entries without '+' or '-'
   *     prefix default to allow rules).
   * @return array{'default': bool, 'allow': array<string>, 'deny': array<string>}
   *     with allow and deny arrays containing role names or null, if the value is empty.
   */
  protected function deserializePermissions(string $value): array {
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
   * @param array{'default': bool, 'allow': array<string>, 'deny': array<string>} $permissions Array
   *     with allow and deny arrays containing role names and default is a boolean value derived from the wildcard policy (+* or -*).
   * @return string (+*, +administrators, -guest, entries without '+' or '-' prefix default to allow rules).
   */
  protected function serializePermissions(array $permissions): string {
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
   * @param string $resource The resource string to authorize.
   * @param array{'default': bool, 'allow': array<string>, 'deny': array<string>} $permissions Array
   *     with allow and deny arrays containing role names and default is a boolean value derived from the wildcard policy (+* or -*).
   *    allow overwrites deny and deny overwrites default
   * @param string $login the login of the user to match the roles for
   * @return bool whether the user is authorized according to the permissions
   */
  protected function matchRoles(string $resource, array $permissions, string $login): bool {
    if (self::logger()->isDebugEnabled()) {
      self::logger()->debug("Matching roles for ".$login);
    }
    $user = $this->principalFactory->getUser($login, true);
    if ($user != null) {
      foreach (['allow' => true, 'deny' => false] as $key => $result) {
        if (isset($permissions[$key])) {
          foreach ($permissions[$key] as $role) {
            if ($this->matchRole($user, $role, $resource)) {
              if (self::logger()->isDebugEnabled()) {
                self::logger()->debug($key." because of role ".$role);
              }
              return $result;
            }
          }
        }
      }
    }
    if (self::logger()->isDebugEnabled()) {
      self::logger()->debug("Check default ".$permissions['default']);
    }
    return (isset($permissions['default']) ? $permissions['default'] : false);
  }

  /**
   * Check if a user matches the role for a resource
   * @param User $user The user instance.
   * @param string $role The role name.
   * @param string $resource The resource string to authorize.
   * @return bool
   */
  protected function matchRole(User $user, string $role, string $resource): bool {
    $isDynamicRole = isset($this->dynamicRoles[$role]);
    return (($isDynamicRole && $this->dynamicRoles[$role]->match($user, $resource) === true)
            || (!$isDynamicRole && $user->hasRole($role)));
  }

  /**
   * @see PermissionManager::withTempPermissions()
   */
  public function withTempPermissions(callable $callable, array ...$permissions) {
    $handles = [];

    foreach ($permissions as $permission) {
      $resource = $permission[0];
      $context = $permission[1];
      $action = $permission[2];

      // add temporary permission
      $this->tempPermissionIndex++;
      $actionKey = ActionKey::createKey($resource, $context, $action);
      if (self::logger()->isDebugEnabled()) {
        self::logger()->debug("Adding temporary permission for '$actionKey'");
      }
      $handle = $actionKey.'#'.$this->tempPermissionIndex;
      $this->tempPermissions[$handle] = $actionKey;
      $handles[] = $handle;
    }

    // execute function
    $result = $callable();

    foreach ($handles as $handle) {
      // remove temporary permission
      if (self::logger()->isDebugEnabled()) {
        self::logger()->debug("Removing temporary permission for '$handle'");
      }
      unset($this->tempPermissions[$handle]);
    }

    return $result;
  }

  /**
   * @see PermissionManager::hasTempPermission()
   */
  public function hasTempPermission(string $resource, string $context, string $action): bool {
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
}
?>
