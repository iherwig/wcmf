<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2016 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\service\impl;

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\core\LogManager;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\presentation\ControllerMessage;
use wcmf\lib\presentation\Request;
use wcmf\lib\service\RemotingClient;

if (!class_exists('Zend_Http_Client')) {
    throw new ConfigurationException(
            'wcmf\lib\service\impl\HTTPClient requires '.
            'Zend_Http_Client. If you are using composer, add zf1/zend-http '.
            'as dependency to your project');
}

/**
 * HTTPClient is used to do calls to other wCMF instances over HTTP.
 * @see RemotingServer
 *
 * @note This class requires Zend_Http_Client
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class HTTPClient implements RemotingClient {

  private static $logger = null;

  private $client = null;
  private $user = null;
  private $sessionId = null;
  private $serverUrl = null;

  /**
   * Constructor
   * @param $serverUrl The url of the other server instance.
   * @param $user The remote user instance.
   */
  public function __construct($serverUrl, $user) {
    if (self::$logger == null) {
      self::$logger = LogManager::getLogger(__CLASS__);
    }
    $this->serverUrl = $serverUrl;
    $this->client = new Zend_Http_Client($this->serverUrl, array(
        'keepalive' => true,
        'timeout' => 3600
      )
    );
    $this->client->setMethod(Zend_Http_Client::POST);
    $this->client->setCookieJar();
    $this->user = $user;
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
    // initially login, if no cookie is set
    $cookyJar = $this->client->getCookieJar();
    if (!$isLogin && sizeof($cookyJar->getAllCookies()) == 0) {
      $response = $this->doLogin();
    }

    // do the request
    $request->setResponseFormat('json');
    $this->client->resetParameters();
    $this->client->setParameterPost('controller', $request->getSender());
    $this->client->setParameterPost('context', $request->getContext());
    $this->client->setParameterPost('action', $request->getAction());
    $this->client->setParameterPost('request_format', $request->getFormat());
    $this->client->setParameterPost('response_format', $request->getResponseFormat());
    $this->client->setParameterPost($request->getValues());
    try {
      $httpResponse = $this->client->request();
    }
    catch (\Exception $ex) {
      self::$logger->error("Error in remote call to ".$url.":\n".$ex, __FILE__);
      throw new \RuntimeException("Error in remote call to ".$url.": ".$ex->getMessage());
    }

    // deserialize the response
    $responseData = json_decode($httpResponse->getBody(), true);
    $response = new ControllerMessage('', '', '', $responseData);
    $response->setFormat('json');
    $formatter = ObjectFactory::getInstance('formatter');
    $formatter->deserialize($response);

    // handle errors
    if (!$response->getValue('success'))
    {
      $errorMsg = $response->getValue('errorMsg');
      // if the session expired, try to relogin
      if (strpos('Authorization failed', $errorMsg) === 0 && !$isLogin) {
        $this->doLogin();
      }
      $url = $this->client->getUri();
      self::$logger->error("Error in remote call to ".$url.": ".$errorMsg."\n".$response->toString(), __FILE__);
      throw new \RuntimeException("Error in remote call: $errorMsg");
    }
    return $response;
  }

  /**
   * Do the login request. If the request is successful,
   * the session id will be set.
   * @return True on success
   */
  protected function doLogin() {
    if ($this->user) {
      $request = ObjectFactory::getNewInstance('request');
      $request->setAction('login');
      $request->setValues(
        array(
          'login' => $this->user['login'],
          'password' => $this->user['password']
        )
      );
      $response = $this->doRemoteCall($request, true);
      if ($response->getValue('success')) {
        $this->sessionId = $response->getValue('sid');
        return true;
      }
    }
    else {
      throw new \RuntimeException("Remote user required for remote call.");
    }
  }

  /**
   * Error handling method
   * @param $response The Response instance
   */
  protected function handleError($response) {
    $errorMsg = $response->getValue('errorMsg');
    self::$logger->error("Error in remote call to ".$this->serverUrl.": ".$errorMsg."\n".$response->toString(), __FILE__);
    throw new \RuntimeException("Error in remote call to ".$this->serverUrl.": ".$errorMsg);
  }
}
?>
