<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2018 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\service;

use wcmf\lib\core\LogManager;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\presentation\Application;
use wcmf\lib\presentation\ApplicationException;
use wcmf\lib\util\URIUtil;

/**
 * SoapServer extends nusoap server to actually process
 * requests inside the application context.
 *
 * Soap requests must include credentials in the form of a wsse:UsernameToken header as 
 * described in the WS-Security UsernameToken Profile. Passwords must be sent as plain text
 * using the PasswordText type and therefore should be sent over a confidential channel.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SoapServer extends \nusoap_server {

  const TNS = 'http://wcmf.sourceforge.net';

  private $application = null;
  private $serverResponse = '';

  private static $logger = null;

  /**
   * Constructor
   */
  public function __construct() {
    if (self::$logger == null) {
      self::$logger = LogManager::getLogger(__CLASS__);
    }
    $scriptURL = URIUtil::getProtocolStr().$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];
    $endpoint = dirname($scriptURL).'/soap';
    $this->configureWSDL('SOAPService', self::TNS, $endpoint, 'document');
    $this->wsdl->schemaTargetNamespace = self::TNS;
    $this->debugLevel = 9;

    // register default complex types
    $this->wsdl->addComplexType(
        'OidList',
        'complexType',
        'array',
        '',
        'SOAP-ENC:Array',
        [],
        [['ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'xsd:string[]']],
        'xsd:string'
    );

    $this->wsdl->addComplexType(
        'SearchResultList',
        'complexType',
        'array',
        '',
        'SOAP-ENC:Array',
        [],
        [['ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:SearchResultItem[]']],
        'tns:SearchResultItem'
    );

    $this->wsdl->addComplexType('SearchResultItem', 'complexType', 'struct', 'sequence', '', [
        'type' => ['name' => 'type', 'type' => 'xsd:string'],
        'oid' => ['name' => 'oid', 'type' => 'xsd:string'],
        'displayValue' => ['name' => 'displayValue', 'type' => 'xsd:string'],
        'summary' => ['name' => 'summary', 'type' => 'xsd:string']
      ]
    );

    // initialize application
    $this->application = new Application();
    try {
      $this->application->initialize();
    }
    catch (\Exception $ex) {
      $this->handleException($ex);
    }
  }

  /**
   * @see nusoap_server::service
   */
  public function service($data) {
    if (self::$logger->isDebugEnabled()) {
      self::$logger->debug($data);
    }
    try {
      $oldErrorReporting = error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
      $this->debug_flag = ObjectFactory::getInstance('configuration')->getBooleanValue('debug', 'Application');
      // call service method, but suppress output
      ob_start(function($buffer) {
        $this->serverResponse = $buffer;
        return '';
      });
      parent::service($data);
      ob_end_clean();
      error_reporting($oldErrorReporting);
      if (self::$logger->isDebugEnabled()) {
        self::$logger->debug($this->serverResponse);
      }
    }
    catch (\Exception $ex) {
      $this->handleException($ex);
    }
  }

  /**
   * Get the response headers after a call to the service method
   * @return Array
   */
  public function getResponseHeaders() {
    $headerStrings = headers_list();
    header_remove();
    $headers = [];
    foreach ($headerStrings as $header) {
      list($name, $value) = explode(':', $header, 2);
      $headers[trim($name)] = trim($value);
    }
    return $headers;
  }

  /**
   * Get the response payload after a call to the service method
   * @return String
   */
  public function getResponsePayload() {
    return $this->serverResponse;
  }

  /**
   * Get a dummy object id to be used in a request
   * @param $type The entity type
   * @return ObjectId
   */
  public function getDummyOid($type) {
    return new ObjectId($type);
  }

  /**
   * Process a soap call
   * @param $action The action
   * @param $params The action parameters
   * @return The Response instance from the executed Controller
   */
  public function doCall($action, $params) {
    if (self::$logger->isDebugEnabled()) {
      self::$logger->debug("SoapServer action: ".$action);
      self::$logger->debug($params);
    }
    $authHeader = $this->requestHeader['Security']['UsernameToken'];

    $request = ObjectFactory::getInstance('request');
    $request->setAction('actionSet');
    $request->setFormat('soap');
    $request->setResponseFormat('null');
    $request->setValues([
      'data' => [
        'action1' => [
          'action' => 'login',
          'params' => [
            'user' => $authHeader['Username'],
            'password' => $authHeader['Password']['!']
          ]
        ],
        'action2' => [
          'action' => $action,
          'params' => $params
        ],
        'action3' => [
          'action' => 'logout'
        ]
      ]
    ]);

    // run the application
    $actionResponse = ObjectFactory::getInstance('response');
    try {
      $response = $this->application->run($request);
      if ($response->hasErrors()) {
        $errors = $response->getErrors();
        $this->handleException(new ApplicationException($request, $response, $errors[0]));
      }
      else {
        $responseData = $response->getValue('data');
        $data = $responseData['action2'];
        $actionResponse->setSender($data['controller']);
        $actionResponse->setContext($data['context']);
        $actionResponse->setAction($data['action']);
        $actionResponse->setFormat('soap');
        $actionResponse->setValues($data);
        $formatter = ObjectFactory::getInstance('formatter');
        $formatter->serialize($actionResponse);
        if (self::$logger->isDebugEnabled()) {
          self::$logger->debug($actionResponse->__toString());
        }
      }
    }
    catch (\Exception $ex) {
      $this->handleException($ex);
    }
    return $actionResponse;
  }

  /**
   * Handle an exception
   * @param $ex
   */
  private function handleException($ex) {
    self::$logger->error($ex->getMessage()."\n".$ex->getTraceAsString());
    $this->fault('SOAP-ENV:SERVER', $ex->getMessage(), '', '');
  }
}
?>
