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

use \Exception;
use wcmf\lib\core\Log;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\Controller;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;

/**
 * LoginController is a controller that handles the login process.
 *
 * <b>Input actions:</b>
 * - @em login Try to login the user with the given user/password parameters
 * - @em logout Terminate the user session
 *
 * <b>Output actions:</b>
 * - @em ok If login succeeded
 * - @em login If login failed
 *
 * @param[in] user The name of the user to log in
 * @param[in] password The password the user is authenticated with
 *
 * @param[out] sid The newly established session id
 * @param[out] roles All roles assigned to the logged in user
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
   * If called with any action except 'login' this Controller presents the login dialog else
   * if action is 'login' it checks the login data ('user' & 'password') and creates AuthUser object in the Session on
   * success.
   * @return Boolean
   * @see Controller::executeKernel()
   */
  protected function executeKernel() {
    $session = ObjectFactory::getInstance('session');
    $request = $this->getRequest();
    $response = $this->getResponse();

    // return immediately if anonymous
    if ($this->_anonymous) {
      $request->setAction('ok');
      return true;
    }

    if ($request->getAction() == 'login') {
      // create AuthUser instance
      $authUser = ObjectFactory::getInstance('authUser');

      // try to login
      $success = false;
      try {
        $success = $authUser->login($request->getValue('user'), $request->getValue('password'));
      }
      catch (Exception $ex) {
        Log::error("Could not log in: ".$ex, __CLASS__);
      }

      if ($success) {
        // login succeeded
        $permissionManager = ObjectFactory::getInstance('permissionManager');
        $session->clear();
        $session->set($permissionManager->getAuthUserVarname(), $authUser);

        // return role names of the user
        $roleNames = array();
        $roles = $authUser->getRoles();
        for ($i=0, $count=sizeof($roles); $i<$count; $i++) {
          $roleNames[] = $roles[$i]->getName();
        }
        $response->setValue('roles', $roleNames);
        $response->setValue('sid', $session->getID());

        $response->setAction('ok');
        return true;
      }
      else {
        // login failed
        $response->addError(ApplicationError::get('AUTHENTICATION_FAILED'));
        return false;
      }
    }
    elseif ($request->getAction() == 'logout') {
      // clear all session data
      $session->destroy();

      // empty response
      $response->clearValues();
      return false;
    }
    else {
      // present the login dialog
      return false;
    }
  }
}
?>
