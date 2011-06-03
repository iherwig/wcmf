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
require_once(WCMF_BASE."wcmf/lib/presentation/class.Controller.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(WCMF_BASE."wcmf/lib/model/class.Node.php");
require_once(WCMF_BASE."wcmf/lib/util/class.ObjectFactory.php");

/**
 * @class UserController
 * @ingroup Controller
 * @brief UserController is used to edit data of the current users.
 *
 * <b>Input actions:</b>
 * - @em save Save the new password
 * - unspecified: Show the change password screen
 *
 * <b>Output actions:</b>
 * - @em edituser In any case
 *
 * @param[in] changepassword Must be 'yes' to initiate password change action
 * @param[in] oldpassword The old password
 * @param[in] newpassword1 The new password
 * @param[in] newpassword2 The new password
 * @param[out] message The message describing the result
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class UserController extends Controller
{
  var $_userManager = null;

  /**
   * @see Controller::initialize()
   */
  function initialize(&$request, &$response)
  {
    parent::initialize($request, $response);

    // create UserManager instance
    $objectFactory = &ObjectFactory::getInstance();
    $this->_userManager = &$objectFactory->createInstanceFromConfig('implementation', 'UserManager');
    if ($this->_userManager == null)
      WCMFException::throwEx($objectFactory->getErrorMsg(), __FILE__, __LINE__);
  }
  /**
   * @see Controller::hasView()
   */
  function hasView()
  {
    return true;
  }
  /**
   * Process action and assign data to View.
   * @return False (Stop action processing chain).
   * @see Controller::executeKernel()
   */
  function executeKernel()
  {
    $persistenceFacade = PersistenceFacade::getInstance();
    $rightsManager = RightsManager::getInstance();

    // process actions
    $result = '';

    // save changes
    if ($this->_request->getAction() == 'save')
    {
      // load model
      $user = $rightsManager->getAuthUser();
      $oid = new ObjectId(UserManager::getUserClassName(), $user->getUserId());
      $principal = $this->_userManager->getPrincipal($oid);

      // password
      $this->_userManager->beginTransaction();
      if ($this->_request->getValue('changepassword') == 'yes')
      {
        $this->_userManager->changePassword($principal->getLogin(), $this->_request->getValue('oldpassword'),
          $this->_request->getValue('newpassword1'), $this->_request->getValue('newpassword2'));
        $message .= Message::get("The password was successfully changed.");
      }
      $this->_userManager->commitTransaction();
    }
    $this->_response->setValue("message", $message);

    // success
    $this->_response->setAction('edituser');
    return false;
  }
}
?>
