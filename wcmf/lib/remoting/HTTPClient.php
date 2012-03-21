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
namespace wcmf\lib\remoting;

use \Exception;
use wcmf\lib\core\Log;
use wcmf\lib\presentation\ControllerMessage;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\format\Formatter;

$includePath = get_include_path();
if (strpos($includePath, 'Zend') === false) {
  set_include_path(get_include_path().PATH_SEPARATOR.WCMF_BASE.'wcmf/3rdparty/zend');
}
require_once('Zend/Http/Client.php');

/**
 * HTTPClient is used to do calls to other wCMF instances over HTTP.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class HTTPClient {

  private $_client = null;
  private $_user = null;

  /**
   * Constructor
   * @param serverUrl The url of the other server instance.
   * @param user The remote user instance.
   */
  public function __construct($serverUrl, $user) {
    $this->_client = new Zend_Http_Client($serverUrl, array(
        'keepalive' => true,
        'timeout' => 3600
      )
    );
    $this->_client->setMethod(Zend_Http_Client::POST);
    $this->_client->setCookieJar();
    $this->_user = $user;
  }

  /**
   * Do a call to the remote server.
   * @param request A Request instance
   * @return A Response instance
   */
  public function call($request) {
    $response = $this->doRemoteCall($request, false);
    return $response;
  }

  /**
   * Do a remote call.
   * @param request The Request instance
   * @param isLogin True/False wether this request is a login request or not
   * @return The Response instance
   */
  protected function doRemoteCall($request, $isLogin) {
    // initially login, if no cookie is set
    $cookyJar = $this->_client->getCookieJar();
    if (!$isLogin && sizeof($cookyJar->getAllCookies()) == 0) {
      $response = $this->doLogin();
    }

    // do the request
    $request->setResponseFormat(MSG_FORMAT_JSON);
    $this->_client->resetParameters();
    $this->_client->setParameterPost('controller', $request->getSender());
    $this->_client->setParameterPost('context', $request->getContext());
    $this->_client->setParameterPost('action', $request->getAction());
    $this->_client->setParameterPost('request_format', $request->getFormat());
    $this->_client->setParameterPost('response_format', $request->getResponseFormat());
    $this->_client->setParameterPost($request->getValues());
    try {
      $httpResponse = $this->_client->request();
    }
    catch (Exception $ex) {
      Log::error("Error in remote call to ".$url.":\n".$ex, __FILE__, __LINE__);
      throw new RuntimeException("Error in remote call to ".$url.": ".$ex->getMessage());
    }

    // deserialize the response
    $responseData = json_decode($httpResponse->getBody(), true);
    $response = new ControllerMessage('', '', '', $responseData);
    $response->setFormat(MSG_FORMAT_JSON);
    Formatter::deserialize($response);

    // handle errors
    if (!$response->getValue('success'))
    {
      $errorMsg = $response->getValue('errorMsg');
      // if the session expired, try to relogin
      if (strpos('Authorization failed', $errorMsg) === 0 && !$isLogin) {
        $this->doLogin();
      }
      $url = $this->_client->getUri();
      Log::error("Error in remote call to ".$url.": ".$errorMsg."\n".$response->toString(), __FILE__, __LINE__);
      throw new RuntimeException("Error in remote call: $errorMsg");
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
      if ($response->getValue('success')) {
        $this->_sessionId = $response->getValue('sid');
        return true;
      }
    }
    else {
      throw new RuntimeException("Remote user required for remote call.");
    }
  }

  /**
   * Error handling method
   * @param response The Response instance
   */
  protected function handleError($response) {
    $errorMsg = $response->getValue('errorMsg');
    Log::error("Error in remote call to ".$this->_serverBase.": ".$errorMsg."\n".$response->toString(), __FILE__, __LINE__);
    throw new RuntimeException("Error in remote call to ".$this->_serverBase.": ".$errorMsg);
  }
}
?>
