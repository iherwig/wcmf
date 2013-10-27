<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2009 wemove digital solutions GmbH
 *
 * Licensed under the terms of any of the following licenses
 * at your choice:
 *
 * - GNU Lesser General Public License (LGPL)
 *   http://www.gnu.org/licenses/lgpl.html
 * - Eclipse Public License (EPL)
 *   http://www.eclipse.org/org/documents/epl-v10.php
 *
 * See the license.txt file distributed with this work for
 * additional information.
 *
 * $Id$
 */
namespace wcmf\lib\security\impl;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\presentation\Action;
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

  /**
   * Actions that do not require authorization
   * TODO: make this configurable
   */
  private static $_PUBLIC_ACTIONS = array('fatal', 'login', 'logout', 'messages');

  private $_anonymousUser = null;

  /**
   * Constructor
   */
  public function __construct() {
    $this->_anonymousUser = new AnonymousUser(new ObjectId('', ''));
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
    if (self::isAnonymous()) {
      return $this->_anonymousUser;
    }
    else {
      // include this later to avoid circular includes
      $session = ObjectFactory::getInstance('session');
      $user = null;
      $userVarname = self::getAuthUserVarname();
      if ($session->exist($userVarname)) {
        $user = $session->get($userVarname);
        $user->resetRoleCache();
      }
      return $user;
    }
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
    if (!in_array($action, self::$_PUBLIC_ACTIONS)) {
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

      $actionKey = Action::getBestMatch(self::AUTHORIZATION_SECTION, $resource, $context, $action);

      $authUser = $this->getAuthUser();
      if (!($authUser && $authUser->authorize($actionKey))) {
        if ($authUser) {
          // valid user but authorization for action failed
          return false;
        }
        else {
          // no valid user
          return false;
        }
      }
    }
    return true;
  }

  /**
   * @see PermissionManager::getPermission()
   */
  public function getPermission($config, $resource, $context, $action) {
    $configuration = new IniFileConfiguration(dirname($config));
    $configuration->addConfiguration(basename($config));

    $rightDef = $resource."?".$context."?".$action;
    if ($configuration->getValue($rightDef, self::AUTHORIZATION_SECTION) !== false) {
      return Policy::parse($configuration->getValue($rightDef, self::AUTHORIZATION_SECTION));
    }
    else {
      return array();
    }
  }

  /**
   * @see PermissionManager::createPermission()
   */
  public function createPermission($config, $resource, $context, $action, $role, $modifier) {
    return self::modifyPermission($config, $resource, $context, $action, $role, $modifier);
  }

  /**
   * @see PermissionManager::removePermission()
   */
  public function removePermission($config, $resource, $context, $action, $role) {
    return self::modifyPermission($config, $resource, $context, $action, $role, null);
  }

  /**
   * @see PermissionManager::modifyPermission()
   */
  protected function modifyPermission($config, $resource, $context, $action, $role, $modifier) {
    $configuration = new IniFileConfiguration(dirname($config));
    $configuration->addConfiguration(basename($config));

    $rightDef = $resource."?".$context."?".$action;
    $rightVal = '';
    if ($modifier != null) {
      $rightVal = $modifier.$role;
    }
    if ($configuration->getValue($rightDef, self::AUTHORIZATION_SECTION) === false && $modifier != null) {
      $configuration->setValue($rightDef, $rightVal, self::AUTHORIZATION_SECTION, true);
    }
    else {
      $value = $configuration->getValue($rightDef, self::AUTHORIZATION_SECTION);
      // remove role from value
      $value = trim(preg_replace("/[+\-]*".$role."/", "", $value));
      if ($value != '') {
        $configuration->setValue($rightDef, $value." ".$rightVal, self::AUTHORIZATION_SECTION, false);
      }
      else {
        $configuration->removeKey($rightDef, self::AUTHORIZATION_SECTION);
      }
    }

    $configuration->writeConfiguration(basename($config));
    return true;
  }
}
?>
