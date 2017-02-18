<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2017 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\service;

use wcmf\lib\core\LogManager;

/**
 * SoapClient is used to communicate with wCMF soap services.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SoapClient extends \SoapClient {

  const OASIS = "http://docs.oasis-open.org/wss/2004/01";

  private $user;
  private $password;

  private static $logger = null;

  /**
   * Constructor
   * @param $wsdl
   * @param $user
   * @param $password
   * @param $options
   */
  public function __construct($wsdl, $user, $password, $options) {
    parent::__construct($wsdl, $options);
    if (self::$logger == null) {
      self::$logger = LogManager::getLogger(__CLASS__);
    }
    $this->user = $user;
    $this->password = $password;
  }

  /**
   * Call the given soap method
   * @param $method
   * @param $params (optional, default: empty array)
   */
  public function call($method, $params=[]) {
    $header = $this->generateWSSecurityHeader($this->user, $this->password);
    $response = $this->__soapCall($method, sizeof($params) > 0 ? [$params] : [], null, $header);
    // in document/literal style the "return" parameter holds the result
    return property_exists($response, 'return') ? $response->return : $response;
  }

  /**
   * Overridden in order to strip bom characters
   * @see SoapClient::__doRequest
   */
  public function __doRequest($request, $location, $action, $version, $oneway=0){
      if (self::$logger->isDebugEnabled()) {
        self::$logger->debug("Request:");
        self::$logger->debug($request);
      }
      $response = trim(parent::__doRequest($request, $location, $action, $version, $oneway));
      if (self::$logger->isDebugEnabled()) {
        self::$logger->debug("Response:");
        self::$logger->debug($response);
        self::$logger->debug($this->getDebugInfos());
      }
      $parsedResponse = preg_replace('/^(\x00\x00\xFE\xFF|\xFF\xFE\x00\x00|\xFE\xFF|\xFF\xFE|\xEF\xBB\xBF)/', "", $response);
      // fix missing last e> caused by php's built-in webserver
      if (preg_match('/^<\?xml/', $parsedResponse) && !preg_match('/e>$/', $parsedResponse)) {
        $parsedResponse .= 'e>';
      }
      return $parsedResponse;
  }

  /**
   * Create the WS-Security authentication header for the given credentials
   * @param $user
   * @param $password
   * @return SoapHeader
   */
  private function generateWSSecurityHeader($user, $password) {
    $nonce = sha1(mt_rand());
    $xml = '<wsse:Security SOAP-ENV:mustUnderstand="1" xmlns:wsse="'.self::OASIS.'/oasis-200401-wss-wssecurity-secext-1.0.xsd">
        <wsse:UsernameToken>
          <wsse:Username>'.$user.'</wsse:Username>
          <wsse:Password Type="'.self::OASIS.'/oasis-200401-wss-username-token-profile-1.0#PasswordText">'.$password.'</wsse:Password>
          <wsse:Nonce EncodingType="'.self::OASIS.'/oasis-200401-wss-soap-message-security-1.0#Base64Binary">'.$nonce.'</wsse:Nonce>
        </wsse:UsernameToken>
      </wsse:Security>';
    return new \SoapHeader(self::OASIS.'/oasis-200401-wss-wssecurity-secext-1.0.xsd', 'Security', new \SoapVar($xml, XSD_ANYXML), true);
  }

  /**
   * Get informations about the last request. Available
   * if constructor options contain 'trace' => 1
   * @return String
   */
  public function getDebugInfos() {
    $requestHeaders = $this->__getLastRequestHeaders();
    $request = $this->__getLastRequest();
    $responseHeaders = $this->__getLastResponseHeaders();
    $response = $this->__getLastResponse();

    $msg = '';
    $msg .= "Request Headers:\n" . $requestHeaders . "\n";
    $msg .= "Request:\n" . $request . "\n";

    $msg .= "Response Headers:\n" . $responseHeaders . "\n";
    $msg .= "Response:\n" . $response . "\n";
    return $msg;
  }
}
?>
