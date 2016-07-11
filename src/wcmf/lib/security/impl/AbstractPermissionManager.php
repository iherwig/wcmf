<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2016 wemove digital solutions GmbH
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
use wcmf\lib\security\principal\PrincipalFactory;
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

  private $tempPermissions = array();
  private $tempPermissionIndex = 0;

  private static $logger = null;

  protected $persistenceFacade = null;
  protected $session = null;
  protected $principalFactory = null;

  /**
   * Constructor
   * @param $persistenceFacade
   * @param $session
   */
  public function __construct(PersistenceFacade $persistenceFacade,
          Session $session) {
    $this->persistenceFacade = $persistenceFacade;
    $this->session = $session;
    if (self::$logger == null) {
      self::$logger = LogManager::getLogger(__CLASS__);
    }
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
    elseif ($this->persistenceFacade->isKnownType($resourceStr)) {
      $resourceType = self::RESOURCE_TYPE_ENTITY_TYPE;
      $type = $resourceStr;
    }
    elseif ($this->persistenceFacade->isKnownType($extensionRemoved)) {
      $resourceType = self::RESOURCE_TYPE_ENTITY_TYPE_PROPERTY;
      $type = $extensionRemoved;
      $typeProperty = $resourceStr;
    }
    else {
      // defaults to other
      $resourceType = self::RESOURCE_TYPE_OTHER;
    }
    if (self::$logger->isDebugEnabled()) {
      self::$logger->debug("Resource type: ".$resourceType);
    }

    // proceed by authorizing type depending resource
    // always start checking from most specific
    switch ($resourceType) {
      case (self::RESOURCE_TYPE_ENTITY_INSTANCE_PROPERTY):
        $authorized = $this->authorizeAction($oidProperty, $context, $action, $login);
        if ($authorized === null) {
          $authorized = $this->authorizeAction($typeProperty, $context, $action, $login);
          if ($authorized === null) {
            $authorized = $this->authorizeAction($oid, $context, $action, $login);
            if ($authorized === null) {
              $authorized = $this->authorizeAction($type, $context, $action, $login);
            }
          }
        }
        break;

      case (self::RESOURCE_TYPE_ENTITY_INSTANCE):
        $authorized = $this->authorizeAction($oid, $context, $action, $login);
        if ($authorized === null) {
          $authorized = $this->authorizeAction($type, $context, $action, $login);
        }
        break;

      case (self::RESOURCE_TYPE_ENTITY_TYPE_PROPERTY):
        $authorized = $this->authorizeAction($typeProperty, $context, $action, $login);
        if ($authorized === null) {
          $authorized = $this->authorizeAction($type, $context, $action, $login);
        }
        break;

      case (self::RESOURCE_TYPE_ENTITY_TYPE_PROPERTY):
        $authorized = $this->authorizeAction($type, $context, $action, $login);
        break;

      default:
        $authorized = $this->authorizeAction($resourceStr, $context, $action, $login);
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
      $authorized = $this->getDefaultPolicy($login);
    }
    if (self::$logger->isDebugEnabled()) {
      self::$logger->debug("Result for $resource?$context?$action: ".(!$authorized ? "not " : "")."authorized");
    }

    return $authorized;
  }

  /**
   * Authorize the given resource, context, action triple using the
   * temporary permissions or the current user.
   * @param $resource The resource to authorize (e.g. class name of the Controller or ObjectId instance).
   * @param $context The context in which the action takes place.
   * @param $action The action to process.
   * @param $login The login of the user to use for authorization
   * @param $returnNullIfNoPermissionExists Optional, default: true
   * @return Boolean
   */
  protected function authorizeAction($resource, $context, $action, $login, $returnNullIfNoPermissionExists=true) {
    if (self::$logger->isDebugEnabled()) {
      self::$logger->debug("Authorizing $resource?$context?$action");
    }
    $authorized = null;

    // check temporary permissions
    if ($this->hasTempPermission($resource, $context, $action)) {
      if (self::$logger->isDebugEnabled()) {
        self::$logger->debug("Has temporary permission");
      }
      $authorized = true;
    }
    else {
      // check other permissions
      $permissions = $this->getPermissions($resource, $context, $action);
      if (self::$logger->isDebugEnabled()) {
        self::$logger->debug("Permissions: ".StringUtil::getDump($permissions));
      }
      if ($permissions != null) {
        // matching permissions found, check user roles
        $authorized = $this->matchRoles($permissions, $login);
      }
      elseif (!$returnNullIfNoPermissionExists) {
        // no permission definied, check for user's default policy
        $authorized = $this->getDefaultPolicy($login);
      }
    }
    if (self::$logger->isDebugEnabled()) {
      self::$logger->debug("Result for $resource?$context?$action: ".(is_bool($authorized) ? ((!$authorized ? "not " : "")."authorized") : "not defined"));
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
   * @param $login the login of the user to match the roles for
   * @return Boolean whether the user has access right according to the permissions.
   */
  protected function matchRoles($permissions, $login) {
    if (self::$logger->isDebugEnabled()) {
      self::$logger->debug("Matching roles for ".$login);
    }
    $user = $this->principalFactory->getUser($login, true);
    if ($user != null) {
      if (isset($permissions['allow'])) {
        foreach ($permissions['allow'] as $value) {
          if ($user->hasRole($value)) {
            if (self::$logger->isDebugEnabled()) {
              self::$logger->debug("Allowed because of role ".$value);
            }
            return true;
          }
        }
      }
      if (isset($permissions['deny'])) {
        foreach ($permissions['deny'] as $value) {
          if ($user->hasRole($value)) {
            if (self::$logger->isDebugEnabled()) {
              self::$logger->debug("Denied because of role ".$value);
            }
            return false;
          }
        }
      }
    }
    if (self::$logger->isDebugEnabled()) {
      self::$logger->debug("Check default ".$permissions['default']);
    }
    return isset($permissions['default']) ? $permissions['default'] : false;
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
    $actionKey = ActionKey::createKey($resource, $context, $action);
    return isset(array_flip($this->tempPermissions)[$actionKey]);
  }

  /**
   * @see PermissionManager::clearTempPermissions()
   */
  public function clearTempPermissions() {
    $this->tempPermissions = array();
  }
}
?>
