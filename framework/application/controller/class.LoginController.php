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
require_once(BASE."wcmf/lib/presentation/class.Controller.php");
require_once(BASE."wcmf/lib/util/class.InifileParser.php");
require_once(BASE."wcmf/lib/security/class.AuthUser.php");
require_once(BASE."wcmf/lib/security/class.UserManager.php");
require_once(BASE."wcmf/lib/persistence/class.LockManager.php");
require_once(BASE."wcmf/lib/util/class.SessionData.php");

/**
 * @class LoginController
 * @ingroup Controller
 * @brief LoginController is a controller that handles the login process.
 *
 * <b>Input actions:</b>
 * - @em login Present the login dialog
 * - @em dologin Try to login the user with the given credentials
 * - @em logout Terminate the user session
 *
 * <b>Output actions:</b>
 * - @em ok If login succeeded
 * - @em login If login failed
 *
 * @param[in] login The users login name
 * @param[in] password The user's password
 * @param[in] remember_me If given with any value a login cookie will be created in the browser
 * @param[in] password_is_encrypted True/False wether the given password is encrypted on not (default: false)
 * @param[out] loginmessage A message if login failed
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class LoginController extends Controller
{
  private $_anonymous = 0; // in anonymous mode all authorization requests answered positive
                       // and AuthUser is an instance of AnonymousUser
                       // The mode is set in configuration section 'cms' key 'anonymous'
  private $NUM_LOGINTRIES_VARNAME = 'LoginController.logintries';
  private $LOGINMESSAGE_VARNAME = 'LoginController.loginmessage';

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
    if ($request->getAction() == 'dologin' && !$this->_anonymous)
    {
      if(!$request->hasValue('login'))
      {
        $this->setErrorMsg("No 'login' given in data.");
        return false;
      }
      if(!$request->hasValue('password'))
      {
        $this->setErrorMsg("No 'password' given in data.");
        return false;
      }
    }
    return true;
  }
  /**
   * @see Controller::hasView()
   */
  protected function hasView()
  {
    $request = $this->getRequest();
    if ($request->getAction() == 'dologin' || $this->_anonymous || $this->isCookieLogin()) {
      return false;
    }
    else {
      return true;
    }
  }
  /**
   * If called with any usr_action except 'dologin' this Controller presents the login dialog else
   * if usr_action is 'dologin' it checks the login data ('login' & 'password') and creates AuthUser object in the Session on
   * success (session variable name: auth_user).
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

    if ($request->getAction() == 'login')
    {
      // preserve login failure details
      $loginTries = $session->get($this->NUM_LOGINTRIES_VARNAME);
      $loginMessage = $session->get($this->LOGINMESSAGE_VARNAME);
      $session->clear();
      $session->set($this->NUM_LOGINTRIES_VARNAME, $loginTries);
      $session->set($this->LOGINMESSAGE_VARNAME, $loginMessage);
    }

    if ($request->getAction() == 'dologin')
    {
      // create AuthUser instance
      $authUser = new AuthUser();

      $isPasswordEncrypted = false;
      if ($request->hasValue('password_is_encrypted')) {
        $isPasswordEncrypted = $request->getValue('password_is_encrypted');
      }
      if ($authUser->login($request->getValue('login'), $request->getValue('password'), $isPasswordEncrypted))
      {
        // login succeeded
        $session->clear();
        $session->set('auth_user', $authUser);

        // did this user check the 'remember me' checkbox?
        if($request->getValue('remember_me'))
        {
          // if yes store the password login combination in a cookie
          $expire = time() + 1728000; // expire in 20 days
          $cookiePassword = UserManager::encryptPassword($request->getValue('password'));

          setcookie('login', $request->getValue('login'), $expire);
          setcookie('password', $cookiePassword, $expire);
        }
        $response->setAction('ok');
        return true;
      }
      else
      {
        // login failed
        $logintries = $session->get($this->NUM_LOGINTRIES_VARNAME)+1;
        $session->set($this->NUM_LOGINTRIES_VARNAME, $logintries);
        $this->setErrorMsg(Message::get("Login failed. Try again."));
        $session->set($this->LOGINMESSAGE_VARNAME, $this->getErrorMsg());

        $response->setAction('login');
        return true;
      }
    }
    elseif ($request->getAction() == 'logout')
    {
      // release all locks
      $lockManager = LockManager::getInstance();
      $lockManager->releaseAllLocks();

      // delete cookies (also clientside)
      setcookie('login', '', time()-3600, '/');
      setcookie('password', '', time()-3600, '/');
      setcookie(session_name(), '', time()-3600, '/');
      print '<script type="text/javascript">
      document.cookie = "login=; expires=Wed, 1 Mar 2006 00:00:00";
      document.cookie = "password=; expires=Wed, 1 Mar 2006 00:00:00";
      </script>';

      // clear all session data
      $session->destroy();

      // empty response
      $response->clearValues();
      return false;
    }
    else
    {
      // check if the login and password is stored in a cookie
      if ($this->isCookieLogin())
      {
        // if yes redirect to login process
        $response->setValue('login', $_COOKIE['login']);
        $response->setValue('password', $_COOKIE['password']);
        $response->setValue('password_is_encrypted', true);

        $response->setAction('dologin');
        return true;
      }

      // present login dialog
      $loginMessage = $session->get($this->LOGINMESSAGE_VARNAME);
      if (strlen($loginMessage) > 0)
      {
        $msg = $loginMessage;
        if ($session->exist($this->NUM_LOGINTRIES_VARNAME))
          $msg .= " (".Message::get("Attempt")." #".($session->get($this->NUM_LOGINTRIES_VARNAME)+1).")";
        $response->setValue('loginmessage', $msg);
        $this->setErrorMsg($loginMessage);
      }
      return false;
    }
  }

  /**
   * Check if the user logs in via cookies
   * @return True/False
   */
  protected function isCookieLogin()
  {
    $request = $this->getRequest();
    return ($request->getAction() == 'login' && isset($_COOKIE['login'], $_COOKIE['password']));
  }
}
?>
