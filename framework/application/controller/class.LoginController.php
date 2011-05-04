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
require_once(WCMF_BASE."wcmf/lib/util/class.InifileParser.php");
require_once(WCMF_BASE."wcmf/lib/security/class.AuthUser.php");
require_once(WCMF_BASE."wcmf/lib/security/class.UserManager.php");
require_once(WCMF_BASE."wcmf/lib/persistence/locking/class.LockManager.php");
require_once(WCMF_BASE."wcmf/lib/util/class.SessionData.php");

/**
 * @class LoginController
 * @ingroup Controller
 * @brief LoginController is a controller that handles the login process.
 *
 * <b>Input actions:</b>
 * - @em login Present the login dialog
 * - @em dologin Try to login the user with the given user/password parameters
 * - @em logout Terminate the user session
 *
 * <b>Output actions:</b>
 * - @em ok If login succeeded
 * - @em login If login failed
 *
 * @param[in] user The name of the user to log in
 * @param[in] password The password the user is authenticated with
 *
 * @param[in] remember_me If given with a value of 'true' or true, a login cookie will be created in the browser [optional]
 * @param[in] password_is_encrypted True/False wether the given password is encrypted on not [optional, default: false]
 * @param[out] sid The newly established session id
 * @param[out] roles All roles assigned to the logged in user
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class LoginController extends Controller
{
  private $_anonymous = 0; // in anonymous mode all authorization requests answered positive
                       // and AuthUser is an instance of AnonymousUser
                       // The mode is set in configuration section 'cms' key 'anonymous'

  /**
   * @see Controller::initialize()
   */
  public function initialize(Request $request, Response $response)
  {
    // delete all data, if not in login process
    if ($request->getAction() != 'dologin') {
      $request->clearValues();
    }
    $parser = InifileParser::getInstance();
    $this->_anonymous = $parser->getValue('anonymous', 'cms');

    parent::initialize($request, $response);
  }
  /**
   * @see Controller::validate()
   */
  protected function validate()
  {
    $request = $this->getRequest();
    $response = $this->getResponse();
    if ($request->getAction() == 'dologin' && !$this->_anonymous)
    {
      $invalidParameters = array();
      if(!$request->hasValue('user')) {
        $invalidParameters[] = 'user';
      }
      if(!$request->hasValue('password')) {
        $invalidParameters[] = 'password';
      }

      if (sizeof($invalidParameters) > 0)
      {
        $response->addError(ApplicationError::get('PARAMETER_INVALID',
          array('invalidParameters' => $invalidParameters)));
        return false;
      }
    }
    return true;
  }
  /**
   * @see Controller::hasView()
   */
  public function hasView()
  {
    $request = $this->getRequest();
    if (!$request->hasErrors() &&
      ($request->getAction() == 'dologin' || $this->_anonymous || $this->isCookieLogin())) {
      return false;
    }
    else {
      return true;
    }
  }
  /**
   * If called with any action except 'dologin' this Controller presents the login dialog else
   * if action is 'dologin' it checks the login data ('user' & 'password') and creates AuthUser object in the Session on
   * success.
   * @return Array of given context and action 'ok' on success, action 'failure' on failure.
   *         False if the login dialog is presented (Stop action processing chain).
   *         In case of 'failure' a detailed description is provided by getErrorMsg().
   * @see Controller::executeKernel()
   */
  protected function executeKernel()
  {
    $session = SessionData::getInstance();
    $request = $this->getRequest();
    $response = $this->getResponse();

    // return immediately if anonymous
    if ($this->_anonymous)
    {
      $request->setAction('ok');
      return true;
    }

    if ($request->getAction() == 'login') {
      $session->clear();
    }

    if ($request->getAction() == 'dologin')
    {
      // create AuthUser instance
      $authUser = new AuthUser();

      $isPasswordEncrypted = false;
      if ($request->hasValue('password_is_encrypted')) {
        $isPasswordEncrypted = $request->getValue('password_is_encrypted');
      }

      // try to login
      $success = false;
      try {
        $success = $authUser->login($request->getValue('user'), $request->getValue('password'), $isPasswordEncrypted);
      }
      catch (Exception $ex) {
        Log::error("Could not log in: ".$ex, __CLASS__);
      }

      if ($success)
      {
        // login succeeded
        $session->clear();
        $session->set(RightsManager::getAuthUserVarname(), $authUser);

        // did this user check the 'remember me' checkbox?
        $rememberMe = $request->getValue('remember_me');
        if($rememberMe === 'true' || $rememberMe === true)
        {
          // if yes store the password login combination in a cookie
          $expire = time() + 1728000; // expire in 20 days
          $cookiePassword = UserManager::encryptPassword($request->getValue('password'));

          setcookie($this->getCookieName('user'), $request->getValue('user'), $expire);
          setcookie($this->getCookieName('password'), $cookiePassword, $expire);
        }

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
      else
      {
        // login failed
        $response->addError(ApplicationError::get('AUTHENTICATION_FAILED'));
        return false;
      }
    }
    elseif ($request->getAction() == 'logout')
    {
      // release all locks
      $lockManager = LockManager::getInstance();
      $lockManager->releaseAllLocks();

      // delete cookies (also clientside)
      setcookie($this->getCookieName('user'), '', time()-3600, '/');
      setcookie($this->getCookieName('password'), '', time()-3600, '/');
      setcookie(session_name(), '', time()-3600, '/');
      print '<script type="text/javascript">
      document.cookie = "'.$this->getCookieName('user').'=; expires=Wed, 1 Mar 2006 00:00:00";
      document.cookie = "'.$this->getCookieName('password').'=; expires=Wed, 1 Mar 2006 00:00:00";
      </script>';

      // clear all session data
      $session->destroy();

      // empty response
      $response->clearValues();
      return false;
    }
    else
    {
      // check if the user and password is stored in a cookie
      if ($this->isCookieLogin())
      {
        // if yes redirect to login process
        $response->setValue('user', $_COOKIE[$this->getCookieName('user')]);
        $response->setValue('password', $_COOKIE[$this->getCookieName('password')]);
        $response->setValue('password_is_encrypted', true);

        $response->setAction('dologin');
        return true;
      }
      else {
        // present the login dialog
        return false;
      }
    }
  }

  /**
   * Check if the user logs in via cookies
   * @return True/False
   */
  protected function isCookieLogin()
  {
    $request = $this->getRequest();
    $isCookieLogin = ($request->getAction() == 'login' &&
            isset($_COOKIE[$this->getCookieName('user')], $_COOKIE[$this->getCookieName('password')]));
    return $isCookieLogin;
  }

  /**
   * Get a unique cookie name for the given variable name
   * @param name The name of the variable
   * @return String
   */
  protected function getCookieName($name)
  {
    return Application::getId()."_".$name;
  }
}
?>
