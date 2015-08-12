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

use wcmf\lib\config\Configuration;
use wcmf\lib\core\Session;
use wcmf\lib\i18n\Localization;
use wcmf\lib\i18n\Message;
use wcmf\lib\persistence\PersistenceFacade;
use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\Controller;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;
use wcmf\lib\security\AuthenticationManager;
use wcmf\lib\security\PermissionManager;

/**
 * LoginController handles the login process.
 *
 * The controller supports the following actions:
 *
 * <div class="controller-action">
 * <div> __Action__ login </div>
 * <div>
 * Try to login the user with the given user/password parameters.
 * | Parameter              | Description
 * |------------------------|-------------------------
 * | _in_ `user`            | The login of the user to log in
 * | _in_ `password`        | The password the user is authenticated with
 * | _out_ `sid`            | The newly established session id
 * | _out_ `roles`          | Array of role names assigned to the logged in user
 * | __Response Actions__   | |
 * | `ok`                   | If login succeeded
 * </div>
 * </div>
 *
 * <div class="controller-action">
 * <div> __Action__ logout </div>
 * <div>
 * Terminate the user session.
 * </div>
 * </div>
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class LoginController extends Controller {

  private $_authenticationManager = null;

  /**
   * Constructor
   * @param $session
   * @param $persistenceFacade
   * @param $permissionManager
   * @param $localization
   * @param $message
   * @param $configuration
   * @param $authenticationManager
   */
  public function __construct(Session $session,
          PersistenceFacade $persistenceFacade,
          PermissionManager $permissionManager,
          Localization $localization,
          Message $message,
          Configuration $configuration,
          AuthenticationManager $authenticationManager) {
    parent::__construct($session, $persistenceFacade,
            $permissionManager, $localization, $message, $configuration);
    $this->_authenticationManager = $authenticationManager;
  }

  /**
   * @see Controller::initialize()
   */
  public function initialize(Request $request, Response $response) {
    // delete all data, if not in login process
    if ($request->getAction() != 'login') {
      $request->clearValues();
    }

    parent::initialize($request, $response);
  }

  /**
   * @see Controller::validate()
   */
  protected function validate() {
    $request = $this->getRequest();
    $response = $this->getResponse();
    if ($request->getAction() == 'login') {
      $invalidParameters = array();
      if(!$request->hasValue('user')) {
        $invalidParameters[] = 'user';
      }
      if(!$request->hasValue('password')) {
        $invalidParameters[] = 'password';
      }

      if (sizeof($invalidParameters) > 0) {
        $response->addError(ApplicationError::get('PARAMETER_INVALID',
          array('invalidParameters' => $invalidParameters)));
        return false;
      }
    }
    return true;
  }

  /**
   * @see Controller::doExecute()
   */
  protected function doExecute() {
    $session = $this->getSession();
    $request = $this->getRequest();
    $response = $this->getResponse();

    if ($request->getAction() == 'login') {
      // try to login
      try {
        $authUser = $this->_authenticationManager->login(
                $request->getValue('user'), $request->getValue('password'));
      }
      catch (\Exception $ex) {
        $authUser = null;
        $this->getLogger()->error("Could not log in: ".$ex);
      }

      if ($authUser) {
        // login succeeded
        $session->clear();
        $session->setAuthUser($authUser);

        // return role names of the user
        $roleNames = array();
        $roles = $authUser->getRoles();
        for ($i=0, $count=sizeof($roles); $i<$count; $i++) {
          $roleNames[] = $roles[$i]->getName();
        }
        $response->setValue('roles', $roleNames);
        $response->setValue('sid', $session->getID());

        $response->setAction('ok');
      }
      else {
        // login failed
        $response->addError(ApplicationError::get('AUTHENTICATION_FAILED'));
      }
    }
    elseif ($request->getAction() == 'logout') {
      // clear all session data
      $session->destroy();

      // empty response
      $response->clearValues();
    }
  }
}
?>
