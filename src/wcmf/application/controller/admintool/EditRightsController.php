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
namespace wcmf\application\controller\admintool;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\Controller;

/**
 * EditRightsController is used to edit rights on a resource.
 *
 * <b>Input actions:</b>
 * - @em save Save changes right to the current resource
 *
 * <b>Output actions:</b>
 * - @em ok In any case
 *
 * @param[in,out] oid The resource to set the right on
 * @param[out] allroles An array of names of all roles
 * @param[out] rights A 2-dimensional array of defined rights: rights[configFile][action]
 * @param[out] rightnames An array of all right names
 * @param[out] configfiles An array of all config filenames
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class EditRightsController extends Controller {

  /**
   * @see Controller::validate()
   */
  function validate() {
    $request = $this->getRequest();
    $response = $this->getResponse();
    $oid = ObjectId::parse($request->getValue('oid'));
    if(!$oid) {
      $response->addError(ApplicationError::get('OID_INVALID',
        array('invalidOids' => array($request->getValue('oid')))));
      return false;
    }
    return true;
  }

  /**
   * Assign Node data to View.
   * @return False (Stop action processing chain).
   * @see Controller::executeKernel()
   */
  function executeKernel() {
    $request = $this->getRequest();
    $response = $this->getResponse();
    $config = ObjectFactory::getConfigurationInstance();
    $permissionManager = ObjectFactory::getInstance('permissionManager');

    $configurations = $config->getConfigurations();
    $rightNames = array(PersistenceAction::READ, PersistenceAction::MODIFY, PersistenceAction::DELETE, PersistenceAction::CREATE);

    // process actions
    if ($request->getAction() == 'save') {
      $resource = $request->getValue('oid');
      $context = '';

      // for all configuration files do ...
      foreach($configurations as $curConfig) {
        // for all actions files do ...
        foreach ($rightNames as $action) {
          $existingRight = $permissionManager->getPermission($curConfig, $resource, $context, $action);

          // allow
          $controlName = $action."_allow_".str_replace(".", "", $curConfig);
          $newAllowedRoles = $request->getValue($controlName);
          // add new
          if (is_array($newAllowedRoles)) {
            foreach ($newAllowedRoles as $role) {
              if (!is_array($existingRight['allow']) || !in_array($role, $existingRight['allow'])) {
                $permissionManager->createPermission($curConfig, $resource, $context, $action, $role,
                        PermissionManager::RIGHT_MODIFIER_ALLOW);
              }
            }
          }
          // remove old
          if (is_array($existingRight['allow'])) {
            foreach ($existingRight['allow'] as $role) {
              if (!is_array($newAllowedRoles) || !in_array($role, $request->getValue($controlName))) {
                $permissionManager->removePermission($curConfig, $resource, $context, $action, $role);
              }
            }
          }

          // deny
          $controlName = $action."_deny_".str_replace(".", "", $curConfig);
          $newDeniedRoles = $request->getValue($controlName);
          // add new
          if (is_array($newDeniedRoles)) {
            foreach ($newDeniedRoles as $role) {
              if (!is_array($existingRight['deny']) || !in_array($role, $existingRight['deny'])) {
                $permissionManager->createPermission($curConfig, $resource, $context, $action, $role,
                        PermissionManager::RIGHT_MODIFIER_DENY);
              }
            }
          }
          // remove old
          if (is_array($existingRight['deny'])) {
            foreach ($existingRight['deny'] as $role) {
              if (!is_array($newDeniedRoles) || !in_array($role, $request->getValue($controlName))) {
                $permissionManager->removePermission($curConfig, $resource, $context, $action, $role);
              }
            }
          }
        }
      }
    }

    // load model
    $rights = array();
    foreach($configurations as $curConfig) {
      foreach (array(PersistenceAction::READ, PersistenceAction::MODIFY, PersistenceAction::DELETE, PersistenceAction::CREATE) as $action) {
        $right = $permissionManager->getPermission($curConfig, $request->getValue('oid'), '', $action);
        // flatten role array for input control
        foreach ($right as $name => $roles) {
          $right[$name] = join(',', $roles);
        }
        $rights[$curConfig][$action] = $right;
      }
    }

    // assign model to view
    $response->setValue('oid', $request->getValue('oid'));
    // TODO: retrieve all role names
    $response->setValue('allroles', join("|", array()));
    $response->setValue('rights', $rights);
    $response->setValue('rightnames', $rightNames);
    $response->setValue('configfiles', $configurations);

    // success
    $response->setAction('ok');
    return false;
  }
}
?>
