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
namespace wcmf\application\controller;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\i18n\Message;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\Controller;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;

/**
 * UserController is used to edit data of the current users.
 *
 * <b>Input actions:</b>
 * - @em changePassword Save the new password
 * - unspecified: Show the change password screen
 *
 * <b>Output actions:</b>
 * - @em ok In any case
 *
 * @param[in] oldpassword The old password
 * @param[in] newpassword1 The new password
 * @param[in] newpassword2 The new password
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class UserController extends Controller {

  private $_userManager = null;

  /**
   * @see Controller::initialize()
   */
  public function initialize(Request $request, Response $response) {
    parent::initialize($request, $response);

    // create UserManager instance
    $this->_userManager = ObjectFactory::getInstance('userManager');
  }

  /**
   * Process action and assign data to View.
   * @return False (Stop action processing chain).
   * @see Controller::executeKernel()
   */
  public function executeKernel() {
    $permissionManager = ObjectFactory::getInstance('permissionManager');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $request = $this->getRequest();
    $response = $this->getResponse();

    // process actions

    // change password
    if ($request->getAction() == 'changePassword') {
      // load model
      $user = $permissionManager->getAuthUser();
      $oid = new ObjectId(ObjectFactory::getInstance('userManager')->getUserType(), $user->getUserId());
      $principal = $this->_userManager->getPrincipal($oid);

      // start the persistence transaction
      $transaction = $persistenceFacade->getTransaction();
      $transaction->begin();
      try {
        $this->_userManager->changePassword($principal->getLogin(), $request->getValue('oldpassword'),
          $request->getValue('newpassword1'), $request->getValue('newpassword2'));
        $transaction->commit();
      }
      catch(\Exception $ex) {
        $response->addError(ApplicationError::fromException($ex));
        $transaction->rollback();
      }
    }

    // success
    $response->setAction('ok');
    return false;
  }
}
?>
