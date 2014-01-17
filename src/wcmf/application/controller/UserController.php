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
use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\i18n\Message;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\Controller;
use wcmf\lib\security\principal\User;

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
      $authUser = $permissionManager->getAuthUser();
      $oid = new ObjectId(ObjectFactory::getInstance('User')->getType(), $authUser->getUserId());

      // since user should be able to change their own user instance, we propably have to deactivate the
      // PermissionManager for this operation to allow user retrieval from the persistent storage
      $isAnonymous = $permissionManager->isAnonymous();
      if (!$isAnonymous) {
        $permissionManager->deactivate();
      }
      $user = $persistenceFacade->load($oid);

      // start the persistence transaction
      $transaction = $persistenceFacade->getTransaction();
      $transaction->begin();
      try {
        $this->changePassword($user, $request->getValue('oldpassword'),
          $request->getValue('newpassword1'), $request->getValue('newpassword2'));
        $transaction->commit();
      }
      catch(\Exception $ex) {
        $response->addError(ApplicationError::fromException($ex));
        $transaction->rollback();
      }
      // reactivate the PermissionManager if necessary
      if (!$isAnonymous) {
        $permissionManager->activate();
      }
    }

    // success
    $response->setAction('ok');
    return false;
  }

  /**
   * Change a users password.
   * @param user The User instance
   * @param oldPassword The old password of the user
   * @param newPassword The new password for the user
   * @param newPasswordRepeated The new password of the user again
   */
  public function changePassword(User $user, $oldPassword, $newPassword, $newPasswordRepeated) {
    // check old password
    if (!$user->verifyPassword($oldPassword, $user->getPassword())) {
      throw new IllegalArgumentException(Message::get("The old password is incorrect"));
    }
    if (strlen($newPassword) == 0) {
      throw new IllegalArgumentException(Message::get("The password can't be empty"));
    }
    if ($newPassword != $newPasswordRepeated) {
      throw new IllegalArgumentException(Message::get("The given passwords don't match"));
    }
    // set password
    $user->setPassword($newPassword);
  }
}
?>
