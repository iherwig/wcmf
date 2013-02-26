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
namespace wcmf\lib\security;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\presentation\Action;
use wcmf\lib\presentation\Application;
use wcmf\lib\security\AnonymousUser;
use wcmf\lib\security\AuthUser;

define("RIGHT_MODIFIER_ALLOW", "+");
define("RIGHT_MODIFIER_DENY",  "-");

define("AUTHORIZATION_SECTION", "authorization");

/**
 * RightsManager is used to handle all authorization requests.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class RightsManager {

  /**
   * Actions that do not require authorization
   * TODO: make this configurable
   */
  private static $_PUBLIC_ACTIONS = array('fatal', 'login', 'dologin', 'logout');

  private static $_instance = null;
  private $_anonymousUser = null;

  private function __construct() {
    // include this later to avoid circular includes
    require_once(WCMF_BASE."wcmf/lib/security/AnonymousUser.php");
    $this->_anonymousUser = new AnonymousUser(new ObjectId('', ''));
  }

  /**
   * Returns an instance of the class.
   * @return A reference to the only instance of the Singleton object
   */
  public static function getInstance() {
    if (!isset(self::$_instance)) {
      self::$_instance = new RightsManager();
    }
    return self::$_instance;
  }

  /**
   * Get session variable name for the authenticated user.
   * @return The variable name.
   */
  public static function getAuthUserVarname() {
    return 'auth_user_'.Application::getId();
  }

  /**
   * Get authenticated user.
   * @return AuthUser object or null if not logged in.
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
   * See if the RightsManager is working in anonymous mode. In anonymous mode all
   * authorization requests answered positive and AuthUser is an instance of AnonymousUser
   * The mode is set in configuration section 'application' key 'anonymous'
   * @return True/False wether in anonymous mode
   */
  public function isAnonymous() {
    $configuration = ObjectFactory::getInstance('configuration');
    return $configuration->getBooleanValue('anonymous', 'application');
  }

  /**
   * Deactivate rights checking by setting the anonymous confguration value.
   */
  public function deactivate() {
    $configuration = ObjectFactory::getInstance('configuration');
    $configuration->setValue('anonymous', true, 'application');
  }

  /**
   * (Re-)activate rights checking by unsetting the anonymous confguration value.
   */
  public function activate() {
    $configuration = ObjectFactory::getInstance('configuration');
    $configuration->setValue('anonymous', false, 'application');
  }

  /**
   * Authorize for given resource, context, action triple.
   * @param resource The resource to authorize (e.g. class name of the Controller or ObjectId).
   * @param context The context in which the action takes place.
   * @param action The action to process.
   * @return True/False whether authorization succeded/failed.
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

      $actionKey = Action::getBestMatch(AUTHORIZATION_SECTION, $resource, $context, $action);

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
   * Get the rights on a resource, context, action combination.
   * @param config The configuration file to create the right in.
   * @param resource The resource (e.g. class name of the Controller or OID).
   * @param context The context in which the action takes place.
   * @param action The action to process.
   * @return An assoziative array with keys 'default', 'allow', 'deny' and the attached roles as values.
   * @see AuthUser::parsePolicy
   */
  public function getRight($config, $resource, $context, $action) {
    $parser = new IniFileConfiguration();
    $parser->setConfigPath(dirname($config));
    $parser->parseIniFile(basename($config));

    $rightDef = $resource."?".$context."?".$action;
    if ($parser->getValue($rightDef, AUTHORIZATION_SECTION) !== false) {
      return AuthUser::parsePolicy($parser->getValue($rightDef, AUTHORIZATION_SECTION));
    }
    else {
      return array();
    }
  }

  /**
   * Create/Change a permission for a role on a resource, context, action combination.
   * @param config The configuration file to create the right in.
   * @param resource The resource (e.g. class name of the Controller or OID).
   * @param context The context in which the action takes place.
   * @param action The action to process.
   * @param role The role to authorize.
   * @param modifier One of the RIGHT_MODIFIER_ constants.
   * @return True/False whether creation succeded/failed.
   */
  public function createPermission($config, $resource, $context, $action, $role, $modifier) {
    return self::modifyRight($config, $resource, $context, $action, $role, $modifier);
  }

  /**
   * Remove a role from a right on a resource, context, action combination.
   * @param config The configuration file to remove the right from.
   * @param resource The resource (e.g. class name of the Controller or OID).
   * @param context The context in which the action takes place.
   * @param action The action to process.
   * @param role The role to remove.
   * @return True/False whether removal succeded/failed.
   */
  public function removePermission($config, $resource, $context, $action, $role) {
    return self::modifyRight($config, $resource, $context, $action, $role, null);
  }

  /**
   * Modify a right of a role on a resource, context, action combination.
   * @param config The configuration file to remove the right from.
   * @param resource The resource (e.g. class name of the Controller or OID).
   * @param context The context in which the action takes place.
   * @param action The action to process.
   * @param role The role for which to cancel authorization.
   * @param modifier One of the RIGHT_MODIFIER_ constants or null (which means remove role).
   * @return True/False whether modification succeded/failed.
   */
  public function modifyRight($config, $resource, $context, $action, $role, $modifier) {
    $iniFile = new IniFileParser($config);
    $iniFile->parseIniFile($config);

    $rightDef = $resource."?".$context."?".$action;
    $rightVal = '';
    if ($modifier != null) {
      $rightVal = $modifier.$role;
    }
    if ($iniFile->getValue($rightDef, AUTHORIZATION_SECTION) === false && $modifier != null) {
      $iniFile->setValue($rightDef, $rightVal, AUTHORIZATION_SECTION, true);
    }
    else {
      $value = $iniFile->getValue($rightDef, AUTHORIZATION_SECTION);
      // remove role from value
      $value = trim(preg_replace("/[+\-]*".$role."/", "", $value));
      if ($value != '') {
        $iniFile->setValue($rightDef, $value." ".$rightVal, AUTHORIZATION_SECTION, false);
      }
      else {
        $iniFile->removeKey($rightDef, AUTHORIZATION_SECTION);
      }
    }

    $iniFile->writeConfiguration();
    return true;
  }
}
?>
