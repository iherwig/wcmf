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
 * @author ingo herwig <ingo@wemove.com>
 */
class SoapServer extends \nusoap_server {

  const TNS = 'http://wcmf.sourceforge.net';

  private $application = null;

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

    // register default complex types
    $this->wsdl->addComplexType(
        'OidList',
        'complexType',
        'array',
        '',
        'SOAP-ENC:Array',
        array(),
        array(
          array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'xsd:string[]')
        ),
        'xsd:string'
    );

    $this->wsdl->addComplexType(
        'SearchResultList',
        'complexType',
        'array',
        '',
        'SOAP-ENC:Array',
        array(),
        array(
            array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:SearchResultItem[]')
        ),
        'tns:SearchResultItem'
    );

    $this->wsdl->addComplexType('SearchResultItem', 'complexType', 'struct', 'sequence', '',
        array(
            'type' => array('name' => 'type', 'type' => 'xsd:string'),
            'oid' => array('name' => 'oid', 'type' => 'xsd:string'),
            'displayValue' => array('name' => 'displayValue', 'type' => 'xsd:string'),
            'summary' => array('name' => 'summary', 'type' => 'xsd:string')
        )
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
      parent::service($data);
      error_reporting($oldErrorReporting);
    }
    catch (\Exception $ex) {
      $this->handleException($ex);
    }
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
    $request->setValues(array(
      'data' => array(
        'action1' => array(
          'action' => 'login',
          'params' => array(
            'user' => $authHeader['Username'],
            'password' => $authHeader['Password']['!']
          )
        ),
        'action2' => array(
            'action' => $action,
            'params' => $params
        ),
        'action3' => array(
          'action' => 'logout'
        )
      )
    ));

    // run the application
    $actionResponse = ObjectFactory::getInstance('response');
    $actionResponse->setFinal();
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
