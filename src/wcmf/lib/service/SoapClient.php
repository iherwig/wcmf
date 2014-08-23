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
namespace wcmf\lib\service;

use SoapHeader;
use SoapVar;
use wcmf\lib\core\Log;

/**
 * @class SoapClient
 * @ingroup Presentation
 * @brief SoapClient is used to communicate with wCMF soap services.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SoapClient extends \SoapClient {

  const OASIS = "http://docs.oasis-open.org/wss/2004/01";

  private $_user;
  private $_password;

  /**
   * Constructor
   * @param $wsdl
   * @param $user
   * @param $password
   * @param $options
   */
  public function __construct($wsdl, $user, $password, $options) {
    parent::__construct($wsdl, $options);

    $this->_user = $user;
    $this->_password = $password;
  }

  /**
   * Call the given soap method
   * @param $method
   * @param $params [optional]
   */
  public function call($method, $params=array()) {
    $header = $this->generateWSSecurityHeader($this->_user, $this->_password);
    $response = $this->__soapCall($method, sizeof($params) > 0 ? array($params) : array(), null, $header);
    // in document/literal style the "return" parameter holds the result
    return property_exists($response, 'return') ? $response->return : $response;
  }

  /**
   * Overridden in order to strip bom characters
   * @see SoapClient::__doRequest
   */
  public function __doRequest($request, $location, $action, $version, $one_way=0){
      if (Log::isDebugEnabled(__CLASS__)) {
        Log::debug("Request:", __CLASS__);
        Log::debug($request, __CLASS__);
      }
      $response = trim(parent::__doRequest($request, $location, $action, $version, $one_way));
      if (Log::isDebugEnabled(__CLASS__)) {
        Log::debug("Response:", __CLASS__);
        Log::debug($response, __CLASS__);
        Log::debug($this->getDebugInfos(), __CLASS__);
      }
      $parsedResponse = preg_replace('/^(\x00\x00\xFE\xFF|\xFF\xFE\x00\x00|\xFE\xFF|\xFF\xFE|\xEF\xBB\xBF)/', "", $response);
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
    return new SoapHeader(self::OASIS.'/oasis-200401-wss-wssecurity-secext-1.0.xsd', 'Security', new SoapVar($xml, XSD_ANYXML), true);
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
