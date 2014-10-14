<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\application\controller;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\i18n\Message;
use wcmf\lib\persistence\PersistenceAction;
use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\Controller;
use wcmf\lib\security\principal\User;

/**
 * UserController is used to change the current user's password.
 *
 * The controller supports the following actions:
 *
 * <div class="controller-action">
 * <div> __Action__ _default_ </div>
 * <div>
 * Change the user's password.
 * | Parameter              | Description
 * |------------------------|-------------------------
 * | _in_ `oldpassword`     | The old password
 * | _in_ `newpassword1`    | The new password
 * | _in_ `newpassword2`    | The new password
 * | __Response Actions__   | |
 * | `ok`                   | In all cases
 * </div>
 * </div>
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class UserController extends Controller {

  /**
   * @see Controller::doExecute()
   */
  protected function doExecute() {
    $permissionManager = ObjectFactory::getInstance('permissionManager');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $request = $this->getRequest();
    $response = $this->getResponse();

    // change password

    // load model
    $authUser = $permissionManager->getAuthUser();

    // add permissions for this operation
    $oidStr = $authUser->getOID()->_toString();
    $permissionManager->addTempPermission($oidStr, '', PersistenceAction::READ);
    $permissionManager->addTempPermission($oidStr, '', PersistenceAction::UPDATE);

    // start the persistence transaction
    $transaction = $persistenceFacade->getTransaction();
    $transaction->begin();
    try {
      $this->changePassword($authUser, $request->getValue('oldpassword'),
        $request->getValue('newpassword1'), $request->getValue('newpassword2'));
      $transaction->commit();
    }
    catch(\Exception $ex) {
      $response->addError(ApplicationError::fromException($ex));
      $transaction->rollback();
    }
    // remove temporary permissions
    $permissionManager->clearTempPermissions();

    // success
    $response->setAction('ok');
  }

  /**
   * Change a users password.
   * @param $user The User instance
   * @param $oldPassword The old password of the user
   * @param $newPassword The new password for the user
   * @param $newPasswordRepeated The new password of the user again
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
