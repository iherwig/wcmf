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
namespace wcmf\lib\service\impl;

use \RuntimeException;
use wcmf\lib\core\Log;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;
use wcmf\lib\presentation\format\Formatter;
use wcmf\lib\service\RemotingClient;

/**
 * RPCClient is used to do calls to other wCMF instances on the same mashine.
 * @see RemotingFacade
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class RPCClient implements RemotingClient {

  // constants
  const SIDS_SESSION_VARNAME = 'RPCClient.sids';

  private $_serverCli = null;
  private $_php = null;
  private $_user = null;

  /**
   * Constructor
   * @param $serverCli The command line interface of the other server instance.
   * @param $user The remote user instance.
   */
  public function __construct($serverCli, $user) {

    $this->_serverCli = realpath($serverCli);
    if (!file_exists($this->_serverCli)) {
      throw new RuntimeException("Could not setup RPCClient: ".$this->_serverCli." not found.");
    }

    // locate the php executable
    $config = ObjectFactory::getConfigurationInstance();
    $this->_php = $config->getValue('php', 'system');

    // initialize the session variable for storing session
    $session = ObjectFactory::getInstance('session');
    if (!$session->exist(self::SIDS_SESSION_VARNAME)) {
      $var = array();
      $session->set(self::SIDS_SESSION_VARNAME, $var);
    }
    $this->_user = $user;
  }

  /**
   * Do a call to the remote server.
   * @param $request A Request instance
   * @return A Response instance
   */
  public function call(Request $request) {
    $response = $this->doRemoteCall($request, false);
    return $response;
  }

  /**
   * Do a remote call.
   * @param $request The Request instance
   * @param $isLogin Boolean whether this request is a login request or not
   * @return The Response instance
   */
  protected function doRemoteCall(Request $request, $isLogin) {
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

    $formats = ObjectFactory::getInstance('formats');
    $jsonFormat = $formats['json'];

    $request->setResponseFormat($jsonFormat);
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

    $responseData = json_decode($jsonResponse[0], true);
    $response = new Response(null, null, null, $responseData);
    $response->setFormat($jsonFormat);
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
  protected function doLogin() {
    if ($this->_user) {
      $request = new Request(
        '',
        '',
        'login',
        array(
          'login' => $this->_user['login'],
          'password' => $this->_user['password']
        )
      );
      $response = $this->doRemoteCall($request, true);
      if ($response->getValue('success')) {
        // store the session id in the session
      	$this->setSessionId($response->getValue('sid'));
        return true;
      }
    }
    else {
      throw new RuntimeException("Remote user required for remote call.");
    }
  }

  /**
   * Store the session id for our server in the local session
   * @return The session id or null
   */
  protected function setSessionId($sessionId) {
    $session = ObjectFactory::getInstance('session');
    $sids = $session->get(self::SIDS_SESSION_VARNAME);
    $sids[$this->_serverCli] = $sessionId;
    $session->set(self::SIDS_SESSION_VARNAME, $sids);
  }

  /**
   * Get the session id for our server from the local session
   * @return The session id or null
   */
  protected function getSessionId() {
    // check if we already have a session with the server
    $session = ObjectFactory::getInstance('session');
    $sids = $session->get(self::SIDS_SESSION_VARNAME);
    if (isset($sids[$this->_serverCli])) {
      return $sids[$this->_serverCli];
    }
    return null;
  }

  /**
   * Error handling method
   * @param $response The Response instance
   */
  protected function handleError($response) {
    $errorMsg = $response->getValue('errorMsg');
    Log::error("Error in remote call to ".$this->_serverCli.": ".$errorMsg."\n".$response->toString(), __CLASS__);
    throw new RuntimeException("Error in remote call to ".$this->_serverCli.": ".$errorMsg);
  }
}
?>
