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
use wcmf\lib\model\Node;
use wcmf\lib\presentation\Controller;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;
use wcmf\lib\security\User;
use wcmf\lib\security\Role;

/**
 * PrincipalController is used to edit users and roles.
 *
 * <b>Input actions:</b>
 * - @em newprincipal Create a new principal of the given type
 * - @em editprincipal Edit a given principal
 * - @em save Save changes to the given principal
 * - @em delprincipal Delete the given principal
 *
 * <b>Output actions:</b>
 * - @em overview If the action was delprincipal
 * - @em ok In any other case
 *
 * @param[in,out] newtype The principal type to create (user or role)
 * @param[in] deleteoids A comma separated list of principal object ids to delete
 * @param[in,out] oid The object id of the principal to edit
 * @param[in] <oid> A node defining what to save. The node should only contain those values, that should be changed
 *                  This may be achived by creating the node using the node constructor (instead of using PersistenceFacade::create)
 *                  and setting the values on it.
 * @param[in] changepassword If given, the password will tried to be changed
 * @param[in] newpassword1 The new password of the current user
 * @param[in] newpassword2 The new password of the current user repeated
 * @param[in] principals The list of all users that should belong to the displayed role or
 *              the list of all roles that the displayed user should have
 * @param[out] principal The principal to display
 * @param[out] principalBaseList The list of all users, if a role is displayed or
 *              the list of all roles if a user is displayed
 * @param[out] principalList The list of all users that belong to the displayed role or
 *              the list of all roles that the displayed user has
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PrincipalController extends Controller {

  var $_userManager = null;

  /**
   * @see Controller::initialize()
   */
  protected function initialize(Request $request, Response $response) {
    if (strlen($request->getContext()) == 0) {
      $request->setContext('user');
      $response->setContext('user');
    }

    parent::initialize($request, $response);

    // create UserManager instance
    $this->_userManager = ObjectFactory::getInstance('userManager');
  }

  /**
   * @see Controller::validate()
   */
  protected function validate() {
    $request = $this->getRequest();
    $response = $this->getResponse();
    $invalidParameters = array();
    if($request->getAction() == 'newprincipal') {
      if(strlen($request->getValue('newtype')) == 0) {
        $invalidParameters[] = 'newtype';
      }
    }
    if(in_array($request->getAction(), array('editprincipal', 'save'))) {
      if(strlen($request->getValue('oid')) == 0) {
        $invalidParameters[] = 'oid';
      }
    }
    if($request->getAction() == 'delprincipal') {
      if(strlen($request->getValue('deleteoids')) == 0) {
        $invalidParameters[] = 'deleteoids';
      }
    }
    if (sizeof($invalidParameters) > 0) {
      $response->addError(ApplicationError::get('PARAMETER_INVALID',
        array('invalidParameters' => $invalidParameters)));
      return false;
    }
    return true;
  }

  /**
   * Process action and assign data to View.
   * @return Array of given context and action 'overview' on delete.
   *         False else (Stop action processing chain).
   * @see Controller::executeKernel()
   */
  protected function executeKernel() {
    $request = $this->getRequest();
    $response = $this->getResponse();
    $this->_userManager->beginTransaction();

    // process actions

    // DELETE
    if($request->getAction() == 'delprincipal') {
      $deleteOIDs = split(',', $request->getValue('deleteoids'));
      foreach($deleteOIDs as $oid) {
        $this->beforeDelete($this->_userManager->getPrincipal($oid));
        $this->_userManager->removePrincipal($oid);
      }
      // return
      $this->_userManager->commitTransaction();
      $response->setAction('overview');
      return true;
    }

    // NEW
    if($request->getAction() == 'newprincipal') {
      $newType = $request->getValue('newtype');
      $newNode = new Node($newType);

      if($newType == 'user')
        $newPrincipal = $this->_userManager->createUser('', '', $newNode->getOID(), '', '');
      else
        $newPrincipal = $this->_userManager->createRole($newNode->getOID());

      // set the login/name to the oid
      if($newType == 'user')
        $this->_userManager->setUserProperty($newNode->getOID(), USER_PROPERTY_LOGIN, $newPrincipal->getOID()->__toString());
      else
        $this->_userManager->setRoleProperty($newNode->getOID(), ROLE_PROPERTY_NAME, $newPrincipal->getOID()->__toString());
      $newPrincipal->save();

      $this->afterInsert($newPrincipal);

      // redirect to edit view by changing the request parameters for the following code
      $request->setAction('editprincipal');
      $request->setValue('oid', $newPrincipal->getOID());
    }

    // EDIT, SAVE
    if (in_array($request->getAction(), array('editprincipal', 'save')) || in_array($request->getContext(), array('user', 'role'))) {
      // load model
      $principal = $this->_userManager->getPrincipal($request->getValue('oid'));

      // save changes
      if ($request->getAction() == 'save') {
        $saveNode = $request->getValue($request->getValue('oid'));

        if ($principal instanceof User) {
          // properties
          foreach(array(USER_PROPERTY_LOGIN, USER_PROPERTY_NAME, USER_PROPERTY_FIRSTNAME, USER_PROPERTY_CONFIG) as $property) {
            $value = $saveNode->getValue($property);
            if ($value != $principal->getValue($property))
            {
              $this->_userManager->setUserProperty($principal->getLogin(), $property, $value);
              $principal->setValue($property, $value);
            }
          }
          // password
          if ($request->hasValue('changepassword')) {
            $this->_userManager->resetPassword($principal->getLogin(), $request->getValue('newpassword1'),
                                                 $request->getValue('newpassword2'));
          }
          // roles
          $roles = $this->_userManager->listRoles();
          $userRoles = $this->_userManager->listUserRoles($principal->getLogin());
          $principals = $request->getValue('principals');
          foreach($roles as $curRole) {
            if ((is_array($principals) && in_array($curRole, $principals)) && (!is_array($userRoles) || !in_array($curRole, $userRoles)))
              $this->_userManager->addUserToRole($curRole, $principal->getLogin());
            if ((!is_array($principals) || !in_array($curRole, $principals)) && (is_array($userRoles) && in_array($curRole, $userRoles)))
              $this->_userManager->removeUserFromRole($curRole, $principal->getLogin());
          }
        }
        if ($principal instanceof Role) {
          // properties
          foreach(array(ROLE_PROPERTY_NAME) as $property) {
            $value = $saveNode->getValue($property);
              if ($value != $principal->getValue($property)) {
                $this->_userManager->setRoleProperty($principal->getName(), $property, $value);
                $principal->setValue($property, $value);
            }
          }
          // members
          $users = $this->_userManager->listUsers();
          $roleMembers = $this->_userManager->listRoleMembers($principal->getName());
          $principals = $request->getValue('principals');
          foreach($users as $curUser) {
            if (in_array($curUser, $principals) && !in_array($curUser, $roleMembers)) {
              $this->_userManager->addUserToRole($principal->getName(), $curUser);
            }
            if (!in_array($curUser, $principals) && in_array($curUser, $roleMembers)) {
              $this->_userManager->removeUserFromRole($principal->getName(), $curUser);
            }
          }
        }
        $this->afterUpdate($this->_userManager->getPrincipal($request->getValue('oid')));
      }

      // reload model
      $principal = $this->_userManager->getPrincipal($request->getValue('oid'));
      if ($principal instanceof User) {
        $principalBaseList = $this->_userManager->listRoles();
        $principalList = $this->_userManager->listUserRoles($principal->getLogin());
      }
      elseif ($principal instanceof Role) {
        $principalBaseList = $this->_userManager->listUsers();
        $principalList = $this->_userManager->listRoleMembers($principal->getName());
      }

      // assign model to view
      $response->setValue('oid', $request->getValue('oid'));
      $response->setValue('newtype', $request->getValue('newtype'));
      $response->setValue('principal', $principal);
      $response->setValue('principalBaseList', join("|", $principalBaseList));
      $response->setValue('principalList', join(",", $principalList));

      $configurations = ObjectFactory::getConfigurationInstance()->getConfigurations();
      array_push($configurations, '');
      $response->setValue('configFiles', join("|", $configurations));
    }

    $this->_userManager->commitTransaction();

    // success
    $response->setAction('ok');
    return false;
  }

  /**
   * Called before deleting an exisiting principal.
   * @note subclasses will override this to implement special application requirements.
   * @param principal A reference to the principal to delete (@see UserManager::getPrincipal).
   */
  function beforeDelete($principal) {}

  /**
   * Called after inserting a new principal.
   * @note subclasses will override this to implement special application requirements.
   * @param principal A reference to the principal inserted (@see UserManager::getPrincipal).
   */
  function afterInsert($principal) {}

  /**
   * Called after updating an existing principal.
   * @note subclasses will override this to implement special application requirements.
   * @param principal A reference to the principal updated (@see UserManager::getPrincipal).
   */
  function afterUpdate($principal) {}
}
?>
