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

use \Exception;
use wcmf\lib\core\Log;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\Controller;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;

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

  private $_anonymous = 0; // in anonymous mode all authorization requests answered positive
                       // and AuthUser is an instance of AnonymousUser
                       // The mode is set in configuration section 'application' key 'anonymous'

  /**
   * @see Controller::initialize()
   */
  public function initialize(Request $request, Response $response) {
    // delete all data, if not in login process
    if ($request->getAction() != 'login') {
      $request->clearValues();
    }
    $config = ObjectFactory::getConfigurationInstance();
    $this->_anonymous = $config->getBooleanValue('anonymous', 'application');

    parent::initialize($request, $response);
  }

  /**
   * @see Controller::validate()
   */
  protected function validate() {
    $request = $this->getRequest();
    $response = $this->getResponse();
    if ($request->getAction() == 'login' && !$this->_anonymous) {
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
    $session = ObjectFactory::getInstance('session');
    $request = $this->getRequest();
    $response = $this->getResponse();

    // return immediately if anonymous
    if ($this->_anonymous) {
      $request->setAction('ok');
      return;
    }

    if ($request->getAction() == 'login') {
      // authenticate
      $authManager = ObjectFactory::getInstance('authenticationManager');

      // try to login
      try {
        $authUser = $authManager->login($request->getValue('user'), $request->getValue('password'));
      }
      catch (Exception $ex) {
        Log::error("Could not log in: ".$ex, __CLASS__);
      }

      if ($authUser) {
        // login succeeded
        $session->clear();
        $permissionManager = ObjectFactory::getInstance('permissionManager');
        $permissionManager->setAuthUser($authUser);

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
