<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2020 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\application\controller;

use wcmf\lib\config\Configuration;
use wcmf\lib\core\EventManager;
use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\core\Session;
use wcmf\lib\i18n\Localization;
use wcmf\lib\i18n\Message;
use wcmf\lib\model\Node;
use wcmf\lib\persistence\PersistenceAction;
use wcmf\lib\persistence\PersistenceFacade;
use wcmf\lib\persistence\TransactionEvent;
use wcmf\lib\presentation\ActionMapper;
use wcmf\lib\presentation\Controller;
use wcmf\lib\security\PermissionManager;
use wcmf\lib\security\principal\PrincipalFactory;
use wcmf\lib\security\principal\User;

/**
 * UserController is used to change the current user's password.
 *
 * The controller supports the following actions:
 *
 * <div class="controller-action">
 * <div> __Action__ _default_ </div>
 * <div>
 * Handle actions regarding the current user
 *
 * For details about the parameters, see documentation of the methods.
 *
 * | __Response Actions__   | |
 * |------------------------|-------------------------
 * | `ok`                   | In all cases
 * </div>
 * </div>
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class UserController extends Controller {
  use \wcmf\lib\presentation\ControllerMethods;

  private $principalFactory = null;
  private $eventManager = null;
  private $tempPermissions = [];

  /**
   * Constructor
   * @param $session
   * @param $persistenceFacade
   * @param $permissionManager
   * @param $actionMapper
   * @param $localization
   * @param $message
   * @param $configuration
   * @param $principalFactory
   * @param $eventManager
   */
  public function __construct(Session $session,
          PersistenceFacade $persistenceFacade,
          PermissionManager $permissionManager,
          ActionMapper $actionMapper,
          Localization $localization,
          Message $message,
          Configuration $configuration,
          PrincipalFactory $principalFactory,
          EventManager $eventManager) {
    parent::__construct($session, $persistenceFacade, $permissionManager,
            $actionMapper, $localization, $message, $configuration);
    $this->principalFactory = $principalFactory;
    $this->eventManager = $eventManager;
    // add transaction listener
    $this->eventManager->addListener(TransactionEvent::NAME, [$this, 'afterCommit']);
  }

  /**
   * Change the user's password
   *
   * | Parameter           | Description
   * |---------------------|----------------------
   * | _in_ `oldpassword`  | The old password
   * | _in_ `newpassword1` | The new password
   * | _in_ `newpassword2` | The new password
   */
  public function changePassword() {
    $this->requireTransaction();
    $session = $this->getSession();
    $response = $this->getResponse();

    // load model
    $authUser = $this->principalFactory->getUser($session->getAuthUser());
    if ($authUser) {
      // add permissions for this operation
      $oidStr = $authUser->getOID()->__toString();
      $requiredPermissions = [
        [$oidStr, '', PersistenceAction::READ],
        [$oidStr.'.password', '', PersistenceAction::UPDATE],
      ];
      $this->getPermissionManager()->withTempPermissions(function () use ($authUser) {
        $request = $this->getRequest();
        $this->changePasswordImpl(
          $authUser,
          $request->getValue('oldpassword'),
          $request->getValue('newpassword1'),
          $request->getValue('newpassword2')
        );
      }, $requiredPermissions);
    }
    // success
    $response->setAction('ok');
  }

  /**
   * Set a configuration for the user
   *
   * | Parameter    | Description
   * |--------------|-----------------------------
   * | _in_ `name`  | The configuration name
   * | _in_ `value` | The configuration value
   */
  public function setConfigValue() {
    $this->requireTransaction();
    $session = $this->getSession();
    $request = $this->getRequest();
    $response = $this->getResponse();
    $persistenceFacade = $this->getPersistenceFacade();

    // load model
    $authUser = $this->principalFactory->getUser($session->getAuthUser());
    if ($authUser) {
      $configKey = $request->getValue('name');
      $configValue = $request->getValue('value');

      // find configuration
      $configObj = null;
      $configList = Node::filter($authUser->getValue('UserConfig'), null, null,
              ['name' => $configKey]);
      if (sizeof($configList) > 0) {
        $configObj = $configList[0];
      }
      else {
        $configObj = $persistenceFacade->create('UserConfig');
        $configObj->setValue('name', $configKey);
        $authUser->addNode($configObj);
      }

      // set value
      if ($configObj != null) {
        $configObj->setValue('value', $configValue);
      }
    }

    // success
    $response->setAction('ok');
  }

  /**
   * Get a configuration for the user
   *
   * | Parameter     | Description
   * |---------------|----------------------------
   * | _in_ `name`   | The configuration name
   * | _out_ `value` | The configuration value
   */
  public function getConfigValue() {
    $session = $this->getSession();
    $request = $this->getRequest();
    $response = $this->getResponse();

    // load model
    $value = null;
    $authUser = $this->principalFactory->getUser($session->getAuthUser());
    if ($authUser) {
      $configKey = $request->getValue('name');

      // find configuration
      $configObj = null;
      $configList = Node::filter($authUser->getValue('UserConfig'), null, null,
              ['name' => $configKey]);
      $value = sizeof($configList) > 0 ?
              $configObj = $configList[0]->getValue('value') : null;
    }
    $response->setValue('value', $value);

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
  protected function changePasswordImpl(User $user, $oldPassword, $newPassword, $newPasswordRepeated) {
    $message = $this->getMessage();
    // check old password
    if (!$user->verifyPassword($oldPassword)) {
      throw new IllegalArgumentException($message->getText("The old password is incorrect"));
    }
    if (strlen($newPassword) == 0) {
      throw new IllegalArgumentException($message->getText("The password can't be empty"));
    }
    if ($newPassword != $newPasswordRepeated) {
      throw new IllegalArgumentException($message->getText("The given passwords don't match"));
    }
    // set password
    $user->setPassword($newPassword);
  }
}
?>
