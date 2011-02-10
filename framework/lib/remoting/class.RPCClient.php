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
require_once(WCMF_BASE."wcmf/lib/util/class.Log.php");
require_once(WCMF_BASE."wcmf/lib/presentation/class.Request.php");
require_once(WCMF_BASE."wcmf/lib/presentation/class.Response.php");

/**
 * @class RPCClient
 * @ingroup Remoting
 * @brief RPCClient is used to do calls to other wCMF instances on
 * the same maschine.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class RPCClient
{
  // constants
  const SIDS_SESSION_VARNAME = 'RPCClient.sids';
  
  private $_serverCli = null;
  private $_php = null;
  private $_user = null;
  
  /**
   * Constructor
   * @param serverCli The command line interface of the other server instance.
   * @param user The remote user instance.
   */
  function __construct($serverCli, $user)
  {
    $this->_serverCli = realpath($serverCli);
    if (!file_exists($this->_serverCli))
    {
      WCMFException::throwEx("Could not setup RPCClient: ".$this->_serverCli." not found.",
        __FILE__, __LINE__);
    }
    
    // locate the php executable
    $parser = InifileParser::getInstance();
    if (($this->_php = $parser->getValue('php', 'system')) === false)
    {
      WCMFException::throwEx("Could not setup RPCClient:\n".
        $parser->getErrorMsg(), __FILE__, __LINE__);
    }
    
    // initialize the session variable for storing session
    $session = &SessionData::getInstance();
    if (!$session->exist(self::SIDS_SESSION_VARNAME)) {
      $var = array();
      $session->set(self::SIDS_SESSION_VARNAME, $var);
    }
    $this->_user = $user;
  }
  /**
   * Do a call to the remote server.
   * @param request A Request instance
   * @return A Response instance
   */
  function call($request)
  {
    $response = $this->doRemoteCall($request, false);
    return $response;
  }
  /**
   * Do a remote call.
   * @param request The Request instance
   * @param isLogin True/False wether this request is a login request or not
   * @return The Response instance
   */
  protected function doRemoteCall($request, $isLogin)
  {
  	// initially login, if no sessionId is set
  	$sessionId = $this->getSessionId();
    if (!$isLogin && $sessionId == null) {
      $response = $this->doLogin();
      if ($response) {
  	    $sessionId = $this->getSessionId();
      }
    }
    
    $jsonResponse = null;
    $returnValue = -1;
    
    $request->setResponseFormat(MSG_FORMAT_JSON);
    $serializedRequest = base64_encode(serialize($request));

    $arguments = array(
      $serializedRequest,
      $sessionId
    );
    $currentDir = getcwd();
    chdir(dirname($this->_serverCli));
    if (Log::isDebugEnabled(__CLASS__)) {
      Log::debug("Do remote call to: ".$this->_serverCli, __CLASS__);
      Log::debug("Request:\n".$request->toString(), __CLASS__);
    }
    // store and reopen the session (see http://bugs.php.net/bug.php?id=44942)
    session_write_close();
    exec($this->_php.' '.$this->_serverCli.' '.join(' ', $arguments), $jsonResponse, $returnValue);
    session_start();
    if (Log::isDebugEnabled(__CLASS__)) {
      Log::debug("Response [JSON]:\n".$jsonResponse[0], __CLASS__);
    }
    chdir($currentDir);
    
    $responseData = JSONUtil::decode($jsonResponse[0], true);
    $response = new Response('', '', '', $responseData);
    $response->setFormat(MSG_FORMAT_JSON);
    Formatter::deserialize($response);
    if (Log::isDebugEnabled(__CLASS__)) {
      Log::debug("Response:\n".$response->toString(), __CLASS__);
    }
    
    if (!$response->getValue('success')) {
      // if the session expired, try to relogin
      if (strpos('Authorization failed', $response->getValue('errorMsg')) === 0 && !$isLogin) {
        $this->doLogin($url);
      }
      else {
        $this->handleError($response);
      }
    }
    return $response;
  }
  /**
   * Do the login request. If the request is successful,
   * the session id will be set.
   * @return True on success
   */
  protected function doLogin()
  {
    if ($this->_user)
  	{
      $request = new Request(
        'LoginController', 
        '', 
        'dologin', 
        array(
          'login' => $this->_user->getLogin(), 
          'password' => $this->_user->getPassword(), 
          'password_is_encrypted' => true
        )
      );    
      $response = $this->doRemoteCall($request, true);
      if ($response->getValue('success'))
      {
        // store the session id in the session
      	$this->setSessionId($response->getValue('sid'));
        return true;
      }
    }
    else {
      WCMFException::throwEx("Remote user required for remote call.", __FILE__, __LINE__);
    }
  }
  /**
   * Store the session id for our server in the local session
   * @return The session id or null
   */
  protected function setSessionId($sessionId)
  {
    $session = SessionData::getInstance();
    $sids = $session->get(self::SIDS_SESSION_VARNAME);
    $sids[$this->_serverCli] = $sessionId;
    $session->set(self::SIDS_SESSION_VARNAME, $sids);
  }
  /**
   * Get the session id for our server from the local session
   * @return The session id or null
   */
  protected function getSessionId()
  {
    // check if we already have a session with the server
    $session = &SessionData::getInstance();
  	$sids = $session->get(self::SIDS_SESSION_VARNAME);
    if (isset($sids[$this->_serverCli])) {
      return $sids[$this->_serverCli];
    }
    return null;
  }
  /**
   * Error handling method
   * @param response The Response instance
   */
  protected function handleError($response)
  {
    $errorMsg = $response->getValue('errorMsg');
    Log::error("Error in remote call to ".$this->_serverCli.": ".$errorMsg."\n".$response->toString(), __FILE__, __LINE__);
    WCMFException::throwEx("Error in remote call to ".$this->_serverCli.": ".$errorMsg, __FILE__, __LINE__);
  }
}
?>
