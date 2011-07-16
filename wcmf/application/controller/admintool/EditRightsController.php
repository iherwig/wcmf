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
require_once(WCMF_BASE."wcmf/lib/presentation/Controller.php");
require_once(WCMF_BASE."wcmf/lib/presentation/WCMFInifileParser.php");
require_once(WCMF_BASE."wcmf/lib/persistence/PersistenceFacade.php");
require_once(WCMF_BASE."wcmf/lib/security/RightsManager.php");
require_once(WCMF_BASE."wcmf/lib/util/ObjectFactory.php");

/**
 * @class EditRightsController
 * @ingroup Controller
 * @brief EditRightsController is used to edit rights on a resource.
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
class EditRightsController extends Controller
{
  /**
   * @see Controller::validate()
   */
  function validate()
  {
    if(strlen($this->_request->getValue('oid')) == 0)
    {
      $this->setErrorMsg("No 'oid' given in data.");
      return false;
    }
    return true;
  }
  /**
   * @see Controller::hasView()
   */
  function hasView()
  {
    return true;
  }
  /**
   * Assign Node data to View.
   * @return False (Stop action processing chain).
   * @see Controller::executeKernel()
   */
  function executeKernel()
  {
    $objectFactory = &ObjectFactory::getInstance();
    $userManager = &$objectFactory->createInstanceFromConfig('implementation', 'UserManager');
    $rightsManager = &RightsManager::getInstance();

    $configFiles = WCMFInifileParser::getIniFiles();
    $rightNames = array(ACTION_READ, ACTION_MODIFY, ACTION_DELETE, ACTION_CREATE);

    // process actions
    if ($this->_request->getAction() == 'save')
    {
      $resource = $this->_request->getValue('oid');
      $context = '';

      // for all configuration files do ...
      foreach($configFiles as $configFile)
      {
        // for all actions files do ...
        foreach ($rightNames as $action)
        {
          $existingRight = $rightsManager->getRight($configFile, $resource, $context, $action);
          
          // allow
          $controlName = $action."_allow_".str_replace(".", "", $configFile);
          $newAllowedRoles = $this->_request->getValue($controlName);
          // add new
          if (is_array($newAllowedRoles))
            foreach ($newAllowedRoles as $role)
              if (!is_array($existingRight['allow']) || !in_array($role, $existingRight['allow']))
                $rightsManager->createPermission($configFile, $resource, $context, $action, $role, RIGHT_MODIFIER_ALLOW);
          // remove old
          if (is_array($existingRight['allow']))
            foreach ($existingRight['allow'] as $role)
              if (!is_array($newAllowedRoles) || !in_array($role, $this->_request->getValue($controlName)))
                $rightsManager->removePermission($configFile, $resource, $context, $action, $role);

          // deny
          $controlName = $action."_deny_".str_replace(".", "", $configFile);
          $newDeniedRoles = $this->_request->getValue($controlName);
          // add new
          if (is_array($newDeniedRoles))
            foreach ($newDeniedRoles as $role)
              if (!is_array($existingRight['deny']) || !in_array($role, $existingRight['deny']))
                $rightsManager->createPermission($configFile, $resource, $context, $action, $role, RIGHT_MODIFIER_DENY);
          // remove old
          if (is_array($existingRight['deny']))
            foreach ($existingRight['deny'] as $role)
              if (!is_array($newDeniedRoles) || !in_array($role, $this->_request->getValue($controlName)))
                $rightsManager->removePermission($configFile, $resource, $context, $action, $role);
        }
      }
    }

    // load model
    $rights = array();
    foreach($configFiles as $configFile)
      foreach (array(ACTION_READ, ACTION_MODIFY, ACTION_DELETE, ACTION_CREATE) as $action)
      {
        $right = $rightsManager->getRight($configFile, $this->_request->getValue('oid'), '', $action);
        // flatten role array for input control
        foreach ($right as $name => $roles)
          $right[$name] = join(',', $roles);
        $rights[$configFile][$action] = $right;
      }

    // assign model to view
    $this->_response->setValue('oid', $this->_request->getValue('oid'));
    $this->_response->setValue('allroles', join("|", $userManager->listRoles()));
    $this->_response->setValue('rights', $rights);
    $this->_response->setValue('rightnames', $rightNames);
    $this->_response->setValue('configfiles', WCMFInifileParser::getIniFiles());

    // success
    $this->_response->setAction('ok');
    return false;
  }
}
?>
